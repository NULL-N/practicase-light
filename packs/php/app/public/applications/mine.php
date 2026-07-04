<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\Repository\ApplicationRepository;
use App\Repository\ProjectRepository;
use App\Service\ApplicationService;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Flash;

// S-06: マイ応募一覧(engineer のみ・自分の分のみ)
$user = Auth::requireLogin();
Auth::requireRole($user, 'engineer');

$repository = new ApplicationRepository();

// 取り下げ(S-06 内 POST。G-5)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify($_POST['csrf_token'] ?? null);
    $service = new ApplicationService($repository, new ProjectRepository());
    $result = $service->withdraw($user, (int) ($_POST['application_id'] ?? 0));
    if ($result === null) {
        abort404(); // G-4: 他人の応募
    }
    $result === true ? Flash::success('応募を取り下げました') : Flash::error($result);
    redirect('/applications/mine.php');
}

$applications = $repository->listByEngineer((int) $user['id']);
// F-06 と同じ表示名を使う(features.md)
$statusLabels = ['applied' => '選考中', 'accepted' => '承認', 'rejected' => '見送り', 'withdrawn' => '取り下げ'];

$pageTitle = 'マイ応募';
require __DIR__ . '/../../templates/header.php';
?>
<h1>マイ応募</h1>
<?php if ($applications === []): ?>
    <p>応募した案件はまだありません。<a href="/projects/index.php">案件一覧</a>から応募できます。</p>
<?php else: ?>
<table>
    <tr><th>案件名</th><th>企業名</th><th>応募日時</th><th>状態</th><th></th></tr>
    <?php foreach ($applications as $application): ?>
    <tr>
        <td><?= e($application['project_title']) ?></td>
        <td><?= e($application['company_name']) ?></td>
        <td><?= e($application['applied_at']) ?></td>
        <td><?= e($statusLabels[$application['status']] ?? $application['status']) ?></td>
        <td>
            <?php if ($application['status'] === 'applied'): ?>
            <form method="post" action="/applications/mine.php"
                  onsubmit="return confirm('この応募を取り下げますか?');">
                <?= Csrf::field() ?>
                <input type="hidden" name="application_id" value="<?= e((string) $application['id']) ?>">
                <button type="submit" class="btn btn-small">取り下げ</button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
<p class="status-legend">状態の見方: <strong>選考中</strong> = クライアント確認中(ボールは相手側)/
<strong>承認</strong> = 稼働決定 / <strong>見送り</strong> = 今回は不成立 / <strong>取り下げ</strong> = 自分で辞退した応募</p>
<?php require __DIR__ . '/../../templates/footer.php';
