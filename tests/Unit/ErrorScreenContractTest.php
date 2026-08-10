<?php

declare(strict_types=1);

namespace XoopsModules\Xtracy\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The obligations XOOPS 2.7.3's error-screen seam puts on this provider.
 *
 * The seam is ADVISORY by design: core passes its answer to xoops_isDeveloperRequest()
 * in the event and does not enforce it, so a provider CAN render a production-safe page.
 * Tracy is not such a provider -- its BlueScreen shows source excerpts, file paths,
 * superglobals and environment -- so the only thing between this module and an anonymous
 * visitor reading all three is the developer gate tested below. Prose cannot hold that
 * line; a test can. ADR-0001 promises this gate; this file is it.
 *
 * Runs without a booted XOOPS on purpose: every case exercises the part of
 * eventCoreDebugErrorscreen()/resolve() that decides BEFORE it touches anything
 * XOOPS-shaped or Tracy-shaped -- the token match, the reporting channel, the developer
 * gate, and the module permission. A contract test that needed a database would not run
 * in CI, and a gate nobody runs is prose again.
 *
 * What this file deliberately does NOT do is drive the fully-granted path to activation.
 * Tracy's Debugger::enable() calls register_shutdown_function(), and a shutdown function
 * cannot be unregistered by anyone -- unlike Whoops, whose Run::unregister() cleanly
 * restores its handlers. Enabling Tracy in-process would therefore leak a shutdown hook
 * into the rest of the suite and cannot be undone in tearDown. Every case here stops at
 * or before the last branch that returns without calling enable(), and asserts that no
 * handler was installed -- which is the property that actually matters.
 */
final class ErrorScreenContractTest extends TestCase
{
    /** @var list<array{0: string, 1: string}> */
    private array $reports = [];

    private mixed $errorHandlerBefore = null;

    private mixed $exceptionHandlerBefore = null;

    protected function setUp(): void
    {
        $this->reports = [];

        // XoopsPreloadItem is a core class; the module's own file only needs it to exist
        // as a parent. A fixture file provides the stand-in -- no eval(), which a scanner
        // flags and a fixture never needs.
        require_once __DIR__ . '/fixtures/XoopsPreloadItem.php';

        // preloads/core.php opens with the mandatory direct-access guard
        // `defined('XOOPS_ROOT_PATH') || exit('Restricted access');`. Requiring it here
        // without the constant would run that exit() and kill the whole PHPUnit process --
        // and because exit('string') yields status 0, the run would look GREEN while the
        // cases below never executed. Define it (to the module root, its only sane value)
        // so the guard passes. This does NOT boot XOOPS: XOOPS_OVERLAY_INTEGRATION stays
        // false, so the integration-gated tests still self-skip; every case here reaches
        // its verdict before any code that reads a real XOOPS constant.
        if (! defined('XOOPS_ROOT_PATH')) {
            define('XOOPS_ROOT_PATH', \dirname(__DIR__, 2));
        }

        require_once \dirname(__DIR__, 2) . '/preloads/core.php';

        $this->errorHandlerBefore = $this->currentErrorHandler();
        $this->exceptionHandlerBefore = $this->currentExceptionHandler();
    }

    protected function tearDown(): void
    {
        // The permission verdict a case may have set must not leak to the next one.
        unset($GLOBALS['__xtracy_test_permission_granted']);

        // POP back to where this case started; do not PUSH the old handler on top.
        // set_error_handler() adds a frame, restore_error_handler() removes one -- calling
        // set_error_handler($original) here would leave the stack one frame deeper than
        // PHPUnit saw at the start, which PHPUnit 11 marks risky (and failOnRisky then
        // reddens the whole gate this file exists to be). Bounded, because a runaway loop
        // in a teardown is a hung build. No case below installs a handler, so this is a
        // belt-and-braces reset rather than a load-bearing one.
        for ($i = 0; $i < 16 && $this->currentErrorHandler() !== $this->errorHandlerBefore; ++$i) {
            restore_error_handler();
        }
        for ($i = 0; $i < 16 && $this->currentExceptionHandler() !== $this->exceptionHandlerBefore; ++$i) {
            restore_exception_handler();
        }
    }

    /**
     * Read the live error handler without disturbing it: PHP offers no getter, and the
     * set-then-restore pair is the only way to look. Net zero frames.
     */
    private function currentErrorHandler(): mixed
    {
        $handler = set_error_handler(null);
        restore_error_handler();

        return $handler;
    }

    private function currentExceptionHandler(): mixed
    {
        $handler = set_exception_handler(null);
        restore_exception_handler();

        return $handler;
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function event(array $overrides = []): array
    {
        return $overrides + [
            'owner' => 'xtracy',
            'developer_request' => true,
            'report' => function (string $status, string $message = ''): bool {
                $this->reports[] = [$status, $message];

                return true;
            },
        ];
    }

    private function handlersMoved(): bool
    {
        if ($this->currentErrorHandler() !== $this->errorHandlerBefore) {
            return true;
        }

        return $this->currentExceptionHandler() !== $this->exceptionHandlerBefore;
    }

    #[Test]
    public function itRefusesWhenTheRequestIsNotADevelopers(): void
    {
        // The obligation core cannot check. Tracy shows source, superglobals and
        // environment; if this branch ever stops refusing, an anonymous visitor sees all
        // three and nothing else in the stack will stop it.
        \XtracyCorePreload::eventCoreDebugErrorscreen($this->event(['developer_request' => false]));

        self::assertCount(1, $this->reports, 'a provider must report in every branch, including this one');
        self::assertSame('disabled', $this->reports[0][0]);
        self::assertFalse($this->handlersMoved(), 'nothing may be registered for a non-developer request');
    }

    #[Test]
    public function itRefusesWhenTheGateAnswerIsAbsentEntirely(): void
    {
        // A core too old to send the flag, or a caller that forgot it. Absent must mean
        // no, never "assume yes" -- the failure has to land on the safe side.
        \XtracyCorePreload::eventCoreDebugErrorscreen([
            'owner' => 'xtracy',
            'report' => function (string $status, string $message = ''): bool {
                $this->reports[] = [$status, $message];

                return true;
            },
        ]);

        self::assertSame('disabled', $this->reports[0][0] ?? '');
        self::assertFalse($this->handlersMoved());
    }

    #[Test]
    public function itIgnoresATokenThatIsNotItsOwn(): void
    {
        // The seat belongs to whoever core named. Answering anyway is the load-order
        // roulette the whole mechanism exists to end.
        \XtracyCorePreload::eventCoreDebugErrorscreen($this->event(['owner' => 'somebodyelse']));

        self::assertSame([], $this->reports, 'a provider must stay silent about a seat it was not offered');
        self::assertFalse($this->handlersMoved());
    }

    #[Test]
    public function itAnswersItsDocumentedLegacyToken(): void
    {
        // 'tracy' is what 2.7.3's development builds used before the token became the
        // dirname. Aliases live in the provider, never in core -- so the provider is the
        // only place this can be verified. developer_request:false keeps the case on the
        // safe side of the gate while still proving the alias is answered.
        \XtracyCorePreload::eventCoreDebugErrorscreen($this->event([
            'owner' => 'tracy',
            'developer_request' => false,
        ]));

        self::assertCount(1, $this->reports, 'the legacy spelling must still be answered');
        self::assertSame('disabled', $this->reports[0][0]);
    }

    #[Test]
    public function itRegistersNothingWhenCoreSendsNoReportingChannel(): void
    {
        // No channel means no way to say what happened. Registering anyway would leave the
        // published constants describing a screen nobody is showing, which is the exact
        // silence this seam was built to remove.
        \XtracyCorePreload::eventCoreDebugErrorscreen([
            'owner' => 'xtracy',
            'developer_request' => true,
        ]);

        self::assertSame([], $this->reports, 'with no channel there is nothing to report through');
        self::assertFalse($this->handlersMoved(), 'no reporting channel must mean no registration');
    }

    #[Test]
    public function itStaysDormantWhenThePermissionIsNotGranted(): void
    {
        // The developer gate and the module permission answer different questions; a site
        // can withhold Tracy from an administrator who would otherwise qualify. This
        // returns at the permission check, before any Tracy code is reached.
        require_once __DIR__ . '/fixtures/Permission.php';
        $GLOBALS['__xtracy_test_permission_granted'] = false;

        \XtracyCorePreload::eventCoreDebugErrorscreen($this->event());

        self::assertSame('disabled', $this->reports[0][0] ?? '');
        self::assertFalse($this->handlersMoved(), 'a withheld permission must register nothing');
    }

    #[Test]
    public function theOwnerTokenIsTheModuleDirectoryName(): void
    {
        // Core resolves a token to a directory to go and look in. If these ever diverge,
        // 'error_screen' => 'xtracy' in debug.php stops finding this module and the
        // failure looks like "the module is broken" rather than "the token is wrong".
        self::assertSame(
            basename(\dirname(__DIR__, 2)),
            \XtracyCorePreload::OWNER,
            'the owner token must equal the module dirname'
        );
    }
}
