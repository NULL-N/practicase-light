<?php

declare(strict_types=1);

namespace PractiCase\Redmine;

use PractiCase\Check\CheckEdition;

/**
 * edition 設定と検出済み課題から、Redmineへ投入する教材IDを決める。
 */
final class SeedSelection
{
    /**
     * @param array<string, array> $tickets
     * @return array{ids: list<string>, label: string}
     */
    public static function resolve(CheckEdition $edition, array $tickets, bool $legacyLightOnly = false): array
    {
        if ($legacyLightOnly && $edition->listMode !== 'ordered') {
            throw new \InvalidArgumentException(
                '--all-light はLight版でのみ使えます。Master版では --all を使ってください'
            );
        }

        if ($edition->listMode === 'ordered') {
            $ids = $edition->ticketOrder;
            $label = 'Light edition';
        } else {
            $ids = array_keys($tickets);
            $label = 'Master edition';
        }

        if ($ids === []) {
            throw new \InvalidArgumentException('seed対象の課題がありません');
        }
        if (count($ids) !== count(array_unique($ids))) {
            throw new \InvalidArgumentException('edition設定に重複IDがあります');
        }
        foreach ($ids as $id) {
            if (preg_match('/\A([TRDC]-[0-9]{3}|tutorial(-[0-9]+)?)\z/', $id) !== 1) {
                throw new \InvalidArgumentException("edition設定に課題IDでない値があります: {$id}");
            }
        }

        if ($edition->listMode === 'ordered') {
            $editionOnly = array_values(array_diff($ids, array_keys($tickets)));
            if ($editionOnly !== []) {
                throw new \InvalidArgumentException(
                    'edition設定にあるのにticket.mdが見つからない課題があります: ' . implode(', ', $editionOnly)
                );
            }
            $discoveryOnly = array_values(array_diff(array_keys($tickets), $ids));
            if ($discoveryOnly !== []) {
                throw new \InvalidArgumentException(
                    '課題ルートにedition設定に無い課題があります: ' . implode(', ', $discoveryOnly)
                );
            }
        }

        return ['ids' => array_values($ids), 'label' => $label];
    }
}
