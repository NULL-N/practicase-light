<?php

declare(strict_types=1);

// tutorial の合格条件: 案件詳細(S-04)の項目名が正式な用語「時間単価」に揃っていること。
// この課題だけは動作ではなく画面ファイルの表記そのものを検査する
// (DB もログインも不要 — 最初の1本が環境要因で転ばないようにするため)

test('案件詳細の単価の項目名が「時間単価」になっている(S-03 の列名・F-02 の用語と一致)', function (): void {
    $source = (string) file_get_contents(__DIR__ . '/../../public/projects/show.php');
    assertTrue(str_contains($source, '<th>時間単価</th>'), '正しい項目名「時間単価」が見つかりません');
    assertTrue(!str_contains($source, '報酬単価'), '誤った表記「報酬単価」が残っています(support/spec.md の用語に揃えます)');
});
