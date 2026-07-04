<?php

declare(strict_types=1);

namespace App\Support;

/**
 * 現在日時の唯一の取得口(ARC-5)。
 * 業務コードは date() / new DateTime('now') を直接呼ばず、必ずここを通す。
 * テストは fix() で基準日を固定し、実行日に依存しない検証を行う。
 */
final class Clock
{
    private const TIMEZONE = 'Asia/Tokyo';

    private static ?\DateTimeImmutable $fixed = null;

    public static function now(): \DateTimeImmutable
    {
        // ARC-5 の唯一の例外: 実時刻の取得はこの1箇所だけ
        return self::$fixed ?? new \DateTimeImmutable('now', new \DateTimeZone(self::TIMEZONE));
    }

    public static function today(): \DateTimeImmutable
    {
        return self::now()->setTime(0, 0, 0);
    }

    // テスト専用: 基準日時を固定する(例: Clock::fix('2026-07-01 10:00:00'))
    public static function fix(string $datetime): void
    {
        self::$fixed = new \DateTimeImmutable($datetime, new \DateTimeZone(self::TIMEZONE));
    }

    // テスト専用: 固定を解除して実時刻に戻す
    public static function clear(): void
    {
        self::$fixed = null;
    }
}
