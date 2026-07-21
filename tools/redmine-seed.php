<?php

declare(strict_types=1);

/**
 * PractiCase Redmine seed(教材チケットの初期投入・冪等)。
 *
 * 実行方法(リポジトリルートで。Redmine profile が起動済みであること):
 *   一部の課題だけ: docker compose exec -T app php tools/redmine-seed.php --ticket-root=<課題ルート> --ids=T-029,T-031
 *   この版の全課題: docker compose exec -T app php tools/redmine-seed.php --ticket-root=packs/php/tickets --all
 *   内容更新(seed 所有項目のみ): 上記に --update-content を付ける
 *
 * --all は edition設定から対象を導出する。Masterは検出した全課題、Lightは
 * ticket_orderの課題だけを投入する。--all-lightは既存Light利用者向けの互換alias。
 *
 * 設計方針:
 * - 冪等キーは カスタムフィールド「PractiCase Ticket ID」だけ。件名や Redmine の連番では判定しない
 * - 通常実行は「未作成の課題を作成する」だけ。既存 issue には触れない
 * - --update-content は seed 所有項目(件名・説明・教材ID)のみ更新する。
 *   status / 担当者 / journal(コメント)は学習者の所有物なので、どのモードでも変更しない
 * - 教材側に存在しない教材IDの issue は報告のみ(自動削除しない)
 * - Redmine に到達できないときは fail-loud で終了し、fallback(Markdown 導線)を案内する。
 *   check は Redmine に依存しないため、本ツールの失敗は学習の継続を妨げない
 * - description には教材への参照だけを書く(支援資料・提出物・内部資料のパスや内容は含めない)
 * - 認証値は標準出力へ出さない
 */

if (PHP_SAPI !== 'cli') {
    exit('CLI 専用です');
}

require __DIR__ . '/lib/CheckSupport.php';
require __DIR__ . '/lib/CheckEdition.php';
require __DIR__ . '/redmine/SeedSelection.php';

use function PractiCase\Check\discoverTickets;

use PractiCase\Check\CheckEdition;
use PractiCase\Redmine\SeedSelection;

// seed の認証と ID 解決は bootstrap が保存した runtime 設定から読む(named volume 経由・
// app からは読み取り専用)。API キーはソースコードへ固定しない
const RUNTIME_CREDENTIALS_DEFAULT = '/var/practicase-redmine/seed-credentials.json';
// 廃止課題の検出で issue のカスタムフィールドを名前で引くために使う(ID は runtime 設定から)
const CUSTOM_FIELD_NAME = 'PractiCase Ticket ID';
const REQUIRED_TICKET_FIELDS = [
    'id',
    'title',
    'level',
    'track',
    'type',
    'priority',
    'estimated_minutes',
    'role',
    'status',
    'pack',
];
// status は正本4値(docs/02_作業ルール/ticket-frontmatter.md)を全てマッピングする
const STATUS_MAP = [
    'open' => 'New',
    'in_progress' => 'In Progress',
    'resolved' => 'Resolved',
    'closed' => 'Closed',
];
const PRIORITY_MAP = [
    'low' => 'Low',
    'normal' => 'Normal',
    'high' => 'High',
    'urgent' => 'Urgent',
    'immediate' => 'Immediate',
];

/** 失敗終了(fail-loud) */
function seedFail(string $message): never
{
    fwrite(STDERR, "redmine-seed: FAIL — {$message}\n");
    exit(1);
}

/** runtime 認証情報(bootstrap が生成)を読む。欠落は fail-loud */
function loadRuntimeCredentials(): array
{
    $path = getenv('PRACTICASE_REDMINE_CREDENTIALS') ?: RUNTIME_CREDENTIALS_DEFAULT;
    if (!is_file($path) || !is_readable($path)) {
        seedFail("runtime 認証情報が見つかりません: {$path}。"
            . '先に bootstrap を実行してください: '
            . "docker compose --profile redmine exec -T redmine sh -c "
            . "'SECRET_KEY_BASE=\"\$REDMINE_SECRET_KEY_BASE\" bin/rails runner -' < tools/redmine/bootstrap.rb");
    }
    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data)) {
        seedFail("runtime 認証情報を JSON として読めません: {$path}(bootstrap を再実行してください)");
    }
    foreach (['api_key', 'custom_field_id', 'tracker_id', 'project_identifier'] as $key) {
        if (!isset($data[$key]) || $data[$key] === '') {
            seedFail("runtime 認証情報に {$key} がありません(bootstrap を再実行してください)");
        }
    }

    return $data;
}

/** Redmine REST 呼び出し(PHP 標準のストリームのみ・拡張不要) */
function redmineRequest(string $baseUrl, string $method, string $path, ?array $body = null): array
{
    static $apiKey = null;
    if ($apiKey === null) {
        $apiKey = (string) loadRuntimeCredentials()['api_key'];
    }
    $headers = 'X-Redmine-API-Key: ' . $apiKey . "\r\nContent-Type: application/json\r\n";
    $context = stream_context_create(['http' => [
        'method' => $method,
        'header' => $headers,
        'content' => $body === null ? '' : json_encode($body, JSON_UNESCAPED_UNICODE),
        'timeout' => 15,
        'ignore_errors' => true, // 4xx/5xx でも本文を受け取る
    ]]);
    $raw = @file_get_contents($baseUrl . $path, false, $context);
    if ($raw === false) {
        seedFail("Redmine へ接続できません({$baseUrl})。Redmine を起動するか、"
            . 'そのまま Markdown 導線(ticket.md)で学習を続けてください: '
            . 'docker compose --profile redmine up -d');
    }
    $status = 0;
    foreach ($http_response_header ?? [] as $line) {
        if (preg_match('#\AHTTP/\S+ (\d{3})#', $line, $m) === 1) {
            $status = (int) $m[1];
        }
    }
    $json = $raw === '' ? [] : json_decode($raw, true);

    return [$status, is_array($json) ? $json : []];
}

/** 一覧 API から name => id の対応を引く */
function lookupIdByName(array $items, string $name): ?int
{
    foreach ($items as $item) {
        if (($item['name'] ?? null) === $name) {
            return (int) $item['id'];
        }
    }

    return null;
}

// ---- 引数 -------------------------------------------------------------------
$ticketRoot = null;
$idsArg = null;
$allEdition = false;
$legacyAllLight = false;
$updateContent = false;
$baseUrl = getenv('PRACTICASE_REDMINE_URL') ?: 'http://redmine:3000';
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--ticket-root=')) {
        $ticketRoot = substr($arg, strlen('--ticket-root='));
    } elseif (str_starts_with($arg, '--ids=')) {
        $idsArg = substr($arg, strlen('--ids='));
    } elseif ($arg === '--all') {
        $allEdition = true;
    } elseif ($arg === '--all-light') {
        $allEdition = true;
        $legacyAllLight = true;
    } elseif ($arg === '--update-content') {
        $updateContent = true;
    } elseif (str_starts_with($arg, '--url=')) {
        $baseUrl = substr($arg, strlen('--url='));
    } else {
        seedFail("不明な引数: {$arg}(使い方: --ticket-root=<dir> (--ids=T-xxx,T-yyy | --all) [--update-content] [--url=...])");
    }
}
if ($ticketRoot === null || $ticketRoot === '') {
    seedFail('--ticket-root=<課題ルート> は必須です');
}
if ($allEdition && $idsArg !== null) {
    seedFail('--ids と --all は同時に指定できません(どちらか一方)');
}
if (!$allEdition && ($idsArg === null || $idsArg === '')) {
    seedFail('--ids=<教材ID をカンマ区切り> か --all のどちらかを指定してください');
}
if (!is_dir($ticketRoot)) {
    seedFail("--ticket-root が見つかりません: {$ticketRoot}");
}

// ---- 教材の走査(check.php と同じ discoverTickets を再利用) -------------------
$discovered = discoverTickets($ticketRoot);
foreach ($discovered['warnings'] as $warning) {
    seedFail("教材の front matter が不正です: {$warning}");
}
$tickets = $discovered['tickets'];

if ($allEdition) {
    try {
        $edition = CheckEdition::load(__DIR__ . '/check-edition.php');
        $selection = SeedSelection::resolve($edition, $tickets, $legacyAllLight);
    } catch (Throwable $e) {
        seedFail('edition設定からseed対象を決められません: ' . $e->getMessage());
    }
    $requestedIds = $selection['ids'];
    $editionLabel = $selection['label'];
} else {
    $requestedIds = array_values(array_filter(array_map('trim', explode(',', (string) $idsArg))));
    $editionLabel = '指定ID';
}

// 作成前の全件検証 — 1件でも不正があれば Redmine へは 0 件も作成しない(fail-loud)
if ($requestedIds === []) {
    seedFail('seed 対象の課題がありません');
}
if (count($requestedIds) !== count(array_unique($requestedIds))) {
    seedFail('seed 対象に重複 ID があります');
}
foreach ($requestedIds as $id) {
    if (!isset($tickets[$id])) {
        seedFail("対象の課題が --ticket-root に見つかりません: {$id}");
    }
    $meta = $tickets[$id];
    foreach (REQUIRED_TICKET_FIELDS as $field) {
        if (!array_key_exists($field, $meta) || !is_scalar($meta[$field]) || trim((string) $meta[$field]) === '') {
            seedFail("{$id}: front matter の必須項目 {$field} がありません");
        }
    }
    if ((string) $meta['id'] !== $id) {
        seedFail("{$id}: front matter の id が課題IDと一致しません");
    }
    $status = (string) $meta['status'];
    if (!isset(STATUS_MAP[$status])) {
        seedFail("{$id}: status '{$status}' は正本4値(open/in_progress/resolved/closed)ではありません");
    }
    $priority = (string) $meta['priority'];
    if (!isset(PRIORITY_MAP[$priority])) {
        seedFail("{$id}: priority '{$priority}' は Redmine へ対応付けできません");
    }
}
echo 'redmine-seed: 対象 ' . count($requestedIds) . ' 課題'
    . "({$editionLabel}) — 事前検証 OK\n";

// ---- Redmine 到達性と前提(bootstrap 済み)の確認 ------------------------------
[$health] = redmineRequest($baseUrl, 'GET', '/');
if ($health !== 200) {
    seedFail("Redmine が応答しません(HTTP {$health})。起動を確認するか、Markdown 導線で継続してください");
}
// tracker / カスタムフィールドの ID は runtime 設定が持つ(bootstrap が保存)。
// admin 専用 API(/custom_fields.json)には依存しない — seed ユーザーは admin ではない
$credentials = loadRuntimeCredentials();
$projectIdentifier = (string) $credentials['project_identifier'];
$trackerId = (int) $credentials['tracker_id'];
$cfId = (int) $credentials['custom_field_id'];
[$st, $statuses] = redmineRequest($baseUrl, 'GET', '/issue_statuses.json');
if ($st === 401 || $st === 403) {
    seedFail('REST 認証に失敗しました。bootstrap(tools/redmine/bootstrap.rb)を実行済みか確認してください');
}
$statusIds = [];
foreach (STATUS_MAP as $fm => $redmineName) {
    $sid = lookupIdByName($statuses['issue_statuses'] ?? [], $redmineName);
    if ($sid === null) {
        seedFail("Redmine 側にステータス「{$redmineName}」がありません(既定データ未投入?)");
    }
    $statusIds[$fm] = $sid;
}
[, $priorities] = redmineRequest($baseUrl, 'GET', '/enumerations/issue_priorities.json');
$priorityIds = [];
foreach ($priorities['issue_priorities'] ?? [] as $p) {
    $priorityIds[(string) $p['name']] = (int) $p['id'];
}

// ---- 冪等 seed --------------------------------------------------------------
$created = 0;
$updated = 0;
$skipped = 0;
foreach ($requestedIds as $id) {
    $meta = $tickets[$id];
    $query = http_build_query([
        'project_id' => $projectIdentifier,
        'status_id' => '*',
        "cf_{$cfId}" => $id,
        'limit' => 5,
    ]);
    [, $found] = redmineRequest($baseUrl, 'GET', "/issues.json?{$query}");
    $count = (int) ($found['total_count'] ?? 0);
    if ($count > 1) {
        seedFail("{$id}: 同じ PractiCase Ticket ID の issue が {$count} 件あります(重複 — 手動で確認してください)");
    }

    $subject = "{$id}: " . (string) $meta['title'];
    $ticketDir = str_replace('\\', '/', (string) $meta['_dir']);
    $packsPos = strpos($ticketDir, 'packs/');
    $ticketPath = ($packsPos === false ? basename($ticketDir) : substr($ticketDir, $packsPos)) . '/ticket.md';
    $description = implode("\n", [
        "PractiCase Ticket ID: {$id}",
        "課題ファイル: {$ticketPath}",
        '',
        '次にすること:',
        '1. 担当者を自分にする',
        '2. ステータスを New から In Progress にする',
        '3. 終了見込みをコメントして送信する',
        "4. VS Codeで {$ticketPath} を開き、手順1から進める",
    ]);

    if ($count === 1) {
        $issue = $found['issues'][0];
        $issueId = (int) $issue['id'];
        if (!$updateContent) {
            echo "  {$id}: 既存(issue #{$issueId})— 変更なし\n";
            $skipped++;
            continue;
        }
        // seed 所有項目(件名・説明・教材ID)だけを更新する。status / 担当者 / journal は送らない
        [$putStatus] = redmineRequest($baseUrl, 'PUT', "/issues/{$issueId}.json", ['issue' => [
            'subject' => $subject,
            'description' => $description,
            'custom_fields' => [['id' => $cfId, 'value' => $id]],
        ]]);
        if ($putStatus !== 204 && $putStatus !== 200) {
            seedFail("{$id}: 内容更新に失敗(HTTP {$putStatus})");
        }
        echo "  {$id}: 内容を更新(issue #{$issueId}・status/担当者/journal は変更しない)\n";
        $updated++;
        continue;
    }

    $payload = ['issue' => [
        'project_id' => $projectIdentifier,
        'tracker_id' => $trackerId,
        'subject' => $subject,
        'description' => $description,
        'custom_fields' => [['id' => $cfId, 'value' => $id]],
    ]];
    $fmStatus = (string) $meta['status'];
    $priorityName = PRIORITY_MAP[(string) ($meta['priority'] ?? 'normal')] ?? null;
    if ($priorityName !== null && isset($priorityIds[$priorityName])) {
        $payload['issue']['priority_id'] = $priorityIds[$priorityName];
    }
    [$postStatus, $createdIssue] = redmineRequest($baseUrl, 'POST', '/issues.json', $payload);
    if ($postStatus !== 201) {
        seedFail("{$id}: issue 作成に失敗(HTTP {$postStatus})");
    }
    $issueId = (int) ($createdIssue['issue']['id'] ?? 0);
    if ($issueId <= 0) {
        seedFail("{$id}: 作成済み issue の ID を取得できません");
    }
    // Redmine 6.1 は非admin userの作成payloadに含めたstatus_idを成功応答のまま
    // 既定値へ落とす場合がある。最小権限を広げず、作成後の通常遷移で確実に反映する
    if ($fmStatus !== 'open') {
        [$statusUpdate] = redmineRequest($baseUrl, 'PUT', "/issues/{$issueId}.json", ['issue' => [
            'status_id' => $statusIds[$fmStatus],
        ]]);
        if ($statusUpdate !== 204 && $statusUpdate !== 200) {
            seedFail("{$id}: 初期 status の反映に失敗(HTTP {$statusUpdate})");
        }
    }
    [, $storedIssue] = redmineRequest($baseUrl, 'GET', "/issues/{$issueId}.json");
    $actualStatus = (string) ($storedIssue['issue']['status']['name'] ?? '');
    if ($actualStatus !== STATUS_MAP[$fmStatus]) {
        seedFail("{$id}: 初期 status の実値が一致しません(期待 " . STATUS_MAP[$fmStatus]
            . " / 実際 {$actualStatus})");
    }
    echo "  {$id}: 作成(issue #{$issueId}・" . STATUS_MAP[$fmStatus] . ")\n";
    $created++;
}

// ---- 廃止課題の検出(報告のみ・自動削除しない) --------------------------------
$knownIds = $allEdition ? $requestedIds : array_keys($tickets);
$orphans = [];
$offset = 0;
do {
    $query = http_build_query([
        'project_id' => $projectIdentifier,
        'status_id' => '*',
        'limit' => 100,
        'offset' => $offset,
    ]);
    [, $page] = redmineRequest($baseUrl, 'GET', "/issues.json?{$query}");
    $issues = $page['issues'] ?? [];
    foreach ($issues as $issue) {
        $value = null;
        foreach ($issue['custom_fields'] ?? [] as $field) {
            if (($field['name'] ?? '') === CUSTOM_FIELD_NAME) {
                $value = (string) ($field['value'] ?? '');
            }
        }
        if ($value !== null && $value !== '' && !in_array($value, $knownIds, true)) {
            $orphans[] = "{$value} (issue #{$issue['id']})";
        }
    }
    $offset += 100;
} while (count($issues) === 100);

echo "redmine-seed: 完了 — 作成 {$created} / 内容更新 {$updated} / 変更なし {$skipped}\n";
if ($orphans !== []) {
    echo '警告: 教材側に存在しない PractiCase Ticket ID の issue を検出しました'
        . "(自動削除はしません — 扱いはオーナーが判断してください):\n";
    foreach ($orphans as $orphan) {
        echo "  - {$orphan}\n";
    }
}
