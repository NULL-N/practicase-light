<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database;

// ARC-2: users テーブルへの SQL はこのクラスに集約する
final class UserRepository
{
    public function findByEmail(string $email): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE email = :email'); // SEC-1
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    public function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    // F-09: 管理画面のユーザー一覧
    public function listAll(): array
    {
        return Database::pdo()->query('SELECT * FROM users ORDER BY id ASC')->fetchAll();
    }

    public function updateStatus(int $id, string $status, string $now): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE users SET status = :status, updated_at = :now WHERE id = :id'
        );
        $stmt->execute(['status' => $status, 'now' => $now, 'id' => $id]);
    }
}
