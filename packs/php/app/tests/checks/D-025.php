<?php

declare(strict_types=1);

// D-025 の合格条件: NULL/DEFAULTメモの実在、必須カラム(部署名/氏名・メール/状態/部署参照)が
// NOT NULLであること、状態にDEFAULT(在籍中相当)があること、任意カラムを入れているなら
// NULL可の理由があること、日時カラムを入れているならDEFAULT(自動記録)があること。
// UNIQUE・PK/FKは検査しない(D-026以降の仕事) — 控えめ設計

function d025Note(): string
{
    $content = '';
    foreach (glob('reports/D-025*.md') ?: [] as $path) {
        $content .= (string) file_get_contents($path);
    }

    return $content;
}

/**
 * 「### テーブル: <名前>」ごとに [name, body] の配列へ分割する(D-023〜D-024と同じ方式)。
 * @return array<int, array{name: string, body: string}>
 */
function d025TableBlocks(string $note): array
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
function d025FindByName(array $blocks, array $nameKeywords): ?array
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

function d025LinesContaining(string $body, string $pattern): string
{
    $result = '';
    foreach (preg_split('/\r?\n/u', $body) ?: [] as $line) {
        if (preg_match($pattern, $line) === 1) {
            $result .= $line . "\n";
        }
    }

    return $result;
}

function d025HasNotNull(string $text): bool
{
    return preg_match('/NOT\s*NULL|必須|NULL不可/iu', $text) === 1;
}

function d025HasNullable(string $text): bool
{
    return preg_match('/NULL\s*可|NULL許容|NULLを許容|NULLABLE/iu', $text) === 1;
}

function d025HasDefault(string $text): bool
{
    return preg_match('/DEFAULT|初期値|既定値/iu', $text) === 1;
}

function d025HasActiveDefaultValue(string $text): bool
{
    return preg_match('/在籍中|active|有効/iu', $text) === 1;
}

function d025HasAutoTimestampValue(string $text): bool
{
    return preg_match('/CURRENT_TIMESTAMP|現在時刻|今この瞬間|登録された時点|自動(的)?に?記録|自動で記録/iu', $text) === 1;
}

function d025ReasonCount(string $body): int
{
    // 全角ダッシュ「—」または「理由:」のどちらの形式も許容する
    return preg_match_all('/[—―]|理由\s*[:\x{FF1A}]/u', $body);
}

test('D-025: NULL/DEFAULTメモ(reports/D-025*.md)が存在する', function (): void {
    assertTrue(count(glob('reports/D-025*.md') ?: []) >= 1, 'reports/D-025_null_default_note.md を作成してください(support/spec.md の型で)');
});

test('D-025: 部署にあたるテーブル・メンバーにあたるテーブルの両方が見つかる', function (): void {
    $blocks = d025TableBlocks(d025Note());
    $dept = d025FindByName($blocks, ['部署', 'department', 'dept']);
    $member = d025FindByName($blocks, ['メンバー', 'member', '社員', 'staff']);
    assertTrue($dept !== null, '部署にあたるテーブルが見つかりません。これまでと同じテーブル名を使ってください');
    assertTrue($member !== null, 'メンバーにあたるテーブルが見つかりません。これまでと同じテーブル名を使ってください');
});

test('D-025: 部署名がNOT NULLとして扱われている', function (): void {
    $blocks = d025TableBlocks(d025Note());
    $dept = d025FindByName($blocks, ['部署', 'department', 'dept']);
    assertTrue($dept !== null, '部署にあたるテーブルが見つかりません');
    if ($dept !== null) {
        $line = d025LinesContaining($dept['body'], '/名前|名称|部署名|name/iu');
        assertTrue($line !== '', '部署名にあたるカラムの記述が見当たりません');
        if ($line !== '') {
            assertTrue(d025HasNotNull($line), '部署名がNOT NULLとして扱われていません。部署名が無いと部署として機能しないため、NOT NULLにしてください');
        }
    }
});

test('D-025: 氏名・メール・状態・部署参照がNOT NULLとして扱われている', function (): void {
    $blocks = d025TableBlocks(d025Note());
    $member = d025FindByName($blocks, ['メンバー', 'member', '社員', 'staff']);
    assertTrue($member !== null, 'メンバーにあたるテーブルが見つかりません');
    if ($member !== null) {
        $body = $member['body'];

        $nameLine = d025LinesContaining($body, '/氏名|名前|name/iu');
        assertTrue($nameLine !== '', '氏名にあたるカラムの記述が見当たりません');
        if ($nameLine !== '') {
            assertTrue(d025HasNotNull($nameLine), '氏名がNOT NULLとして扱われていません');
        }

        $emailLine = d025LinesContaining($body, '/メール|email/iu');
        assertTrue($emailLine !== '', 'メールにあたるカラムの記述が見当たりません');
        if ($emailLine !== '') {
            assertTrue(d025HasNotNull($emailLine), 'メールがNOT NULLとして扱われていません');
        }

        $refLine = d025LinesContaining($body, '/部署|department/iu');
        assertTrue($refLine !== '', '部署への参照にあたるカラムの記述が見当たりません');
        if ($refLine !== '') {
            assertTrue(d025HasNotNull($refLine), '部署への参照がNOT NULLとして扱われていません');
        }
    }
});

test('D-025: 状態がNOT NULLかつDEFAULT(在籍中相当)を検討している', function (): void {
    $blocks = d025TableBlocks(d025Note());
    $member = d025FindByName($blocks, ['メンバー', 'member', '社員', 'staff']);
    assertTrue($member !== null, 'メンバーにあたるテーブルが見つかりません');
    if ($member !== null) {
        $statusLine = d025LinesContaining($member['body'], '/状態|ステータス|status/iu');
        assertTrue($statusLine !== '', '状態にあたるカラムの記述が見当たりません');
        if ($statusLine !== '') {
            assertTrue(d025HasNotNull($statusLine), '状態がNOT NULLとして扱われていません');
            assertTrue(d025HasDefault($statusLine) && d025HasActiveDefaultValue($statusLine), '状態にDEFAULT(在籍中相当の初期値)が見当たりません。新規登録時点で自然な初期値を検討してください');
        }
    }
});

test('D-025: 任意カラム(退職日等)を入れているなら、NULL可の理由が書かれている', function (): void {
    $note = d025Note();
    $optionalLine = d025LinesContaining($note, '/退職日|停止日|利用停止|任意メモ/iu');
    if ($optionalLine === '') {
        return; // 任意カラムを追加していないなら、この課題では検査対象外(必須ではない)
    }
    assertTrue(d025HasNullable($optionalLine), '退職日等のカラムが見つかりましたが、NULL可であることの明記が見当たりません');
    assertTrue(d025ReasonCount($optionalLine) >= 1, '退職日等のカラムが見つかりましたが、なぜ空でもよいかの理由が見当たりません');
});

test('D-025: 作成日時等のカラムを入れているなら、自動記録のDEFAULTを検討している', function (): void {
    $note = d025Note();
    $timestampLine = d025LinesContaining($note, '/作成日時|joined_at|入社日|created_at|updated_at|更新日時/iu');
    if ($timestampLine === '') {
        return; // 日時系カラムを入れていないなら、この課題では検査対象外(必須ではない)
    }
    assertTrue(d025HasDefault($timestampLine) && d025HasAutoTimestampValue($timestampLine), '日時カラムが見つかりましたが、DEFAULT(登録された時点を自動で記録、という趣旨)への言及が見当たりません');
});

test('D-025: 各テーブルに「理由」が複数(2つ以上)添えられている', function (): void {
    $blocks = d025TableBlocks(d025Note());
    foreach ($blocks as $block) {
        $count = d025ReasonCount($block['body']);
        assertTrue($count >= 2, "テーブル「{$block['name']}」に、判断理由の記述が2つ未満です(現在{$count}件)。「— 理由」または「理由: 」の形で、カラムごとに一言添えてください");
    }
});

test('D-025: スコープ外の情報(給与・勤怠・評価・個人番号・住所・健康情報)に触れていない', function (): void {
    $note = d025Note();
    $forbidden = ['給与', '勤怠', '評価', '個人番号', 'マイナンバー', '住所', '健康'];
    foreach ($forbidden as $word) {
        assertTrue(!str_contains($note, $word), "「{$word}」への言及が見つかりました。今回のメンバー管理ではスコープ外の情報です(ticket.md の「スコープ外」を確認してください)");
    }
});
