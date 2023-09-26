/* JavaScript for Admin pages */
jQuery(document).ready(function($) {
	var $wc_aelia_cs_shippingpricing_form = $('#wc_aelia_cs_shippingpricing_form');
	// If form is not found, we are not on this plugin's setting page
	if(!$wc_aelia_cs_shippingpricing_form.length) {
		return;
	}

	// Display tabbed interface
	$wc_aelia_cs_shippingpricing_form.find('.tabs').tabs();

	// Use Chosen plugin to replace standard multiselect
	if(jQuery().chosen) {
	}
});
