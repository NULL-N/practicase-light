<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use App\Repository\ApplicationRepository;
use App\Repository\ProjectRepository;
use App\Service\ApplicationService;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Flash;
use App\Support\Logger;

// S-05: 応募実行(POST 専用・engineer のみ)
$user = Auth::requireLogin();
Auth::requireRole($user, 'engineer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/projects/index.php');
}
Csrf::verify($_POST['csrf_token'] ?? null);

$projectId = (int) ($_POST['project_id'] ?? 0);
$message = trim((string) ($_POST['message'] ?? ''));

$project = (new ProjectRepository())->findById($projectId);
if ($project === null) {
    abort404();
}

$service = new ApplicationService(new ApplicationRepository(), new ProjectRepository());
$result = $service->apply($user, $project, $message);

if ($result === true) {
    Logger::info('application.apply', ['user' => $user['id'], 'project' => $project['id']]);
    Flash::success('応募しました');
    redirect('/applications/mine.php');
}

// G-7: エラー時は入力値を保持して案件詳細へ戻す
$_SESSION['old_message'] = $message;
Flash::error($result);
redirect('/projects/show.php?id=' . $projectId);
