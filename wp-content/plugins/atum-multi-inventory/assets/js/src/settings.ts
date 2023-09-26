/**
 * Atum Multi-Inventory Settings
 *
 * @copyright Stock Management Labs ©2021
 *
 * @since 1.0.0
 */

/**
 * Components
 */

import EnhancedSelect from '../../../../atum-stock-manager-for-woocommerce/assets/js/src/components/_enhanced-select';
import MiSettingsPage from './components/_settings-page';
import Settings from '../../../../atum-stock-manager-for-woocommerce/assets/js/src/config/_settings';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ($) => {
	
	window['$'] = $; // Avoid conflicts.
	
	// Get the options from the localized var.
	let settings = new Settings('atumMultInvSettingsVars');
	let enhancedSelect = new EnhancedSelect();
	new MiSettingsPage(settings, enhancedSelect);
	
});