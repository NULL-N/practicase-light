<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Support\Auth;
use App\Support\Csrf;

// S-02: POST のみ(G-5。GET での状態変更は禁止)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}
Csrf::verify($_POST['csrf_token'] ?? null);

Auth::logout();
redirect('/login.php');
