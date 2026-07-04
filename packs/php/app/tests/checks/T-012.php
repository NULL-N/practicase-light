<?php

declare(strict_types=1);

use App\Repository\ApplicationRepository;
use App\Repository\ProjectRepository;
use App\Service\ApplicationService;
use App\Support\Clock;

// T-012 の合格条件(F-05 の応募メッセージ境界)。修正が正しければ通る。
// 1本目は共通テスト(ApplicationServiceTest)から移動してきた境界テスト。

// ApplicationServiceTest 等と同名ヘルパ(全テスト一括実行時の二重定義を避ける)
if (!function_exists('applicationService')) {
    function applicationService(): ApplicationService
    {
        return new ApplicationService(new ApplicationRepository(), new ProjectRepository());
    }
}

test('応募メッセージは 500文字OK・501文字NG(F-05 境界)', function (): void {
    Clock::fix('2026-07-01 10:00:00');
    $pdo = freshDatabase();
    $companyId = insertCompany($pdo);
    $project1 = insertProject($pdo, $companyId, ['deadline' => '2026-07-10']);
    $project2 = insertProject($pdo, $companyId, ['deadline' => '2026-07-10']);
    $engineer = insertUser($pdo);
    $service = applicationService();

    assertSame(true, $service->apply($engineer, $project1, str_repeat('あ', 500)), '500文字ちょうどは応募できる');
    assertSame(
        ApplicationService::MSG_MESSAGE_TOO_LONG,
        $service->apply($engineer, $project2, str_repeat('あ', 501)),
        '501文字はエラー文言が返る(F-05: 文言はテストの期待値 — G-8)'
    );
    Clock::clear();
});

test('501文字の応募は DB に保存されない(エラーを返すだけでは足りない)', function (): void {
    Clock::fix('2026-07-01 10:00:00');
    $pdo = freshDatabase();
    $project = insertProject($pdo, insertCompany($pdo), ['deadline' => '2026-07-10']);
    $engineer = insertUser($pdo);

    applicationService()->apply($engineer, $project, str_repeat('あ', 501));
    $count = (int) $pdo->query('SELECT COUNT(*) AS cnt FROM applications')->fetch()['cnt'];
    assertSame(0, $count, '検証 NG の応募が applications に書き込まれてはいけない(不正データの永続化は実務で最も危険な事故のひとつ)');
    Clock::clear();
});
