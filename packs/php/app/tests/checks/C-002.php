<?php

declare(strict_types=1);

// C-002 の合格条件: 学習者が「正しいキーでの成功」と「キー無し/誤ったキーでの401」を
// 実際に試した記録が監査ログに残っていること、報告書が3つの観点
// (何を試したか/401から何が分かったか/監査ログに何が残ったか)で書かれていること。
//
// このcheckは一切の書き込みをしない(healthと監査ログ照会のみ — どちらもPCP側で
// 記録されない)。特に**401を自分では発生させない**ため、監査ログ上の
// INVALID_API_KEY 記録はそのまま「学習者が失敗系を試した」証拠になる
// (不変条件の正本: tools/lib/PcpCheckSupport.php 冒頭コメント)。

require_once __DIR__ . '/../../../../../tools/lib/PcpCheckSupport.php';

use function PractiCase\Pcp\isCheckProbe;
use function PractiCase\Pcp\pcpAuditEntries;
use function PractiCase\Pcp\pcpNote;
use function PractiCase\Pcp\pcpRequest;

test('C-002: PCPサーバーへ疎通できる', function (): void {
    [$status, $json] = pcpRequest('GET', '/v1/health', null);
    assertTrue(
        $status === 200 && is_array($json) && ($json['status'] ?? '') === 'ok',
        'PCPに接続できません。docker compose up -d で pcp サービスが起動しているか確認してください'
    );
});

test('C-002: キー無しでの401を試した記録がある', function (): void {
    $noKey = 0;
    foreach (pcpAuditEntries(200, 'INVALID_API_KEY') as $entry) {
        if (($entry['api_key_suffix'] ?? null) === null) {
            $noKey++;
        }
    }
    assertTrue(
        $noKey >= 1,
        'Authorizationヘッダー無しで送った401の記録が見当たりません。support/spec.md の「失敗1」の手順を実行してください'
    );
});

test('C-002: 誤ったキーでの401を試した記録がある', function (): void {
    $wrongKey = 0;
    foreach (pcpAuditEntries(200, 'INVALID_API_KEY') as $entry) {
        $suffix = $entry['api_key_suffix'] ?? null;
        if (is_string($suffix) && $suffix !== '') {
            $wrongKey++;
        }
    }
    assertTrue(
        $wrongKey >= 1,
        '誤ったキー(存在しないダミーキー)で送った401の記録が見当たりません。support/spec.md の「失敗2」の手順を実行してください'
    );
});

test('C-002: 正しいキーでの成功(対照)の記録がある', function (): void {
    $learnerPosts = 0;
    foreach (pcpAuditEntries(200, 'SUCCESS') as $entry) {
        $recipient = (string) ($entry['recipient'] ?? '');
        if (($entry['path'] ?? '') === '/v1/notifications'
            && ($entry['http_status'] ?? 0) === 201
            && $recipient !== ''
            && !isCheckProbe($recipient)) {
            $learnerPosts++;
        }
    }
    assertTrue(
        $learnerPosts >= 1,
        '正しいキーで通知を作成した記録(対照実験の成功側)が見当たりません。support/spec.md の「成功」の例を実行してください'
    );
});

test('C-002: 報告書が3つの見出しで書かれている', function (): void {
    $note = pcpNote('C-002');
    assertTrue($note !== '', '報告書(reports/C-002_report.md)が見当たりません');
    foreach (['何を試したか', '401から何が分かったか', '監査ログに何が残ったか'] as $heading) {
        assertTrue(str_contains($note, $heading), "報告書に見出し「{$heading}」が見当たりません(ticket.md の指定の文言をそのまま使ってください)");
    }
    assertTrue(str_contains($note, 'INVALID_API_KEY'), '報告書にエラーコード(INVALID_API_KEY)が書かれていません。実際に返ってきたコードを書き写してください');
    assertTrue(str_contains($note, '401'), '報告書に401への言及がありません');
});
