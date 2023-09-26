<?php if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\Definitions;
use Aelia\WC\Settings;

if(!function_exists('get_raw_number')) {
	/**
	 * Given a number formatted by WooCommerce, it returns the raw number. Input
	 * number must not include the Currency symbol (i.e. it must only contain digits,
	 * the decimal separator and the thousand separator) .
	 *
	 * @param string formatted_number A number containing a decimal separator and,
	 * optionally, the thousand separator.
	 * @return double A raw number.
	 */
	function get_raw_number($formatted_number) {
		$decimal_sep = Settings::decimal_separator();
		$thousands_sep = Settings::thousand_separator();

		// Remove Thousands separator
		$raw_number = str_replace($thousands_sep, '', $formatted_number);
		// Replace whatever Decimal separator with the dot. At this point, number is
		// raw, i.e. in format "12345.67"
		$raw_number = str_replace($decimal_sep, '.', $raw_number);

		return $raw_number;
	}
}

if(!function_exists('default_currency_decimals')) {
	/**
	 * Returns the decimals used by a currency.
	 *
	 * @param string currency A currency code.
	 * @return int
	 */
	function default_currency_decimals($currency) { // NOSONAR
		$currency_decimals = array(
			'AED' => 2, // UAE Dirham
			'AFN' => 2, // Afghanistan Afghani
			'ALL' => 2, // Albanian Lek
			'AMD' => 2, // Armenian Dram
			'ANG' => 2, // Netherlands Antillian Guilder
			'AOA' => 2, // Angolan Kwanza
			'ARS' => 2, // Argentine Peso
			'AUD' => 2, // Australian Dollar
			'AWG' => 2, // Aruban Guilder
			'AZM' => 2, // Azerbaijanian Manat
			'BAM' => 2, // Bosnia and Herzegovina Convertible Marks
			'BBD' => 2, // Barbados Dollar
			'BDT' => 2, // Bangladesh Taka
			'BGN' => 2, // Bulgarian Lev
			'BHD' => 3, // Bahraini Dinar
			'BIF' => 0, // Burundi Franc
			'BMD' => 2, // Bermudian Dollar
			'BND' => 2, // Brunei Dollar
			'BOB' => 2, // Bolivian Boliviano
			'BRL' => 2, // Brazilian Real
			'BSD' => 2, // Bahamian Dollar
			'BTN' => 2, // Bhutan Ngultrum
			'BWP' => 2, // Botswana Pula
			'BYR' => 0, // Belarussian Ruble
			'BZD' => 2, // Belize Dollar
			'CAD' => 2, // Canadian Dollar
			'CDF' => 2, // Franc Congolais
			'CHF' => 2, // Swiss Franc
			'CLP' => 0, // Chilean Peso
			'CNY' => 2, // Chinese Yuan Renminbi
			'COP' => 2, // Colombian Peso
			'CRC' => 2, // Costa Rican Colon
			'CSD' => 2, // Serbian Dinar
			'CUP' => 2, // Cuban Peso
			'CVE' => 2, // Cape Verde Escudo
			'CYP' => 2, // Cyprus Pound
			'CZK' => 2, // Czech Koruna
			'DJF' => 0, // Djibouti Franc
			'DKK' => 2, // Danish Krone
			'DOP' => 2, // Dominican Peso
			'DZD' => 2, // Algerian Dinar
			'EEK' => 2, // Estonian Kroon
			'EGP' => 2, // Egyptian Pound
			'ERN' => 2, // Eritrea Nafka
			'ETB' => 2, // Ethiopian Birr
			'EUR' => 2, // euro
			'FJD' => 2, // Fiji Dollar
			'FKP' => 2, // Falkland Islands Pound
			'GBP' => 2, // Pound Sterling
			'GEL' => 2, // Georgian Lari
			'GHC' => 2, // Ghana Cedi
			'GIP' => 2, // Gibraltar Pound
			'GMD' => 2, // Gambian Dalasi
			'GNF' => 0, // Guinea Franc
			'GTQ' => 2, // Guatemala Quetzal
			'GYD' => 2, // Guyana Dollar
			'HKD' => 2, // Hong Kong Dollar
			'HNL' => 2, // Honduras Lempira
			'HRK' => 2, // Croatian Kuna
			'HTG' => 2, // Haiti Gourde
			'HUF' => 2, // Hungarian Forint
			'IDR' => 2, // Indonesian Rupiah
			'ILS' => 2, // New Israeli Shekel
			'INR' => 2, // Indian Rupee
			'IQD' => 3, // Iraqi Dinar
			'IRR' => 2, // Iranian Rial
			'ISK' => 0, // Iceland Krona
			'JMD' => 2, // Jamaican Dollar
			'JOD' => 3, // Jordanian Dinar
			'JPY' => 0, // Japanese Yen
			'KES' => 2, // Kenyan Shilling
			'KGS' => 2, // Kyrgyzstan Som
			'KHR' => 2, // Cambodia Riel
			'KMF' => 0, // Comoro Franc
			'KPW' => 2, // North Korean Won
			'KRW' => 0, // Korean Won
			'KWD' => 3, // Kuwaiti Dinar
			'KYD' => 2, // Cayman Islands Dollar
			'KZT' => 2, // Kazakhstan Tenge
			'LAK' => 2, // Lao Kip
			'LBP' => 2, // Lebanese Pound
			'LKR' => 2, // Sri Lanka Rupee
			'LRD' => 2, // Liberian Dollar
			'LSL' => 2, // Lesotho Loti
			'LTL' => 2, // Lithuanian Litas
			'LVL' => 2, // Latvian Lats
			'LYD' => 3, // Libyan Dinar
			'MAD' => 2, // Moroccan Dirham
			'MDL' => 2, // Moldovan Leu
			'MGA' => 2, // Malagasy Ariary
			'MKD' => 2, // Macedonian Denar
			'MMK' => 2, // Myanmar Kyat
			'MNT' => 2, // Mongolian Tugrik
			'MOP' => 2, // Macau Pataca
			'MRU' => 2, // Mauritania Ouguiya
			'MTL' => 2, // Maltese Lira
			'MUR' => 2, // Mauritius Rupee
			'MVR' => 2, // Maldives Rufiyaa
			'MWK' => 2, // Malawi Kwacha
			'MXN' => 2, // Mexican Peso
			'MYR' => 2, // Malaysian Ringgit
			'MZM' => 2, // Mozambique Metical
			'NAD' => 2, // Namibia Dollar
			'NGN' => 2, // Nigerian Naira
			'NIO' => 2, // Nicaragua Cordoba Oro
			'NOK' => 2, // Norwegian Krone
			'NPR' => 2, // Nepalese Rupee
			'NZD' => 2, // New Zealand Dollar
			'OMR' => 3, // Rial Omani
			'PAB' => 2, // Panama Balboa
			'PEN' => 2, // Peruvian Nuevo Sol
			'PGK' => 2, // Papua New Guinea Kina
			'PHP' => 2, // Philippine Peso
			'PKR' => 2, // Pakistan Rupee
			'PLN' => 2, // Polish Zloty
			'PYG' => 0, // Paraguayan Guarani
			'QAR' => 2, // Qatari Rial
			'RON' => 2, // New Romanian Leu
			'RUB' => 2, // Russian Ruble
			'RWF' => 0, // Rwanda Franc
			'SAR' => 2, // Saudi Riyal
			'SBD' => 2, // Solomon Islands Dollar
			'SCR' => 2, // Seychelles Rupee
			'SDD' => 2, // Sudanese Dinar
			'SEK' => 2, // Swedish Krona
			'SGD' => 2, // Singapore Dollar
			'SHP' => 2, // St Helena Pound
			'SIT' => 2, // Slovenian Tolar
			'SKK' => 2, // Slovak Koruna
			'SLL' => 2, // Sierra Leone Leone
			'SOS' => 2, // Somali Shilling
			'SRD' => 2, // Surinam Dollar
			'STD' => 2, // São Tome and Principe Dobra
			'SVC' => 2, // El Salvador Colon
			'SYP' => 2, // Syrian Pound
			'SZL' => 2, // Swaziland Lilangeni
			'THB' => 2, // Thai Baht
			'TJS' => 2, // Tajik Somoni
			'TMM' => 2, // Turkmenistan Manat
			'TND' => 3, // Tunisian Dinar
			'TOP' => 2, // Tonga Pa'anga
			'TRY' => 2, // Turkish Lira
			'TTD' => 2, // Trinidad and Tobago Dollar
			'TWD' => 2, // New Taiwan Dollar
			'TZS' => 2, // Tanzanian Shilling
			'UAH' => 2, // Ukraine Hryvnia
			'UGX' => 2, // Uganda Shilling
			'USD' => 2, // US Dollar
			'UYU' => 2, // Peso Uruguayo
			'UZS' => 2, // Uzbekistan Sum
			'VEB' => 2, // Venezuelan Bolivar
			'VND' => 2, // Vietnamese Dong
			'VUV' => 0, // Vanuatu Vatu
			'WST' => 2, // Samoa Tala
			'XAF' => 0, // CFA Franc BEAC
			'XCD' => 2, // East Caribbean Dollar
			'XDR' => 5, // SDR (Special Drawing Rights)
			'XOF' => 0, // CFA Franc BCEAO
			'XPF' => 0, // CFP Franc
			'YER' => 2, // Yemeni Rial
			'ZAR' => 2, // South African Rand
			'ZMK' => 2, // Zambian Kwacha
			'ZWD' => 2, // Zimbabwe Dollar
		);

		return get_value($currency, $currency_decimals, Settings::DEFAULT_DECIMALS);
	}
}

if(!function_exists('aelia_t')) {
	/**
	 * Passes a string to WPML for translation.
	 *
	 * @param string context The context for the translation. Usually, the plugin name.
	 * @param string name The string name, for identification.
	 * @param string value The original text.
	 * @param bool allow_empty Indicates if the value can be empty.
	 * @param bool has_translation Indicates if a translation for the string was found.
	 * @return int
	 */
	function aelia_t($context, $name, $value, $allow_empty = false, &$has_translation = null) {
		if(function_exists('icl_register_string')) {
			icl_register_string($context, $name, $value, $allow_empty);
		}

		if(function_exists('icl_t')) {
			$value = icl_t($context, $name, $value, $has_translation);
		}

		return $value;
	}
}

if(!function_exists('aelia_wc_version_is')) {
	/**
	 * Indicates if the WooCommerce version is greater or equal to the one passed
	 * as a parameter.
	 *
	 * @param string $comparison_operator The operator to use for version comparison.
	 * Any of the operators supported by the version_compare function can be used.
	 * @param string version The version to which WooCommerce version will be compare.
	 * @return bool The result of the version comparison.
	 * @link http://php.net/manual/en/function.version-compare.php
	 * @since 1.5.10.150505
	 */
	function aelia_wc_version_is($comparison_operator, $version) {
		global $woocommerce;
		return version_compare($woocommerce->version, $version, $comparison_operator);
	}
}

if(!function_exists('aelia_wc_registered_order_types')) {
	/**
	 * Returns a list of registered order types. The order types API was sneakily
	 * introduced in WooCommerce 2.2, without announcement and documentation. Since
	 * the AFC plugin must provide backward compatibility, this function will ensure
	 * that the list of order types can be retrieved in WC2.1 and earlier as well.
	 *
	 * @param bool keys_only If True, only the order types will be passed, without
	 * the array of data associated to them.
	 * @param bool include_refunds If True, the "shop_order_refunds" type will be
	 * excluded from the result.
	 * @return array
	 * @see wc_register_order_type
	 * @since 1.5.18.150604
	 */
	function aelia_wc_registered_order_types($keys_only = true, $include_refunds = false) {
		if(function_exists('wc_get_order_types')) {
			$result = wc_get_order_types();
			// Remove the "refund" order type, if requested
			if(!$include_refunds && isset($result['shop_order_refund'])) {
				unset($result['shop_order_refund']);
			}
		}
		else {
			$result = array('shop_order' => array());
		}

		if($keys_only) {
			$result = array_keys($result);
		}
		return $result;
	}
}

if(!function_exists('aelia_wp_version_is')) {
	/**
	 * Indicates if the WordPress version is greater or equal to the one passed
	 * as a parameter.
	 *
	 * @param string $comparison_operator The operator to use for version comparison.
	 * Any of the operators supported by the version_compare function can be used.
	 * @param string version The version to which WooCommerce version will be compare.
	 * @return bool The result of the version comparison.
	 * @link http://php.net/manual/en/function.version-compare.php
	 * @since 1.8.4.170307
	 */
	function aelia_wp_version_is($comparison_operator, $version) {
		global $wp_version;
		return version_compare($wp_version, $version, $comparison_operator);
	}
}

if(!function_exists('aelia_date_to_string')) {
	/**
	 * Converts a timestamp, or a date object, to the specified format.
	 *
	 * @param int|WC_Datetime date The date to convert.
	 * @param string format The target format.
	 * @return string The date as a string in YMD format.
	 * @since 1.9.19.180713
	 */
	function aelia_date_to_string($date, $format = 'Ymd') {
		if(empty($date)) {
			return '';
		}

		if(is_object($date) && ($date instanceof \WC_DateTime)) {
			return $date->format($format);
		}
		return date($format, $date);
	}
}

if(!function_exists('aelia_array_insert_after')) {
	/**
	 * Inserts a new key/value after the key in the array.
	 *
	 * @param string $key
	 * @param array $target_array
	 * @param array $new_elements
	 * @return array
	 * @since 2.0.20.200605
	 */
	function aelia_array_insert_after($key, array $target_array, array $new_elements) {
		// Insert new fields after the identity token
		$insert_position = (array_search($key, array_keys($target_array)) + 1);

		// Split the target in two parts
		$target_first_part = array_slice($target_array, 0, $insert_position);
		$target_second_part = array_slice($target_array, $insert_position);

		// Merge the first part, the new elements and the second
		return $target_first_part + $new_elements + $target_second_part;
	}
}

if(!function_exists('aelia_apply_deprecated_filters')) {
	/**
	 * Runs a deprecated filter with notice only if used.
	 *
	 * @param string $tag The name of the filter hook.
	 * @param array  $args Array of additional function arguments to be passed to do_action().
	 * @param string $version The version of WooCommerce that deprecated the hook.
	 * @param string $replacement The hook that should have been used.
	 * @param string $message A message regarding the change.
	 * @since 2.1.7.210513
	 */
	function aelia_apply_deprecated_filters($tag, $args, $version, $replacement = null, $message = null) {
		if(!has_filter($tag)) {
			return (is_array($args) && !empty($args)) ? $args[0] : null;
		}

		wc_deprecated_hook($tag, $version, $replacement, $message);
		return apply_filters_ref_array($tag, $args);
	}
}

if(!function_exists('aelia_set_object_read')) {
	/**
	 * Sets the "object read" property against an object. The property can have two values:
	 * - true: this will make any changes to the object's property go to the "changes" list. These changes
	 *         will then be committed to the database when the save() method is called.
	 * - false: this will make changes directly to the object's "data" list, but it won't track them to
	 *          save them against the database.
	 *
	 * @param WC_Data $obj
	 * @param bool $object_read
	 * @return bool The function returns the original value of the "object_read" property. This can be
	 * used to restore it at a later stage.
	 * @since 2.2.7.220502
	 */
	function aelia_set_object_read(\WC_Data $obj, bool $object_read): bool {
		$original_value = $obj->get_object_read();
		$obj->set_object_read($object_read);
		return $original_value;
	}
}

if(!function_exists('aelia_set_object_aux_data')) {
	/**
	 * Adds or replace the value of a piece of data in the data map.
	 *
	 * @param object $object
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 * @since 2.3.0.220730
	 */
	function aelia_set_object_aux_data(object $object, string $name, $value): void {
		\Aelia\WC\Object_Data_Tracking\Object_Data_Tracker::set_value($object, $name, $value);
	}
}

if(!function_exists('aelia_get_object_aux_data')) {
	/**
	 * Returns the value of a piece of data from the data map linked to an object. If
	 * the data is not found, null is returned.
	 *
	 * @param object $object
	 * @param string $name
	 * @return mixed
	 * @since 2.3.0.220730
	 */
	function aelia_get_object_aux_data(object $object, $name) {
		return \Aelia\WC\Object_Data_Tracking\Object_Data_Tracker::get_value($object, $name);
	}
}

if(!function_exists('aelia_delete_object_aux_data')) {
	/**
	 * Adds or replace the value of a piece of data in the data map.
	 *
	 * @param object $object
	 * @param string $name
	 * @return void
	 * @since 2.3.0.220730
	 */
	function aelia_delete_object_aux_data(object $object, string $name): void {
		\Aelia\WC\Object_Data_Tracking\Object_Data_Tracker::delete_value($object, $name);
	}
}

if(!function_exists('aelia_maybe_set_object_prop')) {
	/**
	 * Sets an object property, if that property exists against the object.
	 * This function will be useful to set object properties that may be removed in
	 * the future.
	 *
	 * @param object $object
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 * @since 2.3.0.220730
	 */
	function aelia_maybe_set_object_prop(object $object, string $name, $value): void {
		if(property_exists($object, $name)) {
			$object->{$name} = $value;
		}
	}
}

if(!function_exists('aelia_declare_feature_support')) {
	/**
	 * Allows plugins to declare if a specific feature is supported.
	 *
	 * @param string $plugin_file
	 * @param string $feature_name
	 * @param bool $is_feature_supported
	 * @return void
	 * @since 2.4.0.230202
	 */
	function aelia_declare_feature_support(string $plugin_file, string $feature_name, bool $is_feature_supported): void {
		if(class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility($feature_name, $plugin_file, $is_feature_supported);
		}
	}
}

if(!function_exists('aelia_is_hpos_feature_enabled')) {
	/**
	 * Indicates if the High Performance Order Tables feature is enabled.
	 *
	 * @return bool
	 * @since 2.4.0.230202
	 */
	function aelia_is_hpos_feature_enabled(): bool {
		if(class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController')) {
			$hpos_enabled = wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled();
		}
		else {
			$hpos_enabled = false;
		}

		return $hpos_enabled;
	}
}

if(!function_exists('aelia_get_task_scheduler')) {
	/**
	 * Returns the instance of the task scheduler used by the Aelia plugins.
	 *
	 * @return Aelia\WC\AFC\Scheduler\Task_Scheduler
	 * @since 2.4.9.230616
	 */
	function aelia_get_task_scheduler():  Aelia\WC\AFC\Scheduler\Task_Scheduler {
		if(!did_action('aelia_task_scheduler_initialized')) {
			wc_doing_it_wrong(__FUNCTION__, __('The Aelia Task Scheduler is not avaialble before aelia_task_scheduler_initialized action.', Definitions::TEXT_DOMAIN), '2.4.8.230524');
		}
		return \Aelia\WC\AFC\Scheduler\Task_Scheduler::instance();
	}
}
