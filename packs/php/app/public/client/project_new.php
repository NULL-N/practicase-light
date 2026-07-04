<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\Repository\ProjectRepository;
use App\Service\ProjectService;
use App\Service\ProjectValidator;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Flash;

// S-12: 案件登録(client のみ)
$user = Auth::requireLogin();
Auth::requireRole($user, 'client');

$errors = [];
$input = ['title' => '', 'description' => '', 'hourly_rate' => '', 'capacity' => '',
          'deadline' => '', 'work_start_on' => '', 'is_remote' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify($_POST['csrf_token'] ?? null);
    $input = array_merge($input, array_intersect_key($_POST, $input));

    $service = new ProjectService(new ProjectRepository(), new ProjectValidator());
    $result = $service->register($user, $input);
    if ($result['errors'] === []) {
        Flash::success('案件を登録しました');
        redirect('/client/projects.php');
    }
    $errors = $result['errors']; // G-7: 全エラーをまとめて表示・入力値保持
}

$pageTitle = '案件登録';
require __DIR__ . '/../../templates/header.php';
?>
<h1>案件登録</h1>
<?php if ($errors !== []): ?>
    <div class="flash flash-error">
        <ul class="error-list">
            <?php foreach ($errors as $message): ?>
            <li><?= e($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<form method="post" action="/client/project_new.php" class="form">
    <?= Csrf::field() ?>
    <div class="form-row">
        <label for="title">案件名(100文字以内)</label>
        <input type="text" id="title" name="title" value="<?= e((string) $input['title']) ?>">
    </div>
    <div class="form-row">
        <label for="description">案件内容(2000文字以内)</label>
        <textarea id="description" name="description" rows="6"><?= e((string) $input['description']) ?></textarea>
    </div>
    <div class="form-row">
        <label for="hourly_rate">時間単価(円)</label>
        <input type="text" id="hourly_rate" name="hourly_rate" value="<?= e((string) $input['hourly_rate']) ?>">
    </div>
    <div class="form-row">
        <label for="capacity">募集人数</label>
        <input type="text" id="capacity" name="capacity" value="<?= e((string) $input['capacity']) ?>">
    </div>
    <div class="form-row">
        <label for="deadline">応募締切日</label>
        <input type="date" id="deadline" name="deadline" value="<?= e((string) $input['deadline']) ?>">
    </div>
    <div class="form-row">
        <label for="work_start_on">稼働開始日</label>
        <input type="date" id="work_start_on" name="work_start_on" value="<?= e((string) $input['work_start_on']) ?>">
    </div>
    <div class="form-row">
        <label class="checkbox-label">
            <input type="checkbox" name="is_remote" value="1" <?= ($input['is_remote'] ?? '') === '1' ? 'checked' : '' ?>>
            リモート可
        </label>
    </div>
    <button type="submit" class="btn btn-primary">登録する</button>
</form>
<?php require __DIR__ . '/../../templates/footer.php';
