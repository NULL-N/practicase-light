<?php

declare(strict_types=1);

use App\Service\ProjectValidator;
use App\Support\Clock;

// F-02: 基準日を固定して検証する(ARC-5)。today = 2026-07-01
// 検証ルールの網羅テスト(単価・人数・締切)は tests/checks/T-001.php にある

test('正常な入力はエラーなし・正規化された値を返す(F-02)', function (): void {
    Clock::fix('2026-07-01 10:00:00');
    $result = (new ProjectValidator())->validate(validProjectInput());
    assertSame([], $result['errors']);
    assertSame(3000, $result['values']['hourly_rate']);
    assertSame(2, $result['values']['capacity']);
    assertSame(1, $result['values']['is_remote']);
    Clock::clear();
});

test('必須項目の欠落は全項目分のエラーをまとめて返す(G-7)', function (): void {
    Clock::fix('2026-07-01 10:00:00');
    $result = (new ProjectValidator())->validate([]);
    assertSame(ProjectValidator::MSG_TITLE_REQUIRED, $result['errors']['title']);
    assertSame(ProjectValidator::MSG_DESCRIPTION_REQUIRED, $result['errors']['description']);
    assertSame(ProjectValidator::MSG_HOURLY_RATE_INVALID, $result['errors']['hourly_rate']);
    assertSame(ProjectValidator::MSG_CAPACITY_INVALID, $result['errors']['capacity']);
    assertSame(ProjectValidator::MSG_DEADLINE_INVALID, $result['errors']['deadline']);
    assertSame(ProjectValidator::MSG_WORK_START_INVALID, $result['errors']['work_start_on']);
    Clock::clear();
});

test('title 100文字は通り 101文字は落ちる(境界値)', function (): void {
    Clock::fix('2026-07-01 10:00:00');
    $validator = new ProjectValidator();
    $ok = $validator->validate(array_merge(validProjectInput(), ['title' => str_repeat('あ', 100)]));
    assertTrue(!isset($ok['errors']['title']), '100文字はOK');
    $ng = $validator->validate(array_merge(validProjectInput(), ['title' => str_repeat('あ', 101)]));
    assertSame(ProjectValidator::MSG_TITLE_TOO_LONG, $ng['errors']['title']);
    Clock::clear();
});

test('description 2000/2001文字の境界', function (): void {
    Clock::fix('2026-07-01 10:00:00');
    $validator = new ProjectValidator();
    $ok = $validator->validate(array_merge(validProjectInput(), ['description' => str_repeat('あ', 2000)]));
    assertTrue(!isset($ok['errors']['description']));
    $ng = $validator->validate(array_merge(validProjectInput(), ['description' => str_repeat('あ', 2001)]));
    assertSame(ProjectValidator::MSG_DESCRIPTION_TOO_LONG, $ng['errors']['description']);
    Clock::clear();
});

test('work_start_on は deadline 以降のみ(前日NG・同日OK)', function (): void {
    Clock::fix('2026-07-01 10:00:00');
    $validator = new ProjectValidator();
    $ng = $validator->validate(array_merge(validProjectInput(), ['work_start_on' => '2026-07-09']));
    assertSame(ProjectValidator::MSG_WORK_START_INVALID, $ng['errors']['work_start_on']);
    $ok = $validator->validate(array_merge(validProjectInput(), ['work_start_on' => '2026-07-10']));
    assertTrue(!isset($ok['errors']['work_start_on']), '締切と同日はOK');
    Clock::clear();
});

test('is_remote はチェック有=1・無=0 に正規化される', function (): void {
    Clock::fix('2026-07-01 10:00:00');
    $validator = new ProjectValidator();
    $on = $validator->validate(validProjectInput());
    assertSame(1, $on['values']['is_remote']);
    $off = $validator->validate(array_diff_key(validProjectInput(), ['is_remote' => '']));
    assertSame(0, $off['values']['is_remote']);
    Clock::clear();
});
