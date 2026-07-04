<?php

declare(strict_types=1);

use App\Support\TagSummary;

// tutorial-2 の合格条件 = この部品の仕様書。
// 4つのテストを読めば「何を入れると・何が返るべきか」が全部わかる(テストを仕様として読む練習)

test('タグごとに案件数を数える(基本形)', function (): void {
    $projects = [
        ['title' => 'ECサイトの商品検索改善', 'tags' => ['PHP', 'SQL']],
        ['title' => '在庫管理APIの開発', 'tags' => ['PHP']],
    ];
    assertSame(['PHP' => 2, 'SQL' => 1], TagSummary::countByTag($projects));
});

test('同じ案件に同じタグが複数あれば、その数だけ数える', function (): void {
    $projects = [
        ['title' => '社内ツールの改修', 'tags' => ['JavaScript', 'JavaScript']],
    ];
    assertSame(['JavaScript' => 2], TagSummary::countByTag($projects));
});

test('案件が1件も無ければ、空の配列を返す', function (): void {
    assertSame([], TagSummary::countByTag([]));
});

test('タグが空の案件は、集計に影響しない', function (): void {
    $projects = [
        ['title' => 'タグ未設定の案件', 'tags' => []],
        ['title' => 'レガシーシステムの調査', 'tags' => ['調査']],
    ];
    assertSame(['調査' => 1], TagSummary::countByTag($projects));
});
