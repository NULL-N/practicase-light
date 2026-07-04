<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Repository\ApplicationRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Support\Auth;

// S-00: ロール別ホーム。閲覧のみ(業務ロジックは持たない)
$user = Auth::requireLogin();
$roleLabels = ['engineer' => 'エンジニア', 'client' => 'クライアント', 'admin' => '運営'];

$summary = '';
$actions = [];
if ($user['role'] === 'engineer') {
    $mine = (new ApplicationRepository())->listByEngineer((int) $user['id']);
    $inReview = count(array_filter($mine, fn (array $a): bool => $a['status'] === 'applied'));
    $summary = '応募 ' . count($mine) . ' 件(うち選考中 ' . $inReview . ' 件)';
    $actions = [
        ['案件を探す', '/projects/index.php', '公開中の案件を検索して応募する'],
        ['マイ応募を見る', '/applications/mine.php', '自分の応募と選考状況を確認する'],
    ];
} elseif ($user['role'] === 'client') {
    $projects = (new ProjectRepository())->listByCompany((int) $user['company_id']);
    $open = count(array_filter($projects, fn (array $p): bool => $p['status'] === 'open'));
    $applications = array_sum(array_map(fn (array $p): int => (int) $p['application_count'], $projects));
    $summary = '自社案件 ' . count($projects) . ' 件(公開中 ' . $open . ' 件)/ 累計応募 ' . $applications . ' 件';
    $actions = [
        ['自社案件を見る', '/client/projects.php', '掲載中の案件と応募状況を確認する'],
        ['応募者を確認する', '/client/projects.php', '案件ごとの「応募者を見る」から承認・却下する'],
        ['案件を登録する', '/client/project_new.php', '新しい案件を掲載する'],
    ];
} else {
    $summary = '登録ユーザー ' . count((new UserRepository())->listAll()) . ' 名';
    $actions = [
        ['ユーザーを管理する', '/admin/users.php', '利用者の一覧・停止・再開'],
        ['全案件を見る', '/projects/index.php', '全ステータスの案件を確認する'],
    ];
}

$tutorialMode = tutorialMode(); // 課題 tutorial の演出(教材専用)

$pageTitle = 'ホーム';
require __DIR__ . '/../templates/header.php';
?>
<h1>ようこそ、<?= e($user['name']) ?> さん</h1>
<p>ロール: <strong><?= e($roleLabels[$user['role']] ?? $user['role']) ?></strong> / <?= e($summary) ?></p>

<div class="home-actions">
    <?php foreach ($actions as [$label, $href, $description]): ?>
    <a class="home-action<?= ($tutorialMode && $user['role'] === 'engineer' && $label === '案件を探す') ? ' tutorial-spotlight' : '' ?>" href="<?= e($href) ?>">
        <span class="home-action-title"><?= e($label) ?></span>
        <span class="home-action-desc"><?= e($description) ?></span>
    </a>
    <?php endforeach; ?>
</div>
<?php if ($tutorialMode) {
    $tutorialTip = 'ログインできました。次は<strong>「案件を探す」</strong>から、公開中の案件一覧を開きます。';
    require __DIR__ . '/../templates/tutorial-tip.php';
} ?>

<p class="home-note">このアプリは課題の<strong>症状を確認する場</strong>です。コードの修正・差分確認・レビューは、
エディタ / git diff / Pull Request で行います(進め方: <code>docs/02_作業ルール/workflow.md</code>)。</p>
<?php require __DIR__ . '/../templates/footer.php';
