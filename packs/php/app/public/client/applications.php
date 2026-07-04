<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\Repository\ApplicationRepository;
use App\Repository\ProjectRepository;
use App\Support\Auth;
use App\Support\Csrf;

// S-13: 応募者一覧(client・自社案件のみ)
$user = Auth::requireLogin();
Auth::requireRole($user, 'client');

$project = (new ProjectRepository())->findById((int) ($_GET['project_id'] ?? 0));
// G-4 / SEC-6: project_id を書き換えた他社案件へのアクセスは 404
if ($project === null || (int) $project['company_id'] !== (int) $user['company_id']) {
    abort404();
}

$repository = new ApplicationRepository();
$applications = $repository->listByProject((int) $project['id']);
$acceptedCount = $repository->countAccepted((int) $project['id']);
$statusLabels = ['applied' => '選考中', 'accepted' => '承認済み', 'rejected' => '却下', 'withdrawn' => '取り下げ'];

$pageTitle = '応募者一覧';
require __DIR__ . '/../../templates/header.php';
?>
<h1>応募者一覧: <?= e($project['title']) ?></h1>
<p>承認 <?= e((string) $acceptedCount) ?> / 募集 <?= e((string) $project['capacity']) ?></p>
<?php if ($applications === []): ?>
    <p>この案件への応募はまだありません。</p>
<?php else: ?>
<table>
    <tr><th>応募者名</th><th>応募日時</th><th>応募メッセージ</th><th>状態</th><th></th></tr>
    <?php foreach ($applications as $application): ?>
    <tr>
        <td><?= e($application['engineer_name']) ?></td>
        <td><?= e($application['applied_at']) ?></td>
        <td><?= nl2br(e($application['message'])) ?></td>
        <td><?= e($statusLabels[$application['status']] ?? $application['status']) ?></td>
        <td>
            <?php if ($application['status'] === 'applied'): ?>
            <form method="post" action="/client/application_decide.php" class="decide-form">
                <?= Csrf::field() ?>
                <input type="hidden" name="application_id" value="<?= e((string) $application['id']) ?>">
                <input type="hidden" name="project_id" value="<?= e((string) $project['id']) ?>">
                <button type="submit" name="decision" value="accept" class="btn btn-small btn-primary">承認</button>
                <button type="submit" name="decision" value="reject" class="btn btn-small">却下</button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
<p><a href="/client/projects.php">自社案件一覧へ戻る</a></p>
<?php require __DIR__ . '/../../templates/footer.php';
