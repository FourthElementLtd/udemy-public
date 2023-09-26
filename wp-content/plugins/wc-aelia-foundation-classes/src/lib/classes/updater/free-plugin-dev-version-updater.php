<?php
namespace Aelia\WC;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use \Exception;

/**
 * Handles updates for the development versions of free plugins.
 *
 * @since 1.9.15.180210
 */
class Free_Plugin_Dev_Version_Updater extends Free_Plugin_Updater {
	/**
	 * The ID of the updater class.
	 *
	 * @var string
	 * @since 1.8.3.170110
	 */
	public static $id = 'aelia-free-plugin-dev-version-updater';

	/**
	 * Returns the URL to call the update server.
	 *
	 * @param array args An array of arguments that will be used to build the URL.
	 * @return string
	 */
	protected function get_api_call_url(array $args) {
		return 'https://wpupdate-dev.aelia.co?action=get_metadata&slug=' . $args['slug'];
	}
}
