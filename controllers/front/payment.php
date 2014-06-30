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

require_once(dirname(__FILE__).'./../../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../../header.php');
require_once(dirname(__FILE__).'./../../payplug.php');

/** Backward compatibility */
require(dirname(__FILE__).'/../../backward_compatibility/backward.php');

/**
 * Check currency used
 */
$context = Context::getContext();
$cookie = $context->cookie;

$result_currency = array();
$cart = $context->cart;
if (version_compare(_PS_VERSION_, '1.5', '<'))
	$result_currency['iso_code'] = Currency::getCurrent()->iso_code;
else
{
	$currency = $cart->id_currency;
	$result_currency = Currency::getCurrency($currency);
}

$supported_currencies = explode(';', Configuration::get('PAYPLUG_MODULE_CURRENCIES'));
if (!in_array($result_currency['iso_code'], $supported_currencies))
	return false;

/**
 *  Check amount
 */
$amount = $context->cart->getOrderTotal(true, Cart::BOTH) * 100;
if ($amount < Configuration::get('PAYPLUG_MODULE_MIN_AMOUNT') * 100 || $amount > Configuration::get('PAYPLUG_MODULE_MAX_AMOUNT') * 100)
	return false;

/**
 *  Parameters for payment url
 */
$url_payment = Configuration::get('PAYPLUG_MODULE_URL');
$base_return_url = _PS_BASE_URL_.__PS_BASE_URI__.'modules/payplug/controllers/front/validation.php';

if (version_compare(_PS_VERSION_, '1.5', '<'))
	$customer = new Customer ($context->cookie->id_customer);
else
	$customer = $context->customer;

$payplug = Module::getInstanceByName('payplug');

$params = array('amount'=>$amount,
				'custom_data'=>$context->cart->id,
				'origin'=>'Prestashop '._PS_VERSION_.' module '.$payplug->version,
				'currency'=>$result_currency['iso_code'],
				'ipn_url'=>_PS_BASE_URL_.__PS_BASE_URI__.'modules/payplug/ipn.php',
				'cancel_url'=>$base_return_url.'?ps=2&cartid='.$context->cart->id,
				'return_url'=>$base_return_url.'?ps=1&cartid='.$context->cart->id,
				'email'=>$customer->email,
				'firstname'=>$customer->firstname,
				'lastname'=>$customer->lastname,
				'order'=>$context->cart->id,
				'customer'=>$customer->id
				);
$url_params = http_build_query($params);
$privatekey = Configuration::get('PAYPLUG_MODULE_KEY');
openssl_sign($url_params, $signature, $privatekey, $signature_alg = OPENSSL_ALGO_SHA1);
$url_param_base_encode = base64_encode($url_params);
$signature = base64_encode($signature);
Payplug::redirectForVersion($url_payment.'?data='.urlencode($url_param_base_encode).'&sign='.urlencode($signature));