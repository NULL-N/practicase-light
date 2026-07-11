<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\Support\Auth;
use App\Support\PcpAuditLogClient;

// API監査ログ: C-001〜C-003 で行った API 操作を目で確認するための観察窓
// (admin のみ・読み取り専用)。PCP はホスト非公開のまま、この画面が
// サーバーサイドで GET /v1/audit-log を読む。PCP の状態は一切変えない
$user = Auth::requireLogin();
Auth::requireRole($user, 'admin');

// 絞り込みは event_type だけ。扱う語彙も外部API入門(C-001〜C-003)で
// 登場する3つに限定する
$eventTypes = ['SUCCESS', 'INVALID_API_KEY', 'INSUFFICIENT_SCOPE'];
$eventType = trim((string) ($_GET['event_type'] ?? ''));
if ($eventType !== '' && !in_array($eventType, $eventTypes, true)) {
    $eventType = '';
}
$limit = 50;

$result = (new PcpAuditLogClient())->fetchEntries('', '', $eventType, $limit);

// 直近ログの内訳(取得できた範囲での件数。観察の入口として十分な粒度)
$counts = ['SUCCESS' => 0, 'INVALID_API_KEY' => 0, 'INSUFFICIENT_SCOPE' => 0];
foreach ($result['entries'] as $entry) {
    $type = (string) ($entry['event_type'] ?? '');
    if (isset($counts[$type])) {
        $counts[$type]++;
    }
}

$pageTitle = 'API監査ログ';
require __DIR__ . '/../../templates/header.php';
?>
<h1>API監査ログ</h1>
<p>通知基盤 PCP の監査ログを新しい順に表示します(直近<?= e((string) $limit) ?>件・読み取り専用)。
curl で照会したのと同じ記録が、ここでは画面で確認できます。</p>
<form method="get" action="/admin/pcp-audit-log.php">
    <label>event_type
        <select name="event_type">
            <option value="">(すべて)</option>
            <?php foreach ($eventTypes as $type): ?>
            <option value="<?= e($type) ?>"<?= $type === $eventType ? ' selected' : '' ?>><?= e($type) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit" class="btn">絞り込む</button>
</form>
<?php if (!$result['ok']): ?>
<p><?= e($result['error']) ?></p>
<?php else: ?>
<p>
    内訳: 成功(SUCCESS)<?= e((string) $counts['SUCCESS']) ?>件 /
    認証エラー(INVALID_API_KEY・401)<?= e((string) $counts['INVALID_API_KEY']) ?>件 /
    権限エラー(INSUFFICIENT_SCOPE・403)<?= e((string) $counts['INSUFFICIENT_SCOPE']) ?>件
</p>
<?php if ($result['entries'] === []): ?>
<p>該当する記録がありません。</p>
<?php else: ?>
<table>
    <tr>
        <th>event_type</th><th>HTTP</th><th>キー末尾</th><th>宛先</th><th>時刻(UTC)</th>
    </tr>
    <?php foreach ($result['entries'] as $entry): ?>
    <tr>
        <td><?= e((string) ($entry['event_type'] ?? '')) ?></td>
        <td><?= e((string) ($entry['http_status'] ?? '')) ?></td>
        <td><?= e((string) ($entry['api_key_suffix'] ?? '')) ?></td>
        <td><?= e((string) ($entry['recipient'] ?? '')) ?></td>
        <td><?= e((string) ($entry['timestamp'] ?? '')) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/../../templates/footer.php';
