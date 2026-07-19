<?php

declare(strict_types=1);

use App\Support\Clock;
use App\Support\Logger;

// Logger の書式(共通項目 + イベント固有)を決定的に検証する。
// Clock は固定時刻に、request_id は注入した生成器で固定する(ファイル IO はしない)。

test('beginRequest は注入した生成器の request_id を返し、method/path を取り込む', function (): void {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/client/application_decide.php?project_id=1&x=secret';
    $id = Logger::beginRequest(fn (): string => 'abcdef0123456789');
    assertSame('abcdef0123456789', $id);
    assertSame('abcdef0123456789', Logger::requestId());
});

test('format: 共通項目 → イベント固有 の順で1行を組み立てる', function (): void {
    $common = ['request_id' => 'abcdef0123456789', 'method' => 'GET', 'path' => '/x.php', 'user' => 'anonymous'];
    $line = Logger::format('info', 'login.failure', $common, []);
    // 日時は Clock 依存なので固定部分だけ検証する
    assertTrue(str_contains($line, '[info] login.failure request_id=abcdef0123456789 method=GET path=/x.php user=anonymous'), $line);
});

test('format: path はクエリを含まない(beginRequest がクエリ除去済みの path を作る)', function (): void {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/client/application_decide.php?project_id=1&x=secret';
    Logger::beginRequest(fn (): string => '0000000000000000');
    // common() は private のため format 経由で確認: path にクエリが出ないこと
    $ref = new ReflectionMethod(Logger::class, 'common');
    $ref->setAccessible(true);
    $common = $ref->invoke(null, []);
    assertSame('/client/application_decide.php', $common['path']);
    assertTrue(!str_contains($common['path'], '?'), 'path にクエリ文字列が混ざらない');
    assertTrue(!str_contains($common['path'], 'secret'), '任意クエリ値をログに出さない');
});

test('format: context の user は共通 user へ畳み込まれ、二重に出ない', function (): void {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/login.php';
    Logger::beginRequest(fn (): string => '1111111111111111');
    $commonRef = new ReflectionMethod(Logger::class, 'common');
    $commonRef->setAccessible(true);
    $withoutUserRef = new ReflectionMethod(Logger::class, 'withoutUser');
    $withoutUserRef->setAccessible(true);

    $context = ['user' => 5];
    $common = $commonRef->invoke(null, $context);
    $rest = $withoutUserRef->invoke(null, $context);
    assertSame('5', $common['user']);
    assertSame([], $rest);

    $line = Logger::format('info', 'login.success', $common, $rest);
    assertSame(1, substr_count($line, 'user='), 'user= は1回だけ');
    assertTrue(str_contains($line, 'user=5'));
});

test('format: uncaught_exception は共通項目 + type/message/at を持つ', function (): void {
    Clock::fix('2026-07-17 09:30:00');
    $common = ['request_id' => 'deadbeefdeadbeef', 'method' => 'POST', 'path' => '/client/application_decide.php', 'user' => '4'];
    $context = ['type' => 'TypeError', 'message' => 'boom', 'at' => '/app/x.php:10'];
    $line = Logger::format('error', 'uncaught_exception', $common, $context);
    assertSame(
        '2026-07-17 09:30:00 [error] uncaught_exception request_id=deadbeefdeadbeef method=POST path=/client/application_decide.php user=4 type=TypeError message=boom at=/app/x.php:10',
        $line
    );
    Clock::clear();
});

test('format: 値の改行はスペースへ潰し1行を保つ', function (): void {
    $common = ['request_id' => '2222222222222222', 'method' => 'GET', 'path' => '/x.php', 'user' => 'anonymous'];
    $line = Logger::format('error', 'uncaught_exception', $common, ['message' => "line1\nline2"]);
    assertTrue(!str_contains($line, "\n") || substr_count($line, "\n") === 0, '1行に収まる');
    assertTrue(str_contains($line, 'message=line1 line2'));
});

test('既定の request_id 生成は 16 桁の小文字 hex', function (): void {
    $id = Logger::beginRequest();
    assertSame(1, preg_match('/\A[0-9a-f]{16}\z/', $id), "request_id 書式: {$id}");
});
