<?php

declare(strict_types=1);

use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Flash;

/** @var string $pageTitle 各ページが設定する */
$currentUser = Auth::user();
$flash = Flash::pull();
$roleLabels = ['engineer' => 'エンジニア', 'client' => 'クライアント', 'admin' => '運営'];
// ロール別のナビゲーション(自分に何ができるかを常に見えるようにする)
$navLinks = match ($currentUser['role'] ?? '') {
    'engineer' => [['案件一覧', '/projects/index.php'], ['マイ応募', '/applications/mine.php']],
    'client' => [['自社案件', '/client/projects.php'], ['案件登録', '/client/project_new.php']],
    'admin' => [['ユーザー一覧', '/admin/users.php'], ['案件一覧', '/projects/index.php']],
    default => [],
};
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'PractiCase') ?> | PractiCase</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="site-header-inner">
        <a class="brand" href="/index.php">PractiCase</a>
        <?php if ($currentUser !== null): ?>
            <nav class="main-nav">
                <?php foreach ($navLinks as [$label, $href]): ?>
                    <a href="<?= e($href) ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </nav>
            <nav class="user-nav">
                <span><?= e($currentUser['name']) ?>(<?= e($roleLabels[$currentUser['role']] ?? $currentUser['role']) ?>)</span>
                <form method="post" action="/logout.php">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn-link">ログアウト</button>
                </form>
            </nav>
        <?php endif; ?>
    </div>
</header>
<main class="container">
<?php if ($flash !== null): ?>
    <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>
