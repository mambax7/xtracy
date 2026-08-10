<?php

declare(strict_types=1);

namespace XoopsModules\Xtracy\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Baseline smoke test.
 *
 * Proves the DevOps baseline passes its own quality gate: Composer autoload resolves and
 * PHPUnit executes at least one test (so `composer qa` exits 0). Also exercises the
 * RequiresXoops integration helper. Real unit tests live alongside in tests/Unit/.
 */
final class BaselineTest extends TestCase
{
    use \RequiresXoops;

    public function testComposerAutoloadResolves(): void
    {
        self::assertTrue(class_exists(TestCase::class));
    }

    public function testRequiresXoopsSkipsWithoutRuntime(): void
    {
        // Self-skips in unit-only mode; only reached when a XOOPS runtime is booted.
        $this->requiresXoops();

        self::assertTrue(function_exists('xoops_load'));
    }
}
