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

// S-14: 承認/却下の実行(POST 専用・client のみ)
$user = Auth::requireLogin();
Auth::requireRole($user, 'client');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/client/projects.php');
}
Csrf::verify($_POST['csrf_token'] ?? null);

$applicationId = (int) ($_POST['application_id'] ?? 0);
$decision = (string) ($_POST['decision'] ?? '');
// project_id はリダイレクト先の表示にだけ使う(権限判定はサービス内で応募→案件→企業を辿る)
$projectId = (int) ($_POST['project_id'] ?? 0);

$service = new ApplicationService(new ApplicationRepository(), new ProjectRepository());
$result = match ($decision) {
    'accept' => $service->accept($user, $applicationId),
    'reject' => $service->reject($user, $applicationId),
    default => null,
};

if ($result === null) {
    abort404(); // D-1 / G-4
}
if ($result === true) {
    Logger::info('application.decide', ['user' => $user['id'], 'application' => $applicationId, 'decision' => $decision]);
    Flash::success($decision === 'accept' ? '応募を承認しました' : '応募を却下しました');
} else {
    Flash::error($result);
}
redirect('/client/applications.php?project_id=' . $projectId);
