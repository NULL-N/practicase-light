<?php

declare(strict_types=1);

test('T-028: 報告書に必須タグ([bug]2件以上/[impact]1件以上/[fix]か対応方針1件以上)が揃っている', function (): void {
    $reportPath = null;
    foreach (glob('reports/*.md') ?: [] as $path) {
        if (stripos(basename($path), 'T-028') === 0) {
            $reportPath = $path;
            break;
        }
    }
    assertNotNull($reportPath, 'reports/ に T-028 で始まる報告書(例: reports/T-028_review.md)を作成してください');

    $content = (string) file_get_contents((string) $reportPath);

    $bugCount = substr_count($content, '[bug]');
    $impactCount = substr_count($content, '[impact]');
    $fixCount = substr_count($content, '[fix]') + substr_count($content, '対応方針');

    assertTrue($bugCount >= 2, "[bug] タグは2件以上必要です(現在 {$bugCount} 件)");
    assertTrue($impactCount >= 1, "[impact] タグは1件以上必要です(現在 {$impactCount} 件)");
    assertTrue($fixCount >= 1, '[fix] タグ、または「対応方針」の記述が1件以上必要です');
});
