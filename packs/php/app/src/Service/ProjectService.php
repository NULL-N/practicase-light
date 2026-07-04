<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ProjectRepository;
use App\Support\Clock;

final class ProjectService
{
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly ProjectValidator $validator,
    ) {
    }

    /**
     * F-02: 案件登録。company_id は入力からではなく、
     * 必ずログイン中 client の所属企業を使う(改竄防止)。
     *
     * @return array{errors: array<string, string>, id: ?int}
     */
    public function register(array $clientUser, array $input): array
    {
        $result = $this->validator->validate($input);
        if ($result['errors'] !== []) {
            return ['errors' => $result['errors'], 'id' => null];
        }

        $id = $this->projects->create(
            (int) $clientUser['company_id'],
            $result['values'],
            Clock::now()->format('Y-m-d H:i:s')
        );

        return ['errors' => [], 'id' => $id];
    }

    /**
     * S-11: 掲載終了(open → closed)。自社案件のみ。
     *
     * @return bool true=成功 / false=対象なし・他社(呼び出し側は 404 にする。G-4)
     */
    public function close(array $clientUser, int $projectId): bool
    {
        $project = $this->projects->findById($projectId);
        if ($project === null || (int) $project['company_id'] !== (int) $clientUser['company_id']) {
            return false;
        }

        $this->projects->close($projectId, Clock::now()->format('Y-m-d H:i:s'));

        return true;
    }
}
