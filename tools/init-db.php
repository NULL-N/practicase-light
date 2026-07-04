<?php

declare(strict_types=1);

// DB 初期化ツール。リポジトリルートで実行する:
//   docker compose exec app php tools/init-db.php
// 既存の DB ファイルは削除して作り直す(学習中は何度でもやり直してよい)

if (PHP_SAPI !== 'cli') {
    exit('CLI 専用です');
}

require __DIR__ . '/../packs/php/app/src/bootstrap.php';

use App\Database;
use App\Support\Clock;

const SEED_PASSWORD = 'password123'; // 全シードユーザー共通(docs/03_参考資料/world.md 利用者名簿)

$config = require __DIR__ . '/../packs/php/app/src/config.php';
$dbPath = $config['db_path'];
$appDir = dirname(__DIR__) . '/packs/php/app';

if (is_file($dbPath)) {
    unlink($dbPath);
    echo "既存の DB を削除しました: {$dbPath}\n";
}

$pdo = Database::connect('sqlite:' . $dbPath);
Database::useConnection($pdo);

$pdo->exec((string) file_get_contents($appDir . '/database/schema.sql'));
echo "スキーマを適用しました(companies / users / projects / applications)\n";

$seeds = require $appDir . '/database/seeds.php';
$now = Clock::now()->format('Y-m-d H:i:s');
$passwordHash = password_hash(SEED_PASSWORD, PASSWORD_DEFAULT); // SEC-3

$companyIds = [];
$stmt = $pdo->prepare(
    'INSERT INTO companies (name, contact_email, created_at, updated_at)
     VALUES (:name, :contact_email, :now, :now)'
);
foreach ($seeds['companies'] as $i => $company) {
    $stmt->execute(['name' => $company['name'], 'contact_email' => $company['contact_email'], 'now' => $now]);
    $companyIds[$i + 1] = (int) $pdo->lastInsertId();
}

$userIdsByEmail = [];
$stmt = $pdo->prepare(
    'INSERT INTO users (name, email, password_hash, role, company_id, status, created_at, updated_at)
     VALUES (:name, :email, :password_hash, :role, :company_id, \'active\', :now, :now)'
);
foreach ($seeds['users'] as $user) {
    $stmt->execute([
        'name' => $user['name'],
        'email' => $user['email'],
        'password_hash' => $passwordHash,
        'role' => $user['role'],
        'company_id' => $user['company'] === null ? null : $companyIds[$user['company']],
        'now' => $now,
    ]);
    $userIdsByEmail[$user['email']] = (int) $pdo->lastInsertId();
}

$projectIds = [];
$stmt = $pdo->prepare(
    'INSERT INTO projects (company_id, title, description, hourly_rate, capacity,
                           deadline, work_start_on, is_remote, status, created_at, updated_at)
     VALUES (:company_id, :title, :description, :hourly_rate, :capacity,
             :deadline, :work_start_on, :is_remote, :status, :now, :now)'
);
foreach ($seeds['projects'] as $i => $project) {
    $stmt->execute([
        'company_id' => $companyIds[$project['company']],
        'title' => $project['title'],
        'description' => $project['description'],
        'hourly_rate' => $project['hourly_rate'],
        'capacity' => $project['capacity'],
        'deadline' => $project['deadline'],
        'work_start_on' => $project['work_start_on'],
        'is_remote' => $project['is_remote'],
        'status' => $project['status'],
        'now' => $now,
    ]);
    $projectIds[$i + 1] = (int) $pdo->lastInsertId();
}

$stmt = $pdo->prepare(
    'INSERT INTO applications (project_id, engineer_id, message, status, applied_at, decided_at, created_at, updated_at)
     VALUES (:project_id, :engineer_id, :message, :status, :applied_at, :decided_at, :now, :now)'
);
foreach ($seeds['applications'] as $application) {
    $appliedAt = Clock::now()->modify('-' . $application['applied_days_ago'] . ' days')->format('Y-m-d H:i:s');
    $decidedAt = $application['decided_days_ago'] === null
        ? null
        : Clock::now()->modify('-' . $application['decided_days_ago'] . ' days')->format('Y-m-d H:i:s');
    $stmt->execute([
        'project_id' => $projectIds[$application['project']],
        'engineer_id' => $userIdsByEmail[$application['engineer']],
        'message' => $application['message'],
        'status' => $application['status'],
        'applied_at' => $appliedAt,
        'decided_at' => $decidedAt,
        'now' => $now,
    ]);
}

printf(
    "初期データを投入しました(企業 %d / ユーザー %d / 案件 %d / 応募 %d)\n",
    count($seeds['companies']),
    count($seeds['users']),
    count($seeds['projects']),
    count($seeds['applications'])
);
echo "ログイン: docs/03_参考資料/world.md の利用者名簿を参照(パスワードは全員 " . SEED_PASSWORD . ")\n";
$port = getenv('PRACTICASE_PORT') ?: '8180';
echo "URL: http://localhost:{$port}/login.php\n";
