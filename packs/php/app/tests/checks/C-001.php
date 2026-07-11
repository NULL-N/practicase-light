<?php

declare(strict_types=1);

// C-001 の合格条件: PCP(教材内のローカル架空クラウドAPI)が実際に応答すること、
// 学習者が通知APIを自分のリクエストで呼んだ記録が監査ログに残っていること、
// 報告書が3つの観点(何を送ったか/何が返ったか/監査ログに何が残ったか)で
// 書かれていて、報告書内の通知IDがPCP上に実在すること。
//
// checkは学習者の提出物を実行せず、check自身が固定URLへHTTPリクエストを送り、
// 応答と監査ログを直接検証する(共通ヘルパーと不変条件の正本:
// tools/lib/PcpCheckSupport.php — このcheckも401を自分では発生させない)。
// 宛先 C001_PROBE_RECIPIENT の通知はcheckの自動送信分なので、学習者の操作の
// 判定からは除外する。

require_once __DIR__ . '/../../../../../tools/lib/PcpCheckSupport.php';

use function PractiCase\Pcp\isCheckProbe;
use function PractiCase\Pcp\pcpAuditEntries;
use function PractiCase\Pcp\pcpNote;
use function PractiCase\Pcp\pcpRequest;
use const PractiCase\Pcp\PCP_API_KEY;

const C001_PROBE_RECIPIENT = 'check-c001-probe';

test('C-001: PCPサーバーへ疎通できる', function (): void {
    [$status, $json] = pcpRequest('GET', '/v1/health', null);
    assertTrue(
        $status === 200 && is_array($json) && ($json['status'] ?? '') === 'ok',
        'PCPに接続できません。docker compose up -d で pcp サービスが起動しているか確認してください'
    );
});

test('C-001: 通知APIが作成→状態確認→配送まで動く(checkによる実測)', function (): void {
    [$status, $created] = pcpRequest('POST', '/v1/notifications', PCP_API_KEY, [
        'recipient' => C001_PROBE_RECIPIENT . '-ok',
        'message' => 'check用の自動送信(学習者の操作ではありません)',
    ]);
    assertTrue($status === 201, "通知の作成が201になりません(実際: {$status})");
    $id = is_array($created) ? (string) ($created['id'] ?? '') : '';
    assertTrue(preg_match('/\Antf_[0-9]+\z/', $id) === 1, '作成レスポンスに通知ID(id)がありません');
    assertTrue(is_array($created) && ($created['status'] ?? '') === 'queued', '作成直後のstatusがqueuedではありません');
    if ($id === '') {
        return;
    }
    [$status, $shown] = pcpRequest('GET', '/v1/notifications/' . $id, PCP_API_KEY);
    assertTrue($status === 200 && is_array($shown) && ($shown['status'] ?? '') === 'queued', '作成直後のGETがqueuedを返しません');
    sleep(3);
    [$status, $shown] = pcpRequest('GET', '/v1/notifications/' . $id, PCP_API_KEY);
    assertTrue(
        $status === 200 && is_array($shown) && ($shown['status'] ?? '') === 'delivered',
        '約2秒経過後のstatusがdeliveredになりません(状態遷移の不具合)'
    );
});

test('C-001: 監査ログが記録され、APIキーは末尾4桁だけが残る', function (): void {
    [$status, $log, $raw] = pcpRequest('GET', '/v1/audit-log?limit=200', PCP_API_KEY);
    assertTrue($status === 200 && is_array($log), '監査ログの照会に失敗しました');
    $entries = is_array($log) ? ($log['entries'] ?? []) : [];
    assertTrue(is_array($entries) && $entries !== [], '監査ログにエントリがありません');
    assertTrue(!str_contains($raw, PCP_API_KEY), '監査ログにAPIキーの全文が残っています(末尾4桁だけになるはずです)');
    $suffixes = array_column(is_array($entries) ? $entries : [], 'api_key_suffix');
    assertTrue(in_array('0718', $suffixes, true), '教材用テストキーの末尾4桁(0718)が監査ログに見当たりません');
});

test('C-001: 学習者自身が通知APIを呼んだ記録がある(checkの自動送信分を除く)', function (): void {
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
        '通知APIをあなた自身のリクエストで呼んだ記録が見つかりません。support/spec.md の手順どおり、appコンテナの中からcurlで通知を作成してください'
    );
});

test('C-001: 報告書が3つの見出しで書かれている', function (): void {
    $note = pcpNote('C-001');
    assertTrue($note !== '', '報告書(reports/C-001_report.md)が見当たりません');
    foreach (['何を送ったか', '何が返ったか', '監査ログに何が残ったか'] as $heading) {
        assertTrue(str_contains($note, $heading), "報告書に見出し「{$heading}」が見当たりません(ticket.md の指定の文言をそのまま使ってください)");
    }
    assertTrue(preg_match('/ntf_[0-9]+/', $note) === 1, '報告書に通知ID(ntf_...)が書かれていません。実際に返ってきたIDを「何が返ったか」に書いてください');
});

test('C-001: 報告書の通知IDがPCP上に実在する', function (): void {
    $note = pcpNote('C-001');
    preg_match_all('/ntf_[0-9]+/', $note, $m);
    $ids = array_values(array_unique($m[0] ?? []));
    if ($ids === []) {
        assertTrue(false, '報告書に通知ID(ntf_...)が書かれていません');

        return;
    }
    $found = false;
    foreach ($ids as $id) {
        [$status] = pcpRequest('GET', '/v1/notifications/' . $id, PCP_API_KEY);
        if ($status === 200) {
            $found = true;
            break;
        }
    }
    assertTrue($found, '報告書に書かれた通知IDがPCP上に存在しません。実際に送信した通知のID(作成レスポンスのid)を書き写してください');
});
