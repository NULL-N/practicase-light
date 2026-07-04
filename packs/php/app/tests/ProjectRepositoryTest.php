<?php

declare(strict_types=1);

use App\Repository\ProjectRepository;

// F-03: 検索の母集合・条件・並び順。today は '2026-07-01' 固定で渡す
const SEARCH_TODAY = '2026-07-01';

test('検索母集合は open かつ締切が本日以降のみ(締切当日は含む=G-2)', function (): void {
    $pdo = freshDatabase();
    $companyId = insertCompany($pdo);
    insertProject($pdo, $companyId, ['title' => '締切前', 'deadline' => '2026-07-02']);
    insertProject($pdo, $companyId, ['title' => '締切当日', 'deadline' => '2026-07-01']);
    insertProject($pdo, $companyId, ['title' => '締切超過', 'deadline' => '2026-06-30']);
    insertProject($pdo, $companyId, ['title' => '掲載終了', 'deadline' => '2026-07-10', 'status' => 'closed']);
    insertProject($pdo, $companyId, ['title' => '下書き', 'deadline' => '2026-07-10', 'status' => 'draft']);

    $titles = array_column((new ProjectRepository())->searchOpen('', false, SEARCH_TODAY), 'title');
    sort($titles);
    assertSame(['締切前', '締切当日'], array_values(array_intersect(['締切前', '締切当日'], $titles)), '含まれるべき2件');
    assertSame(2, count($titles), '締切超過・closed・draft は出ない');
});

// キーワードの title/description 部分一致テストは tests/checks/T-013.php へ移動(T-013 の合格条件になったため)

test('LIKE 特殊文字はリテラル扱い(% で全件マッチしない)', function (): void {
    $pdo = freshDatabase();
    $companyId = insertCompany($pdo);
    insertProject($pdo, $companyId, ['title' => '進捗100%管理ツール']);
    insertProject($pdo, $companyId, ['title' => '普通の案件']);

    $result = (new ProjectRepository())->searchOpen('100%管理', false, SEARCH_TODAY);
    assertSame(1, count($result), '% をリテラルとして検索できる');
    assertSame('進捗100%管理ツール', $result[0]['title']);
});


test('並びは締切が近い順、同日は id 降順。上限50件(F-03)', function (): void {
    $pdo = freshDatabase();
    $companyId = insertCompany($pdo);
    insertProject($pdo, $companyId, ['title' => '遠い締切', 'deadline' => '2026-08-01']);
    $first = insertProject($pdo, $companyId, ['title' => '近い締切・先に登録', 'deadline' => '2026-07-05']);
    insertProject($pdo, $companyId, ['title' => '近い締切・後に登録', 'deadline' => '2026-07-05']);
    for ($i = 0; $i < 50; $i++) {
        insertProject($pdo, $companyId, ['title' => "埋め草{$i}", 'deadline' => '2026-09-01']);
    }

    $result = (new ProjectRepository())->searchOpen('', false, SEARCH_TODAY);
    assertSame(50, count($result), '最大50件');
    assertSame('近い締切・後に登録', $result[0]['title'], '同日は id 降順');
    assertSame('近い締切・先に登録', $result[1]['title']);
    assertSame('遠い締切', $result[2]['title']);
});

test('admin 用 searchAll は全 status を返す(F-03)', function (): void {
    $pdo = freshDatabase();
    $companyId = insertCompany($pdo);
    insertProject($pdo, $companyId, ['status' => 'open', 'deadline' => '2020-01-01']);
    insertProject($pdo, $companyId, ['status' => 'closed']);
    insertProject($pdo, $companyId, ['status' => 'draft']);

    assertSame(3, count((new ProjectRepository())->searchAll('', false)), '締切超過・closed・draft も出る');
});

test('listByCompany は自社のみ・応募数と承認数付き(S-11)', function (): void {
    $pdo = freshDatabase();
    $mine = insertCompany($pdo, '自社');
    $other = insertCompany($pdo, '他社');
    $project = insertProject($pdo, $mine);
    insertProject($pdo, $other);
    $engineer1 = insertUser($pdo);
    $engineer2 = insertUser($pdo);
    insertApplication($pdo, $project['id'], $engineer1['id'], ['status' => 'applied']);
    insertApplication($pdo, $project['id'], $engineer2['id'], ['status' => 'accepted']);

    $list = (new ProjectRepository())->listByCompany($mine);
    assertSame(1, count($list), '他社案件は含まない');
    assertSame(2, (int) $list[0]['application_count']);
    assertSame(1, (int) $list[0]['accepted_count']);
});
