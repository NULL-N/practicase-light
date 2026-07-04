<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../tools/lib/CheckSupport.php';

use function PractiCase\Check\collectChangedFiles;
use function PractiCase\Check\discoverTickets;
use function PractiCase\Check\hasReportSubmission;
use function PractiCase\Check\matchesScope;
use function PractiCase\Check\parseFrontMatter;

// 一時ディレクトリはコンテナ内 /tmp に uniqid 付きで作る。
// コンテナの破棄とともに消えるため、テスト内では片付けない(片付け処理はテストの本質ではない)。
function tempDir(string $prefix): string
{
    $dir = sys_get_temp_dir() . '/' . $prefix . '-' . uniqid();
    mkdir($dir, 0777, true);

    return $dir;
}

test('front matter を読める(スカラーと配列。ticket-frontmatter.md の範囲)', function (): void {
    $md = "---\nid: T-001\ntitle: 案件登録で不正な値が登録できる\nlevel: 1\nscope:\n  - \"packs/php/app/src/Service/**\"\n  - \"packs/php/app/tests/**\"\ndepends_on:\n  - \"T-000\"\n---\n\n# 本文";
    $meta = parseFrontMatter($md);
    assertSame('T-001', $meta['id']);
    assertSame('案件登録で不正な値が登録できる', $meta['title']);
    assertSame('1', $meta['level']);
    assertSame(['packs/php/app/src/Service/**', 'packs/php/app/tests/**'], $meta['scope']);
    assertSame(['T-000'], $meta['depends_on']);
});

test('front matter が無い Markdown は null', function (): void {
    assertNull(parseFrontMatter("# ただの文書\n本文"));
});

test('discoverTickets は id 不正を警告し、正常な課題だけ返す', function (): void {
    $dir = tempDir('pc-tickets');
    mkdir($dir . '/T-001_valid', 0777, true);
    mkdir($dir . '/D-900_design', 0777, true);
    mkdir($dir . '/tutorial', 0777, true);
    mkdir($dir . '/tutorial-2', 0777, true);
    mkdir($dir . '/broken', 0777, true);
    file_put_contents($dir . '/T-001_valid/ticket.md', "---\nid: T-001\ntitle: 正常な課題\nlevel: 1\n---\n本文");
    file_put_contents($dir . '/D-900_design/ticket.md', "---\nid: D-900\ntitle: 設計課題\nlevel: 3\ntrack: design\n---\n本文");
    file_put_contents($dir . '/tutorial/ticket.md', "---\nid: tutorial\ntitle: 導入課題\nlevel: 1\n---\n本文");
    file_put_contents($dir . '/tutorial-2/ticket.md', "---\nid: tutorial-2\ntitle: 導入課題2\nlevel: 1\n---\n本文");
    file_put_contents($dir . '/broken/ticket.md', "---\ntitle: id が無い\n---\n本文");

    $result = discoverTickets($dir);
    assertSame(['D-900', 'T-001', 'tutorial', 'tutorial-2'], array_keys($result['tickets']), 'D 系(設計課題)と id 特例 tutorial も受理される');
    assertSame(1, count($result['warnings']), 'id 欠落は警告になる');
});

test('scope マッチ: ** は任意階層、リストのどれかに一致すればよい', function (): void {
    $scope = ['packs/php/app/src/Service/**', 'packs/php/app/tests/**'];
    assertTrue(matchesScope('packs/php/app/src/Service/ProjectValidator.php', $scope));
    assertTrue(matchesScope('packs/php/app/tests/checks/T-001.php', $scope), '深い階層にも一致');
    assertTrue(
        matchesScope('packs/php/app/database/practicase.db', \PractiCase\Check\IMPLICIT_SCOPE),
        'DB 生成物は暗黙許可(.gitignore が欠けた環境でも scope 違反にしない)'
    );
    assertTrue(!matchesScope('packs/php/app/src/Repository/UserRepository.php', $scope), 'scope 外は false');
    assertTrue(!matchesScope('docs/01_設計資料/features.md', $scope));
});

test('git リポジトリでない場所ではエラーメッセージを返す(T-000 前提の明示)', function (): void {
    $dir = tempDir('pc-nogit');
    $cwd = getcwd();
    try {
        chdir($dir);
        $result = collectChangedFiles();
        assertTrue(is_string($result), 'エラーメッセージ(文字列)が返る');
        assertTrue(str_contains($result, 'T-000'), 'T-000 の手順へ誘導する');
    } finally {
        chdir($cwd);
    }
});

test('コミットゼロ(ベースライン無し)ではエラーメッセージを返す', function (): void {
    $dir = tempDir('pc-empty');
    $cwd = getcwd();
    try {
        chdir($dir);
        exec('git init -q -b main 2>/dev/null');
        $result = collectChangedFiles();
        assertTrue(is_string($result), 'エラーメッセージが返る');
        assertTrue(str_contains($result, '最初のコミット'), '最初のコミットへ誘導する');
    } finally {
        chdir($cwd);
    }
});

test('diff と未追跡ファイルの両方を集める(新規ファイルの見落とし防止)', function (): void {
    $dir = tempDir('pc-repo');
    $cwd = getcwd();
    try {
        chdir($dir);
        exec('git init -q -b main && git -c user.name=t -c user.email=t@example.com commit -q --allow-empty -m base');
        file_put_contents($dir . '/tracked.txt', 'v1');
        exec('git add . && git -c user.name=t -c user.email=t@example.com commit -q -m add');
        file_put_contents($dir . '/tracked.txt', 'v2');       // 追跡済みの変更
        file_put_contents($dir . '/brand-new.txt', 'new');    // 未追跡の新規

        $result = collectChangedFiles();
        assertTrue(is_array($result));
        assertSame('main', $result['base']);
        assertSame('M', $result['files']['tracked.txt'] ?? '', '追跡済みの変更は M');
        assertSame('?', $result['files']['brand-new.txt'] ?? '', '未追跡は ? として検出される');
    } finally {
        chdir($cwd);
    }
});

test('提出物は「課題IDで始まる reports/*.md の実在」で判定する(コミット済みでも他課題の報告でも誤判定しない)', function (): void {
    $dir = tempDir('pc-reports');
    file_put_contents($dir . '/T-904_bug_report.md', 'report');
    file_put_contents($dir . '/README.md', 'guide');

    assertTrue(hasReportSubmission('T-904', $dir), '自課題の提出物は検出される');
    assertTrue(hasReportSubmission('t-904', $dir), '課題IDの大文字小文字は吸収する');
    assertTrue(!hasReportSubmission('R-901', $dir), '他課題の報告だけでは提出にならない');
    assertTrue(!hasReportSubmission('README', $dir), 'README は提出物ではない');
});
