<?php

declare(strict_types=1);

namespace App\Support;

/**
 * アプリの動作ログ(packs/php/app/logs/app.log)。障害調査の一次資料になる。
 * 書式は「日時 [レベル] イベント request_id=.. method=.. path=.. user=.. key=value ...」の
 * 1行1イベント(LOG-3: grep で絞れる形)。
 * 何を出してよいか・出してはいけないかの規約は docs/02_作業ルール/coding-rules.md の LOG 節が正。
 *
 * 共通項目(全イベントに自動で付く): request_id / method / path / user。
 *   - request_id … HTTP リクエスト単位の 16 桁小文字 hex(bootstrap で1回だけ生成)
 *   - path       … REQUEST_URI からクエリ文字列を除いたもの(?以降を出さない)
 *   - user       … 認証済みならセッションの user_id、未認証は anonymous
 * 呼び出し側が context に 'user' を渡した場合はそれを共通 user として採用する
 * (login.success 等、セッション確定前に本人を記録したいイベント向け)。
 *
 * - CLI(テスト・tools)では書き込まない — テスト実行でログを汚さないため
 * - 書き込みの失敗でアプリを止めない(ログはベストエフォート — エラー抑制はそのための意図的な例外)
 * - 秘密値・Cookie・認証情報・任意のクエリ値は出さない(LOG-2。path はクエリ除去済み)
 */
final class Logger
{
    private static ?string $requestId = null;
    private static string $method = '-';
    private static string $path = '-';

    /**
     * リクエスト開始時に1回だけ呼ぶ(bootstrap)。request_id を確定し、method / path を取り込む。
     * request_id 生成器は注入可能(単体テストを決定的にするため)。既定は 16 桁小文字 hex。
     *
     * @param callable(): string|null $idGenerator
     */
    public static function beginRequest(?callable $idGenerator = null): string
    {
        $generator = $idGenerator ?? static fn (): string => bin2hex(random_bytes(8));
        self::$requestId = $generator();
        self::$method = (string) ($_SERVER['REQUEST_METHOD'] ?? '-');
        self::$path = self::stripQuery((string) ($_SERVER['REQUEST_URI'] ?? '-'));

        return self::$requestId;
    }

    public static function requestId(): ?string
    {
        return self::$requestId;
    }

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
        @file_put_contents(
            self::path(),
            self::format($level, $event, self::common($context), self::withoutUser($context)) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }

    /**
     * 1行を組み立てる(純粋関数 — ファイル IO なし。単体テストはここを検証する)。
     * 並びは 日時 [レベル] イベント <共通項目> <イベント固有 context>。
     *
     * @param array<string, scalar> $common  request_id / method / path / user(この順)
     * @param array<string, scalar> $context イベント固有(user は含めない)
     */
    public static function format(string $level, string $event, array $common, array $context): string
    {
        $parts = [];
        foreach (array_merge($common, $context) as $key => $value) {
            // 1行1イベントを守る(値に改行が混ざってもログの形を壊さない)
            $parts[] = $key . '=' . str_replace(["\r", "\n"], ' ', (string) $value);
        }

        return sprintf(
            '%s [%s] %s%s',
            Clock::now()->format('Y-m-d H:i:s'),
            $level,
            $event,
            $parts === [] ? '' : ' ' . implode(' ', $parts)
        );
    }

    /**
     * 共通項目(request_id / method / path / user)を組み立てる。
     * user は context に指定があればそれを、無ければセッション(未認証は anonymous)を採る。
     *
     * @param array<string, scalar> $context
     * @return array<string, string>
     */
    private static function common(array $context): array
    {
        return [
            'request_id' => self::$requestId ?? '-',
            'method' => self::$method,
            'path' => self::$path,
            'user' => array_key_exists('user', $context) ? (string) $context['user'] : self::sessionUser(),
        ];
    }

    /**
     * @param array<string, scalar> $context
     * @return array<string, scalar>
     */
    private static function withoutUser(array $context): array
    {
        unset($context['user']);

        return $context;
    }

    private static function sessionUser(): string
    {
        return isset($_SESSION['user_id']) ? (string) $_SESSION['user_id'] : 'anonymous';
    }

    private static function stripQuery(string $uri): string
    {
        return explode('?', $uri, 2)[0];
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
