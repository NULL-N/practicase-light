<?php

declare(strict_types=1);

use App\Repository\UserRepository;
use App\Service\AdminService;
use App\Support\Clock;

// F-09: ユーザー停止・再開

test('admin は他のユーザーを停止・再開できる(F-09)', function (): void {
    Clock::fix('2026-07-01 10:00:00');
    $pdo = freshDatabase();
    $admin = insertUser($pdo, ['role' => 'admin']);
    $target = insertUser($pdo);
    $repository = new UserRepository();
    $service = new AdminService($repository);

    assertSame(true, $service->changeUserStatus($admin, $target['id'], 'suspended'));
    assertSame('suspended', $repository->findById($target['id'])['status']);
    assertSame(true, $service->changeUserStatus($admin, $target['id'], 'active'));
    assertSame('active', $repository->findById($target['id'])['status']);
    Clock::clear();
});

test('admin は自分自身を停止できない(F-09)', function (): void {
    $pdo = freshDatabase();
    $admin = insertUser($pdo, ['role' => 'admin']);
    $service = new AdminService(new UserRepository());

    assertSame(AdminService::MSG_CANNOT_SUSPEND_SELF, $service->changeUserStatus($admin, $admin['id'], 'suspended'));
    assertSame('active', (new UserRepository())->findById($admin['id'])['status'], '状態は変わらない');
});

test('存在しないユーザー・不正な status は null(404)', function (): void {
    $pdo = freshDatabase();
    $admin = insertUser($pdo, ['role' => 'admin']);
    $service = new AdminService(new UserRepository());

    assertNull($service->changeUserStatus($admin, 9999, 'suspended'));
    assertNull($service->changeUserStatus($admin, $admin['id'], 'deleted'));
});
