<?php
/**
 * xtracy install / uninstall hooks.
 *
 * @category            Debug
 * @package             xtracy
 * @copyright       (c) 2000-2026 XOOPS Project (https://xoops.org)
 * @license             GNU GPL 2 (https://www.gnu.org/licenses/gpl-2.0.html)
 */

defined('XOOPS_ROOT_PATH') || exit('Restricted access');

/**
 * Grant the module's use_xtracy permission to the webmaster group.
 *
 * A provider that installs with the permission granted to nobody is dormant with no visible
 * cause -- the developer gate passes but the module's own check refuses everyone. Granting
 * the webmaster group on install is the normal XOOPS permission contract; the site narrows
 * it under Permissions if it wants to.
 *
 * @param string $dirname     this module's dirname
 * @param bool   $onlyIfEmpty when true, grant only when no group is set yet, so an update
 *                            re-asserting the grant never clobbers a site's own narrowing
 * @return void
 */
function xtracy_grant_permission_to_admins($dirname, $onlyIfEmpty = false)
{
    if (!class_exists('\\Xmf\\Module\\Helper\\Permission')) {
        return;
    }

    $permission = new \Xmf\Module\Helper\Permission((string) $dirname);
    $adminGroup = defined('XOOPS_GROUP_ADMIN') ? (int) constant('XOOPS_GROUP_ADMIN') : 1;

    if ($onlyIfEmpty && method_exists($permission, 'getGroupsForItem')) {
        $existing = $permission->getGroupsForItem('use_xtracy', 0);
        if (is_array($existing) && [] !== $existing) {
            return;
        }
    }

    if (method_exists($permission, 'savePermissionForItem')) {
        $permission->savePermissionForItem('use_xtracy', 0, [$adminGroup]);
    }
}

/**
 * Refuse to install on a core that does not fire core.debug.errorscreen.
 *
 * min_xoops cannot express this. The seam arrived DURING 2.7.3's development, so a
 * 2.7.3 install may or may not have it, and a version floor would let the module
 * install cleanly and then sit inert -- with the core activating Tracy by itself and
 * enabling or disabling this module changing nothing at all. That failure is invisible
 * and takes a while to work out, so it is worth one function call to prevent.
 *
 * Test the capability, not the version number.
 *
 * @param XoopsModule $module
 * @return bool false to abort the installation
 */
function xoops_module_pre_install_xtracy($module)
{
    if (function_exists('xoops_activateErrorScreen')) {
        return true;
    }

    $module->setErrors(
        'This XOOPS core does not provide the error-screen seam this module plugs into: '
        . 'xoops_activateErrorScreen() is not defined, and include/common.php never fires '
        . 'core.debug.errorscreen. Installing would have no effect — on such a core the '
        . 'error screen is activated by the core itself. Update the core, then install.'
    );

    return false;
}

/**
 * Claim the error screen, unless another provider already holds it.
 *
 * First installed wins, and core enforces that against an ORDINARY claim -- this cannot
 * take a seat another module is sitting in by asking normally. A deliberate handover uses
 * core's $force, which the update hook does after establishing the holder has stopped;
 * module code is trusted code, so the guard stops an accident rather than an attacker.
 * When the seat IS taken the installation still
 * succeeds: refusing would mean you could not keep two providers installed and switch
 * between them, which is a reasonable thing to want. It just says so.
 *
 * @param XoopsModule $module
 * @return bool true — installation succeeds either way
 */
function xoops_module_install_xtracy($module)
{
    if (!function_exists('xoops_recordErrorScreenOwner')) {
        return true;
    }

    $dirname = $module->getVar('dirname', 'n');

    if (xoops_recordErrorScreenOwner($dirname)) {
        // Grant the module's own permission to the webmaster group on install, so an
        // administrator can see the screen without a separate trip to Permissions. Without
        // this the module installs granted to nobody and stays dormant with no hint why. A
        // site can narrow it afterwards under Permissions.
        xtracy_grant_permission_to_admins($dirname);

        // The second sentence is not decoration. The error screen activates under the
        // file-based debug configuration only, so on a site running Admin -> Preferences
        // -> Debug Mode alone this module is recorded and dormant -- and an install
        // message that said only "this module now owns the error screen" would be making
        // a promise that site will never see kept.
        $module->setMessage(
            'This module now owns the error screen, and the webmaster group may see it. It '
            . 'takes effect once xoops_data/data/debug.php exists and is enabled — Admin → '
            . 'Preferences → Debug Mode alone does not activate it. Install the library '
            . 'with: composer require tracy/tracy'
        );

        return true;
    }

    $held = function_exists('xoops_getRecordedErrorScreenOwner')
        ? htmlspecialchars((string) xoops_getRecordedErrorScreenOwner(), ENT_QUOTES)
        : '';

    // All three routes below work, and they are listed in the order of least disruption.
    //
    // "Deactivate and update" works because this module's update hook establishes that the
    // holder has stopped before claiming with $force -- an ordinary claim would still be
    // refused, which is the point of $force existing. An earlier version of this comment
    // said the opposite, written before the update hook was wired, and then sat directly
    // above a message recommending exactly what it said would not work.
    $module->setMessage(
        "WARNING: '" . $held . "' already claims the error screen, and installing this "
        . 'module has not changed that. To hand the screen over: deactivate ' . $held
        . ' and update this module, or uninstall ' . $held . ' and reinstall this module, '
        . "or pin it with 'error_screen' => '" . htmlspecialchars($dirname, ENT_QUOTES)
        . "' in xoops_data/data/debug.php."
    );

    return true;
}

/**
 * Take the error screen from a holder that is gone or inactive.
 *
 * The deliberate handover. Core refuses an ordinary claim while another token is
 * recorded -- correctly, since an update must not quietly take a seat from a provider
 * that is still running -- so the transfer needs someone to establish that the holder is
 * no longer running. That is a question about the module table, which core's bootstrap
 * file cannot answer and should not try to; it belongs here, where the module handler is
 * available and an administrator is watching.
 *
 * A holder that is still installed AND active keeps the seat, and this says so.
 *
 * @param XoopsModule $module
 * @return bool true — the update itself succeeds either way
 */
function xoops_module_update_xtracy($module)
{
    if (!function_exists('xoops_recordErrorScreenOwner')) {
        return true;
    }

    $dirname = $module->getVar('dirname', 'n');
    $held = function_exists('xoops_getRecordedErrorScreenOwner')
        ? (string) xoops_getRecordedErrorScreenOwner()
        : '';

    if ('' === $held || $held === $dirname) {
        // Free, or already ours. An ordinary claim covers both, and recording again is
        // how a module that was uninstalled-and-reinstalled elsewhere gets the seat back.
        xoops_recordErrorScreenOwner($dirname);
        // Re-assert the webmaster grant for a site that installed a version predating it;
        // only when nothing is set yet, so a site's own narrowing is never clobbered.
        xtracy_grant_permission_to_admins($dirname, true);

        return true;
    }

    $holder = null;
    $handler = function_exists('xoops_getHandler') ? xoops_getHandler('module') : null;
    if (is_object($handler) && method_exists($handler, 'getByDirname')) {
        $holder = $handler->getByDirname($held);
    }

    // Fail CLOSED: an object we cannot read the state of is treated as ACTIVE, so a
    // transfer never force-steals a seat from a holder that might still be running.
    // Prefer isactive(); fall back to the raw var; refuse the steal if neither is legible.
    if (!is_object($holder)) {
        $holderIsRunning = false;
    } elseif (method_exists($holder, 'isactive')) {
        $holderIsRunning = (bool) $holder->isactive();
    } elseif (method_exists($holder, 'getVar')) {
        $holderIsRunning = 1 === (int) $holder->getVar('isactive');
    } else {
        $holderIsRunning = true;
    }

    if ($holderIsRunning) {
        $safeHeld = htmlspecialchars($held, ENT_QUOTES);
        $module->setMessage(
            "The error screen still belongs to '" . $safeHeld . "', which is installed and "
            . 'active, so this update has not taken it. Deactivate ' . $safeHeld
            . ' and update this module again, or pin the owner in xoops_data/data/debug.php.'
        );

        return true;
    }

    // Gone or inactive: take it, and say from whom.
    if (xoops_recordErrorScreenOwner($dirname, true)) {
        $module->setMessage(
            "Took the error screen from '" . htmlspecialchars($held, ENT_QUOTES)
            . "', which is no longer active."
        );
    } else {
        $module->setMessage(
            'WARNING: could not record the error-screen owner. Check that xoops_data/data '
            . 'is writable and update this module again.'
        );
    }

    return true;
}

/**
 * Take the recorded ownership with us on the way out -- but only if we hold it.
 *
 * Uninstalling is the deliberate act that releases the seat. DEACTIVATING is not: a
 * site that switches this module off for an afternoon should find its setup intact when
 * it switches back on, and core does not promote another provider into a released seat
 * in any case.
 *
 * @param XoopsModule $module
 * @return bool
 */
function xoops_module_uninstall_xtracy($module)
{
    if (function_exists('xoops_releaseErrorScreenOwner')
        && !xoops_releaseErrorScreenOwner($module->getVar('dirname', 'n'))) {
        // Uninstalling still succeeds -- refusing to uninstall over an unwritable data
        // directory would be worse -- but the stale record has to be said out loud, or the
        // site sits at 'unclaimed' naming a module that is no longer there.
        $module->setMessage(
            'WARNING: could not release the recorded error-screen owner. Check that '
            . 'xoops_data/data is writable, then remove "error_screen_owner" from '
            . 'xoops_data/data/debug-runtime.json by hand.'
        );
    }

    return true;
}
