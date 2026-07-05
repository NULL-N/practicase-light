<?php

declare(strict_types=1);

// D-014 の合格条件: テスト仕様書(reports/D-014*.md)の実在と、必須の観点・識別子の言及。
// D-010〜D-013と同じ「控えめ」設計 — 観点の質・ケースの十分さは check では判定できない
// (support/rubric.md で見る)。

function d014Note(): string
{
    $content = '';
    foreach (glob('reports/D-014*.md') ?: [] as $path) {
        $content .= (string) file_get_contents($path);
    }

    return $content;
}

function d014ContainsAny(string $haystack, array $needles): bool
{
    foreach ($needles as $needle) {
        if (str_contains($haystack, $needle)) {
            return true;
        }
    }

    return false;
}

test('D-014: テスト仕様書(reports/D-014*.md)が存在する', function (): void {
    assertTrue(count(glob('reports/D-014*.md') ?: []) >= 1, 'reports/D-014_test_spec.md を作成してください(support/spec.md の型で)');
});

test('D-014: 画面(S-03・S-04・S-12)への言及がある', function (): void {
    $note = d014Note();
    foreach (['S-03', 'S-04', 'S-12'] as $id) {
        assertTrue(str_contains($note, $id), "{$id} への言及が見つかりません — タグに関わる3画面すべてのケースを書いてください");
    }
});

test('D-014: タグの登録・検索(絞り込み)、両方への言及がある', function (): void {
    $note = d014Note();
    assertTrue(
        d014ContainsAny($note, ['登録']),
        '「登録」への言及が見つかりません — client がタグを付けて登録するケースを書いてください'
    );
    assertTrue(
        d014ContainsAny($note, ['絞り込み', '検索']),
        '「絞り込み」または「検索」への言及が見つかりません — engineer がタグで絞り込むケースを書いてください'
    );
});

test('D-014: DB保存・参照への言及がある', function (): void {
    $note = d014Note();
    assertTrue(
        d014ContainsAny($note, ['project_tags', 'DB', 'データベース']),
        '「project_tags」または「DB」への言及が見つかりません — タグの保存・参照が正しく動くかを確認してください'
    );
});

test('D-014: 権限・異常系への言及がある', function (): void {
    $note = d014Note();
    assertTrue(str_contains($note, '権限'), '「権限」への言及が見つかりません — 既存の権限マトリクスが変わっていないことを確認してください');
    assertTrue(
        d014ContainsAny($note, ['異常系', '存在しない']),
        '「異常系」または「存在しない」への言及が見つかりません — 不正な tag_id などのケースを書いてください'
    );
});

test('D-014: 境界値としてタグ0個・3個・4個がある', function (): void {
    $note = d014Note();
    foreach (['0個', '3個', '4個'] as $boundary) {
        assertTrue(
            str_contains($note, $boundary),
            "「{$boundary}」への言及が見つかりません — タグの上限(最大3個)の境界値として、0個・3個・4個の3点を確認してください(D-012の業務ルールが根拠)"
        );
    }
});
