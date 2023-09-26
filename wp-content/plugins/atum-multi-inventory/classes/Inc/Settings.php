<?php
/**
 * Add Multi-Inventory Settings' tab to ATUM Settings
 *
 * @package     AtumMultiInventory\Inc
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @since       1.0.1
 */

namespace AtumMultiInventory\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\Settings\Settings as AtumSettings;

class Settings {

	/**
	 * The singleton instance holder
	 *
	 * @var Settings
	 */
	private static $instance;


	/**
	 * Settings singleton constructor
	 *
	 * @since 1.0.1
	 */
	private function __construct() {

		add_filter( 'atum/settings/tabs', array( $this, 'add_settings_tab' ), 11 );
		add_filter( 'atum/settings/defaults', array( $this, 'add_settings_defaults' ), 11 );
		add_action( 'atum/settings/before_script_runner_field', array( $this, 'region_switcher_tool_ui' ) );

		// Enqueue_scripts (the priority is important here).
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 1 );

	}

	/**
	 * Add a new tab to the ATUM settings page
	 *
	 * @since 1.0.0
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function add_settings_tab( $tabs ) {

		// TODO: USE ATUM FONT ICON.
		$tabs['multi_inventory'] = array(
			'label'    => __( 'Multi-Inventory', ATUM_MULTINV_TEXT_DOMAIN ),
			'icon'     => 'atmi-multi-inventory',
			'sections' => array(
				'multi_inventory' => __( 'Multi-Inventory Options', ATUM_MULTINV_TEXT_DOMAIN ),
				'geoprompt'       => __( 'Geo Prompt Popup', ATUM_MULTINV_TEXT_DOMAIN ),
			),
		);

		return $tabs;
	}

	/**
	 * Add fields to the ATUM settings page
	 *
	 * @since 1.0.0
	 *
	 * @param array $defaults
	 *
	 * @return array
	 */
	public function add_settings_defaults( $defaults ) {

		$empty_region_option = [ '' => __( '[ None ]', ATUM_MULTINV_TEXT_DOMAIN ) ];

		$wc_shipping_zones = Helpers::get_regions( 'shipping-zones' );
		$shipping_zones    = $empty_region_option;

		if ( ! empty( $wc_shipping_zones ) ) {

			foreach ( $wc_shipping_zones as $shipping_zone ) {
				$shipping_zones[ $shipping_zone['id'] ] = $shipping_zone['zone_name'];
			}

		}

		$countries_arr = Helpers::get_regions( 'countries' );
		$countries_arr = array_merge( $empty_region_option, $countries_arr );
		$maxmind_msg   = '';

		if ( version_compare( WC()->version, '3.9.0', '>=' ) ) {

			$maxmind_options = get_option( 'woocommerce_maxmind_geolocation_settings' );

			if ( empty( $maxmind_options['license_key'] ) ) {
				/* translators: the WooCommerce settings page URL */
				$maxmind_msg = '<br>' . sprintf( __( "It's recommended to enable the <a href='%s' target='_blank'>MaxMind Geolocation services</a> for better performance and more accurate results.", ATUM_MULTINV_TEXT_DOMAIN ), admin_url( 'admin.php?page=wc-settings&tab=integration&section=maxmind_geolocation' ) );
			}

		}

		$mi_settings = array(
			'mi_default_multi_inventory'             => array(
				'group'      => 'multi_inventory',
				'section'    => 'multi_inventory',
				'name'       => __( 'Enable Multi-Inventory for all products', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'       => __( "Enable multi inventory globally for all products. Products with option 'NO' or 'YES' selected at product level will not be affected.", ATUM_MULTINV_TEXT_DOMAIN ),
				'type'       => 'switcher',
				'default'    => 'no',
				'dependency' => array(
					array(
						'field' => 'mi_list_tables_filter',
						'value' => 'no',
					),
				),
			),
			'mi_region_restriction_mode'             => array(
				'group'      => 'multi_inventory',
				'section'    => 'multi_inventory',
				'name'       => __( 'Region restriction mode', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'       => __( "Restrict individual inventories for sale only within specific regions.<ul><li><strong>No restriction:</strong> Inventories have no region restrictions, and only the Inventory Sorting Mode is in use to fulfil orders</li><li><strong>Countries:</strong> ATUM will try to geolocate the visitor's country and will use the inventories restricted to that country only</li><li><strong>Shipping Zones:</strong> Will ask the visitors for their desired delivery address to know which inventories to use for fulfilling the potential order</li>", ATUM_MULTINV_TEXT_DOMAIN ) . $maxmind_msg,
				'type'       => 'button_group',
				'default'    => 'no-restriction',
				'options'    => array(
					'values' => array(
						'no-restriction' => __( 'No Restriction', ATUM_MULTINV_TEXT_DOMAIN ),
						'countries'      => __( 'Countries', ATUM_MULTINV_TEXT_DOMAIN ),
						'shipping-zones' => __( 'Shipping Zones', ATUM_MULTINV_TEXT_DOMAIN ),
					),
				),
				'dependency' => array(
					array(
						'field'    => 'mi_default_shipping_zone',
						'value'    => 'shipping-zones',
						'animated' => FALSE,
					),
					array(
						'field'    => 'mi_default_zone_for_empty_regions',
						'value'    => 'shipping-zones',
						'animated' => FALSE,
					),
					array(
						'field'    => 'mi_default_country',
						'value'    => 'countries',
						'animated' => FALSE,
					),
					array(
						'field'    => 'mi_default_country_for_empty_regions',
						'value'    => 'countries',
						'animated' => FALSE,
					),
					array(
						'field'        => 'mi_use_geoprompt',
						'value'        => 'shipping-zones',
						'animated'     => FALSE,
						'resetDefault' => TRUE,
					),
				),
			),
			'mi_default_shipping_zone'               => array(
				'group'   => 'multi_inventory',
				'section' => 'multi_inventory',
				'name'    => __( 'Default shipping zone', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'    => __( 'Select a shipping zone from the drop-down that ATUM will use in cases where the system was not able to locate the customer', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'    => 'select',
				'default' => '',
				'options' => array(
					'values' => $shipping_zones,
					'style'  => 'width:200px',
				),
			),
			'mi_default_zone_for_empty_regions'      => array(
				'group'   => 'multi_inventory',
				'section' => 'multi_inventory',
				'name'    => __( 'Default for empty regions', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'    => __( 'Enable this if you want to use the default shipping zone for all your inventories with no region assigned', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'no',
			),
			'mi_default_country'                     => array(
				'group'   => 'multi_inventory',
				'section' => 'multi_inventory',
				'name'    => __( 'Default country', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'    => __( 'Select a country from the drop-down that ATUM will use in cases where the system was not able to use geolocation', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'    => 'select',
				'default' => '',
				'options' => array(
					'values' => $countries_arr,
					'style'  => 'width:200px',
				),
			),
			'mi_default_country_for_empty_regions'   => array(
				'group'   => 'multi_inventory',
				'section' => 'multi_inventory',
				'name'    => __( 'Default for empty regions', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'    => __( 'Enable this if you want to use the default country for all your inventories with no region assigned', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'no',
			),
			'mi_default_inventory_sorting_mode'      => array(
				'group'   => 'multi_inventory',
				'section' => 'multi_inventory',
				'name'    => __( 'Inventory sorting mode', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'    => __( 'Default global setting for the inventory sorting mode.<ul><li><strong>FIFO:</strong> First added sells first</li><li><strong>LIFO:</strong> Last added sells first</li><li><strong>BBE:</strong> Shortest lifespan sells first</li><li><strong>Manual:</strong> Set your priorities manually</li>', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'    => 'button_group',
				'default' => 'fifo',
				'options' => array(
					'values' => array(
						'fifo'   => __( 'FIFO', ATUM_MULTINV_TEXT_DOMAIN ),
						'lifo'   => __( 'LIFO', ATUM_MULTINV_TEXT_DOMAIN ),
						'bbe'    => __( 'BBE/Exp.', ATUM_MULTINV_TEXT_DOMAIN ),
						'manual' => __( 'Manual', ATUM_MULTINV_TEXT_DOMAIN ),
					),
				),
			),
			'mi_default_inventory_iteration'         => array(
				'group'   => 'multi_inventory',
				'section' => 'multi_inventory',
				'name'    => __( 'Inventory iteration', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'    => __( 'What to do when the first selling inventory in the list of inventories is out of stock?', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'    => 'button_group',
				'default' => 'use_next',
				'options' => array(
					'values' => array(
						'use_next'     => __( 'Use next in priority order', ATUM_MULTINV_TEXT_DOMAIN ),
						'out_of_stock' => __( 'Show out of stock', ATUM_MULTINV_TEXT_DOMAIN ),
					),
				),
			),
			'mi_default_expirable_inventories'       => array(
				'group'   => 'multi_inventory',
				'section' => 'multi_inventory',
				'name'    => __( 'Expirable inventories', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'    => __( "Set the inventories as 'Out of Stock' when reaching their BBE dates. This global setting can be specified at product level too.", ATUM_MULTINV_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'no',
			),
			'mi_expiry_dates_in_cart'                => array(
				'group'   => 'multi_inventory',
				'section' => 'multi_inventory',
				'name'    => __( 'Expiry dates in cart', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'    => __( 'Show the expiration dates for the products in cart (if any).', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'no',
			),
			'mi_default_price_per_inventory'         => array(
				'group'   => 'multi_inventory',
				'section' => 'multi_inventory',
				'name'    => __( 'Price per inventory', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'    => __( 'Allow to set prices per inventory for all the products that have Multi-Inventory enabled when no specific option is set at product level.', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'no',
			),
			'mi_list_tables_filter'                  => array(
				'group'   => 'multi_inventory',
				'section' => 'multi_inventory',
				'name'    => __( "List tables' filter", ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'    => __( 'Show/Hide the filter to display the MI products only on list tables (products list, Stock Central, Manufacturing Central, etc).', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes',
			),
			'mi_batch_tracking'                      => array(
				'group'   => 'multi_inventory',
				'section' => 'multi_inventory',
				'name'    => __( 'Batch tracking', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'    => __( 'Add search boxes to the WC Orders, Purchase Orders and Inventory Logs screens to be able to track where was used any Inventory batch number.', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'no',
			),
			'mi_default_selectable_inventories'      => array(
				'group'      => 'multi_inventory',
				'section'    => 'multi_inventory',
				'name'       => __( 'Selectable inventories', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'       => __( 'Enable this option to allow users to choose the inventories they want to purchase within product the page and cart.', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'       => 'switcher',
				'default'    => 'no',
				'dependency' => array(
					array(
						'field' => 'mi_default_selectable_inventories_mode',
						'value' => 'yes',
					),
				),
			),
			'mi_default_selectable_inventories_mode' => array(
				'group'   => 'multi_inventory',
				'section' => 'multi_inventory',
				'name'    => __( 'Inventory selection mode', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'    => __( 'Select between a dropdown or a list for selecting inventories within the product page.', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'    => 'button_group',
				'default' => 'dropdown',
				'options' => array(
					'values' => array(
						'dropdown' => __( 'Dropdown', ATUM_MULTINV_TEXT_DOMAIN ),
						'list'     => __( 'List', ATUM_MULTINV_TEXT_DOMAIN ),
					),
				),
			),
			'mi_use_geoprompt'                       => array(
				'group'      => 'multi_inventory',
				'section'    => 'multi_inventory',
				'name'       => __( 'Use geo prompt', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'       => __( 'Configure and use a Prompt to ask for required information to work with restriction zone modes', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'       => 'switcher',
				'default'    => 'no',
				'dependency' => array(
					'section' => 'geoprompt',
					'value'   => 'yes',
				),
			),
			'mi_geoprompt_required_fields'           => array(
				'group'   => 'multi_inventory',
				'section' => 'geoprompt',
				'name'    => __( 'Required fields', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'    => __( 'What information do you need to know to work for your region restriction mode? (Choose one at least or the popup will not be shown)', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'    => 'button_group',
				'default' => array(
					'regions'  => 'yes',
					'postcode' => 'yes',
				),
				'options' => array(
					'input_type' => 'checkbox',
					'multiple'   => TRUE,
					'values'     => array(
						'regions'  => __( 'Country/State', ATUM_MULTINV_TEXT_DOMAIN ),
						'postcode' => __( 'Postal Code', ATUM_MULTINV_TEXT_DOMAIN ),
					),
				),
			),
			'mi_geoprompt_title'                     => array(
				'group'   => 'multi_inventory',
				'section' => 'geoprompt',
				'name'    => __( 'Popup title', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'    => __( 'Set the popup title. You can use [br] to insert a line break', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => __( 'Hello!', ATUM_MULTINV_TEXT_DOMAIN ),
			),
			'mi_geoprompt_subtitle'                  => array(
				'group'   => 'multi_inventory',
				'section' => 'geoprompt',
				'name'    => __( 'Popup subtitle', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'    => __( 'Set the popup subtitle. You can use [br] to insert a line break', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => __( 'Select your region, please', ATUM_MULTINV_TEXT_DOMAIN ),
			),
			'mi_geoprompt_text'                      => array(
				'group'   => 'multi_inventory',
				'section' => 'geoprompt',
				'name'    => __( 'Popup text', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'    => __( "Set the popup description text. You can use the tag [country] to display the user's geolocalized country on this message", ATUM_MULTINV_TEXT_DOMAIN ),
				'type'    => 'textarea',
				'rows'    => 5,
				'cols'    => 50,
				'default' => __( 'We have detected your location and we set your nearest region to [country]. If your shipping address is not within this zone, please select one below.', ATUM_MULTINV_TEXT_DOMAIN ),
			),
			'mi_geoprompt_border_radius'             => array(
				'group'   => 'multi_inventory',
				'section' => 'geoprompt',
				'name'    => __( 'Border radius', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'    => __( 'Select a border radius (in pixels) for the popup corners', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'    => 'number',
				'default' => 0,
				'options' => array(
					'min'  => 0,
					'max'  => 100,
					'step' => 1,
				),
			),
			'mi_geoprompt_bg_color'                  => array(
				'group'   => 'multi_inventory',
				'section' => 'geoprompt',
				'name'    => __( 'Background color', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'    => __( 'Select a background color for this popup', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'    => 'color',
				'default' => '#fff',
			),
			'mi_geoprompt_accent_color'              => array(
				'group'   => 'multi_inventory',
				'section' => 'geoprompt',
				'name'    => __( 'Accent color', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'    => __( 'Select an accent color for this popup', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'    => 'color',
				'default' => '#00b8db',
			),
			'mi_geoprompt_font_color'                => array(
				'group'   => 'multi_inventory',
				'section' => 'geoprompt',
				'name'    => __( 'Font color', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'    => __( 'Select a font color for this popup', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'    => 'color',
				'default' => '#adb5bd',
			),
			'mi_geoprompt_exclusions'                => array(
				'group'   => 'multi_inventory',
				'section' => 'geoprompt',
				'name'    => __( 'Exclusion pages', ATUM_MULTINV_TEXT_DOMAIN ),
				/* translators: the anchor tag with the URL */
				'desc'    => sprintf( __( "Write one regular expression per line compatible with PHP and all those pages' URLs matching the patterns won't show the Geoprompt.<br>Please, note that the pattern should match the REQUEST_URI part, not the full URL.<br>You can use this site for building regular expressions: %s", ATUM_MULTINV_TEXT_DOMAIN ), '<a href="https://regex101.com/" target="_blank">https://regex101.com/</a>' ),
				'type'    => 'textarea',
				'rows'    => 8,
				'default' => '',
			),
		);
		
		// Check if there is a privacy page set in WooCommerce to add the privacy link text setting.
		// The WC privacy page was introduced in WC 3.4.0.
		if ( ! function_exists( 'wc_privacy_policy_page_id' ) || wc_privacy_policy_page_id() ) {

			$description = '';

			if ( function_exists( 'wc_privacy_policy_page_id' ) ) {
				$description = __( 'We have detected that you set a Privacy Page in WooCommerce settings.', ATUM_MULTINV_TEXT_DOMAIN ) . '<br>';
			}

			$description .= __( "Please write here the form's privacy text. If you set the tags [link] and [/link] around a word, only this will be a link to your privacy page. If not, the whole text will be a link", ATUM_MULTINV_TEXT_DOMAIN );
			$description .= '<br>' . __( 'Leave blank to not add the confirmation checkbox', ATUM_MULTINV_TEXT_DOMAIN );

			$mi_settings['mi_geoprompt_privacy_text'] = array(
				'group'   => 'multi_inventory',
				'section' => 'geoprompt',
				'name'    => __( 'Privacy link text', ATUM_MULTINV_TEXT_DOMAIN ),
				'desc'    => $description,
				'type'    => 'text',
				'default' => __( 'I accept the [link]privacy policy[/link]', ATUM_MULTINV_TEXT_DOMAIN ),
			);

			// Add a new field to allow the users to set the privacy page link.
			if ( ! function_exists( 'wc_privacy_policy_page_id' ) ) {

				$mi_settings['mi_geoprompt_privacy_page'] = array(
					'group'   => 'multi_inventory',
					'section' => 'geoprompt',
					'name'    => __( 'Privacy page URL', ATUM_MULTINV_TEXT_DOMAIN ),
					'desc'    => __( "Enter the URL to your privacy page. Please note that the Geo Prompt PopUp won't show in this page", ATUM_MULTINV_TEXT_DOMAIN ),
					'type'    => 'text',
					'default' => '',
				);

			}

		}
		else {
			
			$mi_settings['mi_geoprompt_privacy_html'] = array(
				'group'   => 'multi_inventory',
				'section' => 'geoprompt',
				'name'    => __( 'Privacy link text', ATUM_MULTINV_TEXT_DOMAIN ),
				'default' => __( "We have detected that you didn't set a Privacy Page in WooCommerce settings.<br>If you want to add the Privacy checkbox to Geo Prompt, you must <a href='/wp-admin/admin.php?page=wc-settings&tab=account#wp_page_for_privacy_policy'>configure it</a>.", ATUM_MULTINV_TEXT_DOMAIN ),
				'type'    => 'html',
			);
			
		}

		$region_restriction_mode = Helpers::get_region_restriction_mode();

		if ( 'no-restriction' !== $region_restriction_mode ) {

			$mi_settings['mi_tool_region_to_region'] = array(
				'group'   => 'tools',
				'section' => 'tools',
				'type'    => 'script_runner',
				'options' => array(
					'button_text'   => __( 'Update Now!', ATUM_MULTINV_TEXT_DOMAIN ),
					'button_status' => 'disabled',
					'script_action' => 'atum_tool_mi_region_to_region',
					'wrapper_class' => 'region-switcher',
				),
			);

			$mi_label = '<br><span class="label label-secondary">' . __( 'Multi-Inventory', ATUM_MULTINV_TEXT_DOMAIN ) . '</span>';

			if ( 'countries' === $region_restriction_mode ) {

				$mi_settings['mi_tool_zones_to_countries'] = array(
					'group'   => 'tools',
					'section' => 'tools',
					'name'    => __( 'Migrate from Zones to Countries', ATUM_MULTINV_TEXT_DOMAIN ) . $mi_label,
					'desc'    => __( "If you had the Multi-Inventory restriction mode set to 'Shipping Zones' and decided to change it later to 'Countries', this tool will help you to migrate the regions set for all the inventories.", ATUM_MULTINV_TEXT_DOMAIN ),
					'type'    => 'script_runner',
					'options' => array(
						'button_text'   => __( 'Update Now!', ATUM_MULTINV_TEXT_DOMAIN ),
						'button_status' => 'disabled',
						'script_action' => 'atum_tool_mi_zones_to_countries',
						'wrapper_class' => 'region-switcher',
					),
				);

				$mi_settings['mi_tool_region_to_region']['name'] = __( 'Switch from Countries to Countries', ATUM_MULTINV_TEXT_DOMAIN ) . $mi_label;
				$mi_settings['mi_tool_region_to_region']['desc'] = __( "Using this tool, you'll can switch from one (or multiple) country(ies) to another.", ATUM_MULTINV_TEXT_DOMAIN );

			}
			elseif ( 'shipping-zones' === $region_restriction_mode ) {

				$mi_settings['mi_tool_countries_to_zones'] = array(
					'group'   => 'tools',
					'section' => 'tools',
					'name'    => __( 'Migrate from Countries to Zones', ATUM_MULTINV_TEXT_DOMAIN ) . $mi_label,
					'desc'    => __( "If you had the Multi-Inventory restriction mode set to 'Countries' and decided to change it later to 'Shipping Zones', this tool will help you to migrate the regions set for all the inventories.", ATUM_MULTINV_TEXT_DOMAIN ),
					'type'    => 'script_runner',
					'options' => array(
						'button_text'   => __( 'Update Now!', ATUM_MULTINV_TEXT_DOMAIN ),
						'button_status' => 'disabled',
						'script_action' => 'atum_tool_mi_countries_to_zones',
						'wrapper_class' => 'region-switcher',
					),
				);

				$mi_settings['mi_tool_region_to_region']['name'] = __( 'Switch from Zones to Zones', ATUM_MULTINV_TEXT_DOMAIN ) . $mi_label;
				$mi_settings['mi_tool_region_to_region']['desc'] = __( "Using this tool, you'll can switch from one (or multiple) shipping zone(s) to another.", ATUM_MULTINV_TEXT_DOMAIN );

			}

		}

		return array_merge( $defaults, $mi_settings );

	}

	/**
	 * Outputs the UI for the region switcher tool
	 *
	 * @since 1.0.0
	 *
	 * @param array $field_atts
	 */
	public function region_switcher_tool_ui( $field_atts ) {

		if ( in_array( $field_atts['id'], [ 'mi_tool_region_to_region', 'mi_tool_zones_to_countries', 'mi_tool_countries_to_zones' ] ) ) {

			$regions_from            = $regions_to = array();
			$region_restriction_mode = Helpers::get_region_restriction_mode();

			// Get "from" values.
			if ( ( 'mi_tool_region_to_region' === $field_atts['id'] && 'countries' === $region_restriction_mode ) || 'mi_tool_countries_to_zones' === $field_atts['id'] ) {
				$regions_from = Helpers::get_used_regions( 'countries' );
			}
			elseif ( ( 'mi_tool_region_to_region' === $field_atts['id'] && 'shipping-zones' === $region_restriction_mode ) || 'mi_tool_zones_to_countries' === $field_atts['id'] ) {
				$regions_from = Helpers::get_used_regions( 'shipping-zones' );
			}

			// Get "to" values.
			if ( ( 'mi_tool_region_to_region' === $field_atts['id'] && 'countries' === $region_restriction_mode ) || 'mi_tool_zones_to_countries' === $field_atts['id'] ) {
				$regions_to = Helpers::get_regions( 'countries' );
			}
			elseif ( ( 'mi_tool_region_to_region' === $field_atts['id'] && 'shipping-zones' === $region_restriction_mode ) || 'mi_tool_countries_to_zones' === $field_atts['id'] ) {

				$wc_shipping_zones = Helpers::get_regions( 'shipping-zones' );

				if ( ! empty( $wc_shipping_zones ) ) {

					foreach ( $wc_shipping_zones as $shipping_zone ) {
						$regions_to[ $shipping_zone['id'] ] = $shipping_zone['zone_name'];
					}

				}

			}

			AtumHelpers::load_view( ATUM_MULTINV_PATH . 'views/tools/region-switcher', compact( 'field_atts', 'regions_from', 'regions_to' ) );

		}

	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook
	 */
	public function enqueue_admin_scripts( $hook ) {

		// Enqueue the Multi-Inventory's settings script to ATUM settings.
		if ( in_array( $hook, [ Globals::ATUM_UI_HOOK . '_page_' . AtumSettings::UI_SLUG, 'toplevel_page_' . AtumSettings::UI_SLUG ] ) ) {

			wp_register_script( 'atum-mi-settings', ATUM_MULTINV_URL . 'assets/js/build/atum-mi-settings.js', array( 'jquery', AtumSettings::UI_SLUG ), ATUM_MULTINV_VERSION, TRUE );

			$vars = array(
				'requiredFields' => __( 'All the fields are required.', ATUM_MULTINV_TEXT_DOMAIN ),
			);

			wp_localize_script( 'atum-mi-settings', 'atumMultInvSettingsVars', $vars );
			wp_enqueue_script( 'atum-mi-settings' );

		}

	}


	/*******************
	 * Instance methods
	 *******************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_MULTINV_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_MULTINV_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return Settings instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
