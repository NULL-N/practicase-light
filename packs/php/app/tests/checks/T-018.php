<?php

declare(strict_types=1);

require_once __DIR__ . '/../support/SqlSubmission.php';

test('田淵さん(tabuchi@example.com)のアカウント状態を取得できる', function (): void {
    $pdo = freshDatabase();
    $mokuren = insertCompany($pdo, '株式会社モクレン商事');
    $aoba = insertCompany($pdo, '株式会社アオバ計画');

    // 対象: 停止中(ログイン不可の原因)
    $tabuchi = insertUser($pdo, [
        'name' => '田淵 亮', 'email' => 'tabuchi@example.com',
        'role' => 'client', 'company_id' => $mokuren, 'status' => 'suspended',
    ]);

    // 決して混ざってはいけない他ユーザー(別会社のclient・別ロール)
    insertUser($pdo, [
        'name' => '志村 恵', 'email' => 'shimura@example.com',
        'role' => 'client', 'company_id' => $aoba, 'status' => 'active',
    ]);
    insertUser($pdo, [
        'name' => '桐生 蒼', 'email' => 'kiryu@example.com',
        'role' => 'engineer', 'status' => 'active',
    ]);

    $rows = fetchSubmittedRows('T-018', $pdo, ['id']);

    assertSame([
        [
            'id' => (int) $tabuchi['id'],
            'email' => 'tabuchi@example.com',
            'role' => 'client',
            'status' => 'suspended',
        ],
    ], $rows, 'id / email / role / status の4列を、田淵さん(tabuchi@example.com)の1行だけ返してください'
        . '(他のユーザーを混ぜないでください)');
});

test('reports/T-018 の報告書に必須項目が書かれている', function (): void {
    $reportPath = null;
    foreach (glob('reports/*.md') ?: [] as $path) {
        if (stripos(basename($path), 'T-018') === 0) {
            $reportPath = $path;
            break;
        }
    }
    assertNotNull($reportPath, 'reports/ に T-018 で始まる報告書(例: reports/T-018_login_investigation.md)を作成してください');

    $content = (string) file_get_contents((string) $reportPath);
    foreach (['事象', '確認したこと', '原因', '影響範囲', '対応方針', '再発防止'] as $keyword) {
        assertTrue(str_contains($content, $keyword), "報告書に「{$keyword}」の記載が見当たりません");
    }
    foreach (['users', 'AuthService'] as $keyword) {
        assertTrue(str_contains($content, $keyword), "報告書に「{$keyword}」の記載が見当たりません");
    }
    assertTrue(
        str_contains($content, 'suspended') || str_contains($content, '停止'),
        '報告書に原因(suspended、または「停止」)の記載が見当たりません'
    );
});
