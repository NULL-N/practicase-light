<?php

declare(strict_types=1);

// D-012 の合格条件: database.md に tags / project_tags のテーブル定義が存在し、
// PK・FK・NOT NULL・UNIQUE・created_at/updated_at という「欠けるとテーブルとして
// 欠陥になる必須要素」が過不足なくそろっていること。書き方(Markdown 表 / SQL DDL)・
// FK の表記スタイル・UNIQUE の列順は自由。

const D012_TABLES = ['tags', 'project_tags'];

function d012Database(): string
{
    return (string) file_get_contents('docs/01_設計資料/database.md');
}

/**
 * 行に分割する。\R や m 修飾子の ^ は使わない —
 * 環境によっては PCRE が UTF-8 日本語のバイトを改行と誤認するため(実測)。
 */
function d012Lines(string $markdown): array
{
    return preg_split('/\r\n|\n|\r/', $markdown) ?: [];
}

/** 見出し行(## / ###)に $needle を含むセクションを返す */
function d012Section(string $markdown, string $needle): string
{
    $lines = d012Lines($markdown);
    $count = count($lines);
    for ($i = 0; $i < $count; $i++) {
        if (preg_match('/^(#{2,3}) /', $lines[$i], $m) !== 1 || stripos($lines[$i], $needle) === false) {
            continue;
        }
        $level = strlen($m[1]);
        $section = [$lines[$i]];
        for ($j = $i + 1; $j < $count; $j++) {
            if (preg_match('/^(#{1,6}) /', $lines[$j], $mm) === 1 && strlen($mm[1]) <= $level) {
                break;
            }
            $section[] = $lines[$j];
        }

        return implode("\n", $section);
    }

    return '';
}

function d012ContainsAny(string $haystack, array $needles): bool
{
    foreach ($needles as $needle) {
        if (str_contains($haystack, $needle)) {
            return true;
        }
    }

    return false;
}

function d012AllSections(string $markdown): string
{
    $combined = '';
    foreach (D012_TABLES as $table) {
        $combined .= d012Section($markdown, $table) . "\n";
    }

    return $combined;
}

test('database.md: tags / project_tags の節がある', function (): void {
    $db = d012Database();
    foreach (D012_TABLES as $table) {
        assertTrue(
            d012Section($db, $table) !== '',
            "database.md に「{$table}」の見出しが見つかりません — 新しいテーブルの正はdatabase.mdに書きます(applicationsが手本)"
        );
    }
});

test('project_tags: カラム(project_id / tag_id)が書かれている', function (): void {
    $section = d012Section(d012Database(), 'project_tags');
    foreach (['project_id', 'tag_id'] as $column) {
        assertTrue(
            str_contains($section, $column),
            "project_tags の節に「{$column}」が見当たりません — 案件とタグを結びつける中間テーブルの核となるカラムです"
        );
    }
});

test('PRIMARY KEY がある(主キーの無いテーブルは実務で事故のもと)', function (): void {
    foreach (D012_TABLES as $table) {
        $section = d012Section(d012Database(), $table);
        assertTrue(
            d012ContainsAny($section, ['PRIMARY KEY', 'PK']),
            "{$table} の節にPRIMARY KEY(またはPK)がありません — 主キーが無いと、行を一意に特定して更新・削除する手段がなくなります(D-7)"
        );
    }
});

test('FOREIGN KEY(または REFERENCES)がある(他テーブルへの参照が追える)', function (): void {
    $section = d012Section(d012Database(), 'project_tags');
    assertTrue(
        d012ContainsAny($section, ['FOREIGN KEY', 'FK', 'REFERENCES']),
        'project_tags の節に外部キー(FOREIGN KEY / FK / REFERENCES のいずれか)が見当たりません — ' .
            '外部キーが無いと、存在しない案件IDやタグIDを指す不整合な行が作れてしまいます(D-5)'
    );
});

test('NOT NULL が使われている(必須カラムが明記されている)', function (): void {
    foreach (D012_TABLES as $table) {
        $section = d012Section(d012Database(), $table);
        assertTrue(
            str_contains($section, 'NOT NULL'),
            "{$table} の節にNOT NULLが見当たりません — 必須カラムを明記しないと、空のまま登録できてしまいます(D-4)"
        );
    }
});

test('created_at / updated_at がある(既存テーブル共通の型)', function (): void {
    foreach (D012_TABLES as $table) {
        $section = d012Section(d012Database(), $table);
        foreach (['created_at', 'updated_at'] as $column) {
            assertTrue(
                str_contains($section, $column),
                "{$table} の節に「{$column}」がありません — 既存テーブル全部に共通するカラムです。" .
                    '無いと、いつ作られた/更新された記録かが後から追えなくなります'
            );
        }
    }
});

test('二重付与を防ぐユニーク制約が書かれている', function (): void {
    // 複合ユニークの列順は問わない(project_id, tag_id / tag_id, project_id のどちらも同じ制約を表す)
    $db = d012Database();
    assertTrue(
        preg_match('/\(\s*project_id\s*,\s*tag_id\s*\)/u', $db) === 1
            || preg_match('/\(\s*tag_id\s*,\s*project_id\s*\)/u', $db) === 1,
        '同じ案件に同じタグを二重に付けられない仕組み(project_id と tag_id の複合ユニーク等)が見当たりません — ' .
            '無いと、同じタグが同じ案件に何度も登録できてしまい、一覧表示で重複が起きます'
    );
});

test('tags.name の重複を防ぐ UNIQUE がある', function (): void {
    $section = d012Section(d012Database(), 'tags');
    assertTrue(
        str_contains($section, 'UNIQUE'),
        'tags の節にUNIQUEが見当たりません — タグ名(name)が重複すると、同じ意味のタグが複数できて絞り込みが壊れます'
    );
});

test('ER 図(mermaid)にも project_tags が追加されている', function (): void {
    assertTrue(
        preg_match('/erDiagram[\s\S]*project_tags/u', d012Database()) === 1,
        'ER 図(2. 節の mermaid)にも tags / project_tags と関係線を追加してください(表と図の両方が正)'
    );
});
