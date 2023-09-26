/**
 * Atum Multi-Inventory UI for orders
 *
 * @copyright Stock Management Labs ©2021
 *
 * @since 1.0.0
 */

/**
 * Components
 */

import MiOrders from './components/_mi-orders';
import Settings from '../../../../atum-stock-manager-for-woocommerce/assets/js/src/config/_settings';
import Tooltip from '../../../../atum-stock-manager-for-woocommerce/assets/js/src/components/_tooltip';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ($) => {
	
	window['$'] = $; // Avoid conflicts.
	
	// Get the settings from localized var.
	let settings = new Settings('atumMultInvOrdersVars');
	let tooltip = new Tooltip();
	
	new MiOrders(settings, tooltip);
	
});