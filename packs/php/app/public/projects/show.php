<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\Repository\ApplicationRepository;
use App\Repository\ProjectRepository;
use App\Service\ApplicationService;
use App\Support\Auth;
use App\Support\Csrf;

$user = Auth::requireLogin();

$project = (new ProjectRepository())->findById((int) ($_GET['id'] ?? 0));
if ($project === null) {
    abort404();
}

// S-04 閲覧権限(F-04): engineer=open のみ / client=自社のみ / admin=全件
if ($user['role'] === 'engineer' && $project['status'] !== 'open') {
    abort404();
}
if ($user['role'] === 'client' && (int) $project['company_id'] !== (int) $user['company_id']) {
    abort404();
}

$applyBlockedReason = null;
if ($user['role'] === 'engineer') {
    $service = new ApplicationService(new ApplicationRepository(), new ProjectRepository());
    $applyBlockedReason = $service->applyBlockedReason($user, $project);
}
$oldMessage = $_SESSION['old_message'] ?? '';
unset($_SESSION['old_message']);

$tutorialMode = tutorialMode(); // 課題 tutorial の演出(教材専用)

$pageTitle = $project['title'];
require __DIR__ . '/../../templates/header.php';
?>
<h1><?= e($project['title']) ?></h1>
<h2>案件内容</h2>
<div class="project-description"><?= nl2br(e($project['description'])) ?></div>
<table class="detail-table">
    <tr><th>企業名</th><td><?= e($project['company_name']) ?></td></tr>
    <tr<?= $tutorialMode ? ' class="tutorial-spotlight"' : '' ?>><th>報酬単価</th><td><?= e(number_format((int) $project['hourly_rate'])) ?>円</td></tr>
    <tr><th>募集人数</th><td><?= e((string) $project['capacity']) ?>人</td></tr>
    <tr><th>応募締切</th><td><?= e($project['deadline']) ?></td></tr>
    <tr><th>稼働開始日</th><td><?= e($project['work_start_on']) ?></td></tr>
    <tr><th>リモート</th><td><?= $project['is_remote'] ? '可' : '不可' ?></td></tr>
</table>

<?php if ($tutorialMode) {
    $tutorialTip = '明るくなっている行の<strong>項目名</strong>を、さっきの一覧の<strong>列の名前</strong>と'
        . '見比べてください。違和感を見つけたら、ここから先は VS Code です — チケットの<strong>手順4(検索)</strong>へ。';
    require __DIR__ . '/../../templates/tutorial-tip.php';
} ?>

<?php if ($user['role'] === 'engineer'): ?>
    <?php if ($applyBlockedReason !== null): ?>
        <p class="apply-blocked"><?= e($applyBlockedReason) ?></p>
    <?php else: ?>
        <h2>この案件に応募する</h2>
        <form method="post" action="/applications/create.php" class="form">
            <?= Csrf::field() ?>
            <input type="hidden" name="project_id" value="<?= e((string) $project['id']) ?>">
            <div class="form-row">
                <label for="message">応募メッセージ(任意・500文字以内)</label>
                <textarea id="message" name="message" rows="4"><?= e($oldMessage) ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">応募する</button>
        </form>
    <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/../../templates/footer.php';
