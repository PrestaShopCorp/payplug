<?php
	if (!defined('_PS_VERSION_'))
		exit;

	function upgrade_module_0_9_7($module) {
		// Add test status && add hook
		Configuration::deleteByName('PAYPLUG_ORDER_STATE_REFUND');

		Payplug::updateConfiguration('PAYPLUG_SANDBOX', '0');

		$install = new InstallPayplug();
		$install->createOrderState();

		$module->registerHook('header');

		$install->installPayplugLock();

		return true; // Return true if success.
	}

?>