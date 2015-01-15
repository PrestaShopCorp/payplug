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

require_once(_PS_MODULE_DIR_.'/payplug/installPayplug.php');

class Payplug extends PaymentModule
{

	public static $is_active    = 1;
	const PAYMENT_STATUS_PAID   = 0;
	const PAYMENT_STATUS_REFUND = 4;
	const PAYMENT_STATUS_CANCEL = 2;
	const URL_AUTOCONFIG        = 'https://www.payplug.fr/portal/ecommerce/autoconfig';
	/** Url to sandbox */
	const URL_TEST_AUTOCONFIG   = 'https://www.payplug.fr/portal/test/ecommerce/autoconfig';

	public function __construct()
	{
		$this->name = 'payplug';
		$this->tab = 'payments_gateways';
		// Update version
		$this->version = '0.9.7';
		$this->author = 'PayPlug';
		$this->module_key = '1ee28a8fb5e555e274bd8c2e1c45e31a';

		parent::__construct();

		// Backward compatibility
		if (version_compare(_PS_VERSION_, '1.4', '>'))
			require(_PS_MODULE_DIR_.$this->name.'/backward_compatibility/backward.php');

		// Add warning if prestashop is an older version than 1.4
		if (version_compare(_PS_VERSION_, '1.4', '<'))
			$this->warning = $this->l('Sorry Payplug is not compatible with Prestashop for versions < 1.4. Please delete the payplug directory in the Prestashop modules directory for your Prestashop system to get back to normal.');

		$this->currencies = true;
		$this->currencies_mode = 'checkbox';
		// Change descriptionn and display name
		$this->displayName = $this->l('PayPlug – Simple and secure online payments');
		$this->description = $this->l('The simplest online payment solution: no setup fees, no fixed fees, and no merchant account required!');
		$this->confirmUninstall = $this->l('Are you sure you wish to uninstall this module and delete your settings?');
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
		$version = Payplug::getConfiguration($cfg_name);
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

					Payplug::updateConfiguration('PAYPLUG_ORDER_STATE_PAID', (int)$os_payment);
				}
			}
			// Add test status && add hook
			if ($version === false || version_compare($version, '0.9.7', '<'))
			{
				Configuration::deleteByName('PAYPLUG_ORDER_STATE_REFUND');

				Payplug::updateConfiguration('PAYPLUG_SANDBOX', '0');

				$install = new InstallPayplug();
				$install->createOrderState();

				if (version_compare(_PS_VERSION_, '1.5', '<'))
					$this->registerHook('header');
				else
					$this->registerHook('displayHeader');

				$install->installPayplugLock();

			}

			// Upgrade in DataBase the new version
			Payplug::updateConfiguration($cfg_name, $this->version, true);
		}
	}

	/**
	 * Update configuration
	 * @var string  Configuration key
	 * @var mixed   Configuration value
	 * @var boolean If is global configuration
	 */
	public static function updateConfiguration($key, $value, $global = false)
	{
		if (version_compare(_PS_VERSION_, '1.5', '>=') && $global)
			Configuration::updateGlobalValue($key, $value);
		else
			Configuration::updateValue($key, $value);
	}

	/**
	 * Get configuration
	 * @var string   Configuration key
	 * @var boolean  If is global configuration
	 * @return mixed Value of key, or false if not exists
	 */
	public static function getConfiguration($key, $global = false)
	{
		if (version_compare(_PS_VERSION_, '1.5', '>=') && $global)
			$value = Configuration::getGlobalValue($key);
		else
			$value = Configuration::get($key);

		return $value;
	}

	/**
	 * Get Order State configuration
	 * @param  string  $state_name State name
	 * @return integer             Value
	 */
	public static function getOsConfiguration($state_name)
	{
		$key = 'PAYPLUG_ORDER_STATE_'.Tools::strtoupper($state_name);

		if (self::getConfiguration('PAYPLUG_SANDBOX'))
			$key .= '_TEST';

		return self::getConfiguration($key, false);
	}

	public function install()
	{
		if (version_compare(_PS_VERSION_, '1.4', '<') || !parent::install() || !$this->registerHook('payment') || !$this->registerHook('paymentReturn'))
			return false;

		// add hook for 1.6
		if (version_compare(_PS_VERSION_, '1.5', '<'))
		{
			if (!$this->registerHook('header'))
				return false;
		}
		else
		{
			if (!$this->registerHook('displayHeader'))
				return false;
		}

		$payplug_install = new InstallPayplug();
		$payplug_install->createConfig();
		$payplug_install->createOrderState();

		$install->installPayplugLock();

		return true;
	}

	public function uninstall()
	{
		$payplug_install = new InstallPayplug();
		$payplug_install->deleteConfig();
		$payplug_install->uninstallPayplugLock();

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
		// if ps version is not available
		if (version_compare(_PS_VERSION_, '1.4', '<'))
			return;

		// Link base
		if (version_compare(_PS_VERSION_, '1.5', '>'))
			$this->_link = 'index.php?controller='.Tools::getValue('controller');
		else
			$this->_link = 'index.php?tab='.Tools::getValue('tab');

		$this->_link .= '&configure='.$this->name.'&token='.Tools::getValue('token').'&tab_module='.$this->tab.'&module_name='.$this->name;

		$display_form = true;
		// For 1.6
		$this->bootstrap = true;
		// Check extensions
		$curl_exists = extension_loaded('curl');
		$openssl_exists = extension_loaded('openssl');
		$errors = array();
		// Add msg if extension not exists
		if (!$curl_exists || !$openssl_exists)
		{

			// cURL not found
			if (!$curl_exists)
				$errors[] = sprintf($this->l('Connection error: %s library is missing. Please ask your hosting provider to install the %s library on your server, and try configuring the PayPlug module on Prestashop again.'), 'cURL', 'cURL');

			// OpenSSL not found
			if (!$openssl_exists)
				$errors[] = sprintf($this->l('Connection error: %s library is missing. Please ask your hosting provider to install the %s library on your server, and try configuring the PayPlug module on Prestashop again.'), 'OpenSSL', 'OpenSSL');
		}

		// Check if form was sent
		if (Tools::getValue('payplug_email') && Tools::getValue('payplug_password'))
		{
			$this->assignForVersion('email', Tools::getValue('payplug_email'));
			// if extensions exist
			if ($curl_exists && $openssl_exists)
			{

				$sandbox_button = Tools::isSubmit('sandboxButton');
				// Get url to curl
				$url = $sandbox_button ? Payplug::URL_TEST_AUTOCONFIG : Payplug::URL_AUTOCONFIG;

				$process = curl_init($url);
				curl_setopt($process, CURLOPT_USERPWD, Tools::getValue('payplug_email').':'.Tools::getValue('payplug_password'));
				curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
				// CURL const are in uppercase
				curl_setopt($process, CURLOPT_SSLVERSION, defined('CURL_SSLVERSION_TLSV1') ? CURL_SSLVERSION_TLSV1 : 1);
				curl_setopt($process, CURLOPT_SSL_VERIFYPEER, true);
				curl_setopt($process, CURLOPT_SSL_VERIFYHOST, true);
				curl_setopt($process, CURLOPT_CAINFO, realpath(dirname(__FILE__).'/cacert.pem')); //work only wiht cURL 7.10+
				$answer = curl_exec($process);

				$error_curl = curl_errno($process);
				curl_close($process);

				// if no error
				if ($error_curl == 0)
				{
					$json_answer = Tools::jsonDecode($answer);
					// if account is just in test mod
					if ($json_answer->status == 403)
					{
						$errors[] = sprintf(
							$this->l('To set up the module in LIVE mode, you first need %s to request your account to be activated %s.'),
							'<a href="http://support.payplug.fr/customer/portal/articles/1438899-comment-activer-mon-compte-">',
							'</a>');
					}
					else if ($json_answer->status == 200)
					{
						$payplug_install = new InstallPayplug();

						if (!is_array($json_answer->currencies))
							$currencies = implode(Tools::jsonDecode($json_answer->currencies), ';');
						else
							$currencies = $json_answer->currencies[0];

						$private_key = $json_answer->yourPrivateKey;
						$public_key = $json_answer->payplugPublicKey;

						// explode for validator
						$payplug_install->updateConfig(
							$private_key,
							$public_key,
							$json_answer->url,
							$json_answer->amount_min,
							$json_answer->amount_max,
							$currencies,
							(string)(int)$sandbox_button);

						$display_form = false;
						// redirect for update message
						Tools::redirectAdmin($this->_link.'&conf=4');
					}
					else
						$errors[] = $this->l('Your email or password is incorrect.');
				}
				else
					$errors[] = $error_curl;
			}
		}
		// toggle debug mode
		else if (Tools::getIsset('debug_mode'))
		{
			self::updateConfiguration('PAYPLUG_DEBUG', !self::getConfiguration('PAYPLUG_DEBUG'));
			Tools::redirectAdmin($this->_link.'&conf=4');
		}

		if (Configuration::get('PAYPLUG_MODULE_KEY') != ''
			&& Configuration::get('PAYPLUG_MODULE_PUBLIC_KEY') != ''
			&& Configuration::get('PAYPLUG_MODULE_URL') != ''
			&& Configuration::get('PAYPLUG_MODULE_MIN_AMOUNT') != ''
			&& Configuration::get('PAYPLUG_MODULE_MAX_AMOUNT') != ''
			&& Configuration::get('PAYPLUG_MODULE_CURRENCIES') != '')
		{
				$this->assignForVersion(
					array(
						'moduleInstalled' => true,
						'minAmount'       => Configuration::get('PAYPLUG_MODULE_MIN_AMOUNT'),
						'maxAmount'       => Configuration::get('PAYPLUG_MODULE_MAX_AMOUNT'),
						'currencies'      => Configuration::get('PAYPLUG_MODULE_CURRENCIES'),
						'sandboxMode'     => Payplug::getConfiguration('PAYPLUG_SANDBOX'), // Assign sandbox mode
						'debugMode'       => Payplug::getConfiguration('PAYPLUG_DEBUG'), // assign debug mode
					)
				);
		}

		// Assign datas
		$datas = array(
			'this_path'   => $this->_path,
			'displayForm' => $display_form,
			'errors'      => $errors,
			'this_link'   => $this->_link,
		);

		$this->assignForVersion($datas);

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
		$this->assignForVersion('iso_lang', $this->context->language->iso_code);

		// Different tpl depending version
		if (version_compare(_PS_VERSION_, '1.6', '<'))
		{
			if (version_compare(_PS_VERSION_, '1.5', '<'))
				return $this->display(__FILE__, './views/templates/hook/payment.tpl');
			else
				return $this->display(__FILE__, 'payment.tpl');
		}
		else
			return $this->display(__FILE__, 'payment_16.tpl');
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
		// if waiting_test state
		else if (isset($order->current_state) && $order->current_state == Configuration::get('PAYPLUG_ORDER_STATE_WAITING_TEST'))
			$state = 'waiting_test';
		// if paid_test state
		elseif (isset($order->current_state) && $order->current_state == Configuration::get('PAYPLUG_ORDER_STATE_PAID_TEST'))
			$state = 'paid_test';

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

	/**
	 * Hook for 1.4
	 */
	public function hookHeader()
	{
		return $this->hookDisplayHeader();
	}

	/**
	 * Hook for >= 1.5
	 */
	public function hookDisplayHeader()
	{
		if (version_compare(_PS_VERSION_, '1.6', '<'))
			return;

		$controller = $this->context->controller;

		if ($controller instanceof OrderOpcController || ($controller instanceof OrderController && $controller->step == 3))
		{
			$file = 'css/front.css';
			$this->context->controller->addCss($this->getLocalPath().$file);
		}
	}
}
