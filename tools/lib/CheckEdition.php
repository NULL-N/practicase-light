<?php

declare(strict_types=1);

namespace PractiCase\Check;

/**
 * check.php の版(edition)設定。
 *
 * check.php は tools/check-edition.php が返す設定だけを読み、
 * 自分がどの版で動いているかを判定しない(条件分岐は設定値に対してのみ)。
 * 配布物ごとの違いは設定ファイルの中身の違いとして表現する —
 * ツール本体のソースを配布時に書き換える方式はここで廃止した。
 *
 * 設定項目:
 *   list_mode           grouped(track ごとに見出しで束ねる)/ ordered(指定順の1本リスト)
 *   list_title          ordered のときのリスト見出し
 *   ticket_order        ordered のときの表示順(課題ID の配列)
 *   track_labels        grouped のときの track 見出し(track => 表示名)
 *   report_required_ids type では括れない、ID 単位で提出物を必須にする課題
 */
final class CheckEdition
{
    public const LIST_MODES = ['grouped', 'ordered'];

    /**
     * @param list<string> $ticketOrder
     * @param array<string, string> $trackLabels
     * @param list<string> $reportRequiredIds
     */
    public function __construct(
        public readonly string $listMode,
        public readonly ?string $listTitle,
        public readonly array $ticketOrder,
        public readonly array $trackLabels,
        public readonly array $reportRequiredIds,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $listMode = (string) ($data['list_mode'] ?? '');
        if (!in_array($listMode, self::LIST_MODES, true)) {
            throw new \InvalidArgumentException('list_mode は ' . implode('/', self::LIST_MODES) . ' のいずれか');
        }
        $listTitle = $data['list_title'] ?? null;
        if ($listTitle !== null && !is_string($listTitle)) {
            throw new \InvalidArgumentException('list_title は文字列か null');
        }
        if ($listMode === 'ordered' && ($listTitle === null || trim($listTitle) === '')) {
            throw new \InvalidArgumentException('ordered のとき list_title は空にできない');
        }
        $ticketOrder = self::asStringList($data['ticket_order'] ?? [], 'ticket_order');
        if ($listMode === 'ordered' && $ticketOrder === []) {
            throw new \InvalidArgumentException('ordered のとき ticket_order は空にできない');
        }
        $trackLabels = [];
        foreach ((array) ($data['track_labels'] ?? []) as $track => $label) {
            if (!is_string($track) || !is_string($label)) {
                throw new \InvalidArgumentException('track_labels は 文字列 => 文字列 の連想配列');
            }
            $trackLabels[$track] = $label;
        }

        return new self(
            listMode: $listMode,
            listTitle: is_string($listTitle) ? $listTitle : null,
            ticketOrder: $ticketOrder,
            trackLabels: $trackLabels,
            reportRequiredIds: self::asStringList($data['report_required_ids'] ?? [], 'report_required_ids'),
        );
    }

    /** 設定ファイル(PHP return 配列)を読み込み、検証済みの設定を返す */
    public static function load(string $path): self
    {
        if (!is_file($path)) {
            throw new \RuntimeException("edition 設定ファイルが見つかりません: {$path}");
        }
        $data = require $path;
        if (!is_array($data)) {
            throw new \RuntimeException("edition 設定ファイルは配列を return してください: {$path}");
        }

        return self::fromArray($data);
    }

    /**
     * @return list<string>
     */
    private static function asStringList(mixed $value, string $name): array
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException("{$name} は文字列の配列");
        }
        $out = [];
        foreach ($value as $v) {
            if (!is_string($v) || $v === '') {
                throw new \InvalidArgumentException("{$name} は空でない文字列の配列");
            }
            $out[] = $v;
        }

        return $out;
    }
}
