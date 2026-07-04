<?php

declare(strict_types=1);

// テスト共通の準備。実 DB には触れず、in-memory SQLite を使う

require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/lib.php';

use App\Database;

// スキーマ適用済みの in-memory DB を作り、アプリの接続として差し込む
function freshDatabase(): \PDO
{
    $pdo = Database::connect('sqlite::memory:');
    $pdo->exec((string) file_get_contents(__DIR__ . '/../database/schema.sql'));
    Database::useConnection($pdo);

    return $pdo;
}

function insertUser(\PDO $pdo, array $overrides = []): array
{
    static $seq = 0;
    $seq++;
    $user = array_merge([
        'name' => 'テスト 太郎' . $seq,
        'email' => "test{$seq}@example.com",
        'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
        'role' => 'engineer',
        'company_id' => null,
        'status' => 'active',
    ], $overrides);

    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, role, company_id, status, created_at, updated_at)
         VALUES (:name, :email, :password_hash, :role, :company_id, :status, :now, :now)'
    );
    $stmt->execute($user + ['now' => '2026-01-01 00:00:00']);
    $user['id'] = (int) $pdo->lastInsertId();

    return $user;
}

function insertCompany(\PDO $pdo, string $name = 'テスト商事'): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO companies (name, contact_email, created_at, updated_at)
         VALUES (:name, :email, :now, :now)'
    );
    $stmt->execute(['name' => $name, 'email' => 'company@example.com', 'now' => '2026-01-01 00:00:00']);

    return (int) $pdo->lastInsertId();
}

function insertProject(\PDO $pdo, int $companyId, array $overrides = []): array
{
    $project = array_merge([
        'title' => 'テスト案件',
        'description' => 'テスト用の案件です',
        'hourly_rate' => 3000,
        'capacity' => 2,
        'deadline' => '2026-07-10',
        'work_start_on' => '2026-07-20',
        'is_remote' => 1,
        'status' => 'open',
    ], $overrides);

    $stmt = $pdo->prepare(
        'INSERT INTO projects (company_id, title, description, hourly_rate, capacity,
                               deadline, work_start_on, is_remote, status, created_at, updated_at)
         VALUES (:company_id, :title, :description, :hourly_rate, :capacity,
                 :deadline, :work_start_on, :is_remote, :status, :now, :now)'
    );
    $stmt->execute($project + ['company_id' => $companyId, 'now' => '2026-01-01 00:00:00']);
    $project['id'] = (int) $pdo->lastInsertId();
    $project['company_id'] = $companyId;

    return $project;
}

function insertApplication(\PDO $pdo, int $projectId, int $engineerId, array $overrides = []): array
{
    $application = array_merge([
        'message' => '',
        'status' => 'applied',
        'applied_at' => '2026-07-01 09:00:00',
        'decided_at' => null,
    ], $overrides);

    $stmt = $pdo->prepare(
        'INSERT INTO applications (project_id, engineer_id, message, status, applied_at, decided_at, created_at, updated_at)
         VALUES (:project_id, :engineer_id, :message, :status, :applied_at, :decided_at, :now, :now)'
    );
    $stmt->execute($application + [
        'project_id' => $projectId,
        'engineer_id' => $engineerId,
        'now' => '2026-07-01 09:00:00',
    ]);
    $application['id'] = (int) $pdo->lastInsertId();

    return $application;
}

// F-02 案件登録の正常入力(ProjectValidator 系テストの共通 fixture)
function validProjectInput(): array
{
    return [
        'title' => 'ECサイト改修',
        'description' => '検索機能の改善です',
        'hourly_rate' => '3000',
        'capacity' => '2',
        'deadline' => '2026-07-10',
        'work_start_on' => '2026-07-20',
        'is_remote' => '1',
    ];
}
