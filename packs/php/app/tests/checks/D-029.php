<?php

declare(strict_types=1);

// D-029(基礎編卒業課題)の合格条件: sqlコードブロックのCREATE TABLE文が実際に
// SQLiteで実行できること、実行後のスキーマ(PRAGMA)を直接調べて、D-022〜D-027の
// 判断(PK/FK/UNIQUE/NOT NULL/DEFAULT)がDDLに反映されていること、
// 設計レビュー欄・気をつけたこと欄があること。
// 正規表現でのゆるい判定ではなく、SQLite自身にDDLを解釈させて検証する(この課題のみ)。

const D029_DEPT_KEYWORDS = ['部署', 'department', 'dept'];
const D029_MEMBER_KEYWORDS = ['メンバー', 'member', '社員', 'staff'];

function d029Note(): string
{
    $content = '';
    foreach (glob('reports/D-029*.md') ?: [] as $path) {
        $content .= (string) file_get_contents($path);
    }

    return $content;
}

function d029ExtractSql(string $note): string
{
    if (preg_match('/```sql\s*\n(.*?)```/su', $note, $m) === 1) {
        return trim($m[1]);
    }

    return '';
}

/** @return array<int, string> セミコロン区切りの各SQL文(コメント除去済み・空要素なし) */
function d029SplitStatements(string $sql): array
{
    $withoutLineComments = preg_replace('/--.*$/m', '', $sql) ?? '';
    $withoutComments = preg_replace('/\/\*.*?\*\//s', '', $withoutLineComments) ?? '';
    $statements = [];
    foreach (explode(';', $withoutComments) as $piece) {
        $trimmed = trim($piece);
        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }
    }

    return $statements;
}

function d029AllCreateTable(array $statements): bool
{
    if ($statements === []) {
        return false;
    }
    foreach ($statements as $stmt) {
        if (preg_match('/\ACREATE\s+TABLE\b/iu', $stmt) !== 1) {
            return false;
        }
    }

    return true;
}

/**
 * DDLをin-memory SQLiteに対して実行し、接続済みのPDOを返す(実行できなければnull)。
 * CREATE TABLE以外の文が混ざっている場合も安全側に倒してnullを返す。
 * プロセス内で1度だけ実行するようメモ化する。
 */
function d029ConnectedPdo(): ?\PDO
{
    static $computed = false;
    static $pdo = null;
    if ($computed) {
        return $pdo;
    }
    $computed = true;

    $statements = d029SplitStatements(d029ExtractSql(d029Note()));
    if (!d029AllCreateTable($statements)) {
        return null;
    }

    try {
        $conn = new \PDO('sqlite::memory:');
        $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $conn->exec('PRAGMA foreign_keys = ON');
        foreach ($statements as $stmt) {
            $conn->exec($stmt);
        }
        $pdo = $conn;
    } catch (\Throwable) {
        $pdo = null;
    }

    return $pdo;
}

/** @return array<int, string> */
function d029TableNames(\PDO $pdo): array
{
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");

    return $stmt !== false ? $stmt->fetchAll(\PDO::FETCH_COLUMN) : [];
}

/** @param array<int, string> $tableNames */
function d029FindTableByKeyword(array $tableNames, array $keywords): ?string
{
    foreach ($tableNames as $name) {
        foreach ($keywords as $keyword) {
            if (stripos($name, $keyword) !== false) {
                return $name;
            }
        }
    }

    return null;
}

function d029QuoteIdentifier(string $identifier): string
{
    return '"' . str_replace('"', '""', $identifier) . '"';
}

/** @return array<int, array<string, mixed>> cid,name,type,notnull,dflt_value,pk を持つ行の配列 */
function d029ColumnInfo(\PDO $pdo, string $table): array
{
    $stmt = $pdo->query('PRAGMA table_info(' . d029QuoteIdentifier($table) . ')');

    return $stmt !== false ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
}

/** @param array<int, array<string, mixed>> $columns */
function d029FindColumnByKeyword(array $columns, array $keywords): ?array
{
    foreach ($columns as $col) {
        foreach ($keywords as $keyword) {
            if (stripos((string) $col['name'], $keyword) !== false) {
                return $col;
            }
        }
    }

    return null;
}

/**
 * 主キー用の id カラムを探す。「department_id」のように語尾に「id」を含む別カラムを
 * 誤って拾わないよう、「id」という単語そのものに一致する列を優先する
 * (単純な部分一致だと、department_id が id より前に定義されていた場合に誤検出しうるため)。
 * @param array<int, array<string, mixed>> $columns
 */
function d029FindIdColumn(array $columns): ?array
{
    foreach ($columns as $col) {
        if (preg_match('/\bid\b/iu', (string) $col['name']) === 1) {
            return $col;
        }
    }

    return d029FindColumnByKeyword($columns, ['id']);
}

/** @return array<int, string> UNIQUE制約(またはPRIMARY KEY)に含まれるカラム名の一覧 */
function d029UniqueColumnNames(\PDO $pdo, string $table): array
{
    $indexes = $pdo->query('PRAGMA index_list(' . d029QuoteIdentifier($table) . ')')->fetchAll(\PDO::FETCH_ASSOC);
    $names = [];
    foreach ($indexes as $idx) {
        if ((int) $idx['unique'] !== 1) {
            continue;
        }
        $cols = $pdo->query('PRAGMA index_info(' . d029QuoteIdentifier((string) $idx['name']) . ')')->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            $names[] = (string) $c['name'];
        }
    }

    return $names;
}

/** @return array<int, array<string, mixed>> id,seq,table,from,to,on_update,on_delete,match を持つ行の配列 */
function d029ForeignKeys(\PDO $pdo, string $table): array
{
    $stmt = $pdo->query('PRAGMA foreign_key_list(' . d029QuoteIdentifier($table) . ')');

    return $stmt !== false ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
}

test('D-029: DDLレビューメモ(reports/D-029*.md)が存在する', function (): void {
    assertTrue(count(glob('reports/D-029*.md') ?: []) >= 1, 'reports/D-029_ddl_review.md を作成してください(support/spec.md の型で)');
});

test('D-029: sqlコードブロックの文はすべて CREATE TABLE である', function (): void {
    $sql = d029ExtractSql(d029Note());
    assertTrue($sql !== '', 'SQLコードブロック(```sql 〜 ```)が見当たりません');
    if ($sql !== '') {
        $statements = d029SplitStatements($sql);
        assertTrue($statements !== [], 'sqlコードブロックの中にCREATE TABLE文が見当たりません');
        foreach ($statements as $stmt) {
            assertTrue(preg_match('/\ACREATE\s+TABLE\b/iu', $stmt) === 1, 'この課題ではCREATE TABLE文だけを書いてください(DROP/ALTER/INSERT等は対象外です)');
        }
    }
});

test('D-029: DDLがSQLiteとして実行できる', function (): void {
    $pdo = d029ConnectedPdo();
    assertTrue($pdo !== null, 'DDLの実行に失敗しました(構文エラーの可能性があります。CREATE TABLE文だけを書いているか、カンマ・括弧が正しいかを確認してください)');
});

test('D-029: CREATE TABLEが2つ以上あり、部署系・メンバー系テーブルの両方がある', function (): void {
    $pdo = d029ConnectedPdo();
    assertTrue($pdo !== null, 'DDLの実行に失敗しているため、この検査を行えません');
    if ($pdo !== null) {
        $tables = d029TableNames($pdo);
        assertTrue(count($tables) >= 2, 'テーブルが2つ以上作られていません(現在' . count($tables) . '件)');
        $dept = d029FindTableByKeyword($tables, D029_DEPT_KEYWORDS);
        $member = d029FindTableByKeyword($tables, D029_MEMBER_KEYWORDS);
        assertTrue($dept !== null, '部署にあたるテーブルが見当たりません。これまでと同じテーブル名を使ってください');
        assertTrue($member !== null, 'メンバーにあたるテーブルが見当たりません。これまでと同じテーブル名を使ってください');
    }
});

test('D-029: 両テーブルに id 相当の主キーがある', function (): void {
    $pdo = d029ConnectedPdo();
    assertTrue($pdo !== null, 'DDLの実行に失敗しているため、この検査を行えません');
    if ($pdo !== null) {
        $tables = d029TableNames($pdo);
        $dept = d029FindTableByKeyword($tables, D029_DEPT_KEYWORDS);
        $member = d029FindTableByKeyword($tables, D029_MEMBER_KEYWORDS);
        if ($dept !== null) {
            $col = d029FindIdColumn(d029ColumnInfo($pdo, $dept));
            assertTrue($col !== null && (int) $col['pk'] > 0, '部署テーブルに、PRIMARY KEYとなるid相当のカラムが見当たりません');
        }
        if ($member !== null) {
            $col = d029FindIdColumn(d029ColumnInfo($pdo, $member));
            assertTrue($col !== null && (int) $col['pk'] > 0, 'メンバーテーブルに、PRIMARY KEYとなるid相当のカラムが見当たりません');
        }
    }
});

test('D-029: 部署名・氏名・メール・状態・部署参照のカラムが存在する', function (): void {
    $pdo = d029ConnectedPdo();
    assertTrue($pdo !== null, 'DDLの実行に失敗しているため、この検査を行えません');
    if ($pdo !== null) {
        $tables = d029TableNames($pdo);
        $dept = d029FindTableByKeyword($tables, D029_DEPT_KEYWORDS);
        $member = d029FindTableByKeyword($tables, D029_MEMBER_KEYWORDS);
        if ($dept !== null) {
            $col = d029FindColumnByKeyword(d029ColumnInfo($pdo, $dept), ['名前', '名称', '部署名', 'name']);
            assertTrue($col !== null, '部署テーブルに、部署名にあたるカラムが見当たりません');
        }
        if ($member !== null) {
            $columns = d029ColumnInfo($pdo, $member);
            assertTrue(d029FindColumnByKeyword($columns, ['氏名', '名前', 'name']) !== null, 'メンバーテーブルに、氏名にあたるカラムが見当たりません');
            assertTrue(d029FindColumnByKeyword($columns, ['メール', 'email']) !== null, 'メンバーテーブルに、メールにあたるカラムが見当たりません');
            assertTrue(d029FindColumnByKeyword($columns, ['状態', 'ステータス', 'status']) !== null, 'メンバーテーブルに、状態にあたるカラムが見当たりません');
            assertTrue(d029FindColumnByKeyword($columns, ['部署', 'department']) !== null, 'メンバーテーブルに、部署参照にあたるカラムが見当たりません');
        }
    }
});

test('D-029: メールにUNIQUEがあり、氏名にはUNIQUE・PRIMARY KEYが無い', function (): void {
    $pdo = d029ConnectedPdo();
    assertTrue($pdo !== null, 'DDLの実行に失敗しているため、この検査を行えません');
    if ($pdo !== null) {
        $member = d029FindTableByKeyword(d029TableNames($pdo), D029_MEMBER_KEYWORDS);
        assertTrue($member !== null, 'メンバーにあたるテーブルが見当たりません');
        if ($member !== null) {
            $columns = d029ColumnInfo($pdo, $member);
            $uniqueNames = d029UniqueColumnNames($pdo, $member);
            $emailCol = d029FindColumnByKeyword($columns, ['メール', 'email']);
            if ($emailCol !== null) {
                assertTrue(in_array($emailCol['name'], $uniqueNames, true), 'メールのカラムにUNIQUE制約が見当たりません');
            }
            $nameCol = d029FindColumnByKeyword($columns, ['氏名', '名前', 'name']);
            if ($nameCol !== null) {
                assertTrue(!in_array($nameCol['name'], $uniqueNames, true), '氏名のカラムにUNIQUE制約が付いています。氏名は重複しうるため付けないでください');
                assertTrue((int) $nameCol['pk'] === 0, '氏名のカラムにPRIMARY KEYが付いています。氏名は主キーにしないでください');
            }
        }
    }
});

test('D-029: 状態にDEFAULT(在籍中相当)がある', function (): void {
    $pdo = d029ConnectedPdo();
    assertTrue($pdo !== null, 'DDLの実行に失敗しているため、この検査を行えません');
    if ($pdo !== null) {
        $member = d029FindTableByKeyword(d029TableNames($pdo), D029_MEMBER_KEYWORDS);
        assertTrue($member !== null, 'メンバーにあたるテーブルが見当たりません');
        if ($member !== null) {
            $statusCol = d029FindColumnByKeyword(d029ColumnInfo($pdo, $member), ['状態', 'ステータス', 'status']);
            assertTrue($statusCol !== null, '状態にあたるカラムが見当たりません');
            if ($statusCol !== null) {
                $default = (string) ($statusCol['dflt_value'] ?? '');
                assertTrue($default !== '', '状態のカラムにDEFAULT値が設定されていません');
            }
        }
    }
});

test('D-029: 部署名・氏名・メール・状態・部署参照がNOT NULLになっている', function (): void {
    $pdo = d029ConnectedPdo();
    assertTrue($pdo !== null, 'DDLの実行に失敗しているため、この検査を行えません');
    if ($pdo !== null) {
        $tables = d029TableNames($pdo);
        $dept = d029FindTableByKeyword($tables, D029_DEPT_KEYWORDS);
        $member = d029FindTableByKeyword($tables, D029_MEMBER_KEYWORDS);
        if ($dept !== null) {
            $col = d029FindColumnByKeyword(d029ColumnInfo($pdo, $dept), ['名前', '名称', '部署名', 'name']);
            assertTrue($col !== null && (int) $col['notnull'] === 1, '部署名がNOT NULLになっていません');
        }
        if ($member !== null) {
            $columns = d029ColumnInfo($pdo, $member);
            foreach ([['氏名', '名前', 'name'], ['メール', 'email'], ['状態', 'ステータス', 'status'], ['部署', 'department']] as $keywords) {
                $col = d029FindColumnByKeyword($columns, $keywords);
                assertTrue($col !== null && (int) $col['notnull'] === 1, "「{$keywords[0]}」にあたるカラムがNOT NULLになっていません");
            }
        }
    }
});

test('D-029: 部署参照にFOREIGN KEYがあり、削除時挙動がRESTRICT相当になっている', function (): void {
    $pdo = d029ConnectedPdo();
    assertTrue($pdo !== null, 'DDLの実行に失敗しているため、この検査を行えません');
    if ($pdo !== null) {
        $member = d029FindTableByKeyword(d029TableNames($pdo), D029_MEMBER_KEYWORDS);
        assertTrue($member !== null, 'メンバーにあたるテーブルが見当たりません');
        if ($member !== null) {
            $fks = d029ForeignKeys($pdo, $member);
            $deptFk = null;
            foreach ($fks as $fk) {
                foreach (D029_DEPT_KEYWORDS as $keyword) {
                    if (stripos((string) $fk['table'], $keyword) !== false) {
                        $deptFk = $fk;
                        break 2;
                    }
                }
            }
            assertTrue($deptFk !== null, '部署テーブルへの外部キー(FOREIGN KEY)が見当たりません');
            if ($deptFk !== null) {
                $onDelete = strtoupper((string) $deptFk['on_delete']);
                assertTrue(in_array($onDelete, ['RESTRICT', 'NO ACTION'], true), "外部キーのON DELETEがRESTRICT相当になっていません(現在: {$onDelete})。所属メンバーがいる部署を削除できないようにしてください");
            }
        }
    }
});

test('D-029: 設計レビュー欄がある', function (): void {
    assertTrue(preg_match('/設計レビュー/u', d029Note()) === 1, '「設計レビュー」の見出しが見当たりません');
});

test('D-029: 「気をつけたこと」または「見直したこと」欄がある', function (): void {
    assertTrue(preg_match('/気をつけたこと|見直したこと/u', d029Note()) === 1, '「気をつけたこと」または「見直したこと」の見出しが見当たりません');
});

test('D-029: スコープ外の情報(給与・勤怠・評価・個人番号・住所・健康情報)に触れていない', function (): void {
    $note = d029Note();
    $forbidden = ['給与', '勤怠', '評価', '個人番号', 'マイナンバー', '住所', '健康'];
    foreach ($forbidden as $word) {
        assertTrue(!str_contains($note, $word), "「{$word}」への言及が見つかりました。今回のメンバー管理ではスコープ外の情報です(ticket.md の「スコープ外」を確認してください)");
    }
});
