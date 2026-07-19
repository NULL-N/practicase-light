<?php

declare(strict_types=1);

namespace PractiCase\Check;

/**
 * check の1フェーズが返す構造化結果。
 *
 * フェーズは直接出力せず、この結果だけを返す。表示は CheckRenderer、
 * 集約(最終 PASS/FAIL の決定)は check.php の責務。
 * 「先行フェーズが失敗したか」は共有配列から推測せず、必要なフェーズが
 * 依存する結果オブジェクトを明示的に受け取る。
 */
final class CheckPhaseResult
{
    public const STATUSES = ['pass', 'fail', 'note', 'error'];

    /**
     * @param string       $status   pass / fail / note(FAILを情報として扱う)/ error(続行不能)
     * @param list<string> $output   人間向けの出力行(改行込みの完成形。CheckRenderer がそのまま書く)
     * @param list<string> $failures 最終 FAIL 一覧へ載せる文言(空なら合格扱い)
     * @param array<string, mixed> $metadata フェーズ固有の判定材料(後続フェーズへの明示的な受け渡し用)
     */
    public function __construct(
        public readonly string $status,
        public readonly array $output = [],
        public readonly array $failures = [],
        public readonly array $metadata = [],
    ) {
        if (!in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException('status は ' . implode('/', self::STATUSES) . ' のいずれか');
        }
    }
}
