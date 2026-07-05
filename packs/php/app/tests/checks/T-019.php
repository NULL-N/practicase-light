<?php

declare(strict_types=1);

use App\Repository\UserRepository;
use App\Service\AuthService;

test('T-019: admin ロールのユーザーが正しい資格情報でログインできる(修正対象)', function (): void {
    $pdo = freshDatabase();
    insertUser($pdo, ['email' => 'admin-check@example.com', 'role' => 'admin']);

    $user = (new AuthService(new UserRepository()))->attempt('admin-check@example.com', 'password123');
    assertNotNull($user);
    assertSame('admin', $user['role']);
});

test('T-019: engineer / client の通常ログインは変わらず動く(回帰確認)', function (): void {
    $pdo = freshDatabase();
    insertUser($pdo, ['email' => 'engineer-check@example.com', 'role' => 'engineer']);
    insertUser($pdo, ['email' => 'client-check@example.com', 'role' => 'client']);

    $engineer = (new AuthService(new UserRepository()))->attempt('engineer-check@example.com', 'password123');
    $client = (new AuthService(new UserRepository()))->attempt('client-check@example.com', 'password123');
    assertNotNull($engineer);
    assertNotNull($client);
});

test('T-019: パスワード不一致は変わらず拒否される(回帰確認)', function (): void {
    $pdo = freshDatabase();
    insertUser($pdo, ['email' => 'wrongpass-check@example.com', 'role' => 'admin']);

    assertNull((new AuthService(new UserRepository()))->attempt('wrongpass-check@example.com', 'wrong-password'));
});

test('T-019: 存在しないメールアドレスは変わらず拒否される(回帰確認)', function (): void {
    freshDatabase();

    assertNull((new AuthService(new UserRepository()))->attempt('nobody-check@example.com', 'password123'));
});

test('T-019: suspended なユーザーは変わらず拒否される(T-018の仕様・回帰確認)', function (): void {
    $pdo = freshDatabase();
    insertUser($pdo, ['email' => 'suspended-check@example.com', 'role' => 'admin', 'status' => 'suspended']);

    assertNull((new AuthService(new UserRepository()))->attempt('suspended-check@example.com', 'password123'));
});
