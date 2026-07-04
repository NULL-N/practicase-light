<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\Repository\UserRepository;
use App\Service\AdminService;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Flash;

// S-21: ユーザー一覧(admin のみ)
$user = Auth::requireLogin();
Auth::requireRole($user, 'admin');

$repository = new UserRepository();

// 停止 / 再開(S-21 内 POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify($_POST['csrf_token'] ?? null);
    $action = (string) ($_POST['action'] ?? '');
    $status = match ($action) {
        'suspend' => 'suspended',
        'activate' => 'active',
        default => null,
    };
    if ($status === null) {
        abort404();
    }
    $service = new AdminService($repository);
    $result = $service->changeUserStatus($user, (int) ($_POST['user_id'] ?? 0), $status);
    if ($result === null) {
        abort404();
    }
    $result === true
        ? Flash::success($status === 'suspended' ? 'ユーザーを停止しました' : 'ユーザーを再開しました')
        : Flash::error($result);
    redirect('/admin/users.php');
}

$users = $repository->listAll();
$roleLabels = ['engineer' => 'エンジニア', 'client' => 'クライアント', 'admin' => '運営'];
$statusLabels = ['active' => '有効', 'suspended' => '停止中'];

$pageTitle = 'ユーザー一覧';
require __DIR__ . '/../../templates/header.php';
?>
<h1>ユーザー一覧</h1>
<table>
    <tr><th>ID</th><th>名前</th><th>メールアドレス</th><th>ロール</th><th>状態</th><th></th></tr>
    <?php foreach ($users as $row): ?>
    <tr>
        <td><?= e((string) $row['id']) ?></td>
        <td><?= e($row['name']) ?></td>
        <td><?= e($row['email']) ?></td>
        <td><?= e($roleLabels[$row['role']] ?? $row['role']) ?></td>
        <td><?= e($statusLabels[$row['status']] ?? $row['status']) ?></td>
        <td>
            <?php if ((int) $row['id'] !== (int) $user['id']): ?>
            <form method="post" action="/admin/users.php">
                <?= Csrf::field() ?>
                <input type="hidden" name="user_id" value="<?= e((string) $row['id']) ?>">
                <?php if ($row['status'] === 'active'): ?>
                <button type="submit" name="action" value="suspend" class="btn btn-small">停止</button>
                <?php else: ?>
                <button type="submit" name="action" value="activate" class="btn btn-small">再開</button>
                <?php endif; ?>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../../templates/footer.php';
