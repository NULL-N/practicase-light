<?php

declare(strict_types=1);

// C-003 の合格条件: 学習者が「閲覧用キーでの参照成功(200)」と「同じキーでの
// 通知作成403(INSUFFICIENT_SCOPE)」を実際に試した記録が監査ログに残っていること、
// 報告書が3つの観点(何を試したか/403から何が分かったか/401と403をどう切り分けるか)で
// 書かれていること。
//
// このcheckは一切の書き込みをしない。特に**403を自分では発生させない**
// (権限の無いキーでのPOSTはprobeでも行わない — 不変条件の正本:
// tools/lib/PcpCheckSupport.php)。閲覧用キーの認証確認は、監査ログに記録されない
// /v1/audit-log への照会で行うため、checkの実行が学習者の記録を汚さない。

require_once __DIR__ . '/../../../../../tools/lib/PcpCheckSupport.php';

use function PractiCase\Pcp\pcpAuditEntries;
use function PractiCase\Pcp\pcpNote;
use function PractiCase\Pcp\pcpRequest;

const C003_READONLY_KEY = 'PCP_TEST_KEY_readonly00003333';

test('C-003: PCPサーバーへ疎通できる', function (): void {
    [$status, $json] = pcpRequest('GET', '/v1/health', null);
    assertTrue(
        $status === 200 && is_array($json) && ($json['status'] ?? '') === 'ok',
        'PCPに接続できません。docker compose up -d で pcp サービスが起動しているか確認してください'
    );
});

test('C-003: 閲覧用キーで認証が通る(checkによる実測・記録されない照会で確認)', function (): void {
    [$status] = pcpRequest('GET', '/v1/audit-log?limit=1', C003_READONLY_KEY);
    assertTrue(
        $status === 200,
        '閲覧用キーの認証が通りません。PCPサーバーが古い可能性があります — docker compose up -d --build で pcp を再ビルドしてください'
    );
});

test('C-003: 閲覧用キーで参照に成功した記録がある(認証が通っている証拠)', function (): void {
    $readonlyReads = 0;
    foreach (pcpAuditEntries(200, 'SUCCESS', '3333') as $entry) {
        if (($entry['api_key_suffix'] ?? null) === '3333'
            && ($entry['event_type'] ?? '') === 'SUCCESS'
            && ($entry['method'] ?? '') === 'GET') {
            $readonlyReads++;
        }
    }
    assertTrue(
        $readonlyReads >= 1,
        '閲覧用キーで通知を参照(GET)した記録が見当たりません。support/spec.md の手順2を実行してください(403を見る前に「認証は通る」ことを自分で確かめる)'
    );
});

test('C-003: 権限不足の403を実際に受けた記録がある', function (): void {
    $scopeDenied = 0;
    foreach (pcpAuditEntries(200, 'INSUFFICIENT_SCOPE', '3333') as $entry) {
        if (($entry['api_key_suffix'] ?? null) === '3333'
            && ($entry['http_status'] ?? 0) === 403) {
            $scopeDenied++;
        }
    }
    assertTrue(
        $scopeDenied >= 1,
        '閲覧用キーで通知を作成しようとして403になった記録が見当たりません。support/spec.md の手順3を実行してください'
    );
});

test('C-003: 報告書が3つの見出しで書かれている', function (): void {
    $note = pcpNote('C-003');
    assertTrue($note !== '', '報告書(reports/C-003_report.md)が見当たりません');
    foreach (['何を試したか', '403から何が分かったか', '401と403をどう切り分けるか'] as $heading) {
        assertTrue(str_contains($note, $heading), "報告書に見出し「{$heading}」が見当たりません(ticket.md の指定の文言をそのまま使ってください)");
    }
    assertTrue(str_contains($note, 'INSUFFICIENT_SCOPE'), '報告書にエラーコード(INSUFFICIENT_SCOPE)が書かれていません。実際に返ってきたコードを書き写してください');
    assertTrue(str_contains($note, '403'), '報告書に403への言及がありません');
    assertTrue(str_contains($note, '401'), '報告書に401への言及がありません(C-002で見た401と切り分けるのがこの課題の本題です)');
});
