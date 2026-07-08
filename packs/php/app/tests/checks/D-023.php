<?php

declare(strict_types=1);

// D-023 の合格条件: カラム洗い出しメモの実在、テーブルごとのカラム候補の数、
// 部署テーブル/メンバーテーブルそれぞれに期待される情報の種類が挙がっていること。
// 型・NULL・UNIQUE・PK/FKは検査しない(D-024以降の仕事) — 控えめ設計

function d023Note(): string
{
    $content = '';
    foreach (glob('reports/D-023*.md') ?: [] as $path) {
        $content .= (string) file_get_contents($path);
    }

    return $content;
}

/**
 * 「### テーブル: <名前>」ごとに [name, body] の配列へ分割する。
 * @return array<int, array{name: string, body: string}>
 */
function d023TableBlocks(string $note): array
{
    $parts = preg_split('/^(###\s*テーブル\s*[:\x{FF1A}].*)$/mu', $note, -1, PREG_SPLIT_DELIM_CAPTURE);
    if ($parts === false) {
        return [];
    }
    $blocks = [];
    $count = count($parts);
    for ($i = 1; $i + 1 < $count; $i += 2) {
        $blocks[] = ['name' => $parts[$i], 'body' => $parts[$i + 1]];
    }

    return $blocks;
}

/** @param array<int, array{name: string, body: string}> $blocks */
function d023FindByName(array $blocks, array $nameKeywords): ?array
{
    foreach ($blocks as $block) {
        foreach ($nameKeywords as $keyword) {
            if (stripos($block['name'], $keyword) !== false) {
                return $block;
            }
        }
    }

    return null;
}

test('D-023: カラム洗い出しメモ(reports/D-023*.md)が存在する', function (): void {
    assertTrue(count(glob('reports/D-023*.md') ?: []) >= 1, 'reports/D-023_columns_note.md を作成してください(support/spec.md の型で)');
});

test('D-023: テーブルが2つ以上、「### テーブル: 」の形で挙がっている', function (): void {
    $count = count(d023TableBlocks(d023Note()));
    assertTrue($count >= 2, "テーブルが2つ以上見つかりません(現在{$count}件)。D-022で決めたテーブル名をそのまま使ってください");
});

test('D-023: 各テーブルにカラム候補が2つ以上、箇条書きで挙がっている', function (): void {
    $blocks = d023TableBlocks(d023Note());
    foreach ($blocks as $block) {
        $bulletCount = preg_match_all('/^[-*]\s*\S/mu', $block['body']);
        assertTrue($bulletCount >= 2, "テーブル「{$block['name']}」のカラム候補が2つ未満です(現在{$bulletCount}件)。「- 」の箇条書きで2つ以上挙げてください");
    }
});

test('D-023: 部署にあたるテーブルに、部署名が分かるカラムがある', function (): void {
    $blocks = d023TableBlocks(d023Note());
    $dept = d023FindByName($blocks, ['部署', 'department', 'dept']);
    assertTrue($dept !== null, '部署にあたるテーブルが見つかりません。D-022で決めた部署テーブルの名前をそのまま使ってください');
    if ($dept !== null) {
        $hasNameColumn = preg_match('/名前|名称|部署名|name/iu', $dept['body']) === 1;
        assertTrue($hasNameColumn, '部署テーブルに、部署の名前が分かるカラム(例: name / 部署名)が見当たりません');
    }
});

test('D-023: メンバーにあたるテーブルに、氏名・メール・状態・部署参照が分かるカラムがある', function (): void {
    $blocks = d023TableBlocks(d023Note());
    $member = d023FindByName($blocks, ['メンバー', 'member', '社員', 'staff']);
    assertTrue($member !== null, 'メンバーにあたるテーブルが見つかりません。D-022で決めたメンバーテーブルの名前をそのまま使ってください');
    if ($member !== null) {
        $body = $member['body'];
        assertTrue(preg_match('/氏名|名前|name/iu', $body) === 1, 'メンバーテーブルに、氏名・名前が分かるカラムが見当たりません');
        assertTrue(preg_match('/メール|email/iu', $body) === 1, 'メンバーテーブルに、メールアドレスが分かるカラムが見当たりません');
        assertTrue(preg_match('/状態|ステータス|status/iu', $body) === 1, 'メンバーテーブルに、在籍状態のようなカラムが見当たりません(例: status)');
        assertTrue(preg_match('/部署/u', $body) === 1, 'メンバーテーブルに、所属部署への参照が分かるカラムが見当たりません');
    }
});

test('D-023: スコープ外の情報(給与・勤怠・評価・個人番号・住所・健康情報)に触れていない', function (): void {
    $note = d023Note();
    $forbidden = ['給与', '勤怠', '評価', '個人番号', 'マイナンバー', '住所', '健康'];
    foreach ($forbidden as $word) {
        assertTrue(!str_contains($note, $word), "「{$word}」への言及が見つかりました。今回のメンバー管理ではスコープ外の情報です(ticket.md の「スコープ外」を確認してください)");
    }
});
