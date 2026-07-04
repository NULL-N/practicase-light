<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\Repository\ProjectRepository;
use App\Service\ProjectService;
use App\Service\ProjectValidator;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Flash;

// S-11: 自社案件一覧(client のみ)
$user = Auth::requireLogin();
Auth::requireRole($user, 'client');

$repository = new ProjectRepository();

// 掲載終了(S-11 内 POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify($_POST['csrf_token'] ?? null);
    $service = new ProjectService($repository, new ProjectValidator());
    if (!$service->close($user, (int) ($_POST['project_id'] ?? 0))) {
        abort404(); // G-4: 他社・存在しない案件
    }
    Flash::success('案件の掲載を終了しました');
    redirect('/client/projects.php');
}

$projects = $repository->listByCompany((int) $user['company_id']);
$statusLabels = ['draft' => '下書き', 'open' => '公開中', 'closed' => '掲載終了'];

$pageTitle = '自社案件一覧';
require __DIR__ . '/../../templates/header.php';
?>
<h1>自社案件一覧</h1>
<p><a class="btn btn-primary" href="/client/project_new.php">案件を登録する</a></p>
<?php if ($projects === []): ?>
    <p>登録済みの案件はまだありません。</p>
<?php else: ?>
<table>
    <tr><th>案件名</th><th>状態</th><th>応募締切</th><th>応募数</th><th>承認数 / 募集人数</th><th></th><th></th></tr>
    <?php foreach ($projects as $project): ?>
    <tr>
        <td><a href="/projects/show.php?id=<?= e((string) $project['id']) ?>"><?= e($project['title']) ?></a></td>
        <td><?= e($statusLabels[$project['status']] ?? $project['status']) ?></td>
        <td><?= e($project['deadline']) ?></td>
        <td><?= e((string) $project['application_count']) ?></td>
        <td><?= e((string) $project['accepted_count']) ?> / <?= e((string) $project['capacity']) ?></td>
        <td><a href="/client/applications.php?project_id=<?= e((string) $project['id']) ?>">応募者を見る</a></td>
        <td>
            <?php if ($project['status'] === 'open'): ?>
            <form method="post" action="/client/projects.php"
                  onsubmit="return confirm('この案件の掲載を終了しますか?');">
                <?= Csrf::field() ?>
                <input type="hidden" name="project_id" value="<?= e((string) $project['id']) ?>">
                <button type="submit" class="btn btn-small">掲載終了</button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
<?php require __DIR__ . '/../../templates/footer.php';
