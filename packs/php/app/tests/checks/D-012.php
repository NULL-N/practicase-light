<?php

declare(strict_types=1);

// D-012 の合格条件: tags / project_tags の定義が DB 設計書に存在すること。
// 型・制約の細部の妥当性は support/rubric.md で見る(控えめ設計)

function d012Database(): string
{
    return (string) file_get_contents('docs/01_設計資料/database.md');
}

test('D-012: database.md に tags と project_tags がある', function (): void {
    $db = d012Database();
    assertTrue(str_contains($db, 'project_tags'), 'docs/01_設計資料/database.md に project_tags(中間テーブル)が見つかりません');
    assertTrue(str_contains($db, 'tag_id'), 'project_tags のカラム定義(tag_id)が見つかりません');
});

test('D-012: 二重付与を防ぐユニーク制約が書かれている', function (): void {
    // 複合ユニークの列順は問わない(project_id, tag_id / tag_id, project_id のどちらも同じ制約を表す)
    $db = d012Database();
    assertTrue(
        preg_match('/\(\s*project_id\s*,\s*tag_id\s*\)/u', $db) === 1
            || preg_match('/\(\s*tag_id\s*,\s*project_id\s*\)/u', $db) === 1,
        '同じ案件に同じタグを二重に付けられない仕組み(project_id と tag_id の複合ユニーク等)を定義してください'
    );
});

test('D-012: ER 図(mermaid)にも project_tags が追加されている', function (): void {
    assertTrue(
        preg_match('/erDiagram[\s\S]*project_tags/u', d012Database()) === 1,
        'ER 図(2. 節の mermaid)にも tags / project_tags と関係線を追加してください(表と図の両方が正)'
    );
});
