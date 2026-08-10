<?php
defined('XOOPS_ROOT_PATH') || exit('Restricted access');

define('_MI_XTRACY_NAME', 'Tracy error screen');
define('_MI_XTRACY_DESC', "Replaces the XOOPS error page with nette/tracy for developers. Installing this module claims the error screen; it takes effect once xoops_data/data/debug.php is enabled. Install the library into xoops_lib with: composer require tracy/tracy");

define('_MI_XTRACY_HOME', 'Home');
define('_MI_XTRACY_ABOUT', 'About');
define('_MI_XTRACY_PERMISSIONS', 'Permissions');

//Help
\define('_MI_XTRACY_DIRNAME', basename(dirname(__DIR__, 2)));
\define('_MI_XTRACY_HELP_HEADER', __DIR__ . '/help/helpheader.tpl');
\define('_MI_XTRACY_BACK_2_ADMIN', 'Back to Administration of ');
\define('_MI_XTRACY_OVERVIEW', 'Overview');

//\define('_MI_XTRACY_HELP_DIR', __DIR__);

//help multipage
\define('_MI_XTRACY_DISCLAIMER', 'Disclaimer');
\define('_MI_XTRACY_LICENSE', 'License');
\define('_MI_XTRACY_SUPPORT', 'Support');