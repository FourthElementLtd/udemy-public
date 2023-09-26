/**
 * Atum Multi-Inventory Geo Prompt
 *
 * @copyright Stock Management Labs ©2021
 *
 * @since 1.0.0
 */

/**
 * Third Party Plugins
 */

import 'featherlight/release/featherlight.min';                                    // From node_modules
import '../../../../atum-stock-manager-for-woocommerce/assets/js/vendor/select2';  // A fixed version compatible with webpack

/**
 * Components
 */

import MiGeoPrompt from './components/_mi-geo-prompt';
import Settings from '../../../../atum-stock-manager-for-woocommerce/assets/js/src/config/_settings';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ($) => {
	
	window['$'] = $; // Avoid conflicts.
	
	// Get the settings from localized var.
	let settings = new Settings('atumMultGeoPromptVars');
	
	new MiGeoPrompt(settings);
	
});