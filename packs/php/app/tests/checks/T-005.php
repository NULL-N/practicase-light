<?php

declare(strict_types=1);

use App\Support\Clock;

// T-005 の合格条件(support/spec.md の表示ルール)。実装が正しければすべて通る

test('RemainingDays::label — 締切が先の日付なら「残りN日」(spec 表示ルール)', function (): void {
    if (!class_exists(\App\Support\RemainingDays::class)) {
        throw new AssertionError('App\Support\RemainingDays が見つかりません(チケットの設計指定を確認)');
    }
    Clock::fix('2026-07-01 10:00:00');
    assertSame('残り9日', \App\Support\RemainingDays::label('2026-07-10'));
    assertSame('残り1日', \App\Support\RemainingDays::label('2026-07-02'), '翌日は残り1日');
    Clock::clear();
});

test('RemainingDays::label — 本日締切と締切済みの境界', function (): void {
    if (!class_exists(\App\Support\RemainingDays::class)) {
        throw new AssertionError('App\Support\RemainingDays が見つかりません');
    }
    Clock::fix('2026-07-01 23:00:00');
    assertSame('本日締切', \App\Support\RemainingDays::label('2026-07-01'), '当日は時刻に関係なく本日締切');
    assertSame('締切済み', \App\Support\RemainingDays::label('2026-06-30'));
    Clock::clear();
});
