<?php

declare(strict_types=1);

use App\Repository\ProjectRepository;

// T-013 の合格条件(F-03 のキーワード検索)。修正が正しければ通る。
// 1本目は共通テスト(ProjectRepositoryTest)から移動してきた部分一致テスト。

test('キーワードは title または description に部分一致(F-03)', function (): void {
    $pdo = freshDatabase();
    $companyId = insertCompany($pdo);
    insertProject($pdo, $companyId, ['title' => 'EC検索改善', 'description' => 'あああ']);
    insertProject($pdo, $companyId, ['title' => 'いいい', 'description' => '検索まわりの調査']);
    insertProject($pdo, $companyId, ['title' => '無関係な案件', 'description' => 'ううう']);

    $result = (new ProjectRepository())->searchOpen('検索', false, '2026-07-01');
    assertSame(2, count($result), 'title 一致と description 一致の両方が出る(F-03 の「または」)');
});

test('案件内容(description)にだけ含まれる語でも見つかる(問い合わせの症状の再現)', function (): void {
    $pdo = freshDatabase();
    $companyId = insertCompany($pdo);
    insertProject($pdo, $companyId, [
        'title' => '社内勤怠ツールの不具合修正',
        'description' => '月末締め処理でエラーが出ることがあります。',
    ]);

    $result = (new ProjectRepository())->searchOpen('月末', false, '2026-07-01');
    assertSame(1, count($result), '説明文にだけ含まれる語での検索が 0件になってはいけない');
});
