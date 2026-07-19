<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../tools/lib/CheckPhases.php';

use PractiCase\Check\CheckPhaseResult;
use PractiCase\Check\CommonTestPhase;
use PractiCase\Check\ScopePhase;
use PractiCase\Check\SubmissionPhase;
use PractiCase\Check\TaskTestPhase;

// フェーズは echo せず構造化結果を返す。runner / provider / checker を注入し、
// 実コマンド・実 git なしで判定だけを検証する(check.php の子プロセス起動はしない)。

/** 固定の終了コードを返し、受け取ったコマンドを記録する偽 runner */
function fakeRunner(int $exitCode, array &$commands): callable
{
    return static function (string $command) use ($exitCode, &$commands): int {
        $commands[] = $command;

        return $exitCode;
    };
}

// ---- CommonTestPhase ----

test('CommonTestPhase: 終了コード0で pass・failures 空', function (): void {
    $commands = [];
    $result = (new CommonTestPhase(__DIR__, fakeRunner(0, $commands)))->run();
    assertSame('pass', $result->status);
    assertSame([], $result->failures);
    assertSame([], $result->output);
    assertTrue(str_contains($commands[0], 'run.php'), 'run.php を起動していること');
});

test('CommonTestPhase: 非0で fail・従来の文言', function (): void {
    $commands = [];
    $result = (new CommonTestPhase(__DIR__, fakeRunner(1, $commands)))->run();
    assertSame('fail', $result->status);
    assertSame(
        ['共通テストが失敗しています。既存の機能を壊していないか確認してください(TEST-2: 既存テストの削除・弱体化は禁止)'],
        $result->failures
    );
});

// ---- TaskTestPhase ----

test('TaskTestPhase: 課題別テストが無い課題は pass+案内行(runner は呼ばれない)', function (): void {
    $commands = [];
    $result = (new TaskTestPhase(__DIR__, 'T-999', false, fakeRunner(1, $commands)))->run();
    assertSame('pass', $result->status);
    assertSame(
        ["  (この課題に自動テストはありません — support/rubric.md でセルフチェックしてください)\n"],
        $result->output
    );
    assertSame(false, $result->metadata['has_test']);
    assertSame(false, $result->metadata['failed_as_note']);
    assertSame([], $commands, '存在しない課題別テストで runner を呼ばないこと');
});

test('TaskTestPhase: テストありで終了コード0は pass', function (): void {
    $commands = [];
    $result = (new TaskTestPhase(__DIR__, 'T-001', false, fakeRunner(0, $commands)))->run();
    assertSame('pass', $result->status);
    assertSame(true, $result->metadata['has_test']);
    assertTrue(str_contains($commands[0], 'checks/T-001.php'), '課題別テストのファイルを指定していること');
});

test('TaskTestPhase: FAIL(note 対象外)は fail・従来の文言', function (): void {
    $commands = [];
    $result = (new TaskTestPhase(__DIR__, 'T-001', false, fakeRunner(1, $commands)))->run();
    assertSame('fail', $result->status);
    assertSame(
        ['課題別テストが失敗しています。チケットと仕様書(support/spec.md)を読み直してください'],
        $result->failures
    );
    assertSame(false, $result->metadata['failed_as_note']);
});

test('TaskTestPhase: FAIL(調査・報告系)は note・failures 空・メタデータで明示', function (): void {
    $commands = [];
    $result = (new TaskTestPhase(__DIR__, 'T-001', true, fakeRunner(1, $commands)))->run();
    assertSame('note', $result->status);
    assertSame([], $result->failures);
    assertSame(
        ["  note: この課題は調査・報告系です。課題別テストの FAIL は、報告対象の不具合を再現できているサインとして扱います。\n"],
        $result->output
    );
    assertSame(true, $result->metadata['failed_as_note']);
});

// ---- ScopePhase ----

test('ScopePhase: 変更収集が文字列(git 不可)なら error とメッセージ', function (): void {
    $result = (new ScopePhase([], static fn (): string => 'ここは git リポジトリではありません。'))->run();
    assertSame('error', $result->status);
    assertSame('ここは git リポジトリではありません。', $result->metadata['message']);
});

test('ScopePhase: 変更ゼロは pass と「変更ファイルはありません」', function (): void {
    $result = (new ScopePhase([], static fn (): array => ['files' => [], 'base' => 'main']))->run();
    assertSame('pass', $result->status);
    assertSame(["  変更ファイルはありません(ベースライン: main)\n"], $result->output);
    assertSame([], $result->metadata['out_of_scope']);
});

test('ScopePhase: scope 内・IMPLICIT_SCOPE(reports/ 等)・scope 外を判別し行を組み立てる', function (): void {
    $provider = static fn (): array => [
        'files' => [
            'memo.txt' => '?',
            'packs/php/app/public/index.php' => 'M',
            'reports/T-000_setup_report.md' => '?',
        ],
        'base' => 'main',
    ];
    $result = (new ScopePhase(['packs/php/app/**'], $provider))->run();
    assertSame('fail', $result->status);
    assertSame(
        [
            "  変更ファイル(ベースライン: main、? = 未追跡の新規ファイル):\n",
            "    ? memo.txt  ← scope 外\n",
            "    M packs/php/app/public/index.php\n",
            "    ? reports/T-000_setup_report.md\n",
        ],
        $result->output
    );
    assertSame(['memo.txt'], $result->metadata['out_of_scope']);
    assertSame(
        ['チケットの範囲外のファイルが変更されています(CHG-1)。意図した変更なら ticket.md の scope を確認し、意図しない変更なら元に戻してください'],
        $result->failures
    );
});

// ---- SubmissionPhase ----

/** @param array<string, mixed> $metadata */
function taskResultWith(array $metadata): CheckPhaseResult
{
    return new CheckPhaseResult(status: 'pass', metadata: $metadata);
}

test('SubmissionPhase: 提出必須で未提出なら fail・従来の文言', function (): void {
    $result = (new SubmissionPhase('T-000', true, taskResultWith([]), static fn (): bool => false))->run();
    assertSame('fail', $result->status);
    assertSame(
        ['この課題は提出物が必要です。reports/ に T-000 で始まる Markdown(例: reports/T-000_report.md)を作成してください'],
        $result->failures
    );
});

test('SubmissionPhase: 提出必須で提出済みなら pass・failures 空', function (): void {
    $result = (new SubmissionPhase('T-000', true, taskResultWith([]), static fn (): bool => true))->run();
    assertSame('pass', $result->status);
    assertSame([], $result->failures);
});

test('SubmissionPhase: 課題別テストが note 扱い FAIL+提出済みのときだけ note 行を出す', function (): void {
    $noted = taskResultWith(['failed_as_note' => true]);
    $result = (new SubmissionPhase('T-000', true, $noted, static fn (): bool => true))->run();
    assertSame(
        ["  note: reports/ の提出物を確認しました。課題別テストの FAIL は報告対象として扱い、提出判定は継続します。\n"],
        $result->output
    );

    $resultNoSubmission = (new SubmissionPhase('T-000', true, $noted, static fn (): bool => false))->run();
    assertSame([], $resultNoSubmission->output, '未提出なら note 行は出ない');
});

test('SubmissionPhase: 提出不要かつ note 対象外なら出力も failures も空', function (): void {
    $result = (new SubmissionPhase('T-000', false, taskResultWith([]), static fn (): bool => false))->run();
    assertSame('pass', $result->status);
    assertSame([], $result->output);
    assertSame([], $result->failures);
});

// ---- CheckPhaseResult ----

test('CheckPhaseResult: 不正な status は拒否する', function (): void {
    $threw = false;
    try {
        new CheckPhaseResult(status: 'skip');
    } catch (InvalidArgumentException) {
        $threw = true;
    }
    assertTrue($threw, 'pass/fail/note/error 以外は例外');
});
