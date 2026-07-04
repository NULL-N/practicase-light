<?php

declare(strict_types=1);

use App\Repository\ProjectRepository;

// T-002 の合格条件(F-03 のリモート可フィルタ)。修正が正しければ通る

test('リモート可のみ ON は is_remote=1 だけ、OFF は両方出す(F-03)', function (): void {
    $pdo = freshDatabase();
    $companyId = insertCompany($pdo);
    insertProject($pdo, $companyId, ['title' => 'リモート可案件', 'is_remote' => 1, 'deadline' => '2026-07-10']);
    insertProject($pdo, $companyId, ['title' => '出社案件', 'is_remote' => 0, 'deadline' => '2026-07-10']);

    $repository = new ProjectRepository();
    $on = $repository->searchOpen('', true, '2026-07-01');
    assertSame(1, count($on), 'ON のとき リモート可のみ');
    assertSame('リモート可案件', $on[0]['title']);
    assertSame(2, count($repository->searchOpen('', false, '2026-07-01')), 'OFF は絞り込まない');
});
