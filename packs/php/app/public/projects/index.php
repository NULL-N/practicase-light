<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\Repository\ProjectRepository;
use App\Support\Auth;
use App\Support\Clock;

// S-03: engineer と admin のみ(screens.md 権限マトリクス)
$user = Auth::requireLogin();
Auth::requireRole($user, 'engineer', 'admin');

$keyword = trim((string) ($_GET['keyword'] ?? ''));
$remoteOnly = ($_GET['remote_only'] ?? '') === '1';
$tutorialMode = tutorialMode(); // 課題 tutorial の演出(教材専用)

$repository = new ProjectRepository();
$isAdmin = $user['role'] === 'admin';
$projects = $isAdmin
    ? $repository->searchAll($keyword, $remoteOnly)
    : $repository->searchOpen($keyword, $remoteOnly, Clock::today()->format('Y-m-d'));

$statusLabels = ['draft' => '下書き', 'open' => '公開中', 'closed' => '掲載終了'];
$pageTitle = '案件一覧';
require __DIR__ . '/../../templates/header.php';
?>
<h1>案件一覧</h1>
<form method="get" action="/projects/index.php" class="search-form">
    <input type="text" name="keyword" value="<?= e($keyword) ?>" placeholder="キーワード(案件名・内容)">
    <label class="checkbox-label">
        <input type="checkbox" name="remote_only" value="1" <?= $remoteOnly ? 'checked' : '' ?>>
        リモート可のみ
    </label>
    <button type="submit" class="btn btn-primary">検索</button>
</form>
<?php if ($projects === []): ?>
    <p>条件に合う案件はありません</p>
<?php else: ?>
<table>
    <tr>
        <th>案件名</th><th>企業名</th><th>時間単価</th><th>稼働開始日</th><th>応募締切</th><th>リモート</th>
        <?php if ($isAdmin): ?><th>状態</th><?php endif; ?>
        <th></th>
    </tr>
    <?php foreach ($projects as $i => $project): ?>
    <tr>
        <td>
            <a href="/projects/show.php?id=<?= e((string) $project['id']) ?>"><?= e($project['title']) ?></a>
            <div class="excerpt"><?= e(mb_strimwidth($project['description'], 0, 60, '…')) ?></div>
        </td>
        <td><?= e($project['company_name']) ?></td>
        <td><?= e(number_format((int) $project['hourly_rate'])) ?>円</td>
        <td><?= e($project['work_start_on']) ?></td>
        <td><?= e($project['deadline']) ?></td>
        <td><?= $project['is_remote'] ? '可' : '不可' ?></td>
        <?php if ($isAdmin): ?><td><?= e($statusLabels[$project['status']] ?? $project['status']) ?></td><?php endif; ?>
        <td><a class="btn btn-small<?= ($tutorialMode && $i === 0) ? ' tutorial-spotlight' : '' ?>" href="/projects/show.php?id=<?= e((string) $project['id']) ?>">詳細を見る</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
<?php if ($tutorialMode) {
    $tutorialTip = 'これが案件一覧です。表の<strong>列の名前</strong>をざっと眺めたら、'
        . 'どれでもよいので詳細を開きます — いちばん上の<strong>「詳細を見る」</strong>へ。';
    require __DIR__ . '/../../templates/tutorial-tip.php';
} ?>
<?php require __DIR__ . '/../../templates/footer.php';
