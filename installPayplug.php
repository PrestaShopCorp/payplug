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

	/**
	 * Available types
	 * @var array
	 */
	private static $available_types = array(
			'waiting',
			'paid',
			'refund',
			'paid_test',
			'refund_test',
			'waiting_test'
		);

	public function createConfig()
	{
		Configuration::updateValue('PAYPLUG_MODULE_KEY', '');
		Configuration::updateValue('PAYPLUG_MODULE_PUBLIC_KEY', '');
		Configuration::updateValue('PAYPLUG_MODULE_URL', '');
		Configuration::updateValue('PAYPLUG_MODULE_MIN_AMOUNT', '');
		Configuration::updateValue('PAYPLUG_MODULE_MAX_AMOUNT', '');
		Configuration::updateValue('PAYPLUG_MODULE_CURRENCIES', '');
		Payplug::updateConfiguration('PAYPLUG_SANDBOX', '');
		Payplug::updateConfiguration('PAYPLUG_DEBUG', '');
	}

	/**
	 * Update configuration
	 * @param  string  $private_key Private key
	 * @param  string  $public_key  Public key
	 * @param  string  $url         Url
	 * @param  string  $min_amount  Min amount
	 * @param  string  $max_amount  Max amount
	 * @param  string  $currencies  Available currencies
	 * @param  boolean $debug       Mod sandbox or not
	 */
	public function updateConfig($private_key = '', $public_key = '', $url = '', $min_amount = '', $max_amount = '', $currencies = '', $sandbox = null)
	{
		Configuration::updateValue('PAYPLUG_MODULE_KEY', ''.$private_key.'');
		Configuration::updateValue('PAYPLUG_MODULE_PUBLIC_KEY', ''.$public_key.'');
		Configuration::updateValue('PAYPLUG_MODULE_URL', ''.$url.'');
		Configuration::updateValue('PAYPLUG_MODULE_MIN_AMOUNT', $min_amount);
		Configuration::updateValue('PAYPLUG_MODULE_MAX_AMOUNT', $max_amount);
		Configuration::updateValue('PAYPLUG_MODULE_CURRENCIES', $currencies);
		Payplug::updateConfiguration('PAYPLUG_SANDBOX', $sandbox);
	}

	public function deleteConfig()
	{
		Configuration::deleteByName('PAYPLUG_MODULE_KEY');
		Configuration::deleteByName('PAYPLUG_MODULE_PUBLIC_KEY');
		Configuration::deleteByName('PAYPLUG_MODULE_URL');
		Configuration::deleteByName('PAYPLUG_MODULE_MIN_AMOUNT', '');
		Configuration::deleteByName('PAYPLUG_MODULE_MAX_AMOUNT', '');
		Configuration::deleteByName('PAYPLUG_MODULE_CURRENCIES', '');
		Configuration::deleteByName('PAYPLUG_SANDBOX', '');
		Configuration::deleteByName('PAYPLUG_DEBUG', '');
	}

	/**
	 * Create PayPlug order states waiting and paid and save their id in Configuration
	 */
	public function createOrderState()
	{
		// Key => status type, value => Prestashop Default Configuration
		$state_key = array(
			'paid'    => 'PS_OS_PAYMENT',
			'refund'  => 'PS_OS_REFUND',
			'waiting' => null,
		);

		// Loop keys
		foreach ($state_key as $key => $cfg)
		{
			// Configuration key
			$key_config = 'PAYPLUG_ORDER_STATE_'.Tools::strtoupper($key);

			// If config is not null
			if ($cfg != null)
			{
				// Update OS PayPlug payment
				if (defined('_'.$cfg.'_') || Payplug::getConfiguration($cfg))
				{
					// If is in configuration (since 1.5)
					if (!($os = Configuration::get($cfg)))
						$os = defined('_'.$cfg.'_');

					// Update configuration
					Payplug::updateConfiguration($key_config, (int)$os);
				}
			}
			// Create Order State if "Prestashop Default Configuration" is null
			else
				$this->createOrderStateSpecifc($key);

			// Create os tests
			$this->createOrderStateSpecifc($key, true);
		}

	}

	/**
	 * Create order
	 * @param  string  $key  OS Key
	 * @param  boolean $test if os test
	 */
	private function createOrderStateSpecifc($key, $test = false)
	{
		// Logo source
		$source = dirname(__FILE__).'/logo.gif';

		// If is an OS test
		if ($test == true)
			$key .= '_test';

		// Configuration key
		$key_config = 'PAYPLUG_ORDER_STATE_'.Tools::strtoupper($key);

		// If configuration not exists
		if (!($os = Payplug::getConfiguration($key_config)))
		{
			// New state
			$order_state = new OrderState();
			// Init state
			$this->initOrderState($order_state, Tools::strtolower($key));
			// Add state
			if ($order_state->add())
			{
				// Change
				$destination = _PS_IMG_DIR_.'os/'.(int)$order_state->id.'.gif';
				copy($source, $destination);
			}

			// Update configuration
			Payplug::updateConfiguration($key_config, (int)$order_state->id);
		}
		// if configuration exists update status
		else
		{
			// Get status
			$order_state = new OrderState($os);
			// Init status
			$this->initOrderState($order_state, Tools::strtolower($key));
			// Update status
			$order_state->update();
		}
	}

	/**
	 * Init parameters of PayPlug order states
	 */
	private function initOrderState(&$order_state = null, $type = null)
	{
		// Check if parameters is not null & if $type is an available type
		if (is_null($order_state) || is_null($type) || !in_array($type, self::$available_types))
			return;

		$order_state->name = $this->getOrderStateName($type);
		$order_state->send_email = false;

		// OS Waiting or Waiting test
		if ($type == 'waiting' || $type == 'waiting_test')
			$order_state->color = '#a1f8a1';
		// OS Refund or Refund test
		else if ($type == 'refund' || $type == 'refund_test')
			$order_state->color = '#EA3737';
		// OS Paid or Paid test
		else if ($type == 'paid' || $type == 'paid_test')
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
		$order_state->hidden      = false;
		$order_state->delivery    = false;
		$order_state->logable     = true;
		$order_state->invoice     = true;
	}

	/**
	 * Get names in different languages for PayPlug order states waiting and paid
	 */
	private function getOrderStateName($type = null)
	{
		// Check if parameters is not null & if $type is an available type
		if (is_null($type) || !in_array($type, self::$available_types))
			return;

		$order_state_name = array();
		foreach (Language::getLanguages() as $language)
		{
			if (Tools::strtolower($language['iso_code']) == 'fr')
			{
				if ($type == 'waiting')
					$order_state_name[$language['id_lang']] = 'Paiement en cours [PayPlug]';
				else if ($type == 'waiting_test')
					$order_state_name[$language['id_lang']] = 'Paiement en cours [TEST]';
				else if ($type == 'refund_test')
					$order_state_name[$language['id_lang']] = 'Remboursé [TEST]';
				elseif ($type == 'paid_test')
					$order_state_name[$language['id_lang']] = 'Paiement effectué [TEST]';
			}
			else
			{
				if ($type == 'waiting')
					$order_state_name[$language['id_lang']] = 'Payment in progress [PayPlug]';
				if ($type == 'waiting_test')
					$order_state_name[$language['id_lang']] = 'Payment in progress [TEST]';
				else if ($type == 'refund_test')
					$order_state_name[$language['id_lang']] = 'Refunded [TEST]';
				elseif ($type == 'paid_test')
					$order_state_name[$language['id_lang']] = 'Payment successful [TEST]';
			}
		}
		return $order_state_name;
	}
}