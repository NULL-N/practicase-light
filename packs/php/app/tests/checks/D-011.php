<?php

declare(strict_types=1);

// D-011 の合格条件: F-20 が機能仕様書に、タグの扱いが画面設計書に存在すること。
// 記述の質(4点の網羅・文体)は support/rubric.md で見る(控えめ設計)

test('D-011: features.md に F-20(案件タグ)の節がある', function (): void {
    $features = (string) file_get_contents('docs/01_設計資料/features.md');
    assertTrue(str_contains($features, 'F-20'), 'docs/01_設計資料/features.md に F-20 の節が見つかりません');
    assertTrue(str_contains($features, 'タグ'), 'features.md の F-20 にタグの説明が見つかりません');
});

test('D-011: F-20 に「やらないこと」が明記されている', function (): void {
    $features = (string) file_get_contents('docs/01_設計資料/features.md');
    assertTrue(
        str_contains($features, 'やらない') || str_contains($features, '対象外'),
        'F-20 に「やらないこと」(編集での付け直し・タグ管理画面など)を明記してください'
    );
});

test('D-011: screens.md にタグの扱いが追記されている', function (): void {
    $screens = (string) file_get_contents('docs/01_設計資料/screens.md');
    assertTrue(str_contains($screens, 'タグ'), 'docs/01_設計資料/screens.md(S-03 / S-04 / S-12)にタグの扱いを追記してください');
});

test('D-011: 画面遷移図(Mermaid)に、タグ絞り込みの流れが追記されている', function (): void {
    $screens = (string) file_get_contents('docs/01_設計資料/screens.md');
    preg_match_all('/```mermaid(.*?)```/s', $screens, $m);
    $flowchart = '';
    foreach ($m[1] as $block) {
        if (stripos($block, 'flowchart') !== false) {
            $flowchart .= $block;
        }
    }
    assertTrue(
        $flowchart !== '' && str_contains($flowchart, 'タグ'),
        '画面遷移図(4節の Mermaid)に「タグ」による絞り込みの流れが見当たりません — ' .
            '新しい画面は増やさなくてよいので、既存の S-03 に矢印を1本足すだけで十分です(support/spec.md)'
    );
});
