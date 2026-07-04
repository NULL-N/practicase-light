<?php

declare(strict_types=1);

use App\Service\ProjectValidator;
use App\Support\Clock;

// T-001 の合格条件(F-02 の検証ルール表)。修正が正しければすべて通る

test('hourly_rate は 1〜100000 の整数のみ(0/-5/小数/文字/先頭ゼロ/100001 は拒否)', function (): void {
    Clock::fix('2026-07-01 10:00:00');
    $validator = new ProjectValidator();
    foreach (['0', '-5', '1.5', 'abc', '05', '100001', ''] as $bad) {
        $result = $validator->validate(array_merge(validProjectInput(), ['hourly_rate' => $bad]));
        assertSame(ProjectValidator::MSG_HOURLY_RATE_INVALID, $result['errors']['hourly_rate'] ?? '', "hourly_rate={$bad}");
    }
    foreach (['1', '100000'] as $good) {
        $result = $validator->validate(array_merge(validProjectInput(), ['hourly_rate' => $good]));
        assertTrue(!isset($result['errors']['hourly_rate']), "hourly_rate={$good} はOK");
    }
    Clock::clear();
});

test('capacity は 1〜100 の整数のみ(0/101 は拒否)', function (): void {
    Clock::fix('2026-07-01 10:00:00');
    $validator = new ProjectValidator();
    foreach (['0', '101'] as $bad) {
        $result = $validator->validate(array_merge(validProjectInput(), ['capacity' => $bad]));
        assertSame(ProjectValidator::MSG_CAPACITY_INVALID, $result['errors']['capacity'] ?? '', "capacity={$bad}");
    }
    foreach (['1', '100'] as $good) {
        $result = $validator->validate(array_merge(validProjectInput(), ['capacity' => $good]));
        assertTrue(!isset($result['errors']['capacity']), "capacity={$good} はOK");
    }
    Clock::clear();
});

test('deadline は本日以降のみ(昨日NG・今日OK・実在しない日付NG・形式不正NG)', function (): void {
    Clock::fix('2026-07-01 10:00:00');
    $validator = new ProjectValidator();
    foreach (['2026-06-30', '2026-02-30', '2026/07/10', '20260710'] as $bad) {
        $result = $validator->validate(array_merge(validProjectInput(), ['deadline' => $bad]));
        assertSame(ProjectValidator::MSG_DEADLINE_INVALID, $result['errors']['deadline'] ?? '', "deadline={$bad}");
    }
    $today = $validator->validate(array_merge(validProjectInput(), ['deadline' => '2026-07-01', 'work_start_on' => '2026-07-01']));
    assertTrue(!isset($today['errors']['deadline']), '本日はOK(G-2)');
    Clock::clear();
});
