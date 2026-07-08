<?php

declare(strict_types=1);

// D-028 の合格条件: Mermaid erDiagramブロックの実在、部署・メンバー両エンティティに
// PK(id相当)、メンバーエンティティに部署へのFK、部署1:メンバー多の関連、
// メールのUK(UNIQUE相当)、氏名がPKになっていないこと。
// DDL(CREATE TABLE)は検査しない(D-029の仕事) — 控えめ設計

function d028Note(): string
{
    $content = '';
    foreach (glob('reports/D-028*.md') ?: [] as $path) {
        $content .= (string) file_get_contents($path);
    }

    return $content;
}

function d028MermaidBlock(string $note): ?string
{
    if (preg_match('/```mermaid\s*\n(.*?)```/su', $note, $m) === 1) {
        return $m[1];
    }

    return null;
}

/**
 * エンティティ属性ブロック(例: 「DEPARTMENTS {」で始まり「}」で終わる)だけを拾う。
 * 行頭を \w+(識別子)で厳密にアンカーすることで、関連行のカーディナリティ記号
 * (例: 「DEPARTMENTS ||--o{ MEMBERS」の「{」)を属性ブロックの開始と誤認識しないようにする。
 * @return array<int, array{name: string, body: string}> エンティティ名と属性ブロック本文
 */
function d028EntityBlocks(string $mermaid): array
{
    if (preg_match_all('/^\s*(\w+)\s*\{([^}]*)\}/msu', $mermaid, $m, PREG_SET_ORDER) === false) {
        return [];
    }
    $blocks = [];
    foreach ($m as $match) {
        $blocks[] = ['name' => $match[1], 'body' => $match[2]];
    }

    return $blocks;
}

/** @param array<int, array{name: string, body: string}> $blocks */
function d028FindEntityByName(array $blocks, array $nameKeywords): ?array
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

function d028LinesContaining(string $body, string $pattern): string
{
    $result = '';
    foreach (preg_split('/\r?\n/u', $body) ?: [] as $line) {
        if (preg_match($pattern, $line) === 1) {
            $result .= $line . "\n";
        }
    }

    return $result;
}

const D028_DEPT_KEYWORDS = ['部署', 'department', 'dept'];
const D028_MEMBER_KEYWORDS = ['メンバー', 'member', '社員', 'staff'];

/**
 * 「部署 ||--o{ メンバー : ラベル」のような関連行を探し、部署側・メンバー側それぞれの
 * カーディナリティ記号を切り分けて返す。
 * @return array{deptSymbol: string, memberSymbol: string}|null
 */
function d028FindRelationship(string $mermaid): ?array
{
    foreach (preg_split('/\r?\n/u', $mermaid) ?: [] as $line) {
        if (preg_match('/^\s*(\S+)\s*([|o}{.\-]{2,})\s*(\S+)\s*:/u', $line, $m) !== 1) {
            continue;
        }
        [, $entity1, $symbol, $entity2] = $m;
        $e1Dept = d028MatchesAny($entity1, D028_DEPT_KEYWORDS);
        $e1Member = d028MatchesAny($entity1, D028_MEMBER_KEYWORDS);
        $e2Dept = d028MatchesAny($entity2, D028_DEPT_KEYWORDS);
        $e2Member = d028MatchesAny($entity2, D028_MEMBER_KEYWORDS);
        if (!(($e1Dept && $e2Member) || ($e1Member && $e2Dept))) {
            continue;
        }
        $parts = preg_split('/--|\.\./u', $symbol);
        if ($parts === false || count($parts) !== 2) {
            continue;
        }
        [$left, $right] = $parts;

        return $e1Dept
            ? ['deptSymbol' => $left, 'memberSymbol' => $right]
            : ['deptSymbol' => $right, 'memberSymbol' => $left];
    }

    return null;
}

function d028MatchesAny(string $text, array $keywords): bool
{
    foreach ($keywords as $keyword) {
        if (stripos($text, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

// Mermaidのカーディナリティ記号は「多」を表すクロウフットが { または } のどちらの形でも
// 現れうる(多側が関連行の右にあれば { 、左にあれば } を使う書き方になるため、両方を許容する)
function d028IsManySymbol(string $symbol): bool
{
    return str_contains($symbol, '{') || str_contains($symbol, '}');
}

test('D-028: ER図メモ(reports/D-028*.md)が存在する', function (): void {
    assertTrue(count(glob('reports/D-028*.md') ?: []) >= 1, 'reports/D-028_er_diagram.md を作成してください(support/spec.md の型で)');
});

test('D-028: Mermaidの erDiagram コードブロックがある', function (): void {
    $mermaid = d028MermaidBlock(d028Note());
    assertTrue($mermaid !== null, 'Mermaidコードブロック(```mermaid 〜 ```)が見当たりません');
    if ($mermaid !== null) {
        assertTrue(str_contains($mermaid, 'erDiagram'), '「erDiagram」キーワードが見当たりません');
    }
});

test('D-028: 部署・メンバー両エンティティに id 相当のPKがある', function (): void {
    $mermaid = d028MermaidBlock(d028Note());
    assertTrue($mermaid !== null, 'Mermaidコードブロックが見当たりません');
    if ($mermaid !== null) {
        $blocks = d028EntityBlocks($mermaid);
        $dept = d028FindEntityByName($blocks, D028_DEPT_KEYWORDS);
        $member = d028FindEntityByName($blocks, D028_MEMBER_KEYWORDS);
        assertTrue($dept !== null, '部署にあたるエンティティ(属性ブロック { ... })が見当たりません');
        assertTrue($member !== null, 'メンバーにあたるエンティティ(属性ブロック { ... })が見当たりません');
        if ($dept !== null) {
            $line = d028LinesContaining($dept['body'], '/\bid\b/iu');
            assertTrue($line !== '' && preg_match('/\bPK\b/u', $line) === 1, '部署エンティティに id PK の行が見当たりません');
        }
        if ($member !== null) {
            $line = d028LinesContaining($member['body'], '/\bid\b/iu');
            assertTrue($line !== '' && preg_match('/\bPK\b/u', $line) === 1, 'メンバーエンティティに id PK の行が見当たりません');
        }
    }
});

test('D-028: メンバーエンティティに部署への外部キー(FK)がある', function (): void {
    $mermaid = d028MermaidBlock(d028Note());
    assertTrue($mermaid !== null, 'Mermaidコードブロックが見当たりません');
    if ($mermaid !== null) {
        $member = d028FindEntityByName(d028EntityBlocks($mermaid), D028_MEMBER_KEYWORDS);
        assertTrue($member !== null, 'メンバーにあたるエンティティが見当たりません');
        if ($member !== null) {
            $line = d028LinesContaining($member['body'], '/部署|department/iu');
            assertTrue($line !== '' && preg_match('/\bFK\b/u', $line) === 1, 'メンバーエンティティに部署への参照カラム(FK)が見当たりません');
        }
    }
});

test('D-028: 部署とメンバーの関連が1対多で表現されている', function (): void {
    $mermaid = d028MermaidBlock(d028Note());
    assertTrue($mermaid !== null, 'Mermaidコードブロックが見当たりません');
    if ($mermaid !== null) {
        $rel = d028FindRelationship($mermaid);
        assertTrue($rel !== null, '部署とメンバーを結ぶ関連線が見当たりません(例: DEPARTMENTS ||--o{ MEMBERS : ラベル)');
        if ($rel !== null) {
            $isOneToMany = !d028IsManySymbol($rel['deptSymbol']) && d028IsManySymbol($rel['memberSymbol']);
            assertTrue($isOneToMany, '関連が「部署1:メンバー多」になっていません。メンバー側に「多」を表す記号({または})が付いているか確認してください');
        }
    }
});

test('D-028: メールに UK(UNIQUE相当)が付いている', function (): void {
    $mermaid = d028MermaidBlock(d028Note());
    assertTrue($mermaid !== null, 'Mermaidコードブロックが見当たりません');
    if ($mermaid !== null) {
        $member = d028FindEntityByName(d028EntityBlocks($mermaid), D028_MEMBER_KEYWORDS);
        assertTrue($member !== null, 'メンバーにあたるエンティティが見当たりません');
        if ($member !== null) {
            $line = d028LinesContaining($member['body'], '/メール|email/iu');
            assertTrue($line !== '' && preg_match('/\bUK\b/u', $line) === 1, 'メールの行に UK(UNIQUE相当)の印が見当たりません');
        }
    }
});

test('D-028: 氏名に PK が付いていない', function (): void {
    $mermaid = d028MermaidBlock(d028Note());
    assertTrue($mermaid !== null, 'Mermaidコードブロックが見当たりません');
    if ($mermaid !== null) {
        $member = d028FindEntityByName(d028EntityBlocks($mermaid), D028_MEMBER_KEYWORDS);
        assertTrue($member !== null, 'メンバーにあたるエンティティが見当たりません');
        if ($member !== null) {
            $line = d028LinesContaining($member['body'], '/氏名|名前|name/iu');
            assertTrue($line !== '' && preg_match('/\bPK\b/u', $line) !== 1, '氏名にPKが付いています。氏名は主キーにしない、というD-027の判断を反映してください');
        }
    }
});

test('D-028: コードブロックの中に、Mermaid構文を壊すような普通の文章が混ざっていない', function (): void {
    $mermaid = d028MermaidBlock(d028Note());
    assertTrue($mermaid !== null, 'Mermaidコードブロックが見当たりません');
    if ($mermaid !== null) {
        assertTrue(!str_contains($mermaid, '。'), 'コードブロックの中に、日本語の説明文らしき一文(句点「。」)が見つかりました。説明はコードブロックの外に書いてください');
    }
});

test('D-028: スコープ外の情報(給与・勤怠・評価・個人番号・住所・健康情報)に触れていない', function (): void {
    $note = d028Note();
    $forbidden = ['給与', '勤怠', '評価', '個人番号', 'マイナンバー', '住所', '健康'];
    foreach ($forbidden as $word) {
        assertTrue(!str_contains($note, $word), "「{$word}」への言及が見つかりました。今回のメンバー管理ではスコープ外の情報です(ticket.md の「スコープ外」を確認してください)");
    }
});
