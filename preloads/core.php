<?php
/**
 * xtracy — Tracy error screen provider for XOOPS 2.7.3+
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @category            Debug
 * @package             xtracy
 * @copyright       (c) 2000-2026 XOOPS Project (https://xoops.org)
 * @license             GNU GPL 2 (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link                https://xoops.org
 */

defined('XOOPS_ROOT_PATH') || exit('Restricted access');

/**
 * Answers core.debug.errorscreen for the owner token 'xtracy' -- this module's dirname.
 *
 * The older spelling 'tracy', used by 2.7.3 development builds, is still answered; see
 * LEGACY_OWNERS below. Core knows neither name as anything but a string a site wrote in
 * its own config file: aliases belong to the provider that owns the name.
 *
 * The whole module is this one file. Core fires the event as the last statement of
 * include/common.php, having already decided that debugging is on and that this site
 * declared this module as its error-screen owner; everything below is the part that is
 * specific to nette/tracy and therefore has no business living in core.
 */
class XtracyCorePreload extends XoopsPreloadItem
{
    /**
     * The owner token this provider answers for: this module's dirname.
     *
     * Using the dirname means 'error_screen' => 'xtracy' names the directory to go and
     * look in, and no separate vocabulary of token names has to be documented anywhere
     * or kept unique across modules.
     */
    public const OWNER = 'xtracy';

    /**
     * Older spellings this module still answers to.
     *
     * 'tracy' is what 2.7.3's development builds used before the token became the
     * dirname. It lives here rather than in core for the same reason the library's name
     * does: an alias for a vendor is the business of the module that depends on it.
     * Remove when no 2.7.3 site is still carrying the old debug.php.
     *
     * @var string[]
     */
    public const LEGACY_OWNERS = ['tracy'];

    /** The permission the Permissions admin page manages. */
    public const PERMISSION_NAME = 'use_xtracy';

    /** This module has one global permission rather than per-item ones. */
    public const PERMISSION_ITEM_ID = 0;

    /**
     * @param array $args ['owner' => string]
     * @return void
     */
    public static function eventCoreDebugErrorscreen($args)
    {
        // Not our token. Another provider will answer, or core will report 'unclaimed'.
        $owner = (string) ($args['owner'] ?? '');
        if (self::OWNER !== $owner && !in_array($owner, self::LEGACY_OWNERS, true)) {
            return;
        }

        // Core hands us the reporting channel in the event itself. Without it we cannot
        // say what happened, and a provider that registers silently is worse than one that
        // does nothing: the constants would describe a screen nobody is showing. Fail
        // closed on a core whose seam does not match this module's.
        $report = $args['report'] ?? null;
        if (!is_callable($report)) {
            return;
        }

        $message = '';
        $status = self::resolve($message, (bool) ($args['developer_request'] ?? false));

        // Report the outcome whatever it is, including a deliberate decision NOT to
        // register. Core publishes it as XOOPS_ERROR_SCREEN_STATUS; silence here would
        // be indistinguishable from this module not being installed at all.
        $report($status, $message);

        // Backwards compatibility for DebugBar 1.4.0 and earlier, which read these two
        // constants directly -- they predate the generic seam, and 1.4.0 is released.
        // Defined HERE and not in core: the vendor's name belongs to the module that
        // depends on the vendor. Remove when the DebugBar floor reaches the release that
        // reads XOOPS_ERROR_SCREEN_STATUS instead.
        defined('XOOPS_TRACY_STATUS') || define('XOOPS_TRACY_STATUS', $status);
        defined('XOOPS_TRACY_MESSAGE') || define('XOOPS_TRACY_MESSAGE', $message);
    }

    /**
     * Has the site granted this user the module's own permission?
     *
     * Fails closed when Xmf is unavailable: a permission that cannot be evaluated has not
     * been granted.
     *
     * @return bool
     */
    private static function hasModulePermission()
    {
        if (!class_exists('\\Xmf\\Module\\Helper\\Permission')) {
            return false;
        }

        $permissionHelper = new \Xmf\Module\Helper\Permission(self::OWNER);

        return (bool) $permissionHelper->checkPermission(self::PERMISSION_NAME, self::PERMISSION_ITEM_ID, false);
    }

    /**
     * Work out what should happen to Tracy, and do it.
     *
     * @param string|null $message          set to the human explanation for the status
     * @param bool        $developerRequest core's answer to xoops_isDeveloperRequest()
     * @return string active | disabled | missing | incompatible | error
     */
    private static function resolve(&$message, $developerRequest)
    {
        // Tracy renders a toolbar and, on failure, a BlueScreen carrying source excerpts,
        // file paths, superglobals and environment. In Development mode it shows those to
        // whoever is looking. Core passes its answer in the event and does not enforce it,
        // because a provider may legitimately render a production-safe page; one that
        // exposes internals, as this one does, must refuse.
        if (!$developerRequest) {
            $message = 'Tracy is dormant: this request is not from an authenticated site administrator.';

            return 'disabled';
        }

        // The module's own permission, which the Permissions admin page manages, checked
        // separately from core's gate. They answer different questions -- "may diagnostics
        // be exposed to whoever is making this request" and "has this site granted this
        // group xTracy" -- so a site can withhold Tracy from an administrator who would
        // otherwise qualify. Unchecked, the Permissions page would be decorative: it would
        // save a setting nothing ever read.
        if (!self::hasModulePermission()) {
            $message = 'Tracy is dormant: the use_xtracy permission is not granted to this user.';

            return 'disabled';
        }

        // Both spellings. The token is this module's dirname, so 'xtracy' is what a site
        // owner who pinned error_screen will reach for; 'tracy' is what the documentation
        // has always shown. Reading only one of them meant a plausible block was silently
        // ignored. The dirname wins where a site has both.
        $config = function_exists('xoops_getDebugConfig') ? xoops_getDebugConfig() : [];
        $settings = is_array($config[self::OWNER] ?? null) ? $config[self::OWNER] : [];
        if ([] === $settings) {
            $settings = is_array($config['tracy'] ?? null) ? $config['tracy'] : [];
        }

        // Three-state. null / absent means "auto": run whenever the library is present.
        // That is what makes a plain `composer require tracy/tracy` sufficient, with no
        // second edit to debug.php. Normalised here, in the module that gives the value
        // meaning -- core passes the block through without reading it.
        $wanted = $settings['enabled'] ?? null;
        $wanted = is_bool($wanted) ? $wanted : null;

        // The DebugBar module's one-click toggle writes only this small JSON file. Keeping
        // the mutable state out of debug.php means an admin button never has to rewrite
        // executable PHP -- the file it edits cannot be made to run anything.
        $override = function_exists('xoops_readDebugRuntimeOverride') ? xoops_readDebugRuntimeOverride() : [];
        if (is_bool($override['tracy_enabled'] ?? null)) {
            $wanted = $override['tracy_enabled'];
        }

        if (false === $wanted) {
            $message = 'Tracy is switched off for this installation.';

            return 'disabled';
        }

        $vendor = function_exists('xoops_findVendorDirectory') ? xoops_findVendorDirectory() : '';
        $installed = '' !== $vendor && is_dir($vendor . '/tracy/tracy');

        if (!$installed && !class_exists('\\Tracy\\Debugger')) {
            if (true === $wanted) {
                $message = 'Tracy is enabled in debug.php but tracy/tracy is not installed.';

                return 'missing';
            }
            $message = 'Tracy is not installed (composer require tracy/tracy).';

            return 'disabled';
        }

        // Screen the source BEFORE autoloading it. A compile-time attribute error cannot
        // be caught: by the time the class body is parsed the fatal has already happened.
        // Some Tracy dev builds put #[\Deprecated] on a property, which PHP 8.4 and 8.5
        // reject outright, so the only safe check is a textual one on the file.
        foreach ([
            $vendor . '/tracy/tracy/src/Tracy/Debugger/Debugger.php',
            $vendor . '/tracy/tracy/src/Tracy/Debugger.php',
        ] as $sourceFile) {
            if ('' === $vendor || !is_readable($sourceFile)) {
                continue;
            }
            $source = file_get_contents($sourceFile);
            if (is_string($source)
                && PHP_VERSION_ID >= 80400
                && 1 === preg_match('/#\[\\\\?Deprecated\]\s*public\s+static\s+\$maxLen/s', $source)) {
                $message = 'Installed Tracy build uses #[Deprecated] on a property and cannot load on PHP 8.4 or later.';

                return 'incompatible';
            }
        }

        if (!class_exists('\\Tracy\\Debugger')) {
            // The bootstrap does not itself require the Composer autoloader, and on a
            // site with no module supplying one this is the only chance to get it.
            if ('' !== $vendor && is_readable($vendor . '/autoload.php')) {
                include_once $vendor . '/autoload.php';
            }
            if (!class_exists('\\Tracy\\Debugger') && '' !== $vendor && is_readable($vendor . '/tracy/tracy/src/tracy.php')) {
                include_once $vendor . '/tracy/tracy/src/tracy.php';
            }
        }

        if (!class_exists('\\Tracy\\Debugger')) {
            $message = 'tracy/tracy is present on disk but Tracy\\Debugger could not be autoloaded.';

            return 'missing';
        }

        $logDirectory = (string) ($settings['log_directory'] ?? '');
        if ('' === $logDirectory) {
            $logDirectory = defined('XOOPS_VAR_PATH') ? XOOPS_VAR_PATH . '/logs' : '';
        }

        // Debugger::enable() THROWS when handed a directory that does not exist -- and it
        // registers its own handlers before it validates, so by then the exception is
        // Tracy's to handle and it terminates the request. A try/catch around enable()
        // cannot save this; the only defence is not handing it a bad path. A site whose
        // xoops_data/logs is absent (a fresh checkout, a deploy that skipped the writable
        // directories) would otherwise get a fatal on the last line of every page.
        if ('' !== $logDirectory && !(is_dir($logDirectory) && is_writable($logDirectory))) {
            $logDirectory = '';
        }

        try {
            // Debugger::Development is the 2.10+ spelling; older releases only have
            // DEVELOPMENT. Resolving it by name keeps this working across both without
            // pinning a Tracy version.
            $mode = defined('Tracy\\Debugger::Development')
                ? constant('Tracy\\Debugger::Development')
                : constant('Tracy\\Debugger::DEVELOPMENT');

            // Settings BEFORE enable(), so enable() is the last thing that happens.
            //
            // enable() is where Tracy calls set_error_handler(), set_exception_handler()
            // and register_shutdown_function(). Anything that throws after that point
            // leaves Tracy holding the handlers while this module reports 'error' -- and
            // since we catch our own failure below, core's catch never runs. Core hands
            // the two handlers back on an 'error' report, but a shutdown function cannot
            // be unregistered by anybody, so the fix that actually holds is to have
            // nothing fallible left to do.
            //
            // These three are plain declared statics on Debugger, read at render and log
            // time; enable() never assigns them. Setting them first is safe, and it is
            // the order Tracy's own documentation uses.
            \Tracy\Debugger::$strictMode = true === ($settings['strict_mode'] ?? false);
            \Tracy\Debugger::$showBar = false !== ($settings['show_bar'] ?? true);
            \Tracy\Debugger::$logSeverity = (int) ($settings['log_severity'] ?? E_WARNING);

            \Tracy\Debugger::enable($mode, '' !== $logDirectory ? $logDirectory : null);
        } catch (\Throwable $e) {
            $message = 'Tracy could not be initialised: ' . $e->getMessage();

            return 'error';
        }

        $message = '' !== $logDirectory
            ? 'Tracy is active.'
            : 'Tracy is active, but file logging is off: the log directory is missing or not writable.';

        return 'active';
    }
}
