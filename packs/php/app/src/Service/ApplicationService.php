<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ApplicationRepository;
use App\Repository\ProjectRepository;
use App\Support\Clock;

/**
 * F-05(応募・取り下げ)/ F-07(承認・却下)の業務ルール。
 * 文言の正は docs/01_設計資料/features.md(FLOW-3: 定数を経由する)。
 *
 * 戻り値の共通ルール:
 *   null   = 対象が存在しない・他社/他人のもの → 呼び出し側は abort404()(G-4)
 *   string = 業務ルール違反のエラーメッセージ
 *   true   = 成功
 */
final class ApplicationService
{
    public const MSG_NOT_OPEN = 'この案件は現在応募を受け付けていません';
    public const MSG_DEADLINE_PASSED = 'この案件は応募を締め切りました';
    public const MSG_ALREADY_APPLIED = 'この案件にはすでに応募しています';
    public const MSG_MESSAGE_TOO_LONG = '応募メッセージは500文字以内で入力してください';
    public const MSG_CANNOT_WITHDRAW = 'この応募は取り下げできません';
    public const MSG_ALREADY_DECIDED = 'この応募はすでに確定しています';
    public const MSG_CAPACITY_FULL = '募集人数の上限に達しているため承認できません';

    public function __construct(
        private readonly ApplicationRepository $applications,
        private readonly ProjectRepository $projects,
    ) {
    }

    /**
     * F-05 A-2〜A-4: 応募可否。S-04 のフォーム出し分けと応募処理の両方が使う。
     *
     * @return ?string null=応募できる / string=できない理由(表示文言)
     */
    public function applyBlockedReason(array $engineer, array $project): ?string
    {
        if ($project['status'] !== 'open') {
            return self::MSG_NOT_OPEN; // A-2
        }
        // A-3 / G-2: 締切日当日の 23:59:59 までは応募できる
        if ($project['deadline'] < Clock::today()->format('Y-m-d')) {
            return self::MSG_DEADLINE_PASSED;
        }
        if ($this->applications->hasAppliedEver((int) $project['id'], (int) $engineer['id'])) {
            return self::MSG_ALREADY_APPLIED; // A-4(withdrawn 含む)
        }

        return null;
    }

    public function apply(array $engineer, array $project, string $message): true|string
    {
        $blocked = $this->applyBlockedReason($engineer, $project);
        if ($blocked !== null) {
            return $blocked;
        }

        $this->applications->create(
            (int) $project['id'],
            (int) $engineer['id'],
            $message,
            Clock::now()->format('Y-m-d H:i:s')
        );

        return true;
    }

    // F-05: 取り下げ。本人の applied のみ
    public function withdraw(array $engineer, int $applicationId): true|string|null
    {
        $application = $this->applications->findById($applicationId);
        if ($application === null || (int) $application['engineer_id'] !== (int) $engineer['id']) {
            return null; // G-4: 他人の応募は存在も知らせない
        }
        if ($application['status'] !== 'applied') {
            return self::MSG_CANNOT_WITHDRAW;
        }

        $this->applications->decide($applicationId, 'withdrawn', Clock::now()->format('Y-m-d H:i:s'));

        return true;
    }

    // F-07: 承認(D-1〜D-3)
    public function accept(array $clientUser, int $applicationId): true|string|null
    {
        $application = $this->findOwnApplication($clientUser, $applicationId);
        if ($application === null) {
            return null; // D-1
        }
        if ($application['status'] !== 'applied') {
            return self::MSG_ALREADY_DECIDED; // D-2
        }
        $project = $this->projects->findById((int) $application['project_id']);
        // D-3: 処理時点の DB の accepted 数で判定する
        if ($this->applications->countAccepted((int) $project['id']) >= (int) $project['capacity']) {
            return self::MSG_CAPACITY_FULL;
        }

        $this->applications->decide($applicationId, 'accepted', Clock::now()->format('Y-m-d H:i:s'));

        return true;
    }

    // F-07: 却下(D-1〜D-2)
    public function reject(array $clientUser, int $applicationId): true|string|null
    {
        $application = $this->findOwnApplication($clientUser, $applicationId);
        if ($application === null) {
            return null;
        }
        if ($application['status'] !== 'applied') {
            return self::MSG_ALREADY_DECIDED;
        }

        $this->applications->decide($applicationId, 'rejected', Clock::now()->format('Y-m-d H:i:s'));

        return true;
    }

    // D-1 / SEC-6: 応募 → 案件 → 企業の所有チェック
    private function findOwnApplication(array $clientUser, int $applicationId): ?array
    {
        $application = $this->applications->findById($applicationId);
        if ($application === null) {
            return null;
        }
        $project = $this->projects->findById((int) $application['project_id']);
        if ($project === null || (int) $project['company_id'] !== (int) $clientUser['company_id']) {
            return null;
        }

        return $application;
    }
}
