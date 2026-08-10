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

use Xmf\Module\Admin;
use Xmf\Module\Helper;
use Xmf\Module\Helper\Permission;
use Xmf\Request;

require_once __DIR__ . '/admin_header.php';

require_once XOOPS_ROOT_PATH . '/class/xoopsformloader.php';

$moduleAdmin = Admin::getInstance();
$moduleAdmin->displayNavigation('permissions.php');

$helper = Helper::getHelper('xtracy');
$permHelper = new Permission();
if ($permHelper) {
    // The same permission the preload enforces; reused so the two never drift. The preload
    // class is loaded at boot; fall back to the literals only on the off chance it is not.
    $permissionName = class_exists('XtracyCorePreload')
        ? \XtracyCorePreload::PERMISSION_NAME
        : 'use_xtracy';
    $permissionItemId = class_exists('XtracyCorePreload')
        ? \XtracyCorePreload::PERMISSION_ITEM_ID
        : 0;

    // if this is a post operation get our variables
    if ('POST' === Request::getMethod()) {
        if (! $GLOBALS['xoopsSecurity']->check()) {
            redirect_header(XOOPS_URL . '/', 3, $GLOBALS['xoopsSecurity']->getErrors());
        }
        // Get the group input from the request
        $name = $permHelper->defaultFieldName($permissionName, $permissionItemId);
        $groups = Request::getVar($name, [], 'POST');

        // Ensure $groups is an array
        if (! is_array($groups)) {
            $groups = (array) $groups;
        }

        // Save the permission, then Post/Redirect/Get: check the result, and redirect back
        // to the form on success so a refresh cannot resubmit and a failed save is not
        // reported as a success. Rendering straight after the POST did both.
        $saved = $permHelper->savePermissionForItem($permissionName, $permissionItemId, $groups);
        redirect_header(
            'permissions.php',
            2,
            $saved ? _MA_XTRACY_FORM_PROCESSED : _MA_XTRACY_PERMISSION_SAVE_FAILED
        );
    }
    $form = new XoopsThemeForm(_MA_XTRACY_PERMISSION_FORM, 'form', '', 'POST', true);
    $permElement = $permHelper->getGroupSelectFormForItem(
        $permissionName,
        $permissionItemId,
        _MA_XTRACY_PERMISSION_GROUPS,
        null,
        true
    );

    $form->addElement($permElement);
    $form->addElement(new XoopsFormButton('', 'submit', _MA_XTRACY_FORM_SUBMIT, 'submit'));

    echo $form->render();
}

require_once __DIR__ . '/admin_footer.php';
