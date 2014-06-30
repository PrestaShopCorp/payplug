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

if (!defined('_PS_VERSION_'))
	exit;

class InstallPayplug
{
	public function createConfig()
	{
		Configuration::updateValue('PAYPLUG_MODULE_KEY', '');
		Configuration::updateValue('PAYPLUG_MODULE_PUBLIC_KEY', '');
		Configuration::updateValue('PAYPLUG_MODULE_URL', '');
		Configuration::updateValue('PAYPLUG_MODULE_MIN_AMOUNT', '');
		Configuration::updateValue('PAYPLUG_MODULE_MAX_AMOUNT', '');
		Configuration::updateValue('PAYPLUG_MODULE_CURRENCIES', '');
	}
	public function updateConfig($private_key = '', $public_key = '', $url = '', $min_amount = '', $max_amount = '', $currencies = '')
	{
		Configuration::updateValue('PAYPLUG_MODULE_KEY', ''.$private_key.'');
		Configuration::updateValue('PAYPLUG_MODULE_PUBLIC_KEY', ''.$public_key.'');
		Configuration::updateValue('PAYPLUG_MODULE_URL', ''.$url.'');
		Configuration::updateValue('PAYPLUG_MODULE_MIN_AMOUNT', $min_amount);
		Configuration::updateValue('PAYPLUG_MODULE_MAX_AMOUNT', $max_amount);
		Configuration::updateValue('PAYPLUG_MODULE_CURRENCIES', $currencies);
	}
	public function deleteConfig()
	{
		Configuration::deleteByName('PAYPLUG_MODULE_KEY');
		Configuration::deleteByName('PAYPLUG_MODULE_PUBLIC_KEY');
		Configuration::deleteByName('PAYPLUG_MODULE_URL');
		Configuration::deleteByName('PAYPLUG_MODULE_MIN_AMOUNT', '');
		Configuration::deleteByName('PAYPLUG_MODULE_MAX_AMOUNT', '');
		Configuration::deleteByName('PAYPLUG_MODULE_CURRENCIES', '');
	}

	/**
	 * Create PayPlug order states waiting and paid and save their id in Configuration
	 */
	public function createOrderState()
	{
		if (!Configuration::get('PAYPLUG_ORDER_STATE_WAITING'))
		{
			$order_state = new OrderState();
			$this->initOrderState($order_state, 'waiting');
			if ($order_state->add())
			{
				$source = dirname(__FILE__).'/logo.gif';
				$destination = dirname(__FILE__).'/../../img/os/'.(int)$order_state->id.'.gif';
				copy($source, $destination);
			}
			Configuration::updateValue('PAYPLUG_ORDER_STATE_WAITING', (int)$order_state->id);
		}
		else
		{
			$order_state = new OrderState(Configuration::get('PAYPLUG_ORDER_STATE_WAITING'));
			$this->initOrderState($order_state, 'waiting');
			$order_state->update();
		}
		if (!Configuration::get('PAYPLUG_ORDER_STATE_PAID'))
		{
			if (!defined('_PS_OS_PAYMENT_') && !Configuration::get('PS_OS_PAYMENT'))
			{
				$order_state = new OrderState();
				$this->initOrderState($order_state, 'paid');
				if ($order_state->add())
				{
					$source = dirname(__FILE__).'/logo.gif';
					$destination = dirname(__FILE__).'/../../img/os/'.(int)$order_state->id.'.gif';
					copy($source, $destination);
				}
				$os_payment = $order_state->id;
			}
			else if ($os = Configuration::get('PS_OS_PAYMENT'))
				$os_payment = $os;
			else
				$os_payment = defined('_PS_OS_PAYMENT_');
			Configuration::updateValue('PAYPLUG_ORDER_STATE_PAID', (int)$os_payment);
		}
		else
		{
			$order_state = new OrderState(Configuration::get('PAYPLUG_ORDER_STATE_PAID'));
			$this->initOrderState($order_state, 'paid');
			$order_state->update();
		}
		if (!Configuration::get('PAYPLUG_ORDER_STATE_REFUND'))
		{
			$order_state = new OrderState();
			$this->initOrderState($order_state, 'refund');
			if ($order_state->add())
			{
				$source = dirname(__FILE__).'/logo.gif';
				$destination = dirname(__FILE__).'/../../img/os/'.(int)$order_state->id.'.gif';
				copy($source, $destination);
			}
			Configuration::updateValue('PAYPLUG_ORDER_STATE_REFUND', (int)$order_state->id);
		}
		else
		{
			$order_state = new OrderState(Configuration::get('PAYPLUG_ORDER_STATE_REFUND'));
			$this->initOrderState($order_state, 'refund');
			$order_state->update();
		}
	}

	/**
	 * Init parameters of PayPlug order states waiting and paid refund
	 */
	private function initOrderState(&$order_state = null, $type = null)
	{
		if (is_null($order_state) || is_null($type) || ($type != 'waiting' && $type != 'paid' && $type != 'refund'))
			return;
		$order_state->name = $this->getOrderStateName($type);
		$order_state->send_email = false;
		if ($type == 'waiting')
			$order_state->color = '#a1f8a1';
		else if ($type == 'refund')
			$order_state->color = '#EA3737';
		else if ($type == 'paid')
		{
			$order_state->color = '#04B404';
			$order_state->send_email = true;
			if (version_compare(_PS_VERSION_, '1.5', '<'))
			{
				$template = array();
				foreach (Language::getLanguages() as $language)
					$template[$language['id_lang']] = 'payment';
				$order_state->template = $template;
			}
			else
			{
				$order_state->template = 'payment';
				$order_state->paid = true;
			}
		}
		$order_state->module_name = 'PayPlug';
		$order_state->hidden = false;
		$order_state->delivery = false;
		$order_state->logable = true;
		$order_state->invoice = true;
	}

	/**
	 * Get names in different languages for PayPlug order states waiting and paid
	 */
	private function getOrderStateName($type = null)
	{
		if (is_null($type) || ($type != 'waiting' && $type != 'paid' && $type != 'refund'))
			return;
		$order_state_name = array();
		foreach (Language::getLanguages() as $language)
		{
			if (Tools::strtolower($language['iso_code']) == 'fr')
			{
				if ($type == 'waiting')
					$order_state_name[$language['id_lang']] = 'Paiement en cours de traitement par PayPlug';
				else if ($type == 'refund')
					$order_state_name[$language['id_lang']] = 'Paiement remboursé par PayPlug';
				elseif ($type == 'paid')
					$order_state_name[$language['id_lang']] = 'Paiement effectué depuis Payplug';
			}
			else
			{
				if ($type == 'waiting')
					$order_state_name[$language['id_lang']] = 'Payment accepted and in progress by PayPlug';
				else if ($type == 'refund')
					$order_state_name[$language['id_lang']] = 'Payment refunded by PayPlug';
				elseif ($type == 'paid')
					$order_state_name[$language['id_lang']] = 'Payment has passed by Payplug';
			}
		}
		return $order_state_name;
	}
}