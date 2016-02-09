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
/** Call init.php to initialize context */
require_once(dirname(__FILE__).'/../../../../init.php');
require_once(dirname(__FILE__).'/../../classes/PayplugLock.php');

/** Tips to include class of module and backward_compatibility */
$payplug = Module::getInstanceByName('payplug');

/** Check PS_VERSION */
if (version_compare(_PS_VERSION_, '1.4', '<'))
	return;
	
/**
 * LOG SYSTEM if module is in debug mode
 */
$debug = $payplug::getConfiguration('PAYPLUG_DEBUG');
if ($debug) {
	include_once(_PS_MODULE_DIR_.'payplug/classes/MyLogPHP.class.php');
	$log = new MyLogPHP(_PS_MODULE_DIR_.'payplug/log/log-'.date("Y-m-d").'.csv');
}

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
	
if (!($cart_id = Tools::getValue('cartid'))) {
	if ($debug)
		$log->error('Get cart > no GET parameter cartid : '.$cart_id);
	Payplug::redirectForVersion('index.php?controller=order&step=1');
}

$cart = new Cart($cart_id);

/**
 * If no current cart, redirect to order page
 */
if (!$cart->id) {
	if ($debug)
		$log->error('Get cart > The cart canNOT be instantiate with this GET param cartid : '.$cart_id);
	Payplug::redirectForVersion('index.php?controller=order&step=1');
}
	

/**
 * If no GET parameter with payment status code
 */
if (!($ps = Tools::getValue('ps')) || $ps != 1) {
	if ($debug) {
		if ($ps == 2) {
			$log->debug('GET parameter ps = '.$ps.' > Order has been cancelled on PayPlug page', $cart->id);
		} else {
			$log->error('Get cart > wrong GET parameter ps = '.$ps, $cart->id);
		}
	}
		
	Payplug::redirectForVersion('index.php?controller=order&step=1');
}

if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$payplug->active) {
	if ($debug) {
		$log->debug('Get cart > $cart->id_customer = '.$cart->id_customer);
		$log->debug('Get cart > $cart->id_address_delivery = '.$cart->id_address_delivery);
		$log->debug('Get cart > $cart->id_address_invoice = '.$cart->id_address_invoice);
		$log->debug('Get cart > $payplug->active = '.$payplug->active);
		$log->error('Get cart > module is not activated or id_customer or id_address_delivery or id_address_invoice is unknown (see the 4 lines above)');
	}	
	Payplug::redirectForVersion('index.php?controller=order&step=1');
}
	

/**
 * Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
 */
//  TODO put this test earlier maybe
if (!Payplug::moduleIsActive()) {
	if ($debug)
		$log->error('Check if module is active > module is not yet activated validation process is going to die');
	die($payplug->l('This payment method is not available.', 'validation'));
}

/**
 * Check customer
 */

if ($debug)
	$log->debug('Check customer > $cart->id_customer = '.$cart->id_customer, $cart->id);		
$customer = new Customer((int)$cart->id_customer);
if (!Validate::isLoadedObject($customer)) {
	if ($debug)
		$log->error('Check customer > $customer is NOT valid = '.$cart->id_customer, $cart->id);
	Payplug::redirectForVersion('index.php?controller=order&step=1');
}
	
/**
 * Check total cart
 */
$total = (float)$cart->getOrderTotal(true, Cart::BOTH);
if ($debug)
	$log->debug('Check total cart > $total = '.$total, $cart->id);

/**
 * Check cart 
 */
$check_cart = PayplugLock::check($cart->id);
if ($debug)
	$log->debug('Check cart > PayplugLock::check', $cart->id);

/**
 * Create order
 */
$order_id = Order::getOrderByCartId($cart->id);
if ($debug)
	$log->debug('Create order > getOrderByCartId > $order_id = '.$order_id, $cart->id);

if (!$order_id)
{
	if ($debug)
		$log->debug('Create order > order is going to be created ', $cart->id);
	
	$cart_lock = PayplugLock::addLock($cart->id);
	if ($debug)
		$log->debug('Create order > lock is added to cart :'.$cart_lock, $cart->id);
	
	/** Get the right order status following module configuration (Sandbox or not) */
	$order_state = Payplug::getOsConfiguration('waiting');
	if ($debug)
		$log->debug('Create order > get the right order status (Sandbox or not) : '.$order_state, $cart->id);
		
	if ($debug) {
		$log->debug('Create order >  validateOrder() params : ', $cart->id);
		$log->debug('Create order >  $cart->id = '.$cart->id, $cart->id);
		$log->debug('Create order >  $order_state = '.$order_state, $cart->id);
		$log->debug('Create order >  $total = '.$total, $cart->id);
		$log->debug('Create order >  $payplug->displayName = '.$payplug->displayName, $cart->id);
		$log->debug('Create order >  false', $cart->id);
		$log->debug('Create order >  array()', $cart->id);
		$log->debug('Create order >  $currency->id = '.$currency->id, $cart->id);
		$log->debug('Create order >  false', $cart->id);
		$log->debug('Create order >  $customer->secure_key = '.$customer->secure_key, $cart->id);
	}	
	$validateOrder_result = $payplug->validateOrder($cart->id, $order_state, $total, $payplug->displayName, false, array(), (int)$currency->id, false, $customer->secure_key);
	if ($debug)
		$log->debug('Create order >  validateOrder() result : '.$validateOrder_result, $cart->id);
	
	$cart_unlock = PayplugLock::deleteLock($cart->id);
	if ($debug)
		$log->debug('Create order > lock is delete to cart : '.$cart_unlock, $cart->id);
		
	$order_id = $payplug->currentOrder;
	if ($debug)
		$log->debug('Create order > $order_id = '.$order_id, $cart->id);
	
} else {
	if ($debug)
		$log->debug('Create order > order already exists = '.$order_id, $cart->id);
}

/** Change variable name, because $link is already instanciated */
$link_redirect = $order_confirmation_url.'id_cart='.$cart->id.'&id_module='.$payplug->id.'&id_order='.$order_id.'&key='.$customer->secure_key;
if ($debug)
	$log->debug('Create order > $link_redirect = '.$link_redirect, $cart->id);
		
Payplug::redirectForVersion($link_redirect);