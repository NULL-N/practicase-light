<?php

declare(strict_types=1);

// D-010 の合格条件: 要望整理ノートの実在と骨格。
// 内容の質(質問の具体性・根拠)は check では見ない — support/rubric.md でセルフチェックする(控えめ設計)

function d010Notes(): string
{
    $content = '';
    foreach (glob('reports/D-010*.md') ?: [] as $path) {
        $content .= (string) file_get_contents($path);
    }

    return $content;
}

test('D-010: 要望整理ノート(reports/D-010*.md)が存在する', function (): void {
    assertTrue(count(glob('reports/D-010*.md') ?: []) >= 1, 'reports/D-010_requirement_note.md を作成してください(support/spec.md の型で)');
});

test('D-010: 「確認事項」と「決定事項」の節がある', function (): void {
    $content = d010Notes();
    assertTrue(str_contains($content, '確認事項'), '「確認事項」の節が見つかりません');
    assertTrue(str_contains($content, '決定事項'), '「決定事項」の節が見つかりません');
});

test('D-010: 箇条書きの実体がある(質問・決定あわせて5行以上)', function (): void {
    $count = preg_match_all('/^\s*(?:[-*]|\d+\.)\s+\S/mu', d010Notes());
    assertTrue($count >= 5, "箇条書きが {$count} 行しかありません。確認事項・決定事項を書き出してください");
});
