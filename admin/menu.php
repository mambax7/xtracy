<?php declare(strict_types=1);
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @author    Michael Beck (mambax7@gmail.com)
 */

use Xmf\Language;
use Xmf\Module\Admin;

if (! defined('XOOPS_ROOT_PATH')) {
    throw new \RuntimeException('XOOPS root path not defined');
}

$moduleDirName = \basename(\dirname(__DIR__));
Language::load('modinfo', $moduleDirName);
Language::load('admin', $moduleDirName);


// get path to icons
$pathIcon32 = '';
if (class_exists('Xmf\Module\Admin', true)) {
    $pathIcon32 = Admin::menuIconPath('');
}

$adminmenu = [];
// Index
$adminmenu[] = [
    'title' => _MI_XTRACY_HOME,
    'link' => 'admin/index.php',
    'icon' => $pathIcon32 . 'home.png',
];
// Permissions
$adminmenu[] = [
    'title' => _MI_XTRACY_PERMISSIONS,
    'link' => 'admin/permissions.php',
    'icon' => $pathIcon32 . 'permissions.png',
];

// About
$adminmenu[] = [
    'title' => _MI_XTRACY_ABOUT,
    'link' => 'admin/about.php',
    'icon' => $pathIcon32 . 'about.png',
];
