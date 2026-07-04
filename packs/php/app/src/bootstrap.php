<?php

declare(strict_types=1);

// アプリ共通の起動処理。public/ 配下・tools・テストの全入口が最初に読み込む

date_default_timezone_set('Asia/Tokyo'); // ENV-3

// App\ 名前空間を src/ 配下へ対応させる(例: App\Support\Clock → src/Support/Clock.php)
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $path = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

require __DIR__ . '/Support/helpers.php';

// CLI(tools・テスト)ではセッションを使わない
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

// 未捕捉の例外: 詳細はログへ(調査の一次資料)、画面には内部情報を出さない(SEC-2 の思想と同じ)
if (PHP_SAPI !== 'cli') {
    set_exception_handler(function (\Throwable $e): void {
        \App\Support\Logger::error('uncaught_exception', [
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'at' => $e->getFile() . ':' . $e->getLine(),
            'url' => $_SERVER['REQUEST_URI'] ?? '-',
            'user' => $_SESSION['user_id'] ?? '-',
        ]);
        http_response_code(500);
        echo '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><title>500</title></head>'
            . '<body><h1>500 Internal Server Error</h1><p>エラーが発生しました。時間をおいて再度お試しください。</p>'
            . '<p><a href="/index.php">トップへ戻る</a></p></body></html>';
    });
}
