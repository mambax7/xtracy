<?php

declare(strict_types=1);

/**
 * PHPStan bootstrap — XOOPS module DevOps baseline.
 *
 * Defines the legacy XOOPS constants/globals that static analysis needs to "see"
 * but that only exist at runtime inside a booted XOOPS instance.
 *
 * xoops-overlay:profile=core27
 */

// Common XOOPS path constants referenced by modules at analysis time.
if (! defined('XOOPS_ROOT_PATH')) {
    define('XOOPS_ROOT_PATH', __DIR__);
}
if (! defined('XOOPS_TRUST_PATH')) {
    define('XOOPS_TRUST_PATH', __DIR__);
}
if (! defined('XOOPS_URL')) {
    define('XOOPS_URL', 'https://localhost');
}
if (! defined('_CHARSET')) {
    define('_CHARSET', 'utf-8');
}

// The module's own language constants (_MI_*, _MA_*). The files are pure define()
// lists, so loading them lets analysis resolve every constant to a real string --
// and catch a mistyped constant name -- instead of baselining each one as unknown.
// index.php is the anti-indexing stub (it calls exit) and must not be loaded.
$xoopsLanguageFiles = glob(__DIR__ . '/language/english/*.php');
foreach (false === $xoopsLanguageFiles ? [] : $xoopsLanguageFiles as $xoopsLanguageFile) {
    if ('index.php' !== basename($xoopsLanguageFile)) {
        require_once $xoopsLanguageFile;
    }
}
unset($xoopsLanguageFile, $xoopsLanguageFiles);

// Profile target: XoopsCore27 / PHP 8.2+
