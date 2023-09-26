/**
 * Atum Multi-Inventory UI for products
 *
 * @copyright Stock Management Labs ©2021
 *
 * @since 1.0.0
 */


/**
 * Components
 */

import DateTimePicker from '../../../../atum-stock-manager-for-woocommerce/assets/js/src/components/_date-time-picker';
import EditPopovers from '../../../../atum-stock-manager-for-woocommerce/assets/js/src/components/_edit-popovers';
import EnhancedSelect from '../../../../atum-stock-manager-for-woocommerce/assets/js/src/components/_enhanced-select';
import MiUI from './components/_mi-ui';
import Settings from '../../../../atum-stock-manager-for-woocommerce/assets/js/src/config/_settings';
import Tooltip from '../../../../atum-stock-manager-for-woocommerce/assets/js/src/components/_tooltip';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ( $: JQueryStatic ) => {
	
	window[ '$' ] = $; // Avoid conflicts.
	
	// Get the settings from localized var.
	const settings = new Settings( 'atumMultInvVars' );
	const enhancedSelect = new EnhancedSelect();
	const editPopovers = new EditPopovers( settings, enhancedSelect );
	const dateTimePicker = new DateTimePicker( settings );
	const tooltip = new Tooltip();
	
	new MiUI( settings, editPopovers, dateTimePicker, enhancedSelect, tooltip );
	
});