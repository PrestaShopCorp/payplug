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
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PayPlug SAS
*  @copyright 2013 - 2014 PayPlug SAS
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PayPlug SAS
*/

require_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');

$payplug = Module::getInstanceByName('payplug');

if (Payplug::getConfiguration('PAYPLUG_ERROR'))
{
	$display_errors = @ini_get('display_errors');
	@ini_set('display_errors', true);
}

/**
 * Check that payplug module is enabled
 */
if (!Payplug::moduleIsActive())
	die('PayPlug module is not enabled.');

/**
 * define getallheaders function for nginx web server
 */
if (!function_exists('getallheaders'))
{
	function getallheaders()
	{
		$headers = array();
		foreach ($_SERVER as $name => $value)
		{
			if (Tools::substr($name, 0, 5) == 'HTTP_')
			{
				$name = str_replace(' ', '-', ucwords(Tools::strtolower(str_replace('_', ' ', Tools::substr($name, 5)))));
				$headers[$name] = $value;
			}
			else if ($name == 'CONTENT_TYPE')
				$headers['Content-Type'] = $value;
			else if ($name == 'CONTENT_LENGTH')
				$headers['Content-Length'] = $value;
			else
				$headers[$name] = $value;
		}
		return $headers;
	}
}

/**
 * Get data from http request
 */
$headers = getallheaders();
/**
 * Avoid problems with lowercase/uppercase transaformations
 */
$headers = array_change_key_case($headers, CASE_UPPER);


if (!isset($headers['PAYPLUG-SIGNATURE']))
{
	header($_SERVER['SERVER_PROTOCOL'].' 403 Signature not provided', true, 403);
	die;
}

$signature = base64_decode($headers['PAYPLUG-SIGNATURE']);

$body = Tools::file_get_contents('php://input');
$data = Tools::jsonDecode($body);

$status = (int)$data->status;

$status_available = array(
	Payplug::PAYMENT_STATUS_PAID,
	Payplug::PAYMENT_STATUS_REFUND
);

if (in_array($status, $status_available))
{
	$public_key = Configuration::get('PAYPLUG_MODULE_PUBLIC_KEY');
	$check_signature = openssl_verify($body, $signature, $public_key, OPENSSL_ALGO_SHA1);
	$bool_sign = false;

	if ($check_signature == 1)
		$bool_sign = true;
	else if ($check_signature == 0)
	{
		echo 'Invalid signature';
		header($_SERVER['SERVER_PROTOCOL'].' 403 Invalid signature', true, 403);
		die;
	}
	else
	{
		echo 'Error while checking signature';
		header($_SERVER['SERVER_PROTOCOL'].' 500 Error while checking signature', true, 500);
		die;
	}

	if ($data && $bool_sign)
	{
		$cart = new Cart($data->custom_data);
		$address = new Address((int)$cart->id_address_invoice);
		Context::getContext()->country = new Country((int)$address->id_country);
		Context::getContext()->customer = new Customer((int)$cart->id_customer);
		Context::getContext()->language = new Language((int)$cart->id_lang);
		Context::getContext()->currency = new Currency((int)$cart->id_currency);
		$order = new Order();
		$order_id = $order->getOrderByCartId($cart->id);
		/**
		 * If existing order
		 */
		if ($order_id)
		{
			/**
			 * If status paid
			 */
			if ($status == Payplug::PAYMENT_STATUS_PAID)
			{
				$order = new Order($order_id);
				/**
				 * If order state is payment in progress by payplug
				 */
				$order_state = Payplug::getOsConfiguration('waiting');

				if ($order->getCurrentState() == $order_state)
				{
					$order_history = new OrderHistory();
					/**
					 * Change order state to payment paid by payplug
					 */
					$order_history->id_order = $order_id;
					$new_order_state = Payplug::getOsConfiguration('paid');
					$order_history->changeIdOrderState((int)$new_order_state, $order_id);
					$order_history->save();
					if (version_compare(_PS_VERSION_, '1.5', '>') && version_compare(_PS_VERSION_, '1.5.2', '<'))
					{
						$order->current_state = $order_history->id_order_state;
						$order->update();
					}
				}
			}
			/**
			 * If status refund
			 */
			else if ($status == Payplug::PAYMENT_STATUS_REFUND)
			{
				$order_history = new OrderHistory();
				/**
				 * Change order state to refund by payplug
				 */
				$order_history->id_order = $order_id;
				$new_order_state = Payplug::getOsConfiguration('refund');
				$order_history->changeIdOrderState((int)$new_order_state, $order_id);
				$order_history->save();
				if (version_compare(_PS_VERSION_, '1.5', '>') && version_compare(_PS_VERSION_, '1.5.2', '<'))
				{
					$order->current_state = $order_history->id_order_state;
					$order->update();
				}
			}
		}
		/**
		 * Else validate order
		 */
		else
		{
			if ($status == Payplug::PAYMENT_STATUS_PAID)
			{
				$extra_vars = array();
				$extra_vars['transaction_id'] = $data->id_transaction;
				$currency = (int)$cart->id_currency;
				$customer = new Customer((int)$cart->id_customer);
				$order_state = Payplug::getOsConfiguration('paid');
				$amount = (float)$data->amount / 100;
				$payplug->validateOrder($cart->id, $order_state, $amount, $payplug->displayName, null, $extra_vars, $currency, false, $customer->secure_key);
				if (version_compare(_PS_VERSION_, '1.5', '>') && version_compare(_PS_VERSION_, '1.5.2', '<'))
				{
					$order_id = Order::getOrderByCartId($cart->id);
					$order = new Order($order_id);
					$order_payment = end($order->getOrderPayments());
					$order_payment->transaction_id = $extra_vars['transaction_id'];
					$order_payment->update();
				}
			}
		}
		Configuration::updateValue('PAYPLUG_CONFIGURATION_OK', true);
	}
	else
	{
		echo 'Error : missing or wrong parameters.';
		header($_SERVER['SERVER_PROTOCOL'].' 400 Missing or wrong parameters', true, 400);
		die;
	}
}

if (Payplug::getConfiguration('PAYPLUG_ERROR'))
	@ini_set('display_errors', $display_errors);
