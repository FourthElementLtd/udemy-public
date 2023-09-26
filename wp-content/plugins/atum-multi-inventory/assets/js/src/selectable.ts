/**
 * Atum Multi-Inventory script for selectable inventories in the frontend
 *
 * @copyright Stock Management Labs ©2021
 *
 * @since 1.3.7
 */

import SelectableInventories from './components/_selectable-inventories';

// Modules that need to execute when the DOM is ready should go here.
jQuery( ( $: JQueryStatic ) => {
	
	window[ '$' ] = $; // Avoid conflicts.
	
	new SelectableInventories();
	
});