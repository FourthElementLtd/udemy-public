<?php if(!defined('ABSPATH')) exit; // Exit if accessed directly
	// This view will render the admin UI for the Aelia_CS_WC_Shipping_BE_Table_Rate_Shipping
	// method. The code has been copied "as is", from original BE_Table_Rate_Shipping class .
?>

<?php

// Code from original BE_Table_Rate_Shipping::admin_options() method. Copied
// from Table Rates Shipping plugin v3.6.1.
// The code has been copied (almost) verbatim because there was no other way to
// inject the additional UI elements required to handle multi-currency shipping

global $woocommerce;

$cur_symbol = get_woocommerce_currency_symbol($this->active_currency());
$condOpsG = $classOpsG = $zoneOpsJS = "";
$shippingClasses = $woocommerce->shipping->get_shipping_classes();
if(count($shippingClasses) > 0) foreach($shippingClasses as $key => $val) $classOpsG .= "<option value=\"".$val->term_id."\">".$val->name."</option>";

$conds = array("price" => "Price","weight" => "Weight","item-count" => "Item Count","dimensions" => "Dimensions");
$countries = $woocommerce->countries->get_allowed_countries();
$zones = be_get_zones();

if( count( $zones ) ) foreach($zones as $val) $zoneOpsJS .= "<option value=\"".$val['zone_id']."\">".$val['zone_title']."</option>";

$attributes = wc_get_attribute_taxonomies();

if( !empty( $attributes ) ) foreach( $attributes as $ak => $attr ) $conds[$attr->attribute_name] = ( isset( $attr->attribute_label ) ) ? $attr->attribute_label : $attr->attribute_name;
foreach($conds as $key => $val) $condOpsG .= "<option value=\"".$key."\">".$val."</option>";
?>
<style>.check-column input{margin-left:8px;} .check-column {margin: 0;padding: 0;}</style>

<!-- Aelia - Shipping Pricing UI Wrapper -->
<div class="aelia shipping_method_settings">
		<h3><?php echo $this->admin_page_heading; ?></h3>
		<p><?php echo $this->admin_page_description; ?></p>

		<?php
			// Aelia
			// Render the currency selector element
			$this->render_currency_selector();
		?>

		<table class="form-table">
		<?php
			// Generate the HTML For the settings form.
			$this->generate_settings_html();
			?>
			<tr valign="top" id="shipping_handling_rates">
						<th scope="row" class="titledesc"><?php _e( 'Handling / Base Rates', 'be-table-ship' ); ?>:</th>
						<td class="forminp" id="<?php echo $this->id; ?>_handling_rates">
							<table class="shippingrows widefat" style="width: 60%;min-width:550px;" cellspacing="0">
								<thead>
									<tr>
										<th class="check-column"><input type="checkbox"></th>
												<th><?php _e( 'Zone', 'be-table-ship' ); ?> <a class="tips" data-tip="<?php _e('Setup and review zones under the Shipping Zones tab','be-table-ship'); ?>">[?]</a></th>
												<th><?php _e( 'Fee', 'be-table-ship' ); ?> <a class="tips" data-tip="<?php _e('Adds the specified percentage of purchase total followed by the fixed fee','be-table-ship'); ?>">[?]</a></th>
									</tr>
								</thead>
								<tfoot>
									<tr>
										<th colspan="2"><a href="#" class="add button"><?php _e( 'Add Handling Fee', 'be-table-ship' ); ?></a></th>
										<th colspan="1" style="text-align:right;"><a href="#" class="remove button"><?php _e( 'Delete selected fees', 'be-table-ship' ); ?></a></th>
									</tr>
								</tfoot>
								<tbody class="class_priorities">
									<?php
									$i = -1;
									if(count($this->handling_rates) > 0) {
										foreach ( $this->handling_rates as $id => $arr ) {
											$countryOps = "";
											$i++;
											foreach ( $zones as $val ) {
								$countryOps .= '<option value="' . $val['zone_id'] . '" ' . selected( $val['zone_id'], $arr['zone'], false ) . '>' . $val['zone_title'] . '</option>';
											}
											echo '<tr class="handling_fees">
													<td class="check-column"><input type="checkbox" name="select" /></td>
													<td><select name="'. $this->id .'_handling_country[' . $i . ']">' . $countryOps . '</select></td>
													<td>' . $cur_symbol . '<input type="text" value="' . $arr['fee'] . '" name="'. $this->id .'_handling_fee[' . $i . ']" size="5" /> &nbsp; % <input type="text" value="' . $arr['percent'] . '" name="'. $this->id .'_handling_percent[' . $i . ']" size="5" /></td></tr>';
										}
									} echo '<tr colspan="3">' . _e( 'Set different handling rates or base fees for different countries. These prices will be added to all qualifying orders.', 'be-table-ship' ) . '</tr>';
									?>
									</tbody>
								</table>
						</td>
				</tr>
			<tr valign="top" id="table_rate_based">
						<th scope="row" class="titledesc"><?php _e( 'Shipping Table Rates', 'be-table-ship' ); ?>:</th>
						<td class="forminp" id="<?php echo $this->id; ?>_table_rates">
							<table class="shippingrows widefat" cellspacing="0">
								<thead>
									<tr>
										<th class="check-column"><input type="checkbox"></th>
												<th class="shipping_class"><?php _e( 'Title', 'be-table-ship' ); ?>* <a class="tips" data-tip="<?php _e('This controls the title which the user sees during checkout','be-table-ship'); ?>">[?]</a></th>
												<th class="shipping_class"><?php _e( 'Identifier', 'be-table-ship' ); ?> <a class="tips" data-tip="<?php _e('Separates which rates are combined and which become different options. If left blank, one will be generated.','be-table-ship'); ?>">[?]</a></th>
										<th><?php _e( 'Zone', 'be-table-ship' ); ?>* <a class="tips" data-tip="<?php _e('Setup and review zones under the Shipping Zones tab','be-table-ship'); ?>">[?]</a></th>
												<th><?php _e( 'Shipping Class', 'be-table-ship' ); ?></th>
												<th><?php _e( 'Based On', 'be-table-ship' ); ?></th>
												<th><?php _e( 'Min', 'be-table-ship' ); ?></th>
												<th><?php _e( 'Max', 'be-table-ship' ); ?></th>
												<th><?php _e( 'Cost', 'be-table-ship' ); ?> <a class="tips" data-tip="<?php echo $cur_symbol . ' - '; echo __('Fixed Price', 'be-table-ship' ) . '&lt;br /&gt;% - ' . __( 'Percentage of Subtotal', 'be-table-ship' ) . '&lt;br /&gt;x - ' . __( 'Multiply cost by quantity', 'be-table-ship' ) . '&lt;br /&gt;w - ' . __( 'Multiply cost by weight', 'be-table-ship' ) . '&lt;br /&gt;D - ' . __( 'Deny: the titled shipping rate will be removed','be-table-ship'); ?>">[?]</a></th>
												<th><?php _e( 'Bundle', 'be-table-ship' ); ?> <a class="tips" data-tip="<?php _e('If supplied, charges cost up until quantity given. Then charges second price for this and every item after.','be-table-ship'); ?>">[?]</a></th>
												<th><?php _e( 'Default', 'be-table-ship' ); ?> <a class="tips" data-tip="<?php _e('Check the box to set this option as the default selected choice on the cart page','be-table-ship'); ?>">[?]</a></th>
									</tr>
								</thead>
								<tfoot>
									<tr>
										<th colspan="3"><a href="#" class="add button"><?php _e( 'Add Table Rate', 'be-table-ship' ); ?></a></th>
										<th colspan="8" style="text-align:right;"><small><?php _e( 'Use the wildcard symbol (*) to denote multiple regions', 'be-table-ship' ); ?></small>
											<a href="#" class="double button"><?php _e( 'Duplicate selected rates', 'be-table-ship' ); ?></a>
											<a href="#" class="remove button"><?php _e( 'Delete selected rates', 'be-table-ship' ); ?></a></th>
									</tr>
								</tfoot>
								<tbody class="table_rates">
									<?php
									$i = -1;
									if ( $this->table_rates ) {
										foreach ( $this->table_rates as $class => $rate ) {
											$i++;
							$selType = "<select name=\"". $this->id ."_shiptype[" . $i . "]\" class=\"shiptype\">
								<option>".$cur_symbol."</option>
								<option";
								if($rate['shiptype'] == "%") $selType .= " selected=\"selected\"";
								$selType .= ">%</option>
								<option";
								if($rate['shiptype'] == "x") $selType .= " selected=\"selected\"";
								$selType .= ">x</option>
								<option";
								if($rate['shiptype'] == "w") $selType .= " selected=\"selected\"";
								$selType .= ">w</option>
								<option";
								if($rate['shiptype'] == "D") $selType .= " selected=\"selected\"";
								$selType .= ">D</option></select>";
											$condOps = "";
											foreach($conds as $key => $val) {
												$condOps .= '<option value="' . $key . '" ' . selected($rate['cond'], $key, false) . '>' . $val . '</option>';
											}
											$zoneOps = "";
											foreach ($zones as $value) {
												$zoneOps .= '<option value="' . $value['zone_id'] . '" ' . selected($rate['zone'], $value['zone_id'], false) . '>' . $value['zone_title'] . '</option>';
											}

											echo '<tr class="cart_rate">
													<td class="check-column"><input type="checkbox" name="select" /></td>
													<td><input type="text" value="' . stripslashes( $rate['title'] ) . '" name="'. $this->id .'_title[' . $i . ']" class="title" size="25" /></td>
													<td><input type="text" value="' . $rate['identifier'] . '" name="'. $this->id .'_identifier[' . $i . ']" class="identifier" size="25" /></td>
													<td><select name="'. $this->id .'_zone[' . $i . ']" class="zone">' . $zoneOps . '</select></td>
													<td><select name="'. $this->id .'_class[' . $i . ']" class="class"><option>*</option>';
													foreach($shippingClasses as $key => $val) echo '<option value="' . $val->term_id . '" '.selected( $rate['class'], $val->term_id, false) . '>' . $val->name . '</option>';
											echo '</select></td><td><select name="'. $this->id .'_cond[' . $i . ']" class="condition">' . $condOps . '</select></td>
													<td><input type="text" value="' . $rate['min'] . '" name="'. $this->id .'_min[' . $i . ']" class="min" placeholder="'.__( 'n/a', 'be-table-ship' ).'" size="6" /></td>
													<td><input type="text" value="' . $rate['max'] . '" name="'. $this->id .'_max[' . $i . ']" class="max" placeholder="'.__( 'n/a', 'be-table-ship' ).'" size="6" /></td>
													<td>' . $selType . ' <input type="text" value="' . $rate['cost'] . '" name="'. $this->id .'_cost[' . $i . ']" class="cost" placeholder="'.__( '0.00', 'be-table-ship' ).'" size="6" /></td>
													<td>qty >= <input type="text" value="' . $rate['bundle_qty'] . '" name="'. $this->id .'_bundle_qty[' . $i . ']" class="bundle_qty" placeholder="0" size="3" /><br />' . $cur_symbol . '
														<input type="text" value="' . $rate['bundle_cost'] . '" name="' . $this->id . '_bundle_cost[' . $i . ']" class="bundle_cost" placeholder="'.__( '0.00', 'be-table-ship' ).'" size="6" /></td>
													<td><input type="checkbox" name="' . $this->id . '_default[' . $i . ']" class="default" '.checked( $rate['default'], 'on', false) . ' /></td>
												</tr>';
										}
									}
									?>
									</tbody>
								</table>
						</td>
				</tr>
			<tr valign="top" id="shipping_class_priorities">
						<th scope="row" class="titledesc"><?php _e( 'Shipping Class Priorities', 'be-table-ship' ); ?>:</th>
						<td class="forminp" id="<?php echo $this->id; ?>_class_priorities">
							<table class="shippingrows widefat" cellspacing="0">
								<thead>
									<tr>
												<th class="shipping_class"><?php _e( 'Shipping Class', 'be-table-ship' ); ?></th>
												<th><?php _e( 'Priority', 'be-table-ship' ); ?> <a class="tips" data-tip="Enter any whole number, largest number is highest priority">[?]</a></th>
												<th><?php _e( 'Exclude', 'be-table-ship' ); ?> <a class="tips" data-tip="If shipping is free for items with this class, check the box to exclude these cart items from the per-order method">[?]</a></th>
									</tr>
								</thead>
								<tfoot>
									<tr>
										<th colspan="3"><i><?php _e( 'These priorities will be used to calculate the appropriate shipping price in the table above. When an order has items of different shipping classes, the one with the highest priority will be used.', 'be-table-ship' ); ?></i></th>
									</tr>
								</tfoot>
								<tbody class="class_priorities">
									<?php
									$class_priorities_array = array();
									if(count($shippingClasses) > 0) {
										foreach ($shippingClasses as $key => $val) {
											$class_priorities_array[$val->term_id] = array("term_id" => $val->term_id, "name" => $val->name, "priority" => (float) 10, "exclude" => '0');
										}
									}
									if(count($this->class_priorities) > 0) {
										foreach ($this->class_priorities as $key => $val) {
											if(!array_key_exists($val['term_id'], $class_priorities_array)) unset($this->class_priorities[$val['term_id']]);
												elseif( $class_priorities_array[$key]['name'] != $val['name'] ) $this->class_priorities[$key]['name'] = $class_priorities_array[$key]['name'];
										}
									}
									$class_priorities_array = $this->class_priorities + $class_priorities_array;

					// Sort Array by Priority
					if(count($class_priorities_array) > 0) {
						foreach ($class_priorities_array as $key => $row) {
								$name[$key]  = $row['name'];
								$priority[$key] = $row['priority'];
						}
						array_multisort($priority, SORT_DESC, $name, SORT_ASC, $class_priorities_array);
					}

									$i = -1;
									if(count($class_priorities_array) > 0) {
										foreach ( $class_priorities_array as $id => $arr ) {
											$i++;
											$checked = ($arr['excluded'] == 'on') ? ' checked="checked"' : '';
											echo '<tr class="shipping_class">
												<input type="hidden" name="'. $this->id .'_scpid[' . $i . ']" value="' . $arr['term_id'] . '" />
												<input type="hidden" name="'. $this->id .'_scp[' . $i . ']" value="' . $id . '" />
												<input type="hidden" name="'. $this->id .'_sname[' . $i . ']" value="' . $arr['name'] . '" />
												<td>'.$arr['name'].'</td>
													<td><input type="text" value="' . $arr['priority'] . '" name="'. $this->id .'_priority[' . $i . ']" size="5" /></td>
													<td><input type="checkbox" ' . $checked . '" name="'. $this->id .'_excluded[' . $i . ']" size="5" /></td>';
										}
									} else echo '<tr colspan="3"><td>You have no shipping classes available</td></tr>'
									?>
									</tbody>
								</table>
						</td>
				</tr>
	</table><!--/.form-table-->
	<h3 class="title_drop title_h4 ship_free_title"><?php _e('Set the Order Shipping Options Will Appear','be-table-ship'); ?></h3>
	<table class="form-table">
			<tr valign="top" id="shipping_title_order">
						<th scope="row" class="titledesc"><?php _e( 'Shipping Cost Order', 'be-table-ship' ); ?>:</th>
						<td class="forminp" id="<?php echo $this->id; ?>_order_titles">
							<table class="shippingrows widefat" cellspacing="0">
								<tbody>
<?php
								if(count($this->title_order) > 0) {
									foreach ( $this->title_order as $tor ) {
?>
						<tr><td class="title"><input type="hidden" name="<?php echo $this->id; ?>_title_order[]" value="<?php echo $tor; ?>"><span><?php echo $tor; ?></span></td></tr>
<?php
									}
								}
?>
					</tbody>
							</table>
				<p><?php _e('Not seeing all of your options','be-table-ship'); ?>? <a href="#" id="refresh_list"><?php _e('Refresh List','be-table-ship'); ?></a></p>
						</td>
				</tr>
	</table>
	<script type="text/javascript">
		jQuery(function() {
			if( jQuery('h4.title_drop').length != 0 )
				settings_headline = jQuery('h4.title_drop');
			else
				settings_headline = jQuery('h3.title_drop')
			console.log(settings_headline)
			settings_headline.next('.form-table').css('display','none');
			jQuery('.title_drop.general_settings_title').next('.form-table').css('display','table');
			jQuery('.title_drop.table_settings_title').next('.form-table').css('display','table');
			settings_headline.live('click', function(){
				if (jQuery(this).next('.form-table').is(":hidden")) {
					jQuery(this).next('.form-table').show("slow","linear");
					jQuery(this).addClass('active');
				} else {
					jQuery(this).next('.form-table').hide("slow","linear");
					jQuery(this).removeClass('active');
				}
				//jQuery(this).next('.form-table').slideToggle("slow");
			});

			jQuery('#<?php echo $this->id; ?>_table_rates a.add').live('click', function(){
				var size = jQuery('#<?php echo $this->id; ?>_table_rates tbody .cart_rate').size();

				jQuery('<tr class="cart_rate">\
						<td class="check-column"><input type="checkbox" name="select" /></td>\
											<td><input type="text" name="<?php echo $this->id; ?>_title[' + size + ']" class="title" size="25" /></td>\
											<td><input type="text" name="<?php echo $this->id; ?>_identifier[' + size + ']" class="identifier" size="25" /></td>\
												<td><select name="<?php echo $this->id; ?>_zone[' + size + ']" class="zone"><?php echo addslashes($zoneOpsJS); ?></select></td>\
												<td><select name="<?php echo $this->id; ?>_class[' + size + ']" class="class"><option>*</option><?php echo addslashes($classOpsG); ?></select></td>\
												<td><select name="<?php echo $this->id; ?>_cond[' + size + ']" class="condition"><?php echo addslashes($condOpsG); ?></select></td>\
												<td><input type="text" name="<?php echo $this->id; ?>_min[' + size + ']" class="min" placeholder="0" size="6" /></td>\
												<td><input type="text" name="<?php echo $this->id; ?>_max[' + size + ']" class="max" placeholder="*" size="6" /></td>\
												<td><select name="<?php echo $this->id; ?>_shiptype[' + size + ']" class="shiptype"><option><?php echo $cur_symbol; ?></option><option>%</option><option>x</option><option>w</option><option>D</option></select>\
													<input type="text" name="<?php echo $this->id; ?>_cost[' + size + ']" class="cost" placeholder="0.00" size="6" /></td>\
										<td>qty >= <input type="text" name="<?php echo $this->id; ?>_bundle_qty[' + size + ']" class="bundle_qty" placeholder="0" size="3" /><br />\
											<?php echo $cur_symbol; ?> <input type="text" name="<?php echo $this->id; ?>_bundle_cost[' + size + ']" class="bundle_cost" placeholder="0.00" size="6" /></td>\
								<td><input type="checkbox" name="<?php echo $this->id; ?>_default[' + size + ']" class="default" /></td>\
						</tr>').appendTo('#<?php echo $this->id; ?>_table_rates table tbody');

				return false;
			});

			// Duplicate row
			jQuery('#<?php echo $this->id; ?>_table_rates a.double').live('click', function(){
				var size = jQuery('#<?php echo $this->id; ?>_table_rates tbody .cart_rate').size();

				jQuery('#<?php echo $this->id; ?>_table_rates table tbody tr td.check-column input:checked').each(function(i, el){

					jQuery('<tr class="cart_rate">\
							<td class="check-column"><input type="checkbox" name="select" /></td>\
												<td><input type="text" name="<?php echo $this->id; ?>_title[' + size + ']" class="title" size="25" value="' + jQuery(el).closest('tr').find('.title').val() +'" /></td>\
												<td><input type="text" name="<?php echo $this->id; ?>_identifier[' + size + ']" class="identifier" size="25" /></td>\
													<td><select name="<?php echo $this->id; ?>_zone[' + size + ']" class="zone"><?php echo addslashes($zoneOpsJS); ?></select></td>\
													<td><select name="<?php echo $this->id; ?>_class[' + size + ']" class="class"><option>*</option><?php echo addslashes($classOpsG); ?></select></td>\
													<td><select name="<?php echo $this->id; ?>_cond[' + size + ']" class="condition"><?php echo addslashes($condOpsG); ?></select></td>\
													<td><input type="text" name="<?php echo $this->id; ?>_min[' + size + ']" class="min" value="' + jQuery(el).closest('tr').find('.min').val() +'" placeholder="0" size="6" /></td>\
													<td><input type="text" name="<?php echo $this->id; ?>_max[' + size + ']" class="max" value="' + jQuery(el).closest('tr').find('.max').val() +'" placeholder="*" size="6" /></td>\
													<td><select name="<?php echo $this->id; ?>_shiptype[' + size + ']" class="shiptype"><option><?php echo $cur_symbol; ?></option><option>%</option><option>x</option><option>w</option><option>D</option></select>\
														<input type="text" name="<?php echo $this->id; ?>_cost[' + size + ']" class="cost" value="' + jQuery(el).closest('tr').find('.cost').val() +'" placeholder="0.00" size="6" /></td>\
											<td>qty >= <input type="text" name="<?php echo $this->id; ?>_bundle_qty[' + size + ']" placeholder="0" value="' + jQuery(el).closest('tr').find('.bundle_qty').val() +'" size="3" /><br />\
												<?php echo $cur_symbol; ?> <input type="text" name="<?php echo $this->id; ?>_bundle_cost[' + size + ']" value="' + jQuery(el).closest('tr').find('.bundle_cost').val() +'" class="bundle_cost" placeholder="0.00" size="6" /></td>\
											<td><input type="checkbox" name="<?php echo $this->id; ?>_default[' + size + ']" class="default" /></td>\
							</tr>').appendTo('#<?php echo $this->id; ?>_table_rates table tbody');

					jQuery('#<?php echo $this->id; ?>_table_rates table tbody tr').last().find('select.zone').val(jQuery(el).closest('tr').find('select.zone').val())
					jQuery('#<?php echo $this->id; ?>_table_rates table tbody tr').last().find('select.class').val(jQuery(el).closest('tr').find('select.class').val())
					jQuery('#<?php echo $this->id; ?>_table_rates table tbody tr').last().find('select.condition').val(jQuery(el).closest('tr').find('select.condition').val())
					jQuery('#<?php echo $this->id; ?>_table_rates table tbody tr').last().find('select.shiptype').val(jQuery(el).closest('tr').find('select.shiptype').val())
					if(jQuery(el).closest('tr').find('.default').attr('checked') == 'checked') jQuery('#<?php echo $this->id; ?>_table_rates table tbody tr').last().find('.default').attr('checked','checked');

					size = size + 1;
				});
				return false;
			});

			// Remove row
			jQuery('#<?php echo $this->id; ?>_table_rates a.remove').live('click', function(){
				var answer = confirm("<?php _e('Delete the selected rates', 'be-table-ship'); ?>?")
				if (answer) {
					jQuery('#<?php echo $this->id; ?>_table_rates table tbody tr td.check-column input:checked').each(function(i, el){
						jQuery(el).closest('tr').remove();
					});
				}
				return false;
			});

			jQuery('#<?php echo $this->id; ?>_handling_rates a.add').live('click', function(){

			var size = jQuery('#<?php echo $this->id; ?>_handling_rates tbody .handling_fees').size();
			jQuery('<tr class="handling_fees">\
												<td class="check-column"><input type="checkbox" name="select" /></td>\
												<td><select name="<?php echo $this->id; ?>_handling_country[' + size + ']"><?php echo addslashes($zoneOpsJS); ?></select></td>\
												<td><?php echo $cur_symbol; ?> <input type="text" name="<?php echo $this->id; ?>_handling_fee[' + size + ']" placeholder="0.00" size="5" /> &nbsp; % <input type="text" name="<?php echo $this->id; ?>_handling_percent[' + size + ']" placeholder="0.00" size="5" /></td>\
							</tr>').appendTo('#<?php echo $this->id; ?>_handling_rates table tbody');
			return false;
			});

			// Remove row
			jQuery('#<?php echo $this->id; ?>_handling_rates a.remove').live('click', function(){
				var answer = confirm("<?php _e('Delete the selected rates', 'be-table-ship'); ?>?")
				if (answer) {
					jQuery('#<?php echo $this->id; ?>_handling_rates table tbody tr td.check-column input:checked').each(function(i, el){
						jQuery(el).closest('tr').remove();
					});
				}
				return false;
			});

			jQuery('#refresh_list').live('click', function(){
				var tableAr = new Array();
				var titlesAr = new Array();
				jQuery('#<?php echo $this->id; ?>_order_titles table tbody tr').each(function(i, el){
					titlesAr.push(jQuery(el).closest('tr').find('td.title span').html());
				});
				jQuery('#<?php echo $this->id; ?>_table_rates table tbody tr').each(function(i, el){
					tableAr.push(jQuery(el).closest('tr').find('input.identifier').val());
				});

					for ( x = 0; x < tableAr.length; x++ ) {
								if ( jQuery.inArray(tableAr[x], titlesAr) == -1 ) {
								titlesAr.push( tableAr[x] );
								jQuery('<tr><td class="title '+tableAr[x]+'"><input type="hidden" name="<?php echo $this->id; ?>_title_order[]" value="'+tableAr[x]+'"><span>'+tableAr[x]+'</span></td></tr>').appendTo('#<?php echo $this->id; ?>_order_titles table tbody');
							}
					}

					for ( y = 0; y < titlesAr.length; y++ ) {
								if ( jQuery.inArray(titlesAr[y], tableAr) == -1 ) {
								jQuery('#<?php echo $this->id; ?>_order_titles table tbody tr:contains("'+titlesAr[y]+'")').remove();
							}
					}

				return false;
			});

						jQuery(function() {
								var fixHelperModified = function(e, tr) {
										var $originals = tr.children();
										var $helper = tr.clone();
										$helper.children().each(function(index)
										{
											jQuery(this).width($originals.eq(index).width())
										});
										return $helper;
								};
								jQuery("#<?php echo $this->id; ?>_order_titles table tbody").sortable({
										helper: fixHelperModified
								}).disableSelection();
						});
		});
	</script>

</div><!-- Aelia - Shipping Pricing UI Wrapper - END -->
