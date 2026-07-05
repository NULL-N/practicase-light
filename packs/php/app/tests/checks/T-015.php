<?php

declare(strict_types=1);

require_once __DIR__ . '/../support/SqlSubmission.php';

test('企業ごとの open 案件数を集計できる', function (): void {
    $pdo = freshDatabase();
    $mokuren = insertCompany($pdo, '株式会社モクレン商事');
    $aoba = insertCompany($pdo, '株式会社アオバ計画');
    $zero = insertCompany($pdo, '株式会社ゼロ案件');

    insertProject($pdo, $mokuren, ['title' => '検索改善', 'status' => 'open']);
    insertProject($pdo, $mokuren, ['title' => 'API整備', 'status' => 'open']);
    insertProject($pdo, $mokuren, ['title' => '完了済み', 'status' => 'closed']);
    insertProject($pdo, $aoba, ['title' => '予約フォーム', 'status' => 'open']);
    insertProject($pdo, $zero, ['title' => '完了済みだけ', 'status' => 'closed']);

    $rows = fetchSubmittedRows('T-015', $pdo, ['open_project_count']);

    assertSame([
        ['company_name' => '株式会社モクレン商事', 'open_project_count' => 2],
        ['company_name' => '株式会社アオバ計画', 'open_project_count' => 1],
    ], $rows, 'open 案件を持つ企業だけを、件数の多い順に返してください');
});
