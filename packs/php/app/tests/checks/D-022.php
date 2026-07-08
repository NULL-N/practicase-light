<?php

declare(strict_types=1);

// D-022 の合格条件: テーブル洗い出しメモの実在、テーブルが2つ以上、それぞれに役割説明があること。
// 「テーブル名の正解」は判定しない(正解は1つではない) — support/rubric.md と debrief で見る(控えめ設計)

function d022Note(): string
{
    $content = '';
    foreach (glob('reports/D-022*.md') ?: [] as $path) {
        $content .= (string) file_get_contents($path);
    }

    return $content;
}

function d022TableBlockCount(string $note): int
{
    return preg_match_all('/^###\s*テーブル\s*[:\x{FF1A}]/mu', $note);
}

function d022RoleLineCount(string $note): int
{
    return preg_match_all('/役割\s*[:\x{FF1A}]/mu', $note);
}

test('D-022: テーブル洗い出しメモ(reports/D-022*.md)が存在する', function (): void {
    assertTrue(count(glob('reports/D-022*.md') ?: []) >= 1, 'reports/D-022_tables_note.md を作成してください(support/spec.md の型で)');
});

test('D-022: テーブルが2つ以上、「### テーブル: 」の形で挙がっている', function (): void {
    $count = d022TableBlockCount(d022Note());
    assertTrue($count >= 2, "テーブルが2つ以上見つかりません(現在{$count}件)。support/spec.md の「### テーブル: 」の型で、意味のある単位に分けて挙げてください");
});

test('D-022: 各テーブルに「役割: 」の一行説明がある', function (): void {
    $note = d022Note();
    $tables = d022TableBlockCount($note);
    $roles = d022RoleLineCount($note);
    assertTrue($roles >= $tables, "「役割: 」の説明が、テーブルの数({$tables}件)より少ないです({$roles}件)。各テーブルに一行の役割説明を添えてください");
});

test('D-022: スコープ外の情報(給与・勤怠・評価・個人番号・住所・健康情報)に触れていない', function (): void {
    $note = d022Note();
    $forbidden = ['給与', '勤怠', '評価', '個人番号', 'マイナンバー', '住所', '健康'];
    foreach ($forbidden as $word) {
        assertTrue(!str_contains($note, $word), "「{$word}」への言及が見つかりました。今回のメンバー管理ではスコープ外の情報です(ticket.md の「スコープ外」を確認してください)");
    }
});
