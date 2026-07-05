<?php

declare(strict_types=1);

/**
 * SQL 課題用の提出物読み込みヘルパ。
 * 学習者の SQL は in-memory DB に対して実行し、アプリ本体の DB には触れない。
 */

function submittedSqlPath(string $ticketId): string
{
    return __DIR__ . '/../../../sql/' . $ticketId . '.sql';
}

function submittedSelectSql(string $ticketId): string
{
    $path = submittedSqlPath($ticketId);
    assertTrue(is_file($path), "{$ticketId}.sql が見つかりません。packs/php/sql/ に作成してください");

    $sql = trim((string) file_get_contents($path));
    $withoutComments = preg_replace('/--.*$/m', '', $sql) ?? '';
    $withoutComments = preg_replace('/\/\*.*?\*\//s', '', $withoutComments) ?? '';
    $normalized = trim($withoutComments);
    $normalized = rtrim($normalized, " \t\r\n;");

    assertTrue($normalized !== '', "{$ticketId}.sql に SELECT 文を書いてください");
    assertTrue(
        preg_match('/\A(SELECT|WITH)\b/i', $normalized) === 1,
        'この課題で実行できるのは SELECT 文だけです'
    );
    assertTrue(
        preg_match('/\b(INSERT|UPDATE|DELETE|DROP|ALTER|CREATE|REPLACE|PRAGMA|ATTACH|DETACH|VACUUM|TRUNCATE)\b/i', $normalized) !== 1,
        'この課題では DB を変更する SQL は使いません'
    );
    assertTrue(
        !str_contains($normalized, ';'),
        'この課題では 1 つの SELECT 文だけを書いてください'
    );

    return $normalized;
}

/**
 * @return array<int, array<string, mixed>>
 */
function fetchSubmittedRows(string $ticketId, \PDO $pdo, array $intColumns = []): array
{
    $stmt = $pdo->query(submittedSelectSql($ticketId));
    assertTrue($stmt !== false, 'SQL の実行に失敗しました');
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        foreach ($intColumns as $column) {
            if (array_key_exists($column, $row)) {
                $row[$column] = (int) $row[$column];
            }
        }
    }
    unset($row);

    return $rows;
}

/**
 * UPDATE を含む複数文の提出物用。SELECT / UPDATE / BEGIN・COMMIT・ROLLBACK
 * だけを許可し、1文ずつに分割して返す(実行するかどうかは呼び出し側=各checkの判断に委ねる)。
 *
 * @return array<int, string> 正規化済みの各SQL文(コメント除去済み)
 */
function submittedMultiStageSql(string $ticketId): array
{
    $path = submittedSqlPath($ticketId);
    assertTrue(is_file($path), "{$ticketId}.sql が見つかりません。packs/php/sql/ に作成してください");

    $sql = (string) file_get_contents($path);
    $withoutComments = preg_replace('/--.*$/m', '', $sql) ?? '';
    $withoutComments = preg_replace('/\/\*.*?\*\//s', '', $withoutComments) ?? '';

    $statements = [];
    foreach (explode(';', $withoutComments) as $piece) {
        $trimmed = trim($piece);
        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }
    }

    assertTrue($statements !== [], "{$ticketId}.sql にSQL文を書いてください");

    foreach ($statements as $statement) {
        assertTrue(
            preg_match('/\A(SELECT|WITH|UPDATE|BEGIN|COMMIT|ROLLBACK)\b/i', $statement) === 1,
            'この課題で書けるのは SELECT 文・UPDATE 文・トランザクション制御(BEGIN/COMMIT)だけです'
        );
        assertTrue(
            preg_match('/\b(INSERT|DELETE|DROP|ALTER|CREATE|REPLACE|PRAGMA|ATTACH|DETACH|VACUUM|TRUNCATE)\b/i', $statement) !== 1,
            'この課題では UPDATE 以外にDBやスキーマを変更するSQLは使いません'
        );
    }

    return $statements;
}
