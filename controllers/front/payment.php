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
/** Call init.php to initialize context */
require_once(dirname(__FILE__).'/../../../../init.php');

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
if ($debug)
	$log->debug('Check currency > $result_currency = '.$result_currency['iso_code'],$cart->id);

$supported_currencies = explode(';', Configuration::get('PAYPLUG_MODULE_CURRENCIES'));
if ($debug)
	$log->debug('Check currency > $supported_currencies = '.Configuration::get('PAYPLUG_MODULE_CURRENCIES'),$cart->id);

if (!in_array($result_currency['iso_code'], $supported_currencies)) {
	if ($debug)
		$log->error('Check currency > currency is NOT supported',$cart->id);
	return false;
} else {
	if ($debug)
		$log->debug('Check currency > currency is supported',$cart->id);
}

/**
 *  Check amount
 */
$amount = $context->cart->getOrderTotal(true, Cart::BOTH) * 100;
if ($debug)
	$log->debug('Check amount > $amount = '.$amount,$cart->id);
if ($amount < Configuration::get('PAYPLUG_MODULE_MIN_AMOUNT') * 100 || $amount > Configuration::get('PAYPLUG_MODULE_MAX_AMOUNT') * 100) {
	if ($debug) {
		$log->debug('Check amount > PAYPLUG_MODULE_MIN_AMOUNT = '.Configuration::get('PAYPLUG_MODULE_MIN_AMOUNT') * 100,$cart->id);
		$log->debug('Check amount > PAYPLUG_MODULE_MAX_AMOUNT = '.Configuration::get('PAYPLUG_MODULE_MAX_AMOUNT') * 100,$cart->id);
		$log->error('Check amount > cart amount is NOT between MIN and MAX',$cart->id);
	}
	return false;
} else {
	if ($debug)
		$log->debug('Check amount > cart amount is between MIN and MAX',$cart->id);
}

/**
 *  Parameters for payment url
 */
$url_payment = Configuration::get('PAYPLUG_MODULE_URL');
if ($debug)
	$log->debug('URL parameters > $url_payment = '.$url_payment,$cart->id);
		
if (version_compare(_PS_VERSION_, '1.5', '<')) {
	if(Tools::getProtocol() == 'https://')
	    $baseurl = _PS_BASE_URL_SSL_;
	else
	    $baseurl = _PS_BASE_URL_;
} else {
	if(Tools::getShopProtocol() == 'https://')
	    $baseurl = _PS_BASE_URL_SSL_;
	else
	    $baseurl = _PS_BASE_URL_;
}

$base_return_url = $baseurl.__PS_BASE_URI__.'modules/payplug/controllers/front/validation.php';
if ($debug)
	$log->debug('URL parameters > $base_return_url = '.$base_return_url,$cart->id);
	
if (version_compare(_PS_VERSION_, '1.5', '<'))
	$customer = new Customer ($context->cookie->id_customer);
else
	$customer = $context->customer;

$params = array('amount'=>$amount,
				'custom_data'=>$context->cart->id,
				'origin'=>'Prestashop '._PS_VERSION_.' module '.$payplug->version,
				'currency'=>$result_currency['iso_code'],
				'ipn_url'=>$baseurl.__PS_BASE_URI__.'modules/payplug/ipn.php',
				'cancel_url'=>$base_return_url.'?ps=2&cartid='.$context->cart->id,
				'return_url'=>$base_return_url.'?ps=1&cartid='.$context->cart->id,
				'email'=>$customer->email,
				'firstname'=>$customer->firstname,
				'lastname'=>$customer->lastname,
				'order'=>$context->cart->id,
				'customer'=>$customer->id
				);
if ($debug) {
	$log->debug('URL parameters > amount = '.$amount,$cart->id);
	$log->debug('URL parameters > custom_data = '.$context->cart->id,$cart->id);
	$log->debug('URL parameters > origin = '.'Prestashop '._PS_VERSION_.' module '.$payplug->version,$cart->id);
	$log->debug('URL parameters > currency = '.$result_currency['iso_code'],$cart->id);
	$log->debug('URL parameters > ipn_url = '.$baseurl.__PS_BASE_URI__.'modules/payplug/ipn.php',$cart->id);
	$log->debug('URL parameters > cancel_url = '.$base_return_url.'?ps=2&cartid='.$context->cart->id,$cart->id);
	$log->debug('URL parameters > return_url = '.$base_return_url.'?ps=1&cartid='.$context->cart->id,$cart->id);
	$log->debug('URL parameters > email = '.$customer->email,$cart->id);
	$log->debug('URL parameters > firstname = '.$customer->firstname,$cart->id);
	$log->debug('URL parameters > lastname = '.$customer->lastname,$cart->id);
	$log->debug('URL parameters > order = '.$context->cart->id,$cart->id);
	$log->debug('URL parameters > customer = '.$customer->id,$cart->id);
}
				
$url_params = http_build_query($params);
if ($debug)
	$log->debug('URL parameters > $url_params = '.$url_params,$cart->id);
	
$privatekey = Configuration::get('PAYPLUG_MODULE_KEY');
if ($debug)
	$log->debug('URL parameters > $privatekey = '.$privatekey,$cart->id);
		
$ssl_sign = openssl_sign($url_params, $signature, $privatekey, $signature_alg = OPENSSL_ALGO_SHA1);
if ($debug)
	$log->debug('URL parameters > openssl_sign() = '.$ssl_sign,$cart->id);
	
$url_param_base_encode = base64_encode($url_params);
if ($debug)
	$log->debug('URL parameters > $url_param_base_encode = '.$url_param_base_encode,$cart->id);
	
$signature = base64_encode($signature);
if ($debug)
	$log->debug('URL parameters > $signature = '.$signature,$cart->id);

if ($debug)
	$log->debug('URL parameters > redirectLink = '.$url_payment.'?data='.urlencode($url_param_base_encode).'&sign='.urlencode($signature),$cart->id);
Payplug::redirectForVersion($url_payment.'?data='.urlencode($url_param_base_encode).'&sign='.urlencode($signature));