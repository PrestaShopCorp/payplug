<?php
/**
* 2013 - 2015 PayPlug SAS
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PayPlug SAS
*  @copyright 2013 - 2015 PayPlug SAS
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_9_7($module)
{
    // Add test status && add hook
    Configuration::deleteByName('PAYPLUG_ORDER_STATE_REFUND');

    Payplug::updateConfiguration('PAYPLUG_SANDBOX', '0');

    $install = new InstallPayplug();
    $install->createOrderState();

    $module->registerHook('header');

    $install->installPayplugLock();

    return true; // Return true if success.
}
