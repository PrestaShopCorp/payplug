<?php
	if (!defined('_PS_VERSION_'))
		exit;

	function upgrade_module_0_9_2($module) {
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
	  return true; // Return true if success.
	}

?>