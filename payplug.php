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

if (version_compare(_PS_VERSION_, '1.4', '<'))
{
	echo 'Sorry Payplug is not compatible with Prestashop for versions < 1.4. Please delete the payplug directory in the Prestashop modules directory for your Prestashop system to get back to normal.';
	exit;
}

require_once(_PS_MODULE_DIR_.'/payplug/installPayplug.php');

if (!defined('_PS_VERSION_'))
	exit;

class Payplug extends PaymentModule
{

	public static $is_active = 1;
	const PAYMENT_STATUS_PAID = 0;
	const PAYMENT_STATUS_REFUND = 4;
	const PAYMENT_STATUS_CANCEL = 2;
	const URL_AUTOCONFIG = 'https://www.payplug.fr/portal/ecommerce/autoconfig';

	public function __construct()
	{
		$this->name = 'payplug';
		$this->tab = 'payments_gateways';
		$this->version = '0.9.6';
		$this->author = 'PayPlug';
		$this->module_key = '1ee28a8fb5e555e274bd8c2e1c45e31a';
		parent::__construct();

		// Backward compatibility
		require(_PS_MODULE_DIR_.$this->name.'/backward_compatibility/backward.php');

		$this->currencies = true;
		$this->currencies_mode = 'checkbox';
		$this->displayName = $this->l('PayPlug');
		$this->description = $this->l('Payer par carte simplement, rapidement et de manière sécurisée.');
		$this->confirmUninstall = $this->l('Êtes-vous sûr de vouloir désinstaller ce module et supprimer sa configuration ?');
		if (version_compare(_PS_VERSION_, '1.5', '<'))
		{
			$cookie_admin = new Cookie('psAdmin', Tools::substr($_SERVER['PHP_SELF'], Tools::strlen(__PS_BASE_URI__), -10));
			if (Tools::getValue('tab') == 'AdminPayment' && Tools::getValue('token') != Tools::getAdminTokenLite('AdminPayment'))
			{
				// Force admin status
				$this->context->cookie->profile = $cookie_admin->profile;
				$url  = 'index.php?tab=AdminPayment';
				$url .= '&token='.Tools::getAdminTokenLite('AdminPayment');
				Tools::redirectAdmin($url);
			}
		}

		if (Module::isInstalled($this->name))
			$this->upgrade();

	}

	private function upgrade()
	{
		// Configuration name
		$cfg_name = Tools::strtoupper($this->name.'_version');
		// Get latest version upgraded
		$version = Configuration::get($cfg_name);
		// If the first time OR the latest version upgrade is older than this one
		if ($version === false || version_compare($version, $this->version, '<'))
		{

			if ($version === false || version_compare($version, '0.9.2', '<='))
			{
				// Update OS PayPlug payment
				if (defined('_PS_OS_PAYMENT_') || Configuration::get('PS_OS_PAYMENT'))
				{
					// If is in configuration (since 1.5)
					if ($os = Configuration::get('PS_OS_PAYMENT'))
						$os_payment = $os;
					// If is defined
					else
						$os_payment = defined('_PS_OS_PAYMENT_');
					Configuration::updateValue('PAYPLUG_ORDER_STATE_PAID', (int)$os_payment);
				}
			}

			// Upgrade in DataBase the new version
			Configuration::updateValue($cfg_name, $this->version);
		}
	}

	public function install()
	{
		if (version_compare(_PS_VERSION_, '1.4', '<') || !parent::install() || !$this->registerHook('payment') || !$this->registerHook('paymentReturn'))
			return false;
		$payplug_install = new InstallPayplug();
		$payplug_install->createConfig();
		$payplug_install->createOrderState();
		return true;
	}

	public function uninstall()
	{
		$payplug_install = new InstallPayplug();
		$payplug_install->deleteConfig();
		return parent::uninstall();
	}

	public static function moduleIsActive()
	{
		if (self::$is_active == -1)
		{
			// This override is part of the cloudcache module, so the cloudcache.php file exists
			require_once(dirname(__FILE__).'/../../modules/cloudcache/cloudcache.php');
			$module = new CloudCache();
			self::$is_active = $module->active;
		}
		return self::$is_active;
	}

	public function assignForVersion($variable, $contenue = null)
	{
		if (version_compare(_PS_VERSION_, '1.5', '<'))
			$this->context->smarty->assign($variable, $contenue);
		else
			$this->smarty->assign($variable, $contenue);
	}

	public static function redirectForVersion($link)
	{
		if (version_compare(_PS_VERSION_, '1.5', '<'))
			Tools::redirectLink($link);
		else
			Tools::redirect($link);
	}

	public function getContent()
	{
		$display_form = true;
		if (Tools::isSubmit('email') && Tools::isSubmit('password') && Tools::getValue('email') && Tools::getValue('password'))
		{
			$this->assignForVersion('email', Tools::getValue('email'));
			if (extension_loaded('curl'))
			{
				$process = curl_init(Payplug::URL_AUTOCONFIG);
				curl_setopt($process, CURLOPT_USERPWD, Tools::getValue('email').':'.Tools::getValue('password'));
				curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($process, CURLOPT_SSLVERSION, defined('CURL_SSLVERSION_TLSv1') ? CURL_SSLVERSION_TLSv1 : 1);
				curl_setopt($process, CURLOPT_SSL_VERIFYPEER, true);
				curl_setopt($process, CURLOPT_SSL_VERIFYHOST, true);
				curl_setopt($process, CURLOPT_CAINFO, realpath(dirname(__FILE__).'/cacert.pem')); //work only wiht cURL 7.10+
				$answer = curl_exec($process);

				$error_curl = curl_errno($process);
				curl_close($process);
				if ($error_curl == 0)
				{
					$json_answer = Tools::jsonDecode($answer);
					$authorization_success = false;
					if ($json_answer->status == 200)
					{
						$authorization_success = true;
						$payplug_install = new InstallPayplug();
						if (!is_array($json_answer->currencies))
							$currencies = implode(Tools::jsonDecode($json_answer->currencies), ';');
						else
							$currencies = $json_answer->currencies[0];

						$private_key = $json_answer->yourPrivateKey;
						$public_key = $json_answer->payplugPublicKey;

						$payplug_install->updateConfig($private_key, $public_key, $json_answer->url, $json_answer->amount_min, $json_answer->amount_max, $currencies);
						$display_form = false;
					}
					$this->assignForVersion('authorization_success', $authorization_success);
				}
				else
					$this->assignForVersion('errorCurl', $error_curl);
			}
			else
				$this->assignForVersion('noCurl', true);
		}
		if (Configuration::get('PAYPLUG_MODULE_KEY') != ''
			&& Configuration::get('PAYPLUG_MODULE_PUBLIC_KEY') != ''
			&& Configuration::get('PAYPLUG_MODULE_URL') != ''
			&& Configuration::get('PAYPLUG_MODULE_MIN_AMOUNT') != ''
			&& Configuration::get('PAYPLUG_MODULE_MAX_AMOUNT') != ''
			&& Configuration::get('PAYPLUG_MODULE_CURRENCIES') != '')
		{
				$this->assignForVersion(array('moduleInstalled' => true,
					'minAmount' => Configuration::get('PAYPLUG_MODULE_MIN_AMOUNT'),
					'maxAmount' => Configuration::get('PAYPLUG_MODULE_MAX_AMOUNT'),
					'currencies' => Configuration::get('PAYPLUG_MODULE_CURRENCIES')));
				$display_form = false;
				if (isset($authorization_success) && !$authorization_success)
					$display_form = true;
		}
		$this->assignForVersion('this_path', $this->_path);
		$this->assignForVersion('displayForm', $display_form);
		return $this->display(__FILE__, './views/templates/admin/admin.tpl');
	}

	public function hookPayment()
	{
		$cart = $this->context->cart;

		$iso_code = $this->context->currency->iso_code;
		$supported_currencies = explode(';', Configuration::get('PAYPLUG_MODULE_CURRENCIES'));
		if (!in_array($iso_code, $supported_currencies))
			return;
		// Check amount
		$amount = $cart->getOrderTotal(true, Cart::BOTH) * 100;
		if ($amount < Configuration::get('PAYPLUG_MODULE_MIN_AMOUNT') * 100 || $amount > Configuration::get('PAYPLUG_MODULE_MAX_AMOUNT') * 100)
			return;
		$this->assignForVersion('this_path', $this->_path);
		if (version_compare(_PS_VERSION_, '1.5', '<'))
			return $this->display(__FILE__, './views/templates/hook/payment.tpl');
		else
			return $this->display(__FILE__, 'payment.tpl');
	}

	public function hookPaymentReturn()
	{
		if (!$this->active)
			return null;
		$order_id = Tools::getValue('id_order');
		$order = new Order($order_id);
		// Check order state to display appropriate message
		$state = null;
		if (isset($order->current_state) && $order->current_state == Configuration::get('PAYPLUG_ORDER_STATE_WAITING'))
			$state = 'waiting';
		elseif (isset($order->current_state) && $order->current_state == Configuration::get('PAYPLUG_ORDER_STATE_PAID'))
			$state = 'paid';
		$this->assignForVersion('state', $state);
		// Get order information for display
		$total_paid = number_format($order->total_paid, 2, ',', '');
		$context = array('totalPaid' => $total_paid);
		if (isset($order->reference))
			$context['reference'] = $order->reference;
		$this->assignForVersion($context);
		if (version_compare(_PS_VERSION_, '1.5', '<'))
			return $this->display(__FILE__, './views/templates/hook/confirmation.tpl');
		else
			return $this->display(__FILE__, 'confirmation.tpl');
	}
}
