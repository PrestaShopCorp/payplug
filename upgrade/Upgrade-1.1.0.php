<?php
	if (!defined('_PS_VERSION_'))
		exit;

	function upgrade_module_1_1_0($module) {
		// Update OS PayPlug payment
		if (defined('_PS_OS_ERROR_') || Configuration::get('PS_OS_ERROR'))
		{
			// If is in configuration (since 1.5)
			if ($os = Configuration::get('PS_OS_ERROR'))
				$os_payment = $os;
			// If is defined
			else
				$os_payment = defined('_PS_OS_ERROR_');

			Payplug::updateConfiguration('PAYPLUG_ORDER_STATE_ERROR', (int)$os_payment);
		}
	  return true; // Return true if success.
	}

?>