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
// Explicit allowlist: only known language-definition files are ever executed here
// (never index.php or any stray PHP). A new language file must be added to this
// list -- forgetting it is loud, as PHPStan then reports its constants as unknown.
foreach (['admin.php', 'modinfo.php'] as $xoopsLanguageFile) {
    require_once __DIR__ . '/language/english/' . $xoopsLanguageFile;
}
unset($xoopsLanguageFile);

// Profile target: XoopsCore27 / PHP 8.2+
