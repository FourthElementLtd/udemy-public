<?php
namespace Aelia\WC;

/**
 * Manages notices and messages related to upgrades.
 */

if(!defined('ABSPATH')) {	exit; }

/**
 * Handles the messages that can be displayed when a plugin update is available,
 * such as upgrade notices.
 *
 * @ since 2.1.1.201208
 */
class Aelia_Upgrade_Handler {
	/**
	 * The upgrade notice shown inline.
	 *
	 * @var string
	 */
	protected $upgrade_notice = '';

	/**
	 * The instance of this class.
	 *
	 * @var Aelia\WC\Aelia_Upgrade_Handler
	 */
	protected static $_instances = array();

	/**
	 * The instance of the plugin for which the upgrade is being handled.
	 *
	 * @var \Aelia\WC\Plugin
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @param Aelia\WC\Aelia_Plugin $plugin
	 */
	public static function instance(\Aelia\WC\Aelia_Plugin $plugin) {
		return self::$_instances[$plugin->get_slug()] ?? self::$_instances[$plugin->get_slug()] = new static($plugin);
	}

	/**
	 * Constructor.
	 *
	 * @param Aelia\WC\Aelia_Plugin $plugin
	 */
	public function __construct(\Aelia\WC\Aelia_Plugin $plugin) {
		$this->plugin = $plugin;
		$plugin_file = basename(dirname($plugin->main_plugin_file)) . '/' . basename($plugin->main_plugin_file);

		add_action('in_plugin_update_message-' . $plugin_file, array($this, 'in_plugin_update_message'), 10, 2);
	}

	/**
	 * Show plugin changes on the plugins screen.
	 *
	 * @param array $args Unused parameter.
	 * @param stdClass $response Plugin update response.
	 */
	public function in_plugin_update_message($args, $response) {
		$plugin_class = get_class($this->plugin);
		$current_version = $plugin_class::$version;

		if(version_compare($response->new_version, $current_version, '>=')) {
			$readme_file = dirname($this->plugin->main_plugin_file) . '/readme.txt';

			if(file_exists($readme_file)) {
				$this->upgrade_notice = $this->get_upgrade_notice($plugin_class::$plugin_slug, $current_version, $response->new_version, $readme_file);
			}
		}

		echo apply_filters('aelia_upgrade_handler_update_message', $this->upgrade_notice ? '</p>' . wp_kses_post($this->upgrade_notice) . '<p class="aelia-dummy">': '');
	}

	/**
	 * Get the upgrade notice from WordPress.org.
	 *
	 * @param string $plugin_slug
	 * @param string $version
	 * @return string
	 */
	protected function get_upgrade_notice(string $plugin_slug, string $current_version, string $new_version, string $readme_file) {
		$transient_name = "aelia_upgrade_handler_{$plugin_slug}_{$new_version}";
		$upgrade_notice = get_transient($transient_name); //NOSONAR

		if(($upgrade_notice === false) && file_exists($readme_file)) {
			$upgrade_notice = $this->parse_update_notice(file_get_contents($readme_file), $current_version, $new_version);
			set_transient($transient_name, $upgrade_notice, DAY_IN_SECONDS);
		}
		return $upgrade_notice;
	}

	/**
	 * Parse update notice from readme file.
	 *
	 * @param  string $content WooCommerce readme file content.
	 * @param  string $new_version WooCommerce new version.
	 * @return string
	 */
	private function parse_update_notice($content, $current_version, $new_version) {
		$version_parts = explode('.', $new_version);
		$check_for_notices = array(
			$version_parts[0] . '.0', // Major.
			$version_parts[0] . '.0.0', // Major.
			$version_parts[0] . '.' . $version_parts[1], // Minor.
			$version_parts[0] . '.' . $version_parts[1] . '.' . $version_parts[2], // Patch.
			$new_version,
		);
		$notice_regexp = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote($new_version) . '\s*=|$)~Uis';
		$upgrade_notice = ''; //NOSONAR

		foreach($check_for_notices as $check_version) {
			if(version_compare($current_version, $check_version, '>')) {
				continue;
			}

			$matches = null;
			if(preg_match($notice_regexp, $content, $matches)) {
				$notices = (array)preg_split('~[\r\n]+~', trim($matches[2]));

				if(version_compare(trim($matches[1]), $check_version, '=')) {
					$upgrade_notice .= '<p class="aelia_plugin_upgrade_notice">';
					$upgrade_notice .= '<span class="title">' . __('Upgrade notice', Definitions::TEXT_DOMAIN) . '</span>';

					foreach($notices as $index => $line) {
						// Stop as soon as a new entry is found
						if(strpos($line, '=') === 0) {
							break;
						}
						$upgrade_notice .= preg_replace('~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}">${1}</a>', $line);
					}

					$upgrade_notice .= '</p>';
				}
				break;
			}
		}
		return wp_kses_post($upgrade_notice);
	}
}
