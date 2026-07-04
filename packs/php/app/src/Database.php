<?php

declare(strict_types=1);

namespace App;

final class Database
{
    private static ?\PDO $pdo = null;

    public static function pdo(): \PDO
    {
        if (self::$pdo === null) {
            $config = require __DIR__ . '/config.php';
            self::$pdo = self::connect('sqlite:' . $config['db_path']);
        }

        return self::$pdo;
    }

    public static function connect(string $dsn): \PDO
    {
        $pdo = new \PDO($dsn, null, null, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
        // SQLite は接続ごとに外部キーを有効化する必要がある(D-5)
        $pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    }

    // テストが in-memory DB を差し込むための入口(ARC-3: 画面から独立して検証できる形)
    public static function useConnection(\PDO $pdo): void
    {
        self::$pdo = $pdo;
    }
}
