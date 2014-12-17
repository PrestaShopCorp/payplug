<?php
/**
* 2013 - 2014 PayPlug SAS
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
* Do not edit || add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PayPlug SAS
*  @copyright 2013 - 2014 PayPlug SAS
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*/

require_once(dirname(__FILE__).'./../../../../config/config.inc.php');
// Call init.php to initialize context
require_once(dirname(__FILE__).'/../../../../init.php');

// Tips to include class of module and backward_compatibility
$payplug = Module::getInstanceByName('payplug');

// Check PS_VERSION
if (version_compare(_PS_VERSION_, '1.4', '<'))
	return;

if (version_compare(_PS_VERSION_, '1.5', '<'))
{
	$currency = Currency::getCurrent()->iso_code;
	$order_confirmation_url = 'order-confirmation.php?';
}
else
{
	$context = Context::getContext();
	$currency = $context->currency;
	$order_confirmation_url = 'index.php?controller=order-confirmation&';
}

if (!($cart_id = Tools::getValue('cartid')))
	Payplug::redirectForVersion('index.php?controller=order&step=1');

$cart = new Cart($cart_id);

/**
 * If no current cart, redirect to order page
 */
if (!$cart->id)
	Payplug::redirectForVersion('index.php?controller=order&step=1');

/**
 * If no GET parameter with payment status code
 */
if (!($ps = Tools::getValue('ps')) || $ps != 1)
	Payplug::redirectForVersion('index.php?controller=order&step=1');
if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$payplug->active)
	Payplug::redirectForVersion('index.php?controller=order&step=1');

/**
 * Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
 */
if (!Payplug::moduleIsActive())
	die($payplug->l('This payment method is not available.', 'validation'));

$customer = new Customer((int)$cart->id_customer);
if (!Validate::isLoadedObject($customer))
	Payplug::redirectForVersion('index.php?controller=order&step=1');

$total = (float)$cart->getOrderTotal(true, Cart::BOTH);

$order = new Order();
$order_id = $order->getOrderByCartId($cart->id);
if (!$order_id)
{
	// Get the right order status following module configuration (Sandbox or not)
	$order_state = Payplug::getOsConfiguration('waiting');
	$payplug->validateOrder($cart->id, $order_state, $total, $payplug->displayName, false, array(), (int)$currency->id, false, $customer->secure_key);
	$order = new Order($payplug->currentOrder);
}
else
{
	/**
	 * Ipn has been received
	 */
	$order = new Order($order_id);
}

// Change variable name, because $link is already instanciated
$link_redirect = $order_confirmation_url.'id_cart='.$cart->id.'&id_module='.$payplug->id.'&id_order='.$order->id.'&key='.$customer->secure_key;
Payplug::redirectForVersion($link_redirect);