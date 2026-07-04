<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\UserRepository;

// F-01: ログイン判定。失敗理由(該当なし/不一致/停止中)は呼び出し側に区別させない
final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
    ) {
    }

    public function attempt(string $email, string $password): ?array
    {
        $user = $this->users->findByEmail($email);
        if ($user === null) {
            return null;
        }
        if ($user['status'] !== 'active') {
            return null; // suspended もログイン不可(F-01)
        }
        if (!password_verify($password, $user['password_hash'])) { // SEC-3
            return null;
        }

        return $user;
    }
}
