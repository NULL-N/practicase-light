<?php

declare(strict_types=1);

namespace PractiCase\Check;

require_once __DIR__ . '/CheckEdition.php';

/**
 * check.php の人間向け出力を一手に引き受けるレンダラー。
 *
 * 文言・改行・順序は検証時の契約の一部(学習者が読む画面であり、
 * ゴールデンベースライン比較の対象)。ここの文字列を変えることは
 * 出力契約の変更を意味する — 変更時は必ずベースライン比較を通すこと。
 *
 * 設計原則: 出力は即時に書く(バッファしない)。共通テスト・課題別テストの
 * 子プロセス出力は passthru でそのまま流れるため、見出しを先に出力してから
 * 子プロセスを起動する現在の順序を崩さないため。
 */
final class CheckRenderer
{
    /** @var resource|null テスト時に STDERR を差し替えるための注入口 */
    private $stderrStream;

    /** @param resource|null $stderrStream null なら STDERR へ */
    public function __construct($stderrStream = null)
    {
        $this->stderrStream = $stderrStream;
    }

    /** 課題探索の警告(標準エラーへ) */
    public function warning(string $message): void
    {
        fwrite($this->stderrStream ?? STDERR, "警告: {$message}\n");
    }

    /** 続行不能なエラー(標準エラーへ)。exit は呼び出し側 */
    public function fatal(string $message): void
    {
        fwrite($this->stderrStream ?? STDERR, "エラー: {$message}\n");
    }

    /**
     * 使い方と課題一覧(課題IDなし・不明IDのとき)。表示形は edition 設定に従う:
     * grouped は track ごとの見出し、ordered は指定順の1本リスト。
     *
     * @param array<string, array> $tickets discoverTickets の tickets
     */
    public function usage(string $requestedId, array $tickets, CheckEdition $edition): void
    {
        if ($requestedId !== '') {
            echo "課題 '{$requestedId}' が見つかりません。\n\n";
        }
        echo "使い方: docker compose exec app php tools/check.php <課題ID>\n\n";
        echo "利用可能な課題:\n";
        if ($tickets === []) {
            echo "  (packs/php/tickets/ に課題がありません)\n";
        }
        if ($edition->listMode === 'ordered') {
            // 推奨順(edition の ticket_order)の1本リストで見せる
            printf("\n[%s]\n", (string) $edition->listTitle);
            foreach ($edition->ticketOrder as $id) {
                if (!isset($tickets[$id])) {
                    continue;
                }
                $meta = $tickets[$id];
                printf("  %s  Level %s  %s\n", $id, $meta['level'] ?? '?', $meta['title'] ?? '');
            }

            return;
        }
        // 学習トラック(モード)ごとに分けて表示する。合否の判定には影響しない(表示のみ)
        $byTrack = [];
        foreach ($tickets as $id => $meta) {
            $byTrack[(string) ($meta['track'] ?? 'dev')][$id] = $meta;
        }
        foreach ($byTrack as $track => $trackTickets) {
            printf("\n[%s]\n", $edition->trackLabels[$track] ?? $track);
            foreach ($trackTickets as $id => $meta) {
                printf("  %s  Level %s  %s\n", $id, $meta['level'] ?? '?', $meta['title'] ?? '');
            }
        }
    }

    /** check 開始ヘッダー(課題IDとチケット概要) */
    public function header(string $ticketId, array $ticket): void
    {
        printf("=== PractiCase check: %s ===\n", $ticketId);
        printf("チケット: %s(Level %s / %s)\n", $ticket['title'] ?? '(無題)', $ticket['level'] ?? '?', $ticket['type'] ?? '?');
    }

    public function commonTestHeading(): void
    {
        echo "\n[1/3] 共通テスト(既存機能を壊していないか)\n";
    }

    public function taskTestHeading(string $ticketId): void
    {
        echo "\n[2/3] 課題別テスト({$ticketId} の合格条件)\n";
    }

    public function scopeHeading(): void
    {
        echo "\n[3/3] scope 検査(変更がチケットの範囲内か)\n";
    }

    /** scope 検査が実行不能(git 無し等)のときの中断表示。exit は呼び出し側 */
    public function scopeError(string $message): void
    {
        echo "  エラー: {$message}\n\n結果: FAIL\n";
    }

    /**
     * フェーズが返した出力行(改行込みの完成形)をそのまま書く。
     * 行の文言はフェーズ側(CheckPhases.php)が組み立てる — 表示位置と順序だけがここの責務。
     *
     * @param list<string> $lines
     */
    public function phaseLines(array $lines): void
    {
        foreach ($lines as $line) {
            echo $line;
        }
    }

    /** 最終結果: PASS(先頭の空行を含む) */
    public function resultPass(): void
    {
        echo "\n";
        echo "結果: PASS — 提出(Pull Request 作成)に進めます\n";
        echo "提出後: support/rubric.md でセルフチェック → (debrief がある課題は突き合わせ)→ 振り返りを書く → RedmineへPASSをコメント → Resolved → Closed\n";
    }

    /**
     * 最終結果: FAIL(先頭の空行を含む)。結果行が先・番号付き一覧が後。
     *
     * @param list<string> $failures
     */
    public function resultFail(array $failures): void
    {
        echo "\n";
        echo "結果: FAIL\n";
        foreach ($failures as $i => $failure) {
            printf("  %d. %s\n", $i + 1, $failure);
        }
    }
}
