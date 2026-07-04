<?php

declare(strict_types=1);

use App\Repository\ApplicationRepository;
use App\Repository\ProjectRepository;
use App\Service\ApplicationService;
use App\Support\Clock;

// tests/checks 配下にも同名ヘルパがある。全テスト一括実行時の二重定義を避ける
if (!function_exists('applicationService')) {
    function applicationService(): ApplicationService
    {
        return new ApplicationService(new ApplicationRepository(), new ProjectRepository());
    }
}

// ---- F-05 応募(A-2〜A-4、G-2)----

test('条件を満たす応募は成功し applied で記録される(F-05)', function (): void {
    Clock::fix('2026-07-01 10:00:00');
    $pdo = freshDatabase();
    $project = insertProject($pdo, insertCompany($pdo), ['deadline' => '2026-07-10']);
    $engineer = insertUser($pdo);

    assertSame(true, applicationService()->apply($engineer, $project, 'よろしくお願いします'));
    $saved = (new ApplicationRepository())->listByProject($project['id']);
    assertSame(1, count($saved));
    assertSame('applied', $saved[0]['status']);
    assertSame('2026-07-01 10:00:00', $saved[0]['applied_at']);
    Clock::clear();
});

test('open でない案件には応募できない(A-2)', function (): void {
    Clock::fix('2026-07-01 10:00:00');
    $pdo = freshDatabase();
    $project = insertProject($pdo, insertCompany($pdo), ['status' => 'closed']);
    $engineer = insertUser($pdo);

    assertSame(ApplicationService::MSG_NOT_OPEN, applicationService()->apply($engineer, $project, ''));
    Clock::clear();
});

test('締切当日は応募できる・翌日はできない(A-3 / G-2 境界)', function (): void {
    $pdo = freshDatabase();
    $project = insertProject($pdo, insertCompany($pdo), ['deadline' => '2026-07-10']);
    $engineer = insertUser($pdo);
    $service = applicationService();

    Clock::fix('2026-07-10 23:59:59');
    assertNull($service->applyBlockedReason($engineer, $project), '締切日 23:59:59 は応募可');

    Clock::fix('2026-07-11 00:00:00');
    assertSame(ApplicationService::MSG_DEADLINE_PASSED, $service->applyBlockedReason($engineer, $project), '翌日 00:00:00 は締切超過');
    Clock::clear();
});

test('二重応募はできない。withdrawn 後の再応募もできない(A-4)', function (): void {
    Clock::fix('2026-07-01 10:00:00');
    $pdo = freshDatabase();
    $companyId = insertCompany($pdo);
    $project1 = insertProject($pdo, $companyId, ['deadline' => '2026-07-10']);
    $project2 = insertProject($pdo, $companyId, ['deadline' => '2026-07-10']);
    $engineer = insertUser($pdo);
    insertApplication($pdo, $project1['id'], $engineer['id'], ['status' => 'applied']);
    insertApplication($pdo, $project2['id'], $engineer['id'], ['status' => 'withdrawn']);

    $service = applicationService();
    assertSame(ApplicationService::MSG_ALREADY_APPLIED, $service->apply($engineer, $project1, ''));
    assertSame(ApplicationService::MSG_ALREADY_APPLIED, $service->apply($engineer, $project2, ''), 'withdrawn も応募履歴に含む');
    Clock::clear();
});

// message の 500文字境界テストは tests/checks/T-012.php へ移動(T-012 の合格条件になったため)

// ---- F-05 取り下げ ----

test('本人の applied は取り下げでき、decided_at が記録される', function (): void {
    Clock::fix('2026-07-02 12:00:00');
    $pdo = freshDatabase();
    $project = insertProject($pdo, insertCompany($pdo));
    $engineer = insertUser($pdo);
    $application = insertApplication($pdo, $project['id'], $engineer['id']);

    assertSame(true, applicationService()->withdraw($engineer, $application['id']));
    $saved = (new ApplicationRepository())->findById($application['id']);
    assertSame('withdrawn', $saved['status']);
    assertSame('2026-07-02 12:00:00', $saved['decided_at']);
    Clock::clear();
});

test('applied 以外は取り下げできない(F-05)', function (): void {
    $pdo = freshDatabase();
    $project = insertProject($pdo, insertCompany($pdo));
    $engineer = insertUser($pdo);
    $application = insertApplication($pdo, $project['id'], $engineer['id'], ['status' => 'accepted']);

    assertSame(ApplicationService::MSG_CANNOT_WITHDRAW, applicationService()->withdraw($engineer, $application['id']));
});

test('他人の応募の取り下げは null(=404。G-4)', function (): void {
    $pdo = freshDatabase();
    $project = insertProject($pdo, insertCompany($pdo));
    $owner = insertUser($pdo);
    $other = insertUser($pdo);
    $application = insertApplication($pdo, $project['id'], $owner['id']);

    assertNull(applicationService()->withdraw($other, $application['id']));
});

// ---- F-07 承認・却下(D-1〜D-3)----

test('承認は accepted + decided_at、却下は rejected になる(F-07)', function (): void {
    Clock::fix('2026-07-03 15:00:00');
    $pdo = freshDatabase();
    $companyId = insertCompany($pdo);
    $client = insertUser($pdo, ['role' => 'client', 'company_id' => $companyId]);
    $project = insertProject($pdo, $companyId, ['capacity' => 2]);
    $a1 = insertApplication($pdo, $project['id'], insertUser($pdo)['id']);
    $a2 = insertApplication($pdo, $project['id'], insertUser($pdo)['id']);

    $service = applicationService();
    assertSame(true, $service->accept($client, $a1['id']));
    assertSame(true, $service->reject($client, $a2['id']));
    $repository = new ApplicationRepository();
    assertSame('accepted', $repository->findById($a1['id'])['status']);
    assertSame('2026-07-03 15:00:00', $repository->findById($a1['id'])['decided_at']);
    assertSame('rejected', $repository->findById($a2['id'])['status']);
    Clock::clear();
});

test('他社案件への応募の承認/却下は null(D-1 → 404)', function (): void {
    $pdo = freshDatabase();
    $mine = insertCompany($pdo, '自社');
    $other = insertCompany($pdo, '他社');
    $client = insertUser($pdo, ['role' => 'client', 'company_id' => $mine]);
    $project = insertProject($pdo, $other);
    $application = insertApplication($pdo, $project['id'], insertUser($pdo)['id']);

    $service = applicationService();
    assertNull($service->accept($client, $application['id']));
    assertNull($service->reject($client, $application['id']));
});

test('applied 以外の承認/却下はできない(D-2)', function (): void {
    $pdo = freshDatabase();
    $companyId = insertCompany($pdo);
    $client = insertUser($pdo, ['role' => 'client', 'company_id' => $companyId]);
    $project = insertProject($pdo, $companyId);
    $application = insertApplication($pdo, $project['id'], insertUser($pdo)['id'], ['status' => 'accepted']);

    assertSame(ApplicationService::MSG_ALREADY_DECIDED, applicationService()->accept($client, $application['id']));
});

test('承認数が募集人数に達したら以後の承認は拒否・却下は可能(D-3 境界)', function (): void {
    Clock::fix('2026-07-03 15:00:00');
    $pdo = freshDatabase();
    $companyId = insertCompany($pdo);
    $client = insertUser($pdo, ['role' => 'client', 'company_id' => $companyId]);
    $project = insertProject($pdo, $companyId, ['capacity' => 1]);
    $a1 = insertApplication($pdo, $project['id'], insertUser($pdo)['id']);
    $a2 = insertApplication($pdo, $project['id'], insertUser($pdo)['id']);

    $service = applicationService();
    assertSame(true, $service->accept($client, $a1['id']), '1人目は承認できる(定員1)');
    assertSame(ApplicationService::MSG_CAPACITY_FULL, $service->accept($client, $a2['id']), '2人目は上限');
    assertSame(true, $service->reject($client, $a2['id']), '上限後も却下はできる');
    Clock::clear();
});

test('closed 案件でも applied の承認はできる(F-07: 掲載終了≠選考終了)', function (): void {
    Clock::fix('2026-07-03 15:00:00');
    $pdo = freshDatabase();
    $companyId = insertCompany($pdo);
    $client = insertUser($pdo, ['role' => 'client', 'company_id' => $companyId]);
    $project = insertProject($pdo, $companyId, ['status' => 'closed']);
    $application = insertApplication($pdo, $project['id'], insertUser($pdo)['id']);

    assertSame(true, applicationService()->accept($client, $application['id']));
    Clock::clear();
});

// ---- F-06 応募者一覧 ----

test('listByProject は全状態を応募日時の新しい順で返す(F-06)', function (): void {
    $pdo = freshDatabase();
    $project = insertProject($pdo, insertCompany($pdo));
    insertApplication($pdo, $project['id'], insertUser($pdo)['id'], ['status' => 'applied', 'applied_at' => '2026-07-01 09:00:00']);
    insertApplication($pdo, $project['id'], insertUser($pdo)['id'], ['status' => 'accepted', 'applied_at' => '2026-07-02 09:00:00']);
    insertApplication($pdo, $project['id'], insertUser($pdo)['id'], ['status' => 'rejected', 'applied_at' => '2026-07-03 09:00:00']);
    insertApplication($pdo, $project['id'], insertUser($pdo)['id'], ['status' => 'withdrawn', 'applied_at' => '2026-07-04 09:00:00']);

    $list = (new ApplicationRepository())->listByProject($project['id']);
    assertSame(4, count($list), '4状態すべて含む');
    assertSame('withdrawn', $list[0]['status'], '新しい順');
    assertSame('applied', $list[3]['status']);
});
