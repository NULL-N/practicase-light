<?php

declare(strict_types=1);

require_once __DIR__ . '/../support/SqlSubmission.php';

test('open の案件だけを締切が近い順に取得できる', function (): void {
    $pdo = freshDatabase();
    $companyId = insertCompany($pdo);
    insertProject($pdo, $companyId, ['title' => '締切が近い open', 'deadline' => '2026-07-05', 'hourly_rate' => 3000, 'status' => 'open']);
    insertProject($pdo, $companyId, ['title' => '締切が遠い open', 'deadline' => '2026-07-20', 'hourly_rate' => 4500, 'status' => 'open']);
    insertProject($pdo, $companyId, ['title' => 'closed は出さない', 'deadline' => '2026-07-01', 'hourly_rate' => 5000, 'status' => 'closed']);

    $rows = fetchSubmittedRows('T-014', $pdo, ['hourly_rate']);

    assertSame([
        ['title' => '締切が近い open', 'deadline' => '2026-07-05', 'hourly_rate' => 3000],
        ['title' => '締切が遠い open', 'deadline' => '2026-07-20', 'hourly_rate' => 4500],
    ], $rows, 'title / deadline / hourly_rate の3列を、deadline ASC で返してください');
});
