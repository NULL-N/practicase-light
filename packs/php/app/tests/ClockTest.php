<?php

declare(strict_types=1);

use App\Support\Clock;

test('Clock::fix で基準日時を固定できる(ARC-5)', function (): void {
    Clock::fix('2026-07-01 10:30:00');
    assertSame('2026-07-01 10:30:00', Clock::now()->format('Y-m-d H:i:s'));
    assertSame('2026-07-01 00:00:00', Clock::today()->format('Y-m-d H:i:s'), 'today は 00:00:00');
    Clock::clear();
});

test('Clock::clear で実時刻に戻る', function (): void {
    Clock::fix('2000-01-01 00:00:00');
    Clock::clear();
    $diff = abs(Clock::now()->getTimestamp() - time());
    assertTrue($diff < 5, '実時刻との差が5秒未満');
});

test('Clock は Asia/Tokyo を返す(ENV-3)', function (): void {
    Clock::clear();
    assertSame('Asia/Tokyo', Clock::now()->getTimezone()->getName());
});

test('固定した基準日からの相対日付計算ができる(シード・締切判定の土台)', function (): void {
    Clock::fix('2026-02-28 09:00:00');
    assertSame('2026-03-01', Clock::today()->modify('+1 days')->format('Y-m-d'), 'うるう年でない2月末の翌日');
    Clock::clear();
});
