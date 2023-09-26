<?php
namespace Aelia\WC\Countries;

use Aelia\WC\WC_AeliaFoundationClasses;

if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

/**
 * Handles the flags linked to the various currencies.
 *
 * @since 2.1.12.210628
 */
class Country_Flags {
	/**
	 * Returns the base path of the country flags.
	 *
	 * @return string
	 */
	protected static function get_images_path(): string {
		return WC_AeliaFoundationClasses::instance()->path('images') . '/country-flags';
	}

	/**
	 * Returns the base URL of the country flags.
	 *
	 * @return string
	 */
	protected static function get_images_url(): string {
		return WC_AeliaFoundationClasses::instance()->url('images') . '/country-flags';
	}

	/**
	 * Returns a list of the flag for a list of countries.
	 *
	 * @param array $filter_countries If not empty, only the flags for the specified countries will be returned.
	 * @return array
	 */
	public static function get_country_flags(array $filter_countries = []): array {
		// Fetch a list of countries
		$countries = array_keys(WC()->countries->get_countries());

		// If a filter was passed, only keep the countries from the filter list
		if(!empty($filter_countries)) {
			$countries = array_intersect($countries, $filter_countries);
		}

		// Fetch the base URL for the currency icons
		$images_path = self::get_images_path();
		$images_url = self::get_images_url();

		// Prepare the list of currency URLs
		$country_flags = [];
		foreach($countries as $country_code) {
			// If there isn't an image for the specified country code, return a default image
			$flag_file_name = file_exists("{$images_path}/{$country_code}.svg") ? "{$images_url}/{$country_code}.svg" : "{$images_url}/UNAVAILABLE.svg";

			// Allow 3rd parties to replace the URL to a country flag
			$country_flags[$country_code] = apply_filters('wc_aelia_country_flag_url', $flag_file_name, $country_code);
		}

		return apply_filters('wc_aelia_country_flags_urls', $country_flags);
	}
}
