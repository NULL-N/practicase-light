<?php

declare(strict_types=1);

// D-027 の合格条件: 主キー・外部キーメモの実在、両テーブルにPRIMARY KEY(id相当)、
// メンバーテーブルからのFOREIGN KEY(部署への参照)、メール・氏名を主キーにしていない
// 判断、削除時の挙動への言及。ER図・DDLは検査しない(D-028・D-029の仕事) — 控えめ設計

function d027Note(): string
{
    $content = '';
    foreach (glob('reports/D-027*.md') ?: [] as $path) {
        $content .= (string) file_get_contents($path);
    }

    return $content;
}

/**
 * 「### テーブル: <名前>」ごとに [name, body] の配列へ分割する(D-023〜D-026と同じ方式)。
 * @return array<int, array{name: string, body: string}>
 */
function d027TableBlocks(string $note): array
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
function d027FindByName(array $blocks, array $nameKeywords): ?array
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

function d027LinesContaining(string $body, string $pattern): string
{
    $result = '';
    foreach (preg_split('/\r?\n/u', $body) ?: [] as $line) {
        if (preg_match($pattern, $line) === 1) {
            $result .= $line . "\n";
        }
    }

    return $result;
}

function d027HasPrimaryKey(string $text): bool
{
    return preg_match('/PRIMARY\s*KEY/iu', $text) === 1;
}

function d027HasForeignKey(string $text): bool
{
    return preg_match('/FOREIGN\s*KEY/iu', $text) === 1;
}

test('D-027: 主キー・外部キーメモ(reports/D-027*.md)が存在する', function (): void {
    assertTrue(count(glob('reports/D-027*.md') ?: []) >= 1, 'reports/D-027_keys_note.md を作成してください(support/spec.md の型で)');
});

test('D-027: 部署にあたるテーブル・メンバーにあたるテーブルの両方が見つかる', function (): void {
    $blocks = d027TableBlocks(d027Note());
    $dept = d027FindByName($blocks, ['部署', 'department', 'dept']);
    $member = d027FindByName($blocks, ['メンバー', 'member', '社員', 'staff']);
    assertTrue($dept !== null, '部署にあたるテーブルが見つかりません。これまでと同じテーブル名を使ってください');
    assertTrue($member !== null, 'メンバーにあたるテーブルが見つかりません。これまでと同じテーブル名を使ってください');
});

test('D-027: 部署テーブルにPRIMARY KEY(id相当)がある', function (): void {
    $blocks = d027TableBlocks(d027Note());
    $dept = d027FindByName($blocks, ['部署', 'department', 'dept']);
    assertTrue($dept !== null, '部署にあたるテーブルが見つかりません');
    if ($dept !== null) {
        $line = d027LinesContaining($dept['body'], '/PRIMARY\s*KEY/iu');
        assertTrue($line !== '', '部署テーブルにPRIMARY KEYの記述が見当たりません');
        if ($line !== '') {
            assertTrue(stripos($line, 'id') !== false, '部署テーブルのPRIMARY KEYがid相当のカラムになっていません');
        }
    }
});

test('D-027: メンバーテーブルにPRIMARY KEY(id相当)がある', function (): void {
    $blocks = d027TableBlocks(d027Note());
    $member = d027FindByName($blocks, ['メンバー', 'member', '社員', 'staff']);
    assertTrue($member !== null, 'メンバーにあたるテーブルが見つかりません');
    if ($member !== null) {
        $lines = preg_split('/\r?\n/u', $member['body']) ?: [];
        $pkLine = '';
        foreach ($lines as $line) {
            if (preg_match('/PRIMARY\s*KEY/iu', $line) === 1 && stripos($line, 'にしない') === false) {
                $pkLine .= $line . "\n";
            }
        }
        assertTrue($pkLine !== '', 'メンバーテーブルにPRIMARY KEYの記述が見当たりません');
        if ($pkLine !== '') {
            assertTrue(stripos($pkLine, 'id') !== false, 'メンバーテーブルのPRIMARY KEYがid相当のカラムになっていません');
        }
    }
});

test('D-027: メールアドレス・氏名を主キーにしない判断が書かれている', function (): void {
    $blocks = d027TableBlocks(d027Note());
    $member = d027FindByName($blocks, ['メンバー', 'member', '社員', 'staff']);
    assertTrue($member !== null, 'メンバーにあたるテーブルが見つかりません');
    if ($member !== null) {
        $negLines = d027LinesContaining($member['body'], '/PRIMARY\s*KEY\s*にしない/iu');
        assertTrue($negLines !== '', '「PRIMARY KEYにしないもの」の記述が見当たりません。メールアドレス・氏名を主キーにしない判断を書いてください');
        if ($negLines !== '') {
            assertTrue(preg_match('/メール|email/iu', $negLines) === 1, 'メールアドレスを主キーにしない、という判断が見当たりません');
            assertTrue(preg_match('/氏名|名前|name/iu', $negLines) === 1, '氏名を主キーにしない、という判断が見当たりません');
        }
    }
});

test('D-027: メンバーテーブルから部署テーブルへの外部キー(FOREIGN KEY)が設計されている', function (): void {
    $blocks = d027TableBlocks(d027Note());
    $member = d027FindByName($blocks, ['メンバー', 'member', '社員', 'staff']);
    assertTrue($member !== null, 'メンバーにあたるテーブルが見つかりません');
    if ($member !== null) {
        $fkLine = d027LinesContaining($member['body'], '/FOREIGN\s*KEY/iu');
        assertTrue($fkLine !== '', 'FOREIGN KEYの記述が見当たりません');
        if ($fkLine !== '') {
            assertTrue(preg_match('/部署|department/iu', $fkLine) === 1, 'FOREIGN KEYが部署テーブルへの参照として書かれていません');
        }
    }
});

test('D-027: 部署削除時の挙動に触れている', function (): void {
    $note = d027Note();
    assertTrue(preg_match('/RESTRICT|CASCADE|削除できない|削除不可|削除を禁止|削除させない/iu', $note) === 1, '部署を削除しようとした時の挙動(所属メンバーがいたらどうするか)への言及が見当たりません');
});

test('D-027: スコープ外の情報(給与・勤怠・評価・個人番号・住所・健康情報)に触れていない', function (): void {
    $note = d027Note();
    $forbidden = ['給与', '勤怠', '評価', '個人番号', 'マイナンバー', '住所', '健康'];
    foreach ($forbidden as $word) {
        assertTrue(!str_contains($note, $word), "「{$word}」への言及が見つかりました。今回のメンバー管理ではスコープ外の情報です(ticket.md の「スコープ外」を確認してください)");
    }
});
