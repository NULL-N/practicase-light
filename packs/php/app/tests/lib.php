<?php

declare(strict_types=1);

// 依存ゼロの軽量テストランナー(PHP標準機能のみ・教材として読めることを優先)

$GLOBALS['__tests'] = [];

function test(string $name, callable $fn): void
{
    $GLOBALS['__tests'][] = [$name, $fn];
}

function assertSame(mixed $expected, mixed $actual, string $label = ''): void
{
    if ($expected !== $actual) {
        throw new AssertionError(sprintf(
            '%s期待値 %s / 実際 %s',
            $label === '' ? '' : $label . ': ',
            var_export($expected, true),
            var_export($actual, true)
        ));
    }
}

function assertTrue(mixed $actual, string $label = ''): void
{
    assertSame(true, $actual, $label);
}

function assertNull(mixed $actual, string $label = ''): void
{
    assertSame(null, $actual, $label);
}

function assertNotNull(mixed $actual, string $label = ''): void
{
    if ($actual === null) {
        throw new AssertionError(($label === '' ? '' : $label . ': ') . 'null ではない値を期待');
    }
}

function runAllTests(): int
{
    $passed = 0;
    $failed = 0;
    foreach ($GLOBALS['__tests'] as [$name, $fn]) {
        try {
            $fn();
            $passed++;
            echo "  ok    {$name}\n";
        } catch (\Throwable $e) {
            $failed++;
            echo "  FAIL  {$name}\n        {$e->getMessage()}\n";
        }
    }
    printf("\n%d passed, %d failed\n", $passed, $failed);

    return $failed === 0 ? 0 : 1;
}
