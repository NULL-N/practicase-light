<?php

declare(strict_types=1);

// テスト実行。リポジトリルートで:
//   全体:       docker compose exec app php packs/php/app/tests/run.php
//   ファイル指定: docker compose exec app php packs/php/app/tests/run.php checks/T-001.php

require __DIR__ . '/bootstrap.php';

$targets = array_slice($argv, 1);
if ($targets === []) {
    $files = glob(__DIR__ . '/*Test.php');
} else {
    $files = [];
    foreach ($targets as $target) {
        $path = __DIR__ . '/' . $target;
        if (!is_file($path)) {
            fwrite(STDERR, "テストファイルがありません: {$target}\n");
            exit(1);
        }
        $files[] = $path;
    }
}

foreach ($files as $file) {
    echo basename($file) . "\n";
    require $file;
}

exit(runAllTests());
