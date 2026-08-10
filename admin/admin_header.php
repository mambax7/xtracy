<?php declare(strict_types=1);

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

use Xmf\Language;

/**
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @author    Michael Beck (mambax7@gmail.com)
 * @author    Richard Griffith <richard@geekwright.com> (xWhoops, from which this
 *            admin scaffolding is derived)
 */
$path = dirname(__DIR__, 3);
require_once $path . '/mainfile.php';
require_once $path . '/include/cp_functions.php';
require_once $path . '/include/cp_header.php';

if (! class_exists('\Xmf\Module\Admin')) {
    die('XMF is required.');
}

global $xoopsModule;

$moduleDirName = \basename(\dirname(__DIR__));
Language::load('admin', $moduleDirName);

xoops_cp_header();
