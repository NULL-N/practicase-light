<?php

declare(strict_types=1);

/**
 * GitHub Issues に全課題を登録するためのコマンド/文面を「生成」する(このスクリプト自体は何も送信しない)。
 *
 * 使い方(リポジトリルートで):
 *   docker compose exec app php tools/issues-seed.php
 *     … gh CLI 用のコマンドを出力する。全部コピーして、自分のパソコンのターミナルに貼って実行する
 *       (gh CLI: https://cli.github.com/ 無料。`gh auth login` 済みであること)
 *
 *   docker compose exec app php tools/issues-seed.php manual
 *     … gh を使わない人向け。GitHub 画面(Issues → New issue)に貼る題名と本文を課題ごとに出力する
 *
 * チケット(ticket.md の front matter)が正 — 課題を増やせば、再実行で新しい課題の分も出る。
 */

require __DIR__ . '/lib/CheckSupport.php';

use function PractiCase\Check\discoverTickets;

const RECOMMENDED_ORDER = [
    'T-000', 'tutorial', 'tutorial-2', 'T-001', 'T-012', 'T-002', 'T-013',
    'D-010', 'D-011', 'D-012', 'D-013', 'D-014', 'T-017',
    'T-014', 'T-015', 'T-016', 'T-018', 'T-019', 'T-028', 'T-005',
    // 06_設計基礎ドリル(20課題後の任意補強章)
    'D-022', 'D-023', 'D-024', 'D-025', 'D-026', 'D-027', 'D-028', 'D-029',
];

$mode = ($argv[1] ?? '') === 'manual' ? 'manual' : 'gh';

$discovered = discoverTickets('packs/php/tickets');
$tickets = $discovered['tickets'];
if ($tickets === []) {
    fwrite(STDERR, "課題が見つかりません(リポジトリルートで実行していますか?)\n");
    exit(1);
}

// 推奨順に並べ、リストに無い課題は後ろに付ける
$ordered = [];
foreach (RECOMMENDED_ORDER as $id) {
    if (isset($tickets[$id])) {
        $ordered[$id] = $tickets[$id];
    }
}
foreach ($tickets as $id => $meta) {
    $ordered[$id] ??= $meta;
}

function issueTitle(string $id, array $meta): string
{
    return "[{$id}] " . (string) ($meta['title'] ?? '(無題)');
}

// 本文は1行に収める(改行入りコマンドはコピペで崩れやすいため)。詳細はチケット本体へ誘導する
function issueBody(string $id, array $meta): string
{
    // Light は章立て(00_はじめに/…/05_小さな実装)で tickets/ が2階層になるため、
    // basename ではなく _dir(tickets/ からの相対パス。discoverTickets が設定する)をそのまま使う
    $dir = (string) ($meta['_dir'] ?? $id);
    $scope = implode(', ', (array) ($meta['scope'] ?? []));
    // Light では設計の課題(D 系)もその PR で完結するため、常に Closes を使う
    $prKeyword = 'Closes';

    return "PractiCase 課題ID: {$id}(課題の正式な識別子 — Issue 番号は人の環境ごとに変わります)"
        . ' / track: ' . (string) ($meta['track'] ?? 'dev')
        . ' / type: ' . (string) ($meta['type'] ?? '?')
        . ' / Level ' . (string) ($meta['level'] ?? '?')
        . ($scope === '' ? '' : " / scope: {$scope}")
        . " — 詳細・進め方: {$dir}/ticket.md"
        . " / 着手時の推奨ブランチ: feature/issue-<このIssueの番号>-{$id}-短い名前"
        . "(ブランチを切る=この Issue への着手宣言。PR には {$prKeyword} #<このIssueの番号> と PractiCase: {$id} を書く)";
}

if ($mode === 'gh') {
    echo "# 以下を全部コピーして、自分のパソコンのターミナル(gh auth login 済み)に貼って実行してください。\n";
    echo "# 1行 = 1課題。順番は教材の推奨順です。\n\n";
    foreach ($ordered as $id => $meta) {
        printf(
            "gh issue create --title %s --body %s\n",
            escapeshellarg(issueTitle($id, $meta)),
            escapeshellarg(issueBody($id, $meta))
        );
    }
    echo "\n# 実行後: GitHub の Issues タブに課題が並びます。着手するときは Issue 番号をメモして、\n";
    echo "# ブランチ名(feature/課題ID-短い英語)と PR 本文の Closes #番号 で紐付けます(docs/02_作業ルール/workflow.md)。\n";
} else {
    echo "GitHub 画面での手動作成用(Issues → New issue に貼る)。上から推奨順です。\n";
    foreach ($ordered as $id => $meta) {
        echo str_repeat('-', 60) . "\n";
        echo "題名:\n" . issueTitle($id, $meta) . "\n\n";
        echo "本文:\n" . issueBody($id, $meta) . "\n\n";
    }
}
