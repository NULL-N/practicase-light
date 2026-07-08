<?php

declare(strict_types=1);

// D-024 の合格条件: 型選択メモの実在、部署名/氏名・メール/状態/部署参照それぞれに
// ふさわしい型の言及、各カラムに「理由」が添えられていること。
// NULL・UNIQUE・PK/FKは検査しない(D-025以降の仕事) — 控えめ設計

function d024Note(): string
{
    $content = '';
    foreach (glob('reports/D-024*.md') ?: [] as $path) {
        $content .= (string) file_get_contents($path);
    }

    return $content;
}

/**
 * 「### テーブル: <名前>」ごとに [name, body] の配列へ分割する(D-023と同じ方式)。
 * @return array<int, array{name: string, body: string}>
 */
function d024TableBlocks(string $note): array
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
function d024FindByName(array $blocks, array $nameKeywords): ?array
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

function d024HasStringType(string $text): bool
{
    return preg_match('/文字列|TEXT|VARCHAR|CHAR|string/iu', $text) === 1;
}

function d024HasNumericType(string $text): bool
{
    return preg_match('/数値|整数|INTEGER|INT|number/iu', $text) === 1;
}

function d024HasDateType(string $text): bool
{
    return preg_match('/日時|日付|DATE|TIME|date/iu', $text) === 1;
}

function d024HasEnumOrString(string $text): bool
{
    return d024HasStringType($text) || preg_match('/ENUM|enum|決まった値|決まった種類/u', $text) === 1;
}

function d024ReasonCount(string $body): int
{
    // 全角ダッシュ「—」または「理由:」のどちらの形式も許容する
    return preg_match_all('/[—―]|理由\s*[:\x{FF1A}]/u', $body);
}

test('D-024: 型選択メモ(reports/D-024*.md)が存在する', function (): void {
    assertTrue(count(glob('reports/D-024*.md') ?: []) >= 1, 'reports/D-024_column_types_note.md を作成してください(support/spec.md の型で)');
});

test('D-024: 部署にあたるテーブル・メンバーにあたるテーブルの両方が見つかる', function (): void {
    $blocks = d024TableBlocks(d024Note());
    $dept = d024FindByName($blocks, ['部署', 'department', 'dept']);
    $member = d024FindByName($blocks, ['メンバー', 'member', '社員', 'staff']);
    assertTrue($dept !== null, '部署にあたるテーブルが見つかりません。D-022・D-023で決めたテーブル名をそのまま使ってください');
    assertTrue($member !== null, 'メンバーにあたるテーブルが見つかりません。D-022・D-023で決めたテーブル名をそのまま使ってください');
});

test('D-024: 部署名が文字列型として扱われている', function (): void {
    $blocks = d024TableBlocks(d024Note());
    $dept = d024FindByName($blocks, ['部署', 'department', 'dept']);
    assertTrue($dept !== null, '部署にあたるテーブルが見つかりません');
    if ($dept !== null) {
        assertTrue(d024HasStringType($dept['body']), '部署テーブルの中に、文字列型(TEXT・VARCHAR等)への言及が見当たりません。部署名は文字列型として扱ってください');
    }
});

test('D-024: 氏名・メールが文字列型として扱われている', function (): void {
    $blocks = d024TableBlocks(d024Note());
    $member = d024FindByName($blocks, ['メンバー', 'member', '社員', 'staff']);
    assertTrue($member !== null, 'メンバーにあたるテーブルが見つかりません');
    if ($member !== null) {
        assertTrue(d024HasStringType($member['body']), 'メンバーテーブルの中に、文字列型(TEXT・VARCHAR等)への言及が見当たりません。氏名・メールは文字列型として扱ってください');
    }
});

test('D-024: 状態が文字列またはENUM相当として扱われている', function (): void {
    $blocks = d024TableBlocks(d024Note());
    $member = d024FindByName($blocks, ['メンバー', 'member', '社員', 'staff']);
    assertTrue($member !== null, 'メンバーにあたるテーブルが見つかりません');
    if ($member !== null) {
        $statusLine = '';
        foreach (preg_split('/\r?\n/u', $member['body']) ?: [] as $line) {
            if (preg_match('/状態|ステータス|status/iu', $line) === 1) {
                $statusLine .= $line . "\n";
            }
        }
        assertTrue($statusLine !== '', '状態(在籍状況等)にあたるカラムの記述が見当たりません');
        if ($statusLine !== '') {
            assertTrue(d024HasEnumOrString($statusLine), '状態カラムが文字列型・ENUM相当のいずれとしても扱われていません。「在籍中/退職済み」のような決まった値を表す型にしてください');
        }
    }
});

test('D-024: 部署への参照が数値型として扱われている', function (): void {
    $blocks = d024TableBlocks(d024Note());
    $member = d024FindByName($blocks, ['メンバー', 'member', '社員', 'staff']);
    assertTrue($member !== null, 'メンバーにあたるテーブルが見つかりません');
    if ($member !== null) {
        $refLine = '';
        foreach (preg_split('/\r?\n/u', $member['body']) ?: [] as $line) {
            if (preg_match('/部署|department/iu', $line) === 1) {
                $refLine .= $line . "\n";
            }
        }
        assertTrue($refLine !== '', '部署への参照にあたるカラムの記述が見当たりません');
        if ($refLine !== '') {
            assertTrue(d024HasNumericType($refLine), '部署への参照が数値型として扱われていません。部署テーブルのidを指し示す数値型にしてください');
        }
    }
});

test('D-024: 作成日時・更新日時のようなカラムを入れているなら日時型になっている', function (): void {
    $note = d024Note();
    $timestampLine = '';
    foreach (preg_split('/\r?\n/u', $note) ?: [] as $line) {
        if (preg_match('/作成日時|更新日時|created_at|updated_at|joined_at|入社日/iu', $line) === 1) {
            $timestampLine .= $line . "\n";
        }
    }
    if ($timestampLine === '') {
        return; // 日時系カラムを入れていないなら、この課題では検査対象外(必須ではない)
    }
    assertTrue(d024HasDateType($timestampLine), '日付・日時に関するカラムが見つかりましたが、日時型(DATE・DATETIME等)への言及が見当たりません');
});

test('D-024: 各テーブルに「理由」が複数(2つ以上)添えられている', function (): void {
    $blocks = d024TableBlocks(d024Note());
    foreach ($blocks as $block) {
        $count = d024ReasonCount($block['body']);
        assertTrue($count >= 2, "テーブル「{$block['name']}」に、型を選んだ理由の記述が2つ未満です(現在{$count}件)。「— 理由」または「理由: 」の形で、カラムごとに一言添えてください");
    }
});

test('D-024: スコープ外の情報(給与・勤怠・評価・個人番号・住所・健康情報)に触れていない', function (): void {
    $note = d024Note();
    $forbidden = ['給与', '勤怠', '評価', '個人番号', 'マイナンバー', '住所', '健康'];
    foreach ($forbidden as $word) {
        assertTrue(!str_contains($note, $word), "「{$word}」への言及が見つかりました。今回のメンバー管理ではスコープ外の情報です(ticket.md の「スコープ外」を確認してください)");
    }
});
