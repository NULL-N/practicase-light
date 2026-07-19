<?php

declare(strict_types=1);

/**
 * PractiCase 提出前チェック。正式な実行形式(リポジトリルートで):
 *   docker compose exec app php tools/check.php T-001
 *
 * やること:
 *   [1/3] 共通テスト   … 既存機能を壊していないか(packs/php/app/tests/ 全体)
 *   [2/3] 課題別テスト … tests/checks/<課題ID>.php があれば実行(課題の合格条件)
 *   [3/3] scope 検査   … 変更ファイル(未追跡含む)がチケットの scope 内か(CHG-1)
 *
 * 責務の分担:
 *   判定     … tools/lib/CheckPhases.php(各フェーズが構造化結果を返す)
 *   表示     … tools/lib/CheckRenderer.php(文言・順序は出力契約の一部)
 *   この本体 … フェーズを順に呼び、結果を集約して終了コードを決めるだけ
 */

if (PHP_SAPI !== 'cli') {
    exit('CLI 専用です');
}

require __DIR__ . '/lib/CheckSupport.php';
require __DIR__ . '/lib/CheckEdition.php';
require __DIR__ . '/lib/CheckRenderer.php';
require __DIR__ . '/lib/CheckPhases.php';

use function PractiCase\Check\collectChangedFiles;
use function PractiCase\Check\discoverTickets;
use function PractiCase\Check\hasReportSubmission;
use function PractiCase\Check\passthruRunner;

use PractiCase\Check\CheckEdition;
use PractiCase\Check\CheckRenderer;
use PractiCase\Check\CommonTestPhase;
use PractiCase\Check\ScopePhase;
use PractiCase\Check\SubmissionPhase;
use PractiCase\Check\TaskTestPhase;

chdir(dirname(__DIR__)); // リポジトリルート基準で動く(git・パスとも)

const TICKETS_DIR = 'packs/php/tickets';
const TESTS_DIR = 'packs/php/app/tests';

$renderer = new CheckRenderer();

// 版(edition)設定 — 表示形と ID 単位の提出要件だけが設定化されている。
// check.php 自身はどの版で動いているかを判定しない(分岐は設定値に対してのみ)
try {
    $edition = CheckEdition::load(__DIR__ . '/check-edition.php');
} catch (Throwable $e) {
    $renderer->fatal($e->getMessage());
    exit(1);
}

$discovered = discoverTickets(TICKETS_DIR);
foreach ($discovered['warnings'] as $warning) {
    $renderer->warning($warning);
}

// 課題IDの大文字小文字は問わない(t-001 / TUTORIAL でも可)。tickets 側のキー表記に解決する
$requestedId = trim((string) ($argv[1] ?? ''));
foreach (array_keys($discovered['tickets']) as $ticketKey) {
    if (strcasecmp($ticketKey, $requestedId) === 0) {
        $requestedId = $ticketKey;
        break;
    }
}
$ticket = $discovered['tickets'][$requestedId] ?? null;

if ($ticket === null) {
    $renderer->usage($requestedId, $discovered['tickets'], $edition);
    exit(1);
}

$renderer->header($requestedId, $ticket);

// 提出物(reports/)が必須の type。investigation は、加えて課題別テストの合格も必須(調査だけでなく修正まで)
$reportRequiredTypes = ['bug-report', 'integration-test', 'review', 'rework', 'design-review', 'investigation', 'release', 'handover'];
// type では括れない ID 単位の個別要件は edition 設定側(tools/check-edition.php)が持つ
$reportRequiredIds = $edition->reportRequiredIds;
// 課題別テストの FAIL を「報告対象の不具合を再現できているサイン」として扱う type(調査・報告が本体の課題)。
// track: dev 限定 — 「まだ直っていないコードの不具合を再現している」という前提の type だけが対象。
// track: design の課題が同じ type 名を共有しても、書類の構造チェック失敗は素直に FAIL 扱いにする
// (「失敗=不具合の証拠」という意味づけが成立しないため。実測: design-review は現状 track:design
// 専用の課題別テストファイルを持たないため無影響)
$testFailureAsNoteTypes = ['bug-report', 'integration-test', 'review', 'rework', 'design-review'];
$isReportRequired = in_array((string) ($ticket['type'] ?? ''), $reportRequiredTypes, true)
    || in_array($requestedId, $reportRequiredIds, true);
$isTestFailureNote = ((string) ($ticket['track'] ?? 'dev')) === 'dev'
    && in_array((string) ($ticket['type'] ?? ''), $testFailureAsNoteTypes, true);

$runner = passthruRunner();

// [1/3] 共通テスト(見出し → 子テスト出力が passthru で流れる → 結果)
$renderer->commonTestHeading();
$commonResult = (new CommonTestPhase(TESTS_DIR, $runner))->run();
$renderer->phaseLines($commonResult->output);

// [2/3] 課題別テスト
$renderer->taskTestHeading($requestedId);
$taskResult = (new TaskTestPhase(TESTS_DIR, $requestedId, $isTestFailureNote, $runner))->run();
$renderer->phaseLines($taskResult->output);

// [3/3] scope 検査
$renderer->scopeHeading();
$scopeResult = (new ScopePhase((array) ($ticket['scope'] ?? []), collectChangedFiles(...)))->run();
if ($scopeResult->status === 'error') {
    $renderer->scopeError((string) $scopeResult->metadata['message']);
    exit(1);
}
$renderer->phaseLines($scopeResult->output);

// 提出物判定(課題別テストの結果を明示的に受け取る)
$submissionResult = (new SubmissionPhase($requestedId, $isReportRequired, $taskResult, hasReportSubmission(...)))->run();
$renderer->phaseLines($submissionResult->output);

// 結果の集約(フェーズ順 = 表示順 = 従来の failure 追加順)
$failures = array_merge(
    $commonResult->failures,
    $taskResult->failures,
    $scopeResult->failures,
    $submissionResult->failures,
);
if ($failures === []) {
    $renderer->resultPass();
    exit(0);
}
$renderer->resultFail($failures);
exit(1);
