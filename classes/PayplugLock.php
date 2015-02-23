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
	die(header('HTTP/1.0 404 Not Found'));

/**
 * Description of totRules
 *
 * @author 202-ecommerce
 */
class PayplugLock extends ObjectModel {

	const MAX_CHECK_TIME = 5;

	public static $definition;

	public $id_payplug_lock;
	public $id_cart;
	public $date_add;
	public $date_upd;

	protected $table = 'payplug_lock';
	protected $identifier = 'id_payplug_lock';
	protected $fieldsRequired = array(
		'id_cart'
		);
	protected $fieldsValidate = array(
		'id_cart' => 'isInt'
		);
	protected $fieldsValidateLang = array(
		);

	public function __construct($id = false, $id_lang = false)
	{
		parent::__construct($id, $id_lang);

        self::$definition = array(
       		'table'   => $this->table,
       		'primary' => $this->identifier,
       		'fields'  => array(
       			'id_cart' => array('type' => self::TYPE_STRING, 'validate' => 'isInt'),
       		),
       	);
	}

	/**
	 * Check
	 * @param  integer $id_cart Cart identifier
	 * @return boolean          if locked
	 */
	public static function check($id_cart, $loop_time = 1)
	{
		$locked = false;

		$time = 0;

		while (($locked = self::exists($id_cart)) && $time < PayplugLock::MAX_CHECK_TIME)
		{
			if (function_exists('usleep'))
				usleep($loop_time * 1000000);
			else
				self::usleep($loop_time * 1000);

			$time++;
		}
	}

	/**
	 * Check if exists
	 * @param  integer $id_cart Cart identifier
	 * @return boolean          If exists
	 */
	public static function exists($id_cart)
	{
		$lock = self::getInstanceByCart((int)$id_cart);

		return Validate::isLoadedObject($lock);
	}

	/**
	 * Set instance of PayplugLock
	 * @param  integer $id_cart Cart identifier
	 * @return \PayplugLock     Instance
	 */
	public static function getInstanceByCart($id_cart)
	{
		$query = 'SELECT `id_payplug_lock` 
				FROM `'._DB_PREFIX_.'payplug_lock`
				WHERE `id_cart` = '.(int)$id_cart.' ';

		return new PayplugLock((int)Db::getInstance()->getValue($query));
	}

	/**
	 * Create lock
	 * @param  integer $id_cart Cart identifier
	 * @return boolean          Create successfull
	 */
	public static function addLock($id_cart)
	{
		$lock = new PayplugLock();
		$lock->id_cart = (int)$id_cart;

		return $lock->save();
	}

	/**
	 * Delete lock
	 * @param  integer $id_cart Cart identifier
	 * @return boolean          Delete successfull
	 */
	public static function deleteLock($id_cart)
	{
		$lock = self::getInstanceByCart((int)$id_cart);

		return $lock->delete();
	}

	/**
	 * Sleep time
	 */
	private static function usleep($seconds)
	{
		$start = microtime();

		do
		{	
			// Wait !
			$current = microtime();
		} while (($current - $start) < $seconds);
	}
}
