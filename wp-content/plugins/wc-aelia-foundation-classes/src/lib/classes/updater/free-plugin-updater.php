<?php
namespace Aelia\WC;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use \Exception;

/**
 * Handles updates for free plugins.
 *
 * @since 1.7.1.150824
 */
class Free_Plugin_Updater extends Updater {
	/**
	 * The ID of the updater class.
	 *
	 * @var string
	 * @since 1.8.3.170110
	 */
	public static $id = 'aelia-free-plugin-updater';

	public function __construct(array $products_to_update) {
		parent::__construct($products_to_update);
	}

	/**
	 * Returns the URL to call the update server.
	 *
	 * @param array args An array of arguments that will be used to build the URL.
	 * @return string
	 */
	protected function get_api_call_url(array $args) {
		return 'https://wpupdate.aelia.co?action=get_metadata&slug=' . $args['slug'];
	}

	/**
	 * Cheks for updates for a plugin.
	 *
	 * @param object plugin The instance of the plugin for which the updates will
	 * be checked.
	 */
	protected function check_for_product_updates($plugin) {
		$this->logger->debug(__('Checking for updates for free plugin', Definitions::TEXT_DOMAIN), array(
			'Plugin meta' => $plugin,
		));

		// If plugin_file property is not set, we can't check for updates, as such
		// information is required by the updater client
		if(trim($plugin->get_plugin_file()) == '') {
			return;
		}

		$plugin_class = get_class($plugin);
		// In case the plugin uses a different slug from the one registered in the
		// update server, it can set the "slug_for_update_check" property to indicate
		// it
		$slug_for_update_check = isset($plugin_class::$slug_for_update_check) ? $plugin_class::$slug_for_update_check : $plugin_class::$plugin_slug;

		try {
			$update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker( // NOSONAR
					$this->get_api_call_url(array(
						'slug' => $slug_for_update_check,
					)),
					$plugin->get_plugin_file(),
					$slug_for_update_check
			);
		}
		catch(\Exception $e) {
			$this->logger->error(__('Exception occurred while checking for plugin updates.', Definitions::TEXT_DOMAIN), array(
				'Plugin meta' => $plugin,
				'Plugin file' => $plugin->get_plugin_file(),
				'Plugin slug for update check' => $slug_for_update_check,
				'Exception' => $e->getMessage(),
			));
		}
	}

	/**
	 * Checks for updates for the registered products.
	 */
	public function check_for_updates() {
		require_once WC_AeliaFoundationClasses::instance()->path('vendor') . '/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';
		return parent::check_for_updates();
	}
}
