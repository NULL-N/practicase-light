<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\UserRepository;
use App\Support\Clock;

// F-09: ユーザー管理(停止・再開)
final class AdminService
{
    public const MSG_CANNOT_SUSPEND_SELF = '自分自身は停止できません';

    public function __construct(
        private readonly UserRepository $users,
    ) {
    }

    /** @return true|string|null null=対象なし(404) / string=エラー文言 / true=成功 */
    public function changeUserStatus(array $admin, int $targetUserId, string $status): true|string|null
    {
        if (!in_array($status, ['active', 'suspended'], true)) {
            return null;
        }
        $target = $this->users->findById($targetUserId);
        if ($target === null) {
            return null;
        }
        if ($status === 'suspended' && (int) $target['id'] === (int) $admin['id']) {
            return self::MSG_CANNOT_SUSPEND_SELF; // F-09
        }

        $this->users->updateStatus($targetUserId, $status, Clock::now()->format('Y-m-d H:i:s'));

        return true;
    }
}
