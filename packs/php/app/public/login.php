<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Repository\UserRepository;
use App\Service\AuthService;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Logger;

// 課題 tutorial の演出(教材専用)。redirect より先に呼び、開始フラグをセッションに載せる
$tutorialMode = tutorialMode();

// S-01: ログイン済みならトップへ(screens.md 権限マトリクス)
if (Auth::user() !== null) {
    redirect('/index.php');
}

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify($_POST['csrf_token'] ?? null);
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $user = (new AuthService(new UserRepository()))->attempt($email, $password);
    if ($user !== null) {
        Logger::info('login.success', ['user' => $user['id']]);
        Auth::login($user);
        redirect('/index.php');
    }
    // 失敗側は入力値(email)を記録しない — 入力は本人のものとは限らない(LOG-2)
    Logger::info('login.failure');
    // F-01: 失敗理由は区別しない
    $error = 'メールアドレスまたはパスワードが正しくありません';
}

$pageTitle = 'ログイン';
require __DIR__ . '/../templates/header.php';
?>
<h1>ログイン</h1>
<?php if ($error !== null): ?>
    <div class="flash flash-error"><?= e($error) ?></div>
<?php endif; ?>
<form method="post" action="/login.php" class="form">
    <?= Csrf::field() ?>
    <div class="form-row">
        <label for="email">メールアドレス</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
    </div>
    <div class="form-row">
        <label for="password">パスワード</label>
        <input type="password" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary">ログイン</button>
</form>

<div class="quick-login">
    <h2>クイックログイン(ローカル学習環境専用)</h2>
    <p>ロールの切り替え用。クリックだけで通常のログイン処理を通ります(全員 password123 / docs/03_参考資料/world.md の名簿と同じ)。</p>
    <?php
    // ローカル教材専用の入力省略ボタン。認証は通常経路(AuthService)のまま
    $quickUsers = [
        ['桐生 蒼', 'エンジニア', 'kiryu@example.com'],
        ['浅葱 純', 'エンジニア', 'asagi@example.com'],
        ['柚木 涼太', 'エンジニア', 'yuzuki@example.com'],
        ['田淵 亮', 'クライアント', 'tabuchi@example.com'],
        ['志村 恵', 'クライアント', 'shimura@example.com'],
        ['小野寺 玲', '運営', 'admin@example.com'],
    ];
    ?>
    <?php foreach ($quickUsers as [$name, $role, $quickEmail]): ?>
    <form method="post" action="/login.php" class="quick-login-form">
        <?= Csrf::field() ?>
        <input type="hidden" name="email" value="<?= e($quickEmail) ?>">
        <input type="hidden" name="password" value="password123">
        <button type="submit" class="btn btn-small<?= ($tutorialMode && $quickEmail === 'kiryu@example.com') ? ' tutorial-spotlight' : '' ?>"><?= e($name) ?>(<?= e($role) ?>)</button>
    </form>
    <?php endforeach; ?>
</div>
<?php if ($tutorialMode) {
    $tutorialTip = 'ここから<strong>ブラウザ編</strong>です(症状を自分の目で見に行きます)。'
        . '進み方は VS Code と同じ — 画面ごとに<strong>明るい場所</strong>を押していくだけ。<br>'
        . 'まずは明るくなっている<strong>桐生 蒼(エンジニア)</strong>のボタンでログインします。';
    require __DIR__ . '/../templates/tutorial-tip.php';
} ?>
<?php require __DIR__ . '/../templates/footer.php';
