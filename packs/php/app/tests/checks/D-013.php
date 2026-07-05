<?php

declare(strict_types=1);

// D-013 の合格条件: 詳細設計書の実在と必須の節。
// 「実装者が迷わない粒度か」は check では判定できない — support/rubric.md で見る(控えめ設計)

function d013Design(): string
{
    $content = '';
    foreach (glob('reports/D-013*.md') ?: [] as $path) {
        $content .= (string) file_get_contents($path);
    }

    return $content;
}

test('D-013: 詳細設計書(reports/D-013*.md)が存在する', function (): void {
    assertTrue(count(glob('reports/D-013*.md') ?: []) >= 1, 'reports/D-013_detail_design.md を作成してください(support/spec.md の型で)');
});

test('D-013: 入出力(GET/POST)とエラーの記述がある', function (): void {
    $design = d013Design();
    assertTrue(
        str_contains($design, 'GET') || str_contains($design, 'POST'),
        '画面と入出力の節(URL・メソッド・パラメータ)が見つかりません'
    );
    assertTrue(str_contains($design, 'エラー'), '検証ルールとエラー文言の節が見つかりません');
});

test('D-013: 実装の割り当て(Service / Repository)がある', function (): void {
    $design = d013Design();
    assertTrue(str_contains($design, 'Service'), 'Service 層への割り当てが見つかりません(ARC-3)');
    assertTrue(str_contains($design, 'Repository'), 'Repository 層への割り当てが見つかりません(ARC-2)');
});

test('D-013: 権限と異常系の節がある', function (): void {
    assertTrue(str_contains(d013Design(), '権限'), '「権限と異常系」の節が見つかりません');
});
