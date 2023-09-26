<?php
namespace Aelia\WC;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use \Exception;
use \WP_Error;

/**
 * Handles the updates of premium plugins whose licenses are managed via the
 * Aelia Software Licenses plugin.
 *
 * @since 1.8.3.170110
 */
class Premium_Plugin_Updater extends Updater {
	/**
	 * The ID of the updater class.
	 *
	 * @var string
	 * @since 1.8.3.170110
	 */
	public static $id = 'aelia-premium-plugin-updater';

	/**
	 * The name of this updater.
	 *
	 * @since 1.9.8.171002
	 */
	public static $updater_name = 'Aelia Licences';

	/**
	 * The prefix used to identify Aelia license keys amongst the saved options.
	 *
	 * @var string
	 * @since 1.9.4.170202
	 */
	protected static $license_option_name_prefix = 'aelia_premium_license_';

	/**
	 * Stored the loaded license data. It's used as a cache, to avoid multiple
	 * queries to load the same data.
	 *
	 * @var array
	 * @since 1.9.4.170410
	 */
	protected $loaded_license_data = array();

	const URL_CUSTOMER_ACCOUNT = 'https://aelia.co/my-account';
	const URL_MY_ACCOUNT = 'https://aelia.co/my-account';
	const URL_AELIA = 'https://aelia.co/';
	const URL_LICENSES_HOW_TO = 'https://aelia.freshdesk.com/solution/articles/3000071127-managing-your-aelia-licences';

	// Test URL
	const API_URL = 'https://aelia.co/wp-admin/admin-ajax.php?action=wc_aelia_sl_ajax';

	const ACTIVATE_SITE = 'activate_site';
	const DEACTIVATE_SITE = 'deactivate_site';
	const REFRESH_SITE_STATUS = 'refresh_site_status';

	/**
	 * A list of the plugins registered with the updater, indexed by file name.
	 * Used to easily identify for which plugin a section is being rendered in
	 * the WP Admin > Plugins page.
	 *
	 * @see Premium_Plugin_Updater::load_products_to_update_by_filename()
	 * @see Premium_Plugin_Updater::get_plugin_by_file_name()
	 * @see Premium_Plugin_Updater::set_hooks()
	 * @since 1.8.3.170110
	 */
	protected $plugins_by_file_name = array();

	/**
	 * Stores a list of errors occurred while checking updates.
	 *
	 * @var array
	 * @since 1.8.3.170110
	 */
	protected $update_checks_errors = null;

	/**
	 * Stores a list to match the slugs used for update checks against the "main"
	 * plugin slug. This is useful when the main plugin slug doesn't match the
	 * plugin's file name, which is instead described by the "slug used for update
	 * checks".
	 *
	 * @var array
	 * @since 1.9.14.180126
	 */
	protected $slugs_for_update_checks_to_plugin_slugs = array();

	/**
	 * Class constructor.
	 *
	 * @param array products_to_update A list of products that should be checked
	 * for updates.
	 */
	public function __construct(array $products_to_update) {
		$this->load_products_to_update_by_filename($products_to_update);

		parent::__construct($products_to_update);
	}

	/**
	 * Returns the ID of the transient used to store the error messages related to
	 * premium plugin licenses.
	 *
	 * @return string
	 * @since 1.9.6.170823
	 */
	protected static function get_update_checks_errors_transient_id() {
		return get_current_user_id() . '_' . self::$id . '_update_errors';
	}

	/**
	 * Returns the URL of the Admin page to manage the premium licences.
	 *
	 * @return string
	 * @since 1.9.10.171201
	 */
	public static function get_licenses_management_page_url() {
		return admin_url('admin.php?page=wc-settings&tab=wc_aelia_foundation_classes&section=aelia-premium-plugin-updater');
	}

	/**
	 * Returns the list of errors occurred while checking plugin licenses.
	 *
	 * @param bool delete_after_get Indicates if the transient used to store the
	 * messages should be deleted after being retrieved.
	 * @return array
	 * @since 1.9.6.170823
	 */
	protected function get_update_checks_errors($delete_after_get = false) {
		if($this->update_checks_errors === null) {
			$this->update_checks_errors = get_site_transient(self::get_update_checks_errors_transient_id());
			if(!is_array($this->update_checks_errors)) {
				$this->update_checks_errors = array();
			}
		}

		return $this->update_checks_errors;
	}

	/**
	 * Populates a list of the plugins handled by this updater, indexed by file
	 * name.
	 *
	 * @param array product_to_update A list of plugins to update.
	 */
	protected function load_products_to_update_by_filename($products_to_update) {
		foreach($products_to_update as $plugin_slug => $plugin) {
			$this->plugins_by_file_name[$plugin->get_plugin_file(true)] = $plugin;
		}
	}

	/**
	 * Returns the instance of a plugin from its file name.
	 *
	 * @param string file_name A plugin's file name.
	 * @return object
	 */
	protected function get_plugin_by_file_name($file_name) {
		if(isset($this->plugins_by_file_name[$file_name])) {
			return $this->plugins_by_file_name[$file_name];
		}
		return null;
	}

	/**
	 * Returns the instance of a plugin from its slug.
	 *
	 * @param string plugin_slug A plugin's slug.
	 * @return object
	 * @since 1.9.4.170410
	 */
	protected function get_plugin_by_slug($plugin_slug) {
		$plugin = isset($this->products_to_update[$plugin_slug]) ? $this->products_to_update[$plugin_slug] : null;
		if(!is_object($plugin)) {
			$this->logger->debug(__('Could not retrieve active plugin by slug. ', Definitions::TEXT_DOMAIN),
													 array(
														'Updater class' => get_class($this),
														'Plugin Slug' => $plugin_slug,
													 ));
			return new WP_Error(Definitions::ERR_INVALID_PRODUCT_SLUG,
													sprintf(__('Invalid product slug: %s. Could not find ' .
																		 'an active plugin with such slug.', self::$text_domain),
																	$plugin_slug));
		}
		return $plugin;
	}

	/**
	 * Sets the hooks required by the class.
	 */
	protected function set_hooks() {
		parent::set_hooks();
		add_action('delete_site_transient_update_plugins', array($this, 'delete_site_transient_update_plugins'), 10);

		// If user can update plugins, show the data about updates and licenses
		if(current_user_can('update_plugins')) {

			foreach(array_keys($this->plugins_by_file_name) as $plugin_file) {
				add_action('after_plugin_row_' . $plugin_file, array($this, 'after_plugin_row'), 20, 2);
			}

			// Add callback to render the license field for each plugin
			add_action('woocommerce_admin_field_'  . self::$id . '_license', array($this, 'woocommerce_admin_field_premium_license'), 10, 1);
			// Ajax
			add_filter('wc_aelia_afc_ajax_callbacks', array($this, 'wc_aelia_afc_ajax_callbacks'), 10, 1);
			add_action('wc_aelia_afc_load_admin_scripts', array($this, 'wc_aelia_afc_load_admin_scripts'), 10);

			// Add messages related to the Premium Plugin Updater
			// @since 2.1.0.200724
			add_filter('wc_aelia_afc_admin_script_params', array($this, 'wc_aelia_afc_admin_script_params'), 10);
		}
	}

	/**
	 * Deletes the cached error messages.
	 *
	 * @since 1.9.12.180104
	 */
	protected function delete_checked_update_errors() {
		delete_site_transient(self::get_update_checks_errors_transient_id());
	}

	/**
	 * Deletes the transients used by this updater.
	 *
	 */
	public function delete_site_transient_update_plugins() {
		delete_site_transient('wc_aelia_afc_premium_updater_license_statuses');
		$this->delete_checked_update_errors();
	}

	/**
	 * Generates the URL to be used to query the updates server.
	 *
	 * @param array args An array of arguments to be passed to the update server.
	 * @return string The URL to call to check for updates.
	 */
	protected function get_api_call_url(array $args) {
		$url = self::API_URL;
		if(defined('WC_AFC_PREMIUM_UPDATER_API_URL')) {
			$url = WC_AFC_PREMIUM_UPDATER_API_URL;
		}

		return $url . '&' . http_build_query($args);
	}

	/**
	 * Returns the URL matching the specified key.
	 *
	 * @param string url_key The URL key.
	 * @return string
	 */
	protected function url($url_key) {
		$urls = array(
			'customer_account' => self::URL_CUSTOMER_ACCOUNT,
			'support' => Definitions::URL_SUPPORT,
			'api_server' => self::API_URL,
		);
		return isset($urls[$url_key]) ? $urls[$url_key] : '<N/A>';
	}

	/**
	 * Returns the name of the option used to store the license for a product.
	 *
	 * @param string product_slug The product slug.
	 * @return string The name of the option containing the license details.
	 * @since 1.9.4.170202
	 */
	protected function get_license_option_name($product_slug) {
		return self::$license_option_name_prefix . $product_slug;
	}

	/**
	 * Returns the license information for the specified product slug.
	 *
	 * @param string plugin_slug A product slug.
	 * @param bool force_load Indicates if the data should be loaded even if it
	 * exists in the cache.
	 * @return array An array containing the information about the license.
	 */
	protected function get_license_data($product_slug, $force_load = false) {
		if(empty($this->loaded_license_data[$product_slug]) || $force_load) {
			$defaults = array(
				'license_key' => '',
				'license_status' => '',
				'date_expiration' => '',
			);
			$license_option_name = $this->get_license_option_name($product_slug);
			// Get the license information for the plugin
			$license_data = get_option($license_option_name, array());
			if(!is_array($license_data)) {
				$license_data = array();
			}

			$this->loaded_license_data[$product_slug] = array_merge($defaults, $license_data);
		}
		return $this->loaded_license_data[$product_slug];
	}

	/**
	 * Updates the license information for the specified product slug.
	 *
	 * @param string plugin_slug A product slug.
	 * @param array license_data The license information to store.
	 * @return array An array containing the information about the license.
	 * @since 1.8.3.170110
	 */
	protected function save_license_data($product_slug, array $license_data) {
		return update_option($this->get_license_option_name($product_slug), $license_data, false);
	}

	/**
	 * Deletes the license information for the specified product slug.
	 *
	 * @param string plugin_slug A product slug.
	 * @param array license_data The license information to store.
	 * @return array An array containing the information about the license.
	 * @since 1.8.3.170110
	 */
	protected function delete_license_data($product_slug) {
		delete_option($this->get_license_option_name($product_slug));
	}

	/**
	 * Returns the slug that should be used to check for updates for a specific
	 * plugin. This allows plugins to have a different slug for updates (useful
	 * when the plugin is renamed).
	 *
	 * @param object plugin A plugin instance.
	 * @return string
	 * @since 1.8.3.170110
	 */
	protected static function get_plugin_slug_for_update_check($plugin) {
		return $plugin->get_slug(true);
	}

	/**
	 * Returns the arguments to query the update servers.
	 *
	 * @param object plugin The plugin for which the server will be queried.
	 * @param string action The action to perform.
	 * @return array An array of arguments that will be passed with the request to
	 * the update server.
	 * @since 1.8.3.170110
	 */
	protected function get_api_args($plugin, $action) {
		$plugin_class = get_class($plugin);

		return wp_parse_args(array(
			Definitions::ARG_AJAX_ACTION => $action,
			// The slug used to check the updates for a plugin might differ from the
			// actual plugin slug (it happened in the past, due to design mistake that
			// haven't been corrected yet). Due to that, we must take the correct slug
			// before we send a request
			Definitions::ARG_PRODUCT_SLUG => self::get_plugin_slug_for_update_check($plugin),
			Definitions::ARG_INSTALLED_VERSION => $plugin_class::$version,
			Definitions::ARG_SITE_URL => self::get_installation_instance_id(),
			Definitions::ARG_SITE_NAME => get_bloginfo('name'),
			Definitions::ARG_SITE_DESCRIPTION => get_bloginfo('description'),
		), $this->get_license_data($plugin_class::$plugin_slug));
	}

	/**
	 * Sends and receives data to and from the server API
	 *
	 * @access public
	 * @since  1.0.0
	 * @return object $response
	 */
	protected function get_plugin_information(array &$args) {
		// Add the instance ID and the site domain to the API call. They will be used
		// to track activations
		$args = array(
			Definitions::ARG_AJAX_ACTION => Definitions::REQ_REMOTE_CHECK_PRODUCT_VERSION,
			Definitions::ARG_SITE_URL => $this->get_site_url(),

		);

		$target_url = esc_url_raw($this->get_api_call_url($args));
		// Add the target URL to the arguments, for debugging purposes
		$args['request_url'] = $target_url;

		$response = wp_remote_get($target_url);

		if(is_wp_error($response) || (wp_remote_retrieve_response_code($response) != 200)) {
			$this->logger->error(__('An error occurred while retrieving plugin information ' .
															'from the remote API.', Definitions::TEXT_DOMAIN),
													 array(
														'Request URL' => $target_url,
														'Response' => $response,
													 ));
			return false;
		}

		$response = wp_remote_retrieve_body($response);
		$response = unserialize($response);

		if(!is_object($response)) {
			$this->logger->error(__('Unexpected response received from remote API.', Definitions::TEXT_DOMAIN),
													 array(
														'Request URL' => $target_url,
														'Response' => $response,
													 ));
			return false;
		}

		// Add a flag that indicates if a new version is available
		$response->new_version_available = !empty($response->new_version) && version_compare($response->new_version, $args['version'], '>');
		return $response;
	}

	/**
	 * Validates the arguments before they are used for a request.
	 *
	 * @return bool
	 * @since 1.8.2.161216
	 */
	protected function validate_update_check_api_request_args($request_args) {
		// This map indicates the arguments required to perform remote Ajax calls
		$required_args = array(
			Definitions::ARG_LICENSE_KEY,
			Definitions::ARG_SITE_URL,
			Definitions::ARG_PRODUCT_SLUG,
			Definitions::ARG_INSTALLED_VERSION,
		);

		$result = true;

		// Check if the required arguments are included. If not, we can't check for
		// updates (we would get an error, anyway)
		$missing_args = array();
		foreach($required_args as $required_arg) {
			if(empty($request_args[$required_arg])) {
				$missing_args[] = $required_arg;
			}
		}

		if(!empty($missing_args)) {
			$this->logger->info(__('Could not check for updates. API request arguments ' .
														 'are invalid or incomplete.', Definitions::TEXT_DOMAIN),
													array(
														'Request arguments' => $request_args,
														'Missing arguments' => $missing_args,
													));
			$result = false;
		}
		return $result;
	}

	/**
	 * Checks for updates for the specified product. This method must be implemented
	 * by descendant classes.
	 *
	 * @param object plugin A plugin descriptor.
	 */
	protected function check_for_product_updates($plugin) {
		// Check for a plugin update
		$request_args = $this->get_api_args($plugin, Definitions::REQ_REMOTE_CHECK_PRODUCT_VERSION);
		$plugin_slug = $plugin->get_slug();
		$plugin_slug_for_update_check = self::get_plugin_slug_for_update_check($plugin);

		// If the licence is not active, don't bother checking for updates. This will
		// save time and HTTP requests
		$plugin_license = $this->get_license_data($plugin_slug);

		if(empty($plugin_license['license_key']) || ($plugin_license['license_status'] != Definitions::SITE_ACTIVE)) {
			$this->logger->debug(__('Premium Plugin license invalid or not active. Update check skipped.', Definitions::TEXT_DOMAIN),
													 array(
														'Updater class' => get_class($this),
														'Plugin Slug' => $plugin_slug,
														'Request Arguments' => $request_args,
													 ));
			return;
		}

		// Map the "plugin slug for update checks" against the main plugin slug. This
		// will be used to update the licence, which is stored using the main
		// plugin slug
		// @since 1.9.14.180126
		$this->slugs_for_update_checks_to_plugin_slugs[$plugin_slug_for_update_check] = $plugin_slug;

		// If the request arguments are not valid, we can skip the request
		if(!$this->validate_update_check_api_request_args($request_args)) {
			return;
		}

		// We need a file name to check for updates. If none was passed, take the
		// current file
		$main_plugin_file = $plugin->get_plugin_file();

		// Generate the URL that will be used to call the updates API
		$api_call_url = $this->get_api_call_url($request_args);

		$this->logger->debug(__('Checking for Premium Plugin updates.', Definitions::TEXT_DOMAIN),
												 array(
													'Updater class' => get_class($this),
													'Request arguments' => $request_args,
													'API URL' => $api_call_url,
													'Plugin Slug' => $plugin_slug,
													'Plugin file' => $plugin->get_plugin_file(),
												 ));

		// Add a filter to log the result of the plugin update checker operation
		add_filter('puc_request_info_result-' . $plugin_slug_for_update_check,
							 array($this, 'puc_request_info_result'), 10, 2);


		try {
			// Invoke the remote API to check for updates
			$update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker( // NOSONAR
					$api_call_url,
					$main_plugin_file,
					$plugin_slug_for_update_check
			);
			$update_checker->debugMode = false;
		}
		catch(\Exception $e) {
			$this->logger->error(__('Exception occurred while checking for plugin updates.', Definitions::TEXT_DOMAIN), array(
				'API Call URL' => $api_call_url,
				'Plugin Sile' => $main_plugin_file,
				'Plugin Slug' => $plugin_slug,
				'Plugin Slug for Update Check' => $plugin_slug_for_update_check,
				'Exception' => $e->getMessage(),
			));
		}
	}

	/**
	 * Given a plugin slug used for update checks, it returns the main slug used
	 * by the plugin.
	 *
	 * @param string plugin_slug_for_update_check
	 * @return string
	 * @since 1.9.14.180126
	 */
	protected function get_plugin_slug_from_update_slug($plugin_slug_for_update_check) {
		return !empty($this->slugs_for_update_checks_to_plugin_slugs[$plugin_slug_for_update_check]) ? $this->slugs_for_update_checks_to_plugin_slugs[$plugin_slug_for_update_check] : '';
	}

	/**
	 * Logs the result of a plugin update request.
	 *
	 * @param array plugin_info The plugin information returned by the server.
	 * @param object result The result object returnd by the server.
	 * @return array The plugin information received as input, unaltered.
	 * @since 1.8.3.170110
	 */
	public function puc_request_info_result($plugin_info, $result) {
		// Debug
		$this->logger->debug(__('Plugin Update Check Request Result', self::$text_domain), array(
			'Plugin Info' => $plugin_info,
			'Result' => $result,
		));

		// The Update Checker class triggers a filter whose name is generated as
		// "puc_request_info_result-", followed by the slug used to check for the
		// plugin updates. Since this function is used as a "catch all", we can
		// fetch the original filter that was used to call it by calling current_filter().
		// We can then remove the prefix, and we have the plugin slug, from which we
		// can retrieve the plugin's main slug.
		// This method is a bit convoluted, but until the PUC library triggers a
		// generic filter, passing the plugin slug to it, instead of unique
		// filters, this will have to do.
		// @since 1.9.14.180126
		$plugin_slug_for_current_update_check = str_replace('puc_request_info_result-', '', current_filter());

		// Get the main plugin slug from the slug returned by the update server. The
		// latter is the slug used for update checks, which may not match the main
		// plugin slug. Hence, the "mapping".
		// @since 1.9.14.180126
		$plugin_slug = $this->get_plugin_slug_from_update_slug($plugin_slug_for_current_update_check);

		if(empty($plugin_slug)) {
			$this->logger->warning(__('Could not find the main plugin slug matchin the result of the update check.', self::$text_domain), array(
				'Plugin Slug for Update Checks' => $plugin_slug_for_current_update_check,
				'Plugin Info' => $plugin_info,
				'Result' => $result,
			));
		}

		// Reset errors for current update check
		$this->update_checks_errors = $this->get_update_checks_errors();
		if(!is_array($this->update_checks_errors)) {
			$this->update_checks_errors = array();
		}
		unset($this->update_checks_errors[$plugin_slug_for_current_update_check]);

		// If the API server returned a valid response, store the license data
		if(!empty($plugin_info) && is_object($plugin_info)) {
			// When the "check status" call completes successfully, update the license
			// data. This will allow to keep the status and license expiration up to date
			$this->save_license_data($plugin_slug, array(
				'license_key' => $plugin_info->license_key,
				'license_status' => $plugin_info->license_status,
				'date_expiration' => $plugin_info->date_expiration,
			));
		}
		else {
			if(is_wp_error($result)) {
				$this->update_checks_errors[$plugin_slug_for_current_update_check] = array(
					'result' => $result->get_error_code(),
					'message' => $result->get_error_message(),
				);
			}
			else {
				if(!empty($result['body'])) {
					$response = json_decode($result['body'], true);

					if(!empty($response)) {
						$this->update_checks_errors[$plugin_slug_for_current_update_check] = $response;
					}
					else {
						$this->update_checks_errors[$plugin_slug_for_current_update_check] = array(
							'result' => Definitions::ERR_REMOTE_REQUEST_UNEXPECTED_RESPONSE,
							'message' => $this->get_message(Definitions::ERR_REMOTE_REQUEST_UNEXPECTED_RESPONSE)
						);
					}
				}
			}
		}

		// Store the error messages related to plugin updates (if any)
		set_site_transient(self::get_update_checks_errors_transient_id(), $this->update_checks_errors, HOUR_IN_SECONDS);

		$this->logger->debug(__('Plugin Update Checker request completed, logging result.', Definitions::TEXT_DOMAIN),
												 array(
													'Plugin Slug' => $plugin_slug,
													'Plugin Slug for Update Checks' => $plugin_slug_for_current_update_check,
													'Plugin Info' => $plugin_info,
													'Result' => $result,
													'Errors' => $this->update_checks_errors,
												 ));

		return $plugin_info;
	}

	/**
	 * Checks for plugin updates.
	 */
	public function check_for_updates() {
		require_once($this->AFC()->path('vendor') . '/yahnis-elsts/plugin-update-checker/plugin-update-checker.php');
		return parent::check_for_updates();
	}

	/**
	 * Performs a request to the remote API server.
	 *
	 * @param string action The action to perform.
	 * @param array request_args A list of arguments to pass with the request.
	 * @return array
	 */
	protected function remote_api_request($action, $request_args, &$response = null) {
		//Options for the wp_remote_get() call. Plugins can filter these, too.
		$options = array(
			// 10 seconds timeout
			'timeout' => 10,
			'headers' => array(
				'Accept' => 'application/json',
			),
		);
		// Generic filter
		$options = apply_filters('wc_aelia_afc_premium_updater_remote_query_options', $options, $action);
		// Specific filter for the indicated action
		$options = apply_filters('wc_aelia_afc_premium_updater_remote_query_' . $action . '_options', $options);

		if(empty($request_args['exec'])) {
			$request_args['exec'] = $action;
		}

		// Build the URL to call
		$request_url = $this->get_api_call_url($request_args);

		// Debug
		// Pass the XDEBUG cookie to the licensing server, if set, to allow
		// debugging of API calls
		// if(isset($_COOKIE['XDEBUG_SESSION'])) {
		// 	$options['cookies'] = array(
		// 		new \WP_Http_Cookie(array(
		// 			'name' => 'XDEBUG_SESSION',
		// 			'value' => $_COOKIE['XDEBUG_SESSION']
		// 		)),
		// 	);
		// }

		$api_response = wp_remote_get(
			$request_url,
			$options
		);

		$this->logger->debug('Remote API Request', array(
			'Request URL' => $request_url,
			'Request arguments' => $request_args,
			'RAW API Response' => $api_response,
		));

		$request_result = $this->validate_api_response($api_response);

		if(is_wp_error($request_result)) {
			$this->logger->error($request_result->get_error_message(), array(
				'Validation result' => $request_result->get_error_code(),
				'Server URL' => self::API_URL,
				'Request arguments' => $request_args,
				'Server response' => $api_response,
			));
			$request_result->add_data(array(
				'Server URL' => self::API_URL,
				'Request arguments' => $request_args,
				'Server response' => $api_response,
			));

			// TODO Handle response in case of error
			// Return the details of the error
			$response = array(
				'license' => array(),
				'message' => sprintf(__('Request to the license server failed. Error code: "%s". Error message: "%s".',
																self::$text_domain),
														 $request_result->get_error_code(),
														 $request_result->get_error_message()),
			);
		}
		else {
			// All good, return response
			$response = json_decode($api_response['body'], true);
		}
		return $request_result;
	}

	/**
	 * Check if $result is a successful update API response.
	 *
	 * @param array|WP_Error $result
	 * @return true|WP_Error
	 */
	protected function validate_api_response($api_response) { // NOSONAR
		$result = Definitions::RES_OK;
		if(is_wp_error($api_response)) {
			return new WP_Error(Definitions::ERR_REMOTE_REQUEST_HTTP_ERROR,
													__('HTTP Error occurred.', self::$text_domain));
		}

		// Check if the response is empty
		if(empty($api_response['response']['code'])) {
			return new WP_Error(Definitions::ERR_REMOTE_REQUEST_RESPONSE_EMPTY,
													__('The remote licensing server did not return a response code. Unable ' .
														 'to determine the result of the request.', self::$text_domain));
		}

		// Check if the remote server replied "not authorized"
		// @since 2.0.16.200317
		if($api_response['response']['code'] == 401) {
			return new WP_Error(Definitions::ERR_REMOTE_REQUEST_UNAUTHORIZED,
													__('The remote server replied that the licence is currently not linked to this site.', self::$text_domain) .
													' ' .
													__('If you wish to activate the licence on this site, please click on the "Activate" button.', self::$text_domain)) .
													' ' .
													__("If the activation returns an error, please log on to your account to verify that you haven't exceeded your activation limit.", self::$text_domain);
		}

		// Check if the remote server replied with an error
		if($api_response['response']['code'] !== 200) {
			return new WP_Error(Definitions::ERR_REMOTE_REQUEST_UNSUCCESSFUL,
													__('Request failed. Please check log file for more information.', self::$text_domain));
		}

		// Check if the remote server didn't return a response
		if(empty($api_response['body']) || (json_decode($api_response['body'], true) === null)) {
			return new WP_Error(Definitions::ERR_REMOTE_REQUEST_UNEXPECTED_RESPONSE,
													__('Remote server returned an empty or invalid response.', self::$text_domain));
		}

		// If the response body contains an error, the response is still not valid.
		// In such case, we can return the error we received
		// @since 1.9.16.180213
		$response_body = json_decode($api_response['body'], true);

		// IMPORTANT
		// The $response_body['result'] might not always be present. Some remote calls,
		// such as the ones used to check for the plugin version, are handled by the
		// Update Server, which only returns the JSON data needed to update a plugin.
		// In such case, we can just skip this check
		if(isset($response_body['result']) && ($response_body['result'] !== Definitions::RES_OK)) {
			return new WP_Error($response_body['result'], __($response_body['message'], self::$text_domain));
		}

		return $result;
	}

	/**
	 * Validates the license for a plugin.
	 *
	 * @param string plugin_slug The slug of plugin whose license will be validate.
	 * @param array response
	 */
	protected function validate_license($plugin_slug, &$response = array()) {
		$plugin = $this->get_plugin_by_slug($plugin_slug);
		if(is_wp_error($plugin)) {
			return $plugin;
		}

		// Check for a plugin update
		$request_args = $this->get_api_args($plugin, Definitions::REQ_REMOTE_CHECK_PRODUCT_VERSION);

		// If there is no license set for the plugin, return a "non valid" status
		if(empty($request_args[Definitions::ARG_LICENSE_KEY])) {
			return new WP_Error(Definitions::ERR_PRODUCT_LICENSE_NOT_SET,
													__('Could not validate an empty license key', self::$text_domain));
		}

		return $this->remote_api_request(Definitions::REQ_REMOTE_CHECK_PRODUCT_VERSION, $request_args, $response);
	}

	public function after_plugin_row($file, $plugin_data) {
		$this->plugins_list_current_file = $file;

		// See also hook "in_plugin_update_message-"
		// TODO For each plugin, show a box with the following data:
		// - If the plugin doesn't have a license, the box should give the link to
		//   the license management page
		// - If the plugin has a valid license, the box should show when it will
		//   expire and eventually invite to renew it
		// - If the plugin has an invalid license, the box should inform the customer
		//   of the issue

		$plugin_file = $this->plugins_list_current_file;

		$plugin = $this->get_plugin_by_file_name($plugin_file);
		if(!is_object($plugin)) {
			return;
		}

		// Skip plugins that are not registered with this updater
		$plugin_slug = $plugin->get_slug();

		if(empty($this->products_to_update[$plugin_slug])) {
			return;
		}

		$update_message = '';
		$plugin_license = $this->get_license_data($plugin_slug);
		if(empty($plugin_license['license_key']) || ($plugin_license['license_status'] != Definitions::SITE_ACTIVE)) {
			$update_message = implode(' ', array(
				sprintf(__('The license key for plugin <strong>%1$s</strong> is not active, or was not entered.', self::$text_domain),
								$plugin_data['Name']),
				sprintf(__('Please <a href="%1$s">go to the license management page</a> to enter your license key to ' .
									 'receive updates and support for this product.',
									 self::$text_domain),
								self::get_licenses_management_page_url()),
			));
		}

		if(empty($update_message)) {
			$this->update_checks_errors = $this->get_update_checks_errors();

			if(!empty($this->update_checks_errors[$plugin_slug])) {
				$error = $this->update_checks_errors[$plugin_slug];
				$update_message = implode(' ', array(
					sprintf(__('An error occurred while checking for updates for plugin <strong>%1$s</strong>', self::$text_domain), $plugin_data['Name']),
					__('Error', self::$text_domain). ':',
					'<span class="error-message">' .
					sprintf('%1$s - %2$s', $error['result'], $error['message']),
					'</span>',
					sprintf(__('Please <a href="%1$s">go to the license management page</a> to enter or update your license key.', self::$text_domain),
									self::get_licenses_management_page_url()),
					sprintf(__('After entering a valid licence, you can then click on "Check for updates", above, to remove this message.', self::$text_domain)),
					' ',
					sprintf(__('Should you need assistance, please feel free to <a href="%1$s" target="_blank">contact the Aelia Support Team</a>.', self::$text_domain), Definitions::URL_SUPPORT),
				));
			}
		}
		?>
		<?php if(!empty($update_message)): ?>
		<tr class="aelia-premium-plugin plugin-update-tr">
			<td colspan="3" class="plugin-update colspanchange">
				<div class="update-message"><?php
					echo $update_message;
				?></div>
			</td>
		</tr>
		<?php endif; ?>
		<?php
	}

	/**
	 * Loads the licenses for the productsm managed by this updater.
	 *
	 * @return array
	 * @since 1.9.4.170410
	 */
	protected function load_product_licenses() {
		$licenses = array();
		foreach($this->products_to_update as $plugin_slug => $plugin) {
			$licenses[$plugin_slug] = $this->get_license_data($plugin_slug);
		}

		return $licenses;
	}

	/**
	 * Returns a list of fields that will generate the UI to manage the premium
	 * licenses.
	 *
	 * @param array settings
	 * @return array
	 * @since 1.9.4.170410
	 */
	protected function get_license_management_fields($settings = array()) {
		// Set section title
		$fields = array(
			array(
				'id' => static::$id,
				'name' => __('Aelia Premium plugins', self::$text_domain),
				'type' => 'title',
				'desc' => wp_kses_post(implode(' ', array(
					'<div class="' . static::$id . ' section_description">',
					__('Here you can manage the licences for your Aelia products.', self::$text_domain),
					__('By activating a licence, you will get access to the updates for the products you purchased.', self::$text_domain),
					'<h4 class="help_title">',
					__('Where to find the licence keys', self::$text_domain),
					'</h4>',
					__('You can find your licence keys on the order confirmation email you received when you completed your order.', self::$text_domain),
					sprintf(__('You can also access your keys by going to <a href="%1$s/orders" target="_blank">Aelia > My Account > Orders</a>.', self::$text_domain),
									self::URL_CUSTOMER_ACCOUNT),
					__('Open the order details page from the list, and you will be able to access the licence keys.', self::$text_domain),
					'<br />',
					sprintf(__('For more information please refer to our documentation: <a href="%s" target="_blank">Aelia - How to manage the licenses for your premium plugins</a>.', self::$text_domain),
									self::URL_LICENSES_HOW_TO),
					// Show a note about Freemius licences
					// @since 2.1.18.211006
					'<br />',
					'<div class="migrate_licence_to_freemius_note">',
					'<h4 class="help_title">',
					__('Important', self::$text_domain) . ' - ' . __('Are you trying to activate a licence key that starts with <code>sk_</code>?', self::$text_domain),
					'</h4>',
					sprintf(__('If you have a licence key that starts with <code>sk_</code>, it was generated by <a href="%1$s" target="_blank">the new licensing system we are introduced in October 2021</a>.', self::$text_domain),
									'https://aelia.co/new-website-live/'),
					'<br />',
					sprintf(__('Please read the following article to learn how to install the latest version of the product and activate your new licence: <a href="%1$s" target="_blank">How to activate a new Freemius licence on an existing website</a>.', self::$text_domain),
									'https://aelia.freshdesk.com/a/solutions/articles/3000110609'),
					'</div>',
					'<br />',
					'<p>',
					sprintf(__('Should you need assistance with the licences, please feel free to <a href="%1$s" target="_blank">contact the Aelia Support Team</a>.', self::$text_domain), Definitions::URL_SUPPORT),
					'</p>',
					'</div>',
				))),
			),
		);

		// Prepare the field for each of the plugins whose licence should be managed
		$licenses = $this->get_product_licenses();
		foreach($licenses as $plugin_slug => $license_data) {
			$field_id = $plugin_slug . '-license';
			$license_data['plugin_slug'] = $plugin_slug;
			$fields[$field_id] = array(
				'id' => $field_id,
				'type' => self::$id . '_license',
				'data' => $license_data,
			);
		}

		// Close the section
		$fields[] = array(
			'id' => static::$id,
			'type' => 'sectionend',
		);
		return $fields;
	}

	/**
	 * Returns a plugin's name, given its slug.
	 *
	 * IMPORTANT
	 * This method relies on the architecture followed by Aelia plugins, which
	 * have a "plugin name" property attached to their main plugin class.
	 *
	 * @param string plugin_slug A plugin slug.
	 * @return string
	 * @since 1.9.4.170410
	 */
	protected function get_plugin_name($plugin_slug) {
		$plugin = isset($this->products_to_update[$plugin_slug]) ? $this->products_to_update[$plugin_slug] : null;
		if(!empty($plugin)) {
			$class = get_class($plugin);

			if(isset($class::$plugin_name)) {
				return $class::$plugin_name;
			}
		}
		return sprintf(__('Plugin name not found. Slug: %s', self::$text_domain), $plugin_slug);
	}

	/**
	 * Returns a plugin's version, given its slug.
	 *
	 * IMPORTANT
	 * This method relies on the architecture followed by Aelia plugins, which
	 * have a "plugin name" property attached to their main plugin class.
	 *
	 * @param string plugin_slug A plugin slug.
	 * @return string
	 * @since 1.9.5.170623
	 */
	protected function get_plugin_version($plugin_slug) {
		$plugin = isset($this->products_to_update[$plugin_slug]) ? $this->products_to_update[$plugin_slug] : null;
		if(!empty($plugin)) {
			$class = get_class($plugin);

			if(isset($class::$version)) {
				return $class::$version;
			}
		}
		return sprintf(__('Plugin version not found. Slug: %s', self::$text_domain), $plugin_slug);
	}

	/**
	 * Renders a section to manage the license for a specific plugin.
	 *
	 * @param array field A field descriptor, with the license data for a plugin.
	 * @since 1.9.4.170410
	 */
	public function woocommerce_admin_field_premium_license($field) {
		$license_key = trim($field['data']['license_key']);
		$plugin_slug = trim($field['data']['plugin_slug']);

		$license_status = !empty($field['data']['license_status']) ? $field['data']['license_status'] : Definitions::SITE_INACTIVE;
		if(!empty($field['data']['date_expiration'])) {
			$license_expiration_obj = \DateTime::createFromFormat('Y-m-d H:i:s', $field['data']['date_expiration']);

			// We use a year such as "9999" to indicate a license that never
			// expires. For this purpose, a label saying that the license will last
			// forever is more user friendly
			if($license_expiration_obj->format('Y') >= '2999') {
				$license_expiration = __('Forever', self::$text_domain);
			}
			else {
				$license_expiration = $license_expiration_obj->format('d F Y');
			}
		}
		else {
			$license_expiration = __('N/A', self::$text_domain);
		}

		// Load a list of messages to display (e.g. errors, warnings)
		// @since 2.0.20.200605
		$messages = $this->get_messages_for_license($field);

		$plugin_name = $this->get_plugin_name($plugin_slug);
		$plugin_version = $this->get_plugin_version($plugin_slug);
		?>
		<div id="<?= $plugin_slug ?>-license-section" class="<?= static::$id ?> license <?= $license_status ?>">
			<div class="plugin_name section">
				<span class="title"><?php
					echo $plugin_name . ' (' . __('Installed version:', self::$text_domain) . ' ' . $plugin_version . ')';
				?></span>
			</div>
			<div class="license_key_section">
				<div class="license_key_wrapper">
					<label class="label" for="<?= $field['id'] ?>"><?= __('License Key', self::$text_domain) ?></label>
					<input id="<?= $field['id'] ?>"
								 name="<?= $field['id'] ?>"
								 class="license_key"
								 type="text"
								 size="50"
								 value="<?= $field['data']['license_key'] ?>"
								 <?= ($license_status == 'active') ? 'readonly="readonly"' : '' ?> />
				</div>
				<div class="actions">
					<?php $refresh_action_css = empty($license_key) ? 'hidden' : ''; ?>
					<button id="" type="button" class="button refresh_status action <?php echo $refresh_action_css; ?>" data-action="refresh_site_status" data-plugin_slug="<?= $plugin_slug ?>"><?php
						echo __('Check status', self::$text_domain);
					?></button>
					<button id="" type="button" class="button activate action" data-action="activate_site" data-plugin_slug="<?= $plugin_slug ?>"><?php
						echo __('Activate', self::$text_domain);
					?></button>
					<button id="" type="button" class="button deactivate action" data-action="deactivate_site" data-plugin_slug="<?= $plugin_slug ?>"><?php
						echo __('Deactivate', self::$text_domain);
					?></button>
					<?php
						// Allow to replace a license key, without having to go through the
						// deactivate/activate steps
						// @since 1.9.18.180319
					?>
					<div class="license_key_replace_actions_wrapper">
						<button id="" type="button" class="button replace_license action" data-action="replace_license_key" data-plugin_slug="<?= $plugin_slug ?>"
										title="<?php echo __('Click on this button to replace the license key with another one, for example if you purchased a new one for this site.', self::$text_domain); ?>"><?php
							echo __('Replace license key', self::$text_domain);
						?></button>
						<button id="" type="button" class="button cancel_replace_license action hidden" data-action="cancel_replace_license_key" data-plugin_slug="<?= $plugin_slug ?>"><?php
							echo __('Cancel replacement', self::$text_domain);
						?></button>
						<button id="" type="button" class="button button-primary confirm_replace_license action hidden" data-action="confirm_replace_license_key" data-plugin_slug="<?= $plugin_slug ?>"><?php
							echo __('Save new license key', self::$text_domain);
						?></button>
					</div>
				</div>
			</div>
			<div class="license_status_section">
				<span class="label"><?= __('Status', self::$text_domain); ?>:</span>
				<span class="license_status"><?php
					echo $license_status;
				?></span>
				<span class="label"><?= __('Valid until', self::$text_domain); ?>:</span>
				<span class="license_expiration"><?php
					echo $license_expiration;
				?></span>
				<!-- Error message - Hidden by default, displayed after Ajax calls -->
				<span class="license_error_message hidden">
					<span class="label"><?= __('Last response', self::$text_domain); ?>:</span>
					<span class="error_message"><?php
						echo "";// Error message will be displayed here via JS
					?></span>
				</span>
			</div>
			<?php if(!empty($messages)): ?>
				<div class="warnings"><?php
					foreach($messages as $message_id => $message_text) {
						?>
						<div class="warning <?= $message_id ?>"><?php
							echo wp_kses_post($message_text);
						?></div>
						<?php
					}
				?></div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Returns a list of messages (errors, warnings) to be displayed for a section
	 * of licensing data.
	 *
	 * @param array $license_field
	 * @return array
	 * @since 2.0.20.200605
	 */
	protected function get_messages_for_license($license_field) {
		$messages = array();

		if(!empty($license_field['data']['activated_on_site_url']) && ($license_field['data']['activated_on_site_url'] !== self::get_site_url())) {
			// Invite admins to reactivate a licence after the site's domain/URL changed
			$messages['domain_changed'] = implode(' ' , array(
					'<strong class="label">' . __('Important', self::$text_domain) . ':</strong>',
					'<br />',
					__('The domain or address of this site seems to have changed.', self::$text_domain),
					__('A licence activated for this product, when the site had a different URL, could have to be reactivated.', self::$text_domain),
					__('This will not affect the Aelia plugins, which will keep working normally, but it could prevent your site from receiving automatic updates.', self::$text_domain),
					'<br />',
					sprintf(__('If you see any message indicating that this might be the case, please feel free to ' .
										 '<a href="%1$s" target="_blank">contact our support service</a>. We will be happy to assist you.', self::$text_domain), Definitions::URL_SUPPORT),
				));
		}

		return $messages;
	}

	/**
	 * Indicate if we are on the page to configure the licenses managed by this
	 * updater.
	 *
	 * @return bool
	 * @since 1.9.4.170410
	 */
	protected function managing_licenses() {
		$active_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
		$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : '';
		$active_section = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : '';

		return ($active_page === 'wc-settings') &&
					 (($active_tab === Updater::$id) || ($active_tab === \Aelia\WC\AFC\Settings::$id && (empty($active_section) || ($active_section === static::$id))));
	}

	/**
	 * Loads the Admin JavaScript required by this class.
	 *
	 * @since 1.9.4.170410
	 */
	public function wc_aelia_afc_load_admin_scripts() {
		// Prepare parameters for Premium License Management page
		if(self::managing_licenses()) {
			// Load JavaScript for the Premium License Management section
			wp_enqueue_script(Definitions::PLUGIN_SLUG . '-admin-manage-premium-licenses',
												$this->AFC()->url('js') . '/admin/admin-manage-premium-licenses.js',
												array('jquery', Definitions::PLUGIN_SLUG . '-admin-common'),
												null,
												true);
		}
	}

	/**
	 * Adds custom admin parameters.
	 *
	 * @param array $params
	 * @return array
	 * @since 2.1.0.200724
	 */
	public function wc_aelia_afc_admin_script_params($params) {
		$params['premium_updater'] = array(
			'messages' => array(
				'expiration_forever' => __('Forever', self::$text_domain),
				'expiration_not_available' => __('N/A', self::$text_domain),
			),
		);
		return $params;
	}

	/**
	 * Adds the Ajax callbacks implemented by this class.
	 *
	 * @param array ajax_callbacks An array of ajax command => callback pairs.
	 * @return array The modified array of callbacks.
	 * @since 1.9.4.170410
	 */
	public function wc_aelia_afc_ajax_callbacks($ajax_callbacks) {
		return array_merge($ajax_callbacks, array(
			// Remote Ajax requests
			static::$id . '-' . self::ACTIVATE_SITE => array($this, 'ajax_activate_site'),
			static::$id . '-' . self::DEACTIVATE_SITE => array($this, 'ajax_deactivate_site'),
			static::$id . '-' . self::REFRESH_SITE_STATUS => array($this, 'ajax_refresh_site_status'),
		));
	}

	/**
	 * Returns the arguments passed with a remote request.
	 *
	 * @since 0.1.9.160921
	 */
	protected static function get_request_args($args = null) {
		// TODO Implement method
		//if($args === null) {
		//	$args = &$_REQUEST;
		//}
		//
		//$not_defined_message = __('N/A', self::$text_domain);
		//$no_user_id = -1;
		//$defaults = array(
		//	'license_key' => $not_defined_message,
		//	'product_slug' => $not_defined_message,
		//	'site_url' => $not_defined_message,
		//	'site_name' => $not_defined_message,
		//	'site_description' => $not_defined_message,
		//	'user_id' => $no_user_id,
		//	'user_email' => $not_defined_message,
		//);
		//$args = array_merge($defaults, $args);
		//
		//return array(
		//	'license_key' => isset($args[Definitions::ARG_LICENSE_KEY]) ? $args[Definitions::ARG_LICENSE_KEY] : $not_defined_message,
		//	'product_slug' => isset($args[Definitions::ARG_PRODUCT_SLUG]) ? $args[Definitions::ARG_PRODUCT_SLUG] : $not_defined_message,
		//	'site_url' => $args[Definitions::ARG_SITE_URL],
		//	'site_name' => $args[Definitions::ARG_SITE_NAME],
		//	'site_description' => $args[Definitions::ARG_SITE_DESCRIPTION],
		//	'ip_address' => self::get_remote_ip_address(),
		//	'user_id' => isset($args[Definitions::ARG_REMOTE_USER_ID]) ? $args[Definitions::ARG_REMOTE_USER_ID] : $no_user_id,
		//	'user_email' => isset($args[Definitions::ARG_REMOTE_USER_EMAIL]) ? $args[Definitions::ARG_REMOTE_USER_EMAIL] : $not_defined_message,
		//);
	}

	/**
	 * Activates a licence for a site.
	 *
	 * @since 1.9.4.170410
	 */
	public function ajax_activate_site() {
		$plugin_slug = isset($_REQUEST['plugin_slug']) ? $_REQUEST['plugin_slug'] : '';
		$license_key = isset($_REQUEST['license_key']) ? $_REQUEST['license_key'] : '';

		$action_response = $this->set_site_activation_status(
			Definitions::REQ_REMOTE_ACTIVATE_SITE,
			$plugin_slug,
			$license_key
		);

		// Check if the API call went well (i.e. no REST errors)
		if($action_response['result'] === Definitions::RES_OK) {
			// Check the actual result of the operation. If it went well too, we can
			// save the licence details
			if($action_response['response']['result'] === Definitions::RES_OK) {
				$license_data = array(
					'license_key' => $license_key,
					'license_status' => $action_response['response']['license']['site_status'],
					'date_expiration' => $action_response['response']['license']['date_expiration'],
					// Save the URL used for the activation. That will allow to track changes in the
					// domain and inform the admins
					// @since 2.0.20.200605
					'activated_on_site_url' => self::get_site_url(),
				);
				$this->save_license_data($plugin_slug, $license_data);
			}
			// Delete any cached error messages
			// @since 1.9.13.180104
			$this->delete_checked_update_errors();
		}
		else {
			// Error handling
			// @since 1.9.13.180123
			$action_response = $this->handle_ajax_request_error($action_response);
		}

		// TODO Translate $action_response['date_expiration'] to user's locale and add to the response
		// TODO Translate $action_response['license_status'] to a human friendly message

		wp_send_json($action_response);
		exit;
	}

	/**
	 * Deactivates a licence for a site.
	 *
	 * @since 1.9.4.170410
	 */
	public function ajax_deactivate_site() {
		$plugin_slug = isset($_REQUEST['plugin_slug']) ? $_REQUEST['plugin_slug'] : '';
		$license_key = isset($_REQUEST['license_key']) ? $_REQUEST['license_key'] : '';

		$action_response = $this->set_site_activation_status(
			Definitions::REQ_REMOTE_DEACTIVATE_SITE,
			$plugin_slug,
			$license_key
		);

		if(isset($action_response['response']['result']) && ($action_response['response']['result'] === Definitions::RES_OK)) {
			// Update the license data that has changed, while preserving any extra data, such
			// as the URL of the original activation
			// @since 2.0.20.200605
			$license_data = array_merge($this->get_license_data($plugin_slug, true), array(
				'license_key' => $license_key,
				'license_status' => $action_response['response']['license']['site_status'],
				'date_expiration' => $action_response['response']['license']['date_expiration'],
			));
			$this->save_license_data($plugin_slug, $license_data);
			// Delete any cached error messages
			// @since 1.9.13.180104
			$this->delete_checked_update_errors();
		}
		else {
			// Error handling
			// @since 1.9.13.180123
			$action_response = $this->handle_ajax_request_error($action_response);
		}

		// TODO Translate $action_response['date_expiration'] to user's locale and add to the response
		// TODO Translate $action_response['license_status'] to a human friendly message

		wp_send_json($action_response);
		exit;
	}

	/**
	 * Refreshes the status of a licence for a site.
	 *
	 * @since 1.9.4.170410
	 */
	public function ajax_refresh_site_status() {
		$plugin_slug = isset($_REQUEST['plugin_slug']) ? $_REQUEST['plugin_slug'] : '';
		$license_key = isset($_REQUEST['license_key']) ? $_REQUEST['license_key'] : '';

		if(!empty($plugin_slug)) {
			$license_data = $this->get_license_data($plugin_slug, true);

			$response = array();
			$license_validation = $this->validate_license($plugin_slug, $response);

			if(is_wp_error($license_validation)) {
				$license_error_message = $response['message'];

				// Here "license status" is actually the site activation status
				$license_data['license_status'] = Definitions::SITE_INACTIVE;
			}
			else {
				// Here "license status" is actually the site activation status
				if(isset($response['site_status'])) {
					$license_data['license_status'] =  $response['site_status'];
				}

				$license_error_message = '';
			}
		}

		// Update the license data
		$this->save_license_data($plugin_slug, $license_data);

		// We need to pass the "site status" as part of the response, as that's what
		// the UI logic expects
		$license_data['site_status'] = $license_data['license_status'];
		// The "license_status" is redundant, we can remove it to save bandwidth
		unset($license_data['license_status']);

		$result = array(
			'response' => array(
				'license' => $license_data,
				'message' => $license_error_message,
			),
		);

		// TODO Translate $result['date_expiration'] to user's locale and add to the response
		// TODO Translate $result['license_status'] to a human friendly message

		wp_send_json($result);
		exit;
	}

	/**
	 * Handles the error returned by an Ajax request.
	 *
	 * @param array action_response
	 * @return array An array with an error code and a message describing the
	 * error.
	 * @since 1.9.13.180123
	 */
	protected function handle_ajax_request_error($action_response) {
		// Extract the error details from a WP_Error object
		if(is_wp_error($action_response['result'])) {
			$wp_error = $action_response['result'];

			$action_response['response']['result'] = $wp_error->get_error_code();
			$action_response['response']['message'] = implode(' ', array(
				sprintf(__('Response code: %1$s.', self::$text_domain),
								$wp_error->get_error_code()),
				$wp_error->get_error_message(),
				sprintf(__('Should you need assistance, please feel free to <a href="%1$s" target="_blank">contact the Aelia Support Team</a>.', self::$text_domain), Definitions::URL_SUPPORT),
			));
		}
		else {
			$this->logger->notice(__('Unexpected response returned by Ajax request.', self::$text_domain), array(
				'Response' => $action_response,
			));

			// Handle unexpected error formats
			$action_response['response']['message'] = implode(' ', array(
				__('Unexpected result returned.', self::$text_domain),
				sprintf(__('Please <a href="%1$s" target="_blank">report the issue to the Aelia Support Team</a>.', self::$text_domain),
								Definitions::URL_SUPPORT),
				__('Thanks.', self::$text_domain),
			));
		}
		return $action_response;
	}

	/**
	 * Validates the license for a plugin.
	 *
	 * @param string plugin_slug The slug of plugin whose license will be validate.
	 */
	protected function set_site_activation_status($action, $plugin_slug, $license_key) {
		$plugin = $this->get_plugin_by_slug($plugin_slug);
		if(is_wp_error($plugin)) {
			return array(
				'response' => array(
					'result' => $plugin->get_error_code(),
					'message' => $plugin->get_error_message(),
				),
			);
		}

		// If there is no license set for the plugin, return a "non valid" status
		if(empty($license_key)) {
			return array(
				'response' => array(
					'result' => Definitions::ERR_PRODUCT_LICENSE_NOT_SET,
					'message' => __('Site activation/deactivation - License key missing', self::$text_domain),
				),
			);
		}

		$request_args = array(
			Definitions::ARG_LICENSE_KEY => $license_key,
			Definitions::ARG_SITE_URL => self::get_installation_instance_id(),
			Definitions::ARG_PRODUCT_SLUG => self::get_plugin_slug_for_update_check($plugin),
			Definitions::ARG_SITE_NAME => get_bloginfo('name'),
			Definitions::ARG_SITE_DESCRIPTION => get_bloginfo('description'),
		);

		$action_result = $this->remote_api_request($action, $request_args, $response);

		return array(
			'result' => $action_result,
			'response' => $response,
		);
	}

}
