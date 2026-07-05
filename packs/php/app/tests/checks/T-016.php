<?php

declare(strict_types=1);

require_once __DIR__ . '/../support/SqlSubmission.php';

test('案件ごとの応募数と承認済み応募数を集計できる', function (): void {
    $pdo = freshDatabase();
    $companyId = insertCompany($pdo);
    $engineer1 = insertUser($pdo, ['email' => 'sql1@example.com']);
    $engineer2 = insertUser($pdo, ['email' => 'sql2@example.com']);
    $engineer3 = insertUser($pdo, ['email' => 'sql3@example.com']);

    $projectA = insertProject($pdo, $companyId, ['title' => 'A 案件', 'status' => 'open']);
    $projectB = insertProject($pdo, $companyId, ['title' => 'B 案件', 'status' => 'open']);
    insertProject($pdo, $companyId, ['title' => 'C 案件', 'status' => 'open']);
    $closed = insertProject($pdo, $companyId, ['title' => 'D closed', 'status' => 'closed']);

    insertApplication($pdo, $projectA['id'], $engineer1['id'], ['status' => 'accepted']);
    insertApplication($pdo, $projectA['id'], $engineer2['id'], ['status' => 'applied']);
    insertApplication($pdo, $projectA['id'], $engineer3['id'], ['status' => 'rejected']);
    insertApplication($pdo, $projectB['id'], $engineer1['id'], ['status' => 'accepted']);
    insertApplication($pdo, $closed['id'], $engineer1['id'], ['status' => 'accepted']);

    $rows = fetchSubmittedRows('T-016', $pdo, ['application_count', 'accepted_count']);

    assertSame([
        ['project_title' => 'A 案件', 'application_count' => 3, 'accepted_count' => 1],
        ['project_title' => 'B 案件', 'application_count' => 1, 'accepted_count' => 1],
        ['project_title' => 'C 案件', 'application_count' => 0, 'accepted_count' => 0],
    ], $rows, 'open 案件をすべて含め、応募が0件の案件も落とさないでください');
});
