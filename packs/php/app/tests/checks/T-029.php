<?php

declare(strict_types=1);

// T-029(障害ログ調査)の check。
//
// 判定の正本は固定ログ(support/incident-app-log.txt)だけ:
// 事実7項目の期待値はこのファイルへ書かず、固定ログから毎回導出して比較する
// (正解値を二重に持たない — 固定ログを差し替えれば判定も追随する)。
// 自由文2項目(direct_cause / remediation_plan)は記入の有無だけを機械判定し、
// 内容の質は support/rubric.md(セルフレビュー)で扱う。自由文の意味を
// 正規表現で評価することはしない。

const T029_FIXED_LOG = 'packs/php/tickets/08_障害対応入門/T-029_incident_log_investigation/support/incident-app-log.txt';
// 固定ログ(判定の正本)の完全性 digest。改行コードだけを LF へ正規化した全文の SHA-256。
// 事実7項目の期待値は引き続きログから導出する(この digest は「正本が配布時のままか」の
// 確認専用 — 行の追加・削除・値変更・BOM 付与はここで止まる。LF/CRLF 差だけは同一視)。
// 再計算(固定ログを正規に更新したときだけ): T029_FIXED_LOG の正本ファイルに対して
//   hash('sha256', str_replace(["\r\n", "\r"], "\n", file_get_contents(<正本>)))
const T029_FIXED_LOG_SHA256 = '3b4d25c90af962c30fdf66dc90c272c09b51616da081a215c89f639404cb0a70';
const T029_REPORT = 'reports/T-029.md';
const T029_MARKER_START = '<!-- practicase-contract:start -->';
const T029_MARKER_END = '<!-- practicase-contract:end -->';
const T029_FACT_KEYS = ['occurred_at', 'path', 'user', 'event', 'source_file', 'source_line', 'impact_count'];
const T029_FREE_KEYS = ['direct_cause', 'remediation_plan'];

/** 固定ログの1行を分解する(書式: 日時 [レベル] イベント key=value ...) */
function t029ParseLine(string $line): ?array
{
    if (preg_match('/\A(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) \[(\w+)\] (\S+) ?(.*)\z/u', $line, $m) !== 1) {
        return null;
    }
    // 値は「次の key= の直前」まで(message のように空白を含む値があるため)
    $context = [];
    preg_match_all('/(\w+)=((?:(?!\s\w+=).)*)/u', $m[4], $pairs, PREG_SET_ORDER);
    foreach ($pairs as $pair) {
        $context[$pair[1]] = $pair[2];
    }

    return ['time' => $m[1], 'level' => $m[2], 'event' => $m[3], 'context' => $context];
}

/**
 * 固定ログから事実7項目を導出する。ログ自体の前提(error 行の同質性・request_id の
 * 個別性)もここで検証する — 崩れていたら学習者の誤りではなく教材側の不備。
 */
function t029FactsFromFixedLog(): array
{
    assertTrue(is_file(T029_FIXED_LOG), '固定ログが見つかりません: ' . T029_FIXED_LOG . '(教材の配布不備の可能性があります)');
    // 正本の完全性を最初に確認する。不一致なら事実の導出にも報告の比較にも進まない
    // (改ざんされた「正本」から導出した事実に意味はないため)
    $raw = (string) file_get_contents(T029_FIXED_LOG);
    $digest = hash('sha256', str_replace(["\r\n", "\r"], "\n", $raw));
    assertSame(
        T029_FIXED_LOG_SHA256,
        $digest,
        '固定ログが変更されています。配布時の incident-app-log.txt に戻してください'
        . '(この課題の判定の正本は固定ログです — 調査対象を書き換えるのではなく、ログから読み取った事実を報告書に書きます)'
    );
    $errors = [];
    foreach (preg_split('/\r?\n/', trim($raw)) as $line) {
        if (trim($line) === '') {
            continue;
        }
        $parsed = t029ParseLine($line);
        assertNotNull($parsed, '固定ログに書式外の行があります(教材の不備): ' . $line);
        if ($parsed['level'] === 'error') {
            $errors[] = $parsed;
        }
    }
    assertTrue($errors !== [], '固定ログに error 行が1件もありません(教材の不備)');

    $first = $errors[0];
    $requestIds = [];
    foreach ($errors as $error) {
        assertSame($first['event'], $error['event'], '固定ログの error 行の event が揃っていません(教材の不備)');
        foreach (['path', 'user', 'type', 'at'] as $key) {
            assertSame(
                $first['context'][$key] ?? null,
                $error['context'][$key] ?? null,
                "固定ログの error 行の {$key} が揃っていません(教材の不備)"
            );
        }
        $requestIds[] = (string) ($error['context']['request_id'] ?? '');
    }
    assertSame(count($errors), count(array_unique($requestIds)), '固定ログの error 行の request_id が重複しています(教材の不備)');

    // at(例: /var/www/practicase/packs/php/app/src/Support/Flash.php:15)から
    // source_file(packs/ 以降)と source_line(最後の : の後)を切り出す
    $at = (string) ($first['context']['at'] ?? '');
    $packsPos = strpos($at, 'packs/');
    $colonPos = strrpos($at, ':');
    assertTrue(
        $packsPos !== false && $colonPos !== false && $colonPos > $packsPos,
        '固定ログの at からファイルと行番号を特定できません(教材の不備): ' . $at
    );

    return [
        'occurred_at' => $first['time'],
        'path' => (string) ($first['context']['path'] ?? ''),
        'user' => (string) ($first['context']['user'] ?? ''),
        'event' => $first['event'],
        'source_file' => substr($at, $packsPos, $colonPos - $packsPos),
        'source_line' => (int) substr($at, $colonPos + 1),
        'impact_count' => count($errors),
    ];
}

/** reports/T-029.md からマーカー間の JSON(契約ブロック)を取り出す */
function t029Contract(): array
{
    assertTrue(is_file(T029_REPORT), T029_REPORT . ' がありません。ticket.md の「報告書の書き方」どおりに作成してください(ファイル名はこの名前で固定です)');
    $lines = preg_split('/\r?\n/', (string) file_get_contents(T029_REPORT));
    $starts = [];
    $ends = [];
    foreach ($lines as $i => $line) {
        if (trim($line) === T029_MARKER_START) {
            $starts[] = $i;
        }
        if (trim($line) === T029_MARKER_END) {
            $ends[] = $i;
        }
    }
    assertSame(1, count($starts), '開始マーカー ' . T029_MARKER_START . ' は報告書の中に1回だけ書いてください(見つかった数: ' . count($starts) . ')');
    assertSame(1, count($ends), '終了マーカー ' . T029_MARKER_END . ' は報告書の中に1回だけ書いてください(見つかった数: ' . count($ends) . ')');
    assertTrue($starts[0] < $ends[0], '開始マーカーより後に終了マーカーを置いてください');

    $json = implode("\n", array_slice($lines, $starts[0] + 1, $ends[0] - $starts[0] - 1));
    $data = json_decode($json, true);
    assertTrue(is_array($data), 'マーカーの間を JSON として読めません(カンマ・引用符・波かっこを確認してください)');

    return $data;
}

test('課題データ(固定ログ)が調査できる状態にある', function (): void {
    $facts = t029FactsFromFixedLog();
    assertTrue($facts['impact_count'] >= 1, '固定ログから対象エラーを導出できません(教材の不備)');
});

test('報告書の契約ブロックが正しい形をしている(マーカー1組・JSON・9キー・型)', function (): void {
    $contract = t029Contract();
    $expectedKeys = array_merge(T029_FACT_KEYS, T029_FREE_KEYS);
    $unknown = array_values(array_diff(array_keys($contract), $expectedKeys));
    assertSame([], $unknown, '契約に無いキーが含まれています: ' . implode(', ', $unknown) . '(9つのキーだけにしてください)');
    $missing = array_values(array_diff($expectedKeys, array_keys($contract)));
    assertSame([], $missing, '必須キーが欠けています: ' . implode(', ', $missing));
    foreach ($expectedKeys as $key) {
        if ($key === 'source_line' || $key === 'impact_count') {
            assertTrue(is_int($contract[$key]), "{$key} は整数(引用符なしの数値)で書いてください");
        } else {
            assertTrue(is_string($contract[$key]), "{$key} は文字列(\"...\" で囲む)で書いてください");
        }
    }
});

test('事実7項目が固定ログから読み取れる値と一致する', function (): void {
    $facts = t029FactsFromFixedLog();
    $contract = t029Contract();
    foreach (T029_FACT_KEYS as $key) {
        assertSame(
            $facts[$key],
            $contract[$key] ?? null,
            "{$key} が固定ログの事実と一致しません。support/spec.md の「各項目の定義」に沿って、固定ログから読み取った値をそのまま書いてください"
        );
    }
});

test('直接原因と修正方針が記入されている', function (): void {
    $contract = t029Contract();
    foreach (T029_FREE_KEYS as $key) {
        $value = $contract[$key] ?? null;
        assertTrue(
            is_string($value) && trim($value) !== '',
            "{$key} が空です。自分の言葉で1〜3文書いてください(内容の質は support/rubric.md でセルフレビューします)"
        );
    }
});
