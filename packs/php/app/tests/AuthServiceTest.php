<?php

declare(strict_types=1);

use App\Repository\UserRepository;
use App\Service\AuthService;

test('正しい資格情報でログインできる(F-01)', function (): void {
    $pdo = freshDatabase();
    insertUser($pdo, ['email' => 'kiryu@example.com']);

    $user = (new AuthService(new UserRepository()))->attempt('kiryu@example.com', 'password123');
    assertNotNull($user);
    assertSame('kiryu@example.com', $user['email']);
});

test('パスワード不一致は null(F-01)', function (): void {
    $pdo = freshDatabase();
    insertUser($pdo, ['email' => 'kiryu@example.com']);

    assertNull((new AuthService(new UserRepository()))->attempt('kiryu@example.com', 'wrong-password'));
});

test('存在しないメールアドレスは null(F-01)', function (): void {
    freshDatabase();

    assertNull((new AuthService(new UserRepository()))->attempt('nobody@example.com', 'password123'));
});

test('suspended ユーザーは正しいパスワードでも null(F-01)', function (): void {
    $pdo = freshDatabase();
    insertUser($pdo, ['email' => 'kiryu@example.com', 'status' => 'suspended']);

    assertNull((new AuthService(new UserRepository()))->attempt('kiryu@example.com', 'password123'));
});
