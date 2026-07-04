<?php

declare(strict_types=1);

namespace App\Support;

/**
 * アプリの動作ログ(logs/app.log)。障害調査の一次資料になる。
 * 書式は「日時 [レベル] イベント key=value ...」の1行1イベント(LOG-3: grep で絞れる形)。
 * 何を出してよいか・出してはいけないかの規約は docs/02_作業ルール/coding-rules.md の LOG 節が正。
 *
 * - CLI(テスト・tools)では書き込まない — テスト実行でログを汚さないため
 * - 書き込みの失敗でアプリを止めない(ログはベストエフォート — エラー抑制はそのための意図的な例外)
 */
final class Logger
{
    public static function info(string $event, array $context = []): void
    {
        self::write('info', $event, $context);
    }

    public static function error(string $event, array $context = []): void
    {
        self::write('error', $event, $context);
    }

    private static function write(string $level, string $event, array $context): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }
        $parts = [];
        foreach ($context as $key => $value) {
            // 1行1イベントを守る(値に改行が混ざってもログの形を壊さない)
            $parts[] = $key . '=' . str_replace(["\r", "\n"], ' ', (string) $value);
        }
        $line = sprintf(
            "%s [%s] %s%s\n",
            Clock::now()->format('Y-m-d H:i:s'),
            $level,
            $event,
            $parts === [] ? '' : ' ' . implode(' ', $parts)
        );
        @file_put_contents(self::path(), $line, FILE_APPEND | LOCK_EX);
    }

    private static function path(): string
    {
        $dir = dirname(__DIR__, 2) . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        return $dir . '/app.log';
    }
}
