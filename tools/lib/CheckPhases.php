<?php

declare(strict_types=1);

namespace PractiCase\Check;

require_once __DIR__ . '/CheckSupport.php';
require_once __DIR__ . '/CheckPhaseResult.php';

/**
 * check.php の判定フェーズ群。
 *
 * 各フェーズは CheckPhaseResult を返すだけで、echo しない・共有配列を触らない。
 * コマンド実行は callable で注入する(既定は passthru — 子テストの出力を
 * そのまま流す現在の挙動)。単体テストは偽の runner を注入して実コマンドなしで
 * 判定だけを検証できる。
 */

/** 既定のコマンド実行(passthru: 子プロセスの出力を直接流し、終了コードを返す) */
function passthruRunner(): callable
{
    return static function (string $command): int {
        // passthru が終了コードを設定できずに戻った場合(実行不能等)に
        // FAIL 側へ倒すための初期値。0(=PASS 扱い)へは決して倒さない
        $exitCode = 1;
        passthru($command, $exitCode);

        return $exitCode;
    };
}

/** [1/3] 共通テスト(既存機能を壊していないか) */
final class CommonTestPhase
{
    /** @param callable(string): int $runner */
    public function __construct(
        private readonly string $testsDir,
        private $runner,
    ) {
    }

    public function run(): CheckPhaseResult
    {
        $exitCode = ($this->runner)(PHP_BINARY . ' ' . escapeshellarg($this->testsDir . '/run.php'));
        if ($exitCode !== 0) {
            return new CheckPhaseResult(
                status: 'fail',
                failures: ['共通テストが失敗しています。既存の機能を壊していないか確認してください(TEST-2: 既存テストの削除・弱体化は禁止)'],
            );
        }

        return new CheckPhaseResult(status: 'pass');
    }
}

/** [2/3] 課題別テスト(課題の合格条件) */
final class TaskTestPhase
{
    /** @param callable(string): int $runner */
    public function __construct(
        private readonly string $testsDir,
        private readonly string $ticketId,
        private readonly bool $failureAsNote,
        private $runner,
    ) {
    }

    public function run(): CheckPhaseResult
    {
        $checkTest = $this->testsDir . '/checks/' . $this->ticketId . '.php';
        if (!is_file($checkTest)) {
            return new CheckPhaseResult(
                status: 'pass',
                output: ["  (この課題に自動テストはありません — support/rubric.md でセルフチェックしてください)\n"],
                metadata: ['has_test' => false, 'failed_as_note' => false],
            );
        }
        $exitCode = ($this->runner)(
            PHP_BINARY . ' ' . escapeshellarg($this->testsDir . '/run.php') . ' ' . escapeshellarg('checks/' . $this->ticketId . '.php')
        );
        if ($exitCode === 0) {
            return new CheckPhaseResult(status: 'pass', metadata: ['has_test' => true, 'failed_as_note' => false]);
        }
        if ($this->failureAsNote) {
            // 調査・報告系: FAIL は「報告対象の不具合を再現できているサイン」として情報扱い
            return new CheckPhaseResult(
                status: 'note',
                output: ["  note: この課題は調査・報告系です。課題別テストの FAIL は、報告対象の不具合を再現できているサインとして扱います。\n"],
                metadata: ['has_test' => true, 'failed_as_note' => true],
            );
        }

        return new CheckPhaseResult(
            status: 'fail',
            failures: ['課題別テストが失敗しています。チケットと仕様書(support/spec.md)を読み直してください'],
            metadata: ['has_test' => true, 'failed_as_note' => false],
        );
    }
}

/** [3/3] scope 検査(変更がチケットの範囲内か)。変更収集は callable 注入(既定は collectChangedFiles) */
final class ScopePhase
{
    /** @param list<string> $ticketScope @param callable(): (array|string) $changesProvider */
    public function __construct(
        private readonly array $ticketScope,
        private $changesProvider,
    ) {
    }

    public function run(): CheckPhaseResult
    {
        $changes = ($this->changesProvider)();
        if (is_string($changes)) {
            // git が使えない等、検査自体が実行不能(check.php が中断表示して終了する)
            return new CheckPhaseResult(status: 'error', metadata: ['message' => $changes]);
        }

        $scope = array_merge($this->ticketScope, IMPLICIT_SCOPE);
        $output = [];
        $outOfScope = [];
        if ($changes['files'] === []) {
            $output[] = "  変更ファイルはありません(ベースライン: {$changes['base']})\n";
        } else {
            $output[] = "  変更ファイル(ベースライン: {$changes['base']}、? = 未追跡の新規ファイル):\n";
            foreach ($changes['files'] as $path => $status) {
                $inScope = matchesScope($path, $scope);
                $output[] = sprintf("    %s %s%s\n", $status, $path, $inScope ? '' : '  ← scope 外');
                if (!$inScope) {
                    $outOfScope[] = $path;
                }
            }
        }
        $failures = [];
        if ($outOfScope !== []) {
            $failures[] = 'チケットの範囲外のファイルが変更されています(CHG-1)。'
                . '意図した変更なら ticket.md の scope を確認し、意図しない変更なら元に戻してください';
        }

        return new CheckPhaseResult(
            status: $failures === [] ? 'pass' : 'fail',
            output: $output,
            failures: $failures,
            metadata: ['base' => $changes['base'], 'out_of_scope' => $outOfScope],
        );
    }
}

/**
 * 提出物判定。課題別テストの結果(TaskTestPhase)を明示的に受け取る —
 * 「先行フェーズが失敗したか」を共有状態から推測しない。
 */
final class SubmissionPhase
{
    /** @param callable(string): bool $submissionChecker */
    public function __construct(
        private readonly string $ticketId,
        private readonly bool $reportRequired,
        private readonly CheckPhaseResult $taskTestResult,
        private $submissionChecker,
    ) {
    }

    public function run(): CheckPhaseResult
    {
        $hasSubmission = ($this->submissionChecker)($this->ticketId);
        $output = [];
        $failures = [];
        if ($this->reportRequired && !$hasSubmission) {
            $failures[] = "この課題は提出物が必要です。reports/ に {$this->ticketId} で始まる Markdown"
                . "(例: reports/{$this->ticketId}_report.md)を作成してください";
        }
        $failedAsNote = (bool) ($this->taskTestResult->metadata['failed_as_note'] ?? false);
        if ($failedAsNote && $hasSubmission) {
            $output[] = "  note: reports/ の提出物を確認しました。課題別テストの FAIL は報告対象として扱い、提出判定は継続します。\n";
        }

        return new CheckPhaseResult(
            status: $failures === [] ? 'pass' : 'fail',
            output: $output,
            failures: $failures,
            metadata: ['has_submission' => $hasSubmission],
        );
    }
}
