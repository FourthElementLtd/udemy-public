/**
 * Atum Multi-Inventory for List Tables
 *
 * @copyright Stock Management Labs ©2021
 *
 * @since 1.0.1
 */

/**
 * Components
 */

import DateTimePicker from '../../../../atum-stock-manager-for-woocommerce/assets/js/src/components/_date-time-picker';
import EnhancedSelect from '../../../../atum-stock-manager-for-woocommerce/assets/js/src/components/_enhanced-select';
import MiListTable from './components/_list-table';
import RegionsModal from './components/_regions-modal';
import Settings from '../../../../atum-stock-manager-for-woocommerce/assets/js/src/config/_settings';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ($) => {
	
	window['$'] = $; // Avoid conflicts.

	const settings = new Settings( 'atumMultInvVars' );
	const dateTimePicker = new DateTimePicker( settings );
	const enhancedSelect = new EnhancedSelect();

	new MiListTable( settings, dateTimePicker );
	new RegionsModal( settings, enhancedSelect );
	
});