<?php

declare(strict_types=1);

/**
 * PractiCase 提出前チェック。正式な実行形式(リポジトリルートで):
 *   docker compose exec app php tools/check.php T-001
 *
 * やること:
 *   [1/3] 共通テスト   … 既存機能を壊していないか(packs/php/app/tests/ 全体)
 *   [2/3] 課題別テスト … tests/checks/<課題ID>.php があれば実行(課題の合格条件)
 *   [3/3] scope 検査   … 変更ファイル(未追跡含む)がチケットの scope 内か(CHG-1)
 */

if (PHP_SAPI !== 'cli') {
    exit('CLI 専用です');
}

require __DIR__ . '/lib/CheckSupport.php';

use function PractiCase\Check\collectChangedFiles;
use function PractiCase\Check\discoverTickets;
use function PractiCase\Check\hasReportSubmission;
use function PractiCase\Check\matchesScope;

use const PractiCase\Check\IMPLICIT_SCOPE;

chdir(dirname(__DIR__)); // リポジトリルート基準で動く(git・パスとも)

const TICKETS_DIR = 'packs/php/tickets';
const TESTS_DIR = 'packs/php/app/tests';

$discovered = discoverTickets(TICKETS_DIR);
foreach ($discovered['warnings'] as $warning) {
    fwrite(STDERR, "警告: {$warning}\n");
}

// 課題IDの大文字小文字は問わない(t-001 / TUTORIAL でも可)。tickets 側のキー表記に解決する
$requestedId = trim((string) ($argv[1] ?? ''));
foreach (array_keys($discovered['tickets']) as $ticketKey) {
    if (strcasecmp($ticketKey, $requestedId) === 0) {
        $requestedId = $ticketKey;
        break;
    }
}
$ticket = $discovered['tickets'][$requestedId] ?? null;

if ($ticket === null) {
    if ($requestedId !== '') {
        echo "課題 '{$requestedId}' が見つかりません。\n\n";
    }
    echo "使い方: docker compose exec app php tools/check.php <課題ID>\n\n";
    echo "利用可能な課題:\n";
    if ($discovered['tickets'] === []) {
        echo "  (packs/php/tickets/ に課題がありません)\n";
    }
    // Light: トラック分けをせず、推奨順(docs/02_作業ルール/workflow.md と同じ並び)の1本リストで見せる
    printf("\n[%s]\n", 'PractiCase Light for PHP の課題');
    foreach (array (
  0 => 'T-000',
  1 => 'tutorial',
  2 => 'tutorial-2',
  3 => 'T-001',
  4 => 'T-012',
  5 => 'T-002',
  6 => 'T-013',
  7 => 'D-010',
  8 => 'D-011',
  9 => 'D-012',
  10 => 'D-013',
  11 => 'D-014',
  12 => 'T-017',
  13 => 'T-014',
  14 => 'T-015',
  15 => 'T-016',
  16 => 'T-018',
  17 => 'T-019',
  18 => 'T-028',
  19 => 'T-005',
  20 => 'D-022',
  21 => 'D-023',
  22 => 'D-024',
  23 => 'D-025',
  24 => 'D-026',
  25 => 'D-027',
  26 => 'D-028',
  27 => 'D-029',
  28 => 'C-001',
  29 => 'C-002',
  30 => 'C-003',
) as $id) {
        if (!isset($discovered['tickets'][$id])) {
            continue;
        }
        $meta = $discovered['tickets'][$id];
        printf("  %s  Level %s  %s\n", $id, $meta['level'] ?? '?', $meta['title'] ?? '');
    }
    exit(1);
}

printf("=== PractiCase check: %s ===\n", $requestedId);
printf("チケット: %s(Level %s / %s)\n", $ticket['title'] ?? '(無題)', $ticket['level'] ?? '?', $ticket['type'] ?? '?');

$failures = [];
// 提出物(reports/)が必須の type。investigation は、加えて課題別テストの合格も必須(調査だけでなく修正まで)
$reportRequiredTypes = ['bug-report', 'integration-test', 'review', 'rework', 'design-review', 'investigation', 'release', 'handover'];
// Light には type では括れない個別要件を持つ課題が無いため空のまま
$reportRequiredIds = [];
// 課題別テストの FAIL を「報告対象の不具合を再現できているサイン」として扱う type(調査・報告が本体の課題)。
// track: dev 限定 — 「まだ直っていないコードの不具合を再現している」という前提の type だけが対象。
// track: design の課題が同じ type 名を共有しても、書類の構造チェック失敗は素直に FAIL 扱いにする
// (「失敗=不具合の証拠」という意味づけが成立しないため。実測: design-review は現状 track:design
// 専用の課題別テストファイルを持たないため無影響)
$testFailureAsNoteTypes = ['bug-report', 'integration-test', 'review', 'rework', 'design-review'];
$isReportRequired = in_array((string) ($ticket['type'] ?? ''), $reportRequiredTypes, true)
    || in_array($requestedId, $reportRequiredIds, true);
$isTestFailureNote = ((string) ($ticket['track'] ?? 'dev')) === 'dev'
    && in_array((string) ($ticket['type'] ?? ''), $testFailureAsNoteTypes, true);
$taskTestFailedForReport = false;

// [1/3] 共通テスト
echo "\n[1/3] 共通テスト(既存機能を壊していないか)\n";
passthru(PHP_BINARY . ' ' . escapeshellarg(TESTS_DIR . '/run.php'), $exitCode);
if ($exitCode !== 0) {
    $failures[] = '共通テストが失敗しています。既存の機能を壊していないか確認してください(TEST-2: 既存テストの削除・弱体化は禁止)';
}

// [2/3] 課題別テスト
echo "\n[2/3] 課題別テスト({$requestedId} の合格条件)\n";
$checkTest = TESTS_DIR . '/checks/' . $requestedId . '.php';
if (is_file($checkTest)) {
    passthru(PHP_BINARY . ' ' . escapeshellarg(TESTS_DIR . '/run.php') . ' ' . escapeshellarg('checks/' . $requestedId . '.php'), $exitCode);
    if ($exitCode !== 0) {
        if ($isTestFailureNote) {
            $taskTestFailedForReport = true;
            echo "  note: この課題は調査・報告系です。課題別テストの FAIL は、報告対象の不具合を再現できているサインとして扱います。\n";
        } else {
            $failures[] = "課題別テストが失敗しています。チケットと仕様書(support/spec.md)を読み直してください";
        }
    }
} else {
    echo "  (この課題に自動テストはありません — support/rubric.md でセルフチェックしてください)\n";
}

// [3/3] scope 検査
echo "\n[3/3] scope 検査(変更がチケットの範囲内か)\n";
$changes = collectChangedFiles();
if (is_string($changes)) {
    echo "  エラー: {$changes}\n\n結果: FAIL\n";
    exit(1);
}

$scope = array_merge((array) ($ticket['scope'] ?? []), IMPLICIT_SCOPE);
$outOfScope = [];
if ($changes['files'] === []) {
    echo "  変更ファイルはありません(ベースライン: {$changes['base']})\n";
} else {
    echo "  変更ファイル(ベースライン: {$changes['base']}、? = 未追跡の新規ファイル):\n";
    foreach ($changes['files'] as $path => $status) {
        $inScope = matchesScope($path, $scope);
        printf("    %s %s%s\n", $status, $path, $inScope ? '' : '  ← scope 外');
        if (!$inScope) {
            $outOfScope[] = $path;
        }
    }
}
if ($outOfScope !== []) {
    $failures[] = 'チケットの範囲外のファイルが変更されています(CHG-1)。'
        . '意図した変更なら ticket.md の scope を確認し、意図しない変更なら元に戻してください';
}

if ($isReportRequired && !hasReportSubmission($requestedId)) {
    $failures[] = "この課題は提出物が必要です。reports/ に {$requestedId} で始まる Markdown"
        . "(例: reports/{$requestedId}_report.md)を作成してください";
}

if ($taskTestFailedForReport && hasReportSubmission($requestedId)) {
    echo "  note: reports/ の提出物を確認しました。課題別テストの FAIL は報告対象として扱い、提出判定は継続します。\n";
}

// 結果
echo "\n";
if ($failures === []) {
    echo "結果: PASS — 提出(Pull Request 作成)に進めます\n";
echo "提出後: support/rubric.md でセルフチェック → (debrief がある課題は突き合わせ)→ 振り返りを書いて closed に\n";
    exit(0);
}
echo "結果: FAIL\n";
foreach ($failures as $i => $failure) {
    printf("  %d. %s\n", $i + 1, $failure);
}
exit(1);
