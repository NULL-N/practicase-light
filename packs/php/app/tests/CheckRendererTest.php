<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../tools/lib/CheckRenderer.php';

use PractiCase\Check\CheckEdition;
use PractiCase\Check\CheckRenderer;

// CheckRenderer は check.php の出力契約(文言・改行・順序)の正本。
// ここの期待文字列はゴールデンベースライン比較が読むマーカーそのものなので、
// 変更するときは必ずベースライン比較(verify-check-baseline)を通すこと。

/** レンダラーの標準出力を文字列として捕まえる */
function captureRenderer(callable $render): string
{
    ob_start();
    $render();

    return (string) ob_get_clean();
}

test('header は課題IDとチケット概要を出力する(欠損時は既定値)', function (): void {
    $r = new CheckRenderer();
    $out = captureRenderer(fn () => $r->header('T-000', ['title' => '環境構築', 'level' => 1, 'type' => 'setup']));
    assertSame("=== PractiCase check: T-000 ===\nチケット: 環境構築(Level 1 / setup)\n", $out);

    $out = captureRenderer(fn () => $r->header('T-000', []));
    assertSame("=== PractiCase check: T-000 ===\nチケット: (無題)(Level ? / ?)\n", $out);
});

test('フェーズ見出し3種は先頭に空行を持つ', function (): void {
    $r = new CheckRenderer();
    assertSame("\n[1/3] 共通テスト(既存機能を壊していないか)\n", captureRenderer(fn () => $r->commonTestHeading()));
    assertSame("\n[2/3] 課題別テスト(T-001 の合格条件)\n", captureRenderer(fn () => $r->taskTestHeading('T-001')));
    assertSame("\n[3/3] scope 検査(変更がチケットの範囲内か)\n", captureRenderer(fn () => $r->scopeHeading()));
});

test('phaseLines はフェーズが返した行(改行込みの完成形)をそのままの順で書く', function (): void {
    $r = new CheckRenderer();
    assertSame('', captureRenderer(fn () => $r->phaseLines([])));
    assertSame(
        "  1行目\n  2行目\n",
        captureRenderer(fn () => $r->phaseLines(["  1行目\n", "  2行目\n"]))
    );
});

test('scope エラーは中断表示と結果: FAIL を同時に出す', function (): void {
    $r = new CheckRenderer();
    assertSame(
        "  エラー: ここは git リポジトリではありません。\n\n結果: FAIL\n",
        captureRenderer(fn () => $r->scopeError('ここは git リポジトリではありません。'))
    );
});

test('resultPass: 空行 → 結果行(サフィックス付き) → 提出後の案内', function (): void {
    $r = new CheckRenderer();
    assertSame(
        "\n結果: PASS — 提出(Pull Request 作成)に進めます\n"
        . "提出後: support/rubric.md でセルフチェック → (debrief がある課題は突き合わせ)→ 振り返りを書く → RedmineへPASSをコメント → Resolved → Closed\n",
        captureRenderer(fn () => $r->resultPass())
    );
});

test('resultFail: 空行 → 結果行 → 1始まりの番号付き一覧(順序保存)', function (): void {
    $r = new CheckRenderer();
    assertSame(
        "\n結果: FAIL\n  1. 一つ目の問題\n  2. 二つ目の問題\n",
        captureRenderer(fn () => $r->resultFail(['一つ目の問題', '二つ目の問題']))
    );
});

test('warning は注入したストリームへ「警告: 」プレフィックスで書く', function (): void {
    $stream = fopen('php://memory', 'r+');
    $r = new CheckRenderer($stream);
    $r->warning('front matter が不正です');
    rewind($stream);
    assertSame("警告: front matter が不正です\n", (string) stream_get_contents($stream));
    fclose($stream);
});

test('fatal は注入したストリームへ「エラー: 」プレフィックスで書く', function (): void {
    $stream = fopen('php://memory', 'r+');
    $r = new CheckRenderer($stream);
    $r->fatal('設定が読めません');
    rewind($stream);
    assertSame("エラー: 設定が読めません\n", (string) stream_get_contents($stream));
    fclose($stream);
});

test('usage(grouped): 不明ID表示 → 使い方 → track 見出しごとの一覧', function (): void {
    $edition = CheckEdition::fromArray([
        'list_mode' => 'grouped',
        'track_labels' => ['dev' => '手を動かす課題'],
    ]);
    $tickets = [
        'T-000' => ['track' => 'dev', 'level' => 1, 'title' => '環境構築'],
        'D-010' => ['track' => 'design', 'level' => 2, 'title' => '要望整理'],
    ];
    $r = new CheckRenderer();
    $out = captureRenderer(fn () => $r->usage('T-9XX', $tickets, $edition));
    assertSame(
        "課題 'T-9XX' が見つかりません。\n\n"
        . "使い方: docker compose exec app php tools/check.php <課題ID>\n\n"
        . "利用可能な課題:\n"
        . "\n[手を動かす課題]\n"
        . "  T-000  Level 1  環境構築\n"
        . "\n[design]\n"
        . "  D-010  Level 2  要望整理\n",
        $out
    );
});

test('usage(ordered): 指定順の1本リスト・未知IDはスキップ・IDなしなら不明ID行は出ない', function (): void {
    $edition = CheckEdition::fromArray([
        'list_mode' => 'ordered',
        'list_title' => 'テスト用の課題一覧',
        'ticket_order' => ['tutorial', 'T-900', 'T-000'],
    ]);
    $tickets = [
        'T-000' => ['level' => 1, 'title' => '環境構築'],
        'tutorial' => ['level' => 1, 'title' => '肩慣らし'],
    ];
    $r = new CheckRenderer();
    $out = captureRenderer(fn () => $r->usage('', $tickets, $edition));
    assertSame(
        "使い方: docker compose exec app php tools/check.php <課題ID>\n\n"
        . "利用可能な課題:\n"
        . "\n[テスト用の課題一覧]\n"
        . "  tutorial  Level 1  肩慣らし\n"
        . "  T-000  Level 1  環境構築\n",
        $out
    );
});
