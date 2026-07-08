<?php

declare(strict_types=1);

// D-026 の合格条件: UNIQUE判断メモの実在、メールがUNIQUEあり、氏名・状態がUNIQUEなし、
// 部署名についてUNIQUEの有無どちらかが明示され理由が添えられていること、
// 全カラムを機械的にUNIQUEにしていないこと。
// PK/FKは検査しない(D-027の仕事) — 控えめ設計

function d026Note(): string
{
    $content = '';
    foreach (glob('reports/D-026*.md') ?: [] as $path) {
        $content .= (string) file_get_contents($path);
    }

    return $content;
}

/**
 * 「### テーブル: <名前>」ごとに [name, body] の配列へ分割する(D-023〜D-025と同じ方式)。
 * @return array<int, array{name: string, body: string}>
 */
function d026TableBlocks(string $note): array
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
function d026FindByName(array $blocks, array $nameKeywords): ?array
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

function d026LinesContaining(string $body, string $pattern): string
{
    $result = '';
    foreach (preg_split('/\r?\n/u', $body) ?: [] as $line) {
        if (preg_match($pattern, $line) === 1) {
            $result .= $line . "\n";
        }
    }

    return $result;
}

function d026IsUniqueYes(string $text): bool
{
    return preg_match('/UNIQUE\s*あり/u', $text) === 1;
}

function d026IsUniqueNo(string $text): bool
{
    return preg_match('/UNIQUE\s*なし/u', $text) === 1;
}

function d026ReasonCount(string $body): int
{
    // 全角ダッシュ「—」または「理由:」のどちらの形式も許容する
    return preg_match_all('/[—―]|理由\s*[:\x{FF1A}]/u', $body);
}

test('D-026: UNIQUE判断メモ(reports/D-026*.md)が存在する', function (): void {
    assertTrue(count(glob('reports/D-026*.md') ?: []) >= 1, 'reports/D-026_unique_note.md を作成してください(support/spec.md の型で)');
});

test('D-026: 部署にあたるテーブル・メンバーにあたるテーブルの両方が見つかる', function (): void {
    $blocks = d026TableBlocks(d026Note());
    $dept = d026FindByName($blocks, ['部署', 'department', 'dept']);
    $member = d026FindByName($blocks, ['メンバー', 'member', '社員', 'staff']);
    assertTrue($dept !== null, '部署にあたるテーブルが見つかりません。これまでと同じテーブル名を使ってください');
    assertTrue($member !== null, 'メンバーにあたるテーブルが見つかりません。これまでと同じテーブル名を使ってください');
});

test('D-026: メールアドレスがUNIQUEあり判断になっている', function (): void {
    $blocks = d026TableBlocks(d026Note());
    $member = d026FindByName($blocks, ['メンバー', 'member', '社員', 'staff']);
    assertTrue($member !== null, 'メンバーにあたるテーブルが見つかりません');
    if ($member !== null) {
        $line = d026LinesContaining($member['body'], '/メール|email/iu');
        assertTrue($line !== '', 'メールにあたるカラムの記述が見当たりません');
        if ($line !== '') {
            assertTrue(d026IsUniqueYes($line), 'メールアドレスがUNIQUEありになっていません。同じメールの人が複数いると本人特定ができないため、UNIQUEありにしてください');
        }
    }
});

test('D-026: 部署名についてUNIQUEの有無どちらかが明示され、理由が添えられている', function (): void {
    $blocks = d026TableBlocks(d026Note());
    $dept = d026FindByName($blocks, ['部署', 'department', 'dept']);
    assertTrue($dept !== null, '部署にあたるテーブルが見つかりません');
    if ($dept !== null) {
        $line = d026LinesContaining($dept['body'], '/名前|名称|部署名|name/iu');
        assertTrue($line !== '', '部署名にあたるカラムの記述が見当たりません');
        if ($line !== '') {
            $decided = d026IsUniqueYes($line) || d026IsUniqueNo($line);
            assertTrue($decided, '部署名についてUNIQUEあり/UNIQUEなしのどちらかが明示されていません。どちらでも構わないので判断してください');
            assertTrue(d026ReasonCount($line) >= 1, '部署名のUNIQUE判断に理由が添えられていません。どちらの判断でも理由が必要です');
        }
    }
});

test('D-026: 氏名がUNIQUEなし判断になっている', function (): void {
    $blocks = d026TableBlocks(d026Note());
    $member = d026FindByName($blocks, ['メンバー', 'member', '社員', 'staff']);
    assertTrue($member !== null, 'メンバーにあたるテーブルが見つかりません');
    if ($member !== null) {
        $line = d026LinesContaining($member['body'], '/氏名|名前|name/iu');
        assertTrue($line !== '', '氏名にあたるカラムの記述が見当たりません');
        if ($line !== '') {
            assertTrue(d026IsUniqueNo($line), '氏名がUNIQUEなしになっていません。同姓同名のメンバーはいてもおかしくないため、UNIQUEなしにしてください');
        }
    }
});

test('D-026: 状態がUNIQUEなし判断になっている', function (): void {
    $blocks = d026TableBlocks(d026Note());
    $member = d026FindByName($blocks, ['メンバー', 'member', '社員', 'staff']);
    assertTrue($member !== null, 'メンバーにあたるテーブルが見つかりません');
    if ($member !== null) {
        $line = d026LinesContaining($member['body'], '/状態|ステータス|status/iu');
        assertTrue($line !== '', '状態にあたるカラムの記述が見当たりません');
        if ($line !== '') {
            assertTrue(d026IsUniqueNo($line), '状態がUNIQUEなしになっていません。複数のメンバーが同じ状態を持つのは当然のため、UNIQUEなしにしてください');
        }
    }
});

test('D-026: 全カラムを機械的にUNIQUEにしていない(UNIQUEなしの判断が2件以上ある)', function (): void {
    $note = d026Note();
    $noCount = preg_match_all('/UNIQUE\s*なし/u', $note);
    assertTrue($noCount >= 2, "「UNIQUEなし」の判断が2件未満です(現在{$noCount}件)。重複してはいけない理由が具体的に言えるカラムだけにUNIQUEを付け、それ以外はUNIQUEなしと明記してください");
});

test('D-026: スコープ外の情報(給与・勤怠・評価・個人番号・住所・健康情報)に触れていない', function (): void {
    $note = d026Note();
    $forbidden = ['給与', '勤怠', '評価', '個人番号', 'マイナンバー', '住所', '健康'];
    foreach ($forbidden as $word) {
        assertTrue(!str_contains($note, $word), "「{$word}」への言及が見つかりました。今回のメンバー管理ではスコープ外の情報です(ticket.md の「スコープ外」を確認してください)");
    }
});
