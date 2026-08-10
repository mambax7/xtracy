<?php
/**
 * xtracy — Tracy error screen provider
 *
 * @category            Debug
 * @package             xtracy
 * @copyright       (c) 2000-2026 XOOPS Project (https://xoops.org)
 * @license             GNU GPL 2 (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link                https://xoops.org
 * @author              Michael Beck (mambax7@gmail.com)
 */

defined('XOOPS_ROOT_PATH') || exit('Restricted access');

$modversion = [];

$modversion['name']          = _MI_XTRACY_NAME;
$modversion['version']       = '1.0.0-Beta1';
$modversion['module_status'] = 'Beta1';
$modversion['status']        = 'Beta1';
$modversion['release_date']  = '2026/08/08';
$modversion['description']   = _MI_XTRACY_DESC;
$modversion['author']        = 'Michael Beck';
$modversion['nickname']      = 'mamba';
$modversion['credits']       = 'The XOOPS Project. Admin scaffolding derived from xWhoops by Richard Griffith (geekwright).';
$modversion['license']       = 'GNU GPL 2 or later';
$modversion['license_url']   = 'https://www.gnu.org/licenses/gpl-2.0.html';
$modversion['official']      = 0;
$modversion['dirname']       = basename(__DIR__);
$modversion['image']         = 'assets/images/logoModule.png';

// Read by the About page (Frameworks/moduleclasses/moduleadmin), which renders blanks
// without them: author, description, image, license, license_url, module_website_name,
// module_website_url, name, nickname, release_date and website are the full set.
$modversion['website']             = 'https://github.com/XoopsModules27x/xtracy';
$modversion['module_website_url']  = 'https://github.com/XoopsModules27x/xtracy';
$modversion['module_website_name'] = 'XOOPS Project';

// 2.7.3 is a hard floor, not a soft one. This module is nothing but an answer to
// core.debug.errorscreen; on a core that does not fire that event it would install
// cleanly and then do nothing at all, which is worse than refusing.
//
// The version floor is necessary but NOT sufficient: the seam landed during 2.7.3's
// development, so a 2.7.3 install may predate it. install.php tests for the function
// itself and aborts, because a version number cannot express a capability.
$modversion['min_php']   = '8.2.0';
$modversion['min_xoops'] = '2.7.3';

// Error-screen ownership. Installing claims the seat, uninstalling releases it, and an
// update takes it from a holder that is gone or inactive.
$modversion['onInstall']   = 'include/install.php';
$modversion['onUpdate']    = 'include/install.php';
$modversion['onUninstall'] = 'include/install.php';

// No front end: the module is a provider and an admin section, nothing more.
$modversion['hasMain'] = false;

// Admin things
$modversion['hasAdmin']    = true;
$modversion['system_menu'] = true;
$modversion['adminindex']  = 'admin/index.php';
$modversion['adminmenu']   = 'admin/menu.php';

// ------------------- Help files ------------------- //
$modversion['help']        = 'page=help';
$modversion['helpsection'] = [
    ['name' => _MI_XTRACY_OVERVIEW, 'link' => 'page=help'],
    ['name' => _MI_XTRACY_DISCLAIMER, 'link' => 'page=disclaimer'],
    ['name' => _MI_XTRACY_LICENSE, 'link' => 'page=license'],
    ['name' => _MI_XTRACY_SUPPORT, 'link' => 'page=support'],
];
