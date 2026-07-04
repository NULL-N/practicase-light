<?php

declare(strict_types=1);

namespace App\Support;

use App\Repository\UserRepository;

final class Auth
{
    private static ?array $cachedUser = null;

    public static function login(array $user): void
    {
        session_regenerate_id(true); // SEC-4
        $_SESSION['user_id'] = $user['id'];
        self::$cachedUser = null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        self::$cachedUser = null;
    }

    /**
     * ログイン中ユーザー。F-01: 毎リクエスト DB から引き直し、
     * suspended になっていたら次のリクエストで強制ログアウトする。
     */
    public static function user(): ?array
    {
        $id = $_SESSION['user_id'] ?? null;
        if ($id === null) {
            return null;
        }
        if (self::$cachedUser !== null && self::$cachedUser['id'] === (int) $id) {
            return self::$cachedUser;
        }

        $user = (new UserRepository())->findById((int) $id);
        if ($user === null || $user['status'] !== 'active') {
            self::logout();

            return null;
        }
        self::$cachedUser = $user;

        return $user;
    }

    public static function requireLogin(): array
    {
        $user = self::user();
        if ($user === null) {
            redirect('/login.php'); // G-3
        }

        return $user;
    }

    // SEC-6 / G-4: ロール外は 404(存在を知らせない)
    public static function requireRole(array $user, string ...$roles): void
    {
        if (!in_array($user['role'], $roles, true)) {
            abort404();
        }
    }
}
