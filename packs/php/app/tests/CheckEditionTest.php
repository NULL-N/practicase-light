<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../tools/lib/CheckEdition.php';

use PractiCase\Check\CheckEdition;

// edition 設定(tools/check-edition.php)の読み込みと検証。
// 注意: この配布物の設定内容そのもの(具体的な課題IDや見出し文言)は
// ここに書かない — テストは全課題版と Light 版の両方の環境で同じまま green になる、
// 「構造が正しいこと」だけを検証する。

test('fromArray: grouped の妥当な設定を受理する', function (): void {
    $e = CheckEdition::fromArray([
        'list_mode' => 'grouped',
        'list_title' => null,
        'ticket_order' => [],
        'track_labels' => ['dev' => '開発', 'design' => '設計'],
        'report_required_ids' => ['T-000'],
    ]);
    assertSame('grouped', $e->listMode);
    assertSame(null, $e->listTitle);
    assertSame([], $e->ticketOrder);
    assertSame(['dev' => '開発', 'design' => '設計'], $e->trackLabels);
    assertSame(['T-000'], $e->reportRequiredIds);
});

test('fromArray: ordered の妥当な設定を受理する', function (): void {
    $e = CheckEdition::fromArray([
        'list_mode' => 'ordered',
        'list_title' => 'テスト用の課題一覧',
        'ticket_order' => ['T-000', 'tutorial'],
        'track_labels' => [],
        'report_required_ids' => [],
    ]);
    assertSame('ordered', $e->listMode);
    assertSame('テスト用の課題一覧', $e->listTitle);
    assertSame(['T-000', 'tutorial'], $e->ticketOrder);
});

test('fromArray: 不正な list_mode は拒否する', function (): void {
    $threw = false;
    try {
        CheckEdition::fromArray(['list_mode' => 'flat']);
    } catch (InvalidArgumentException) {
        $threw = true;
    }
    assertTrue($threw);
});

test('fromArray: ordered なのに list_title が無い・空・空白のみなら拒否する', function (): void {
    foreach ([null, '', ' '] as $badTitle) {
        $threw = false;
        try {
            CheckEdition::fromArray([
                'list_mode' => 'ordered',
                'list_title' => $badTitle,
                'ticket_order' => ['T-000'],
            ]);
        } catch (InvalidArgumentException) {
            $threw = true;
        }
        assertTrue($threw, 'list_title=' . var_export($badTitle, true) . ' は拒否されること');
    }
});

test('fromArray: ordered なのに ticket_order が空なら拒否する', function (): void {
    $threw = false;
    try {
        CheckEdition::fromArray(['list_mode' => 'ordered', 'ticket_order' => []]);
    } catch (InvalidArgumentException) {
        $threw = true;
    }
    assertTrue($threw);
});

test('fromArray: ticket_order に空文字や非文字列が混ざれば拒否する', function (): void {
    foreach ([['T-000', ''], ['T-000', 123]] as $bad) {
        $threw = false;
        try {
            CheckEdition::fromArray(['list_mode' => 'ordered', 'ticket_order' => $bad]);
        } catch (InvalidArgumentException) {
            $threw = true;
        }
        assertTrue($threw, 'ticket_order の不正要素は拒否されること');
    }
});

test('load: 存在しないパスは例外', function (): void {
    $threw = false;
    try {
        CheckEdition::load(__DIR__ . '/no-such-edition.php');
    } catch (RuntimeException) {
        $threw = true;
    }
    assertTrue($threw);
});

test('load: 配列を return しないファイルは例外', function (): void {
    $tmp = tempnam(sys_get_temp_dir(), 'edt');
    file_put_contents($tmp, "<?php\nreturn 'not-array';\n");
    $threw = false;
    try {
        CheckEdition::load($tmp);
    } catch (RuntimeException) {
        $threw = true;
    } finally {
        unlink($tmp);
    }
    assertTrue($threw);
});

test('この配布物の tools/check-edition.php は構造として妥当(版によらず)', function (): void {
    $e = CheckEdition::load(__DIR__ . '/../../../../tools/check-edition.php');
    assertTrue(in_array($e->listMode, CheckEdition::LIST_MODES, true));
    if ($e->listMode === 'ordered') {
        assertTrue($e->ticketOrder !== [], 'ordered なら表示順を持つこと');
        assertTrue(is_string($e->listTitle) && $e->listTitle !== '', 'ordered なら見出しを持つこと');
    } else {
        assertTrue($e->trackLabels !== [], 'grouped なら track 見出しを持つこと');
    }
});
