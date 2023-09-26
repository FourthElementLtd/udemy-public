<?php
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

require_once('aelia-wc-requirementscheck.php');

/**
 * Checks that plugin's requirements are met.
 */
class Aelia_WC_ShippingPricing_RequirementsChecks extends Aelia_WC_RequirementsChecks {
	// @var string The namespace for the messages displayed by the class.
	protected $text_domain = 'wc-aelia-cs-shippingpricing';
	// @var string The plugin for which the requirements are being checked. Change it in descendant classes.
	protected $plugin_name = 'Aelia Shipping Pricing for Currency Switcher';

	/**
	 * The minimum version of PHP required by the plugin.
	 *
	 * @var string
	 * @since 1.4.0.200225
	 */
	protected $required_php_version = '7.1';

	// @var array An array of WordPress plugins (name => version) required by the plugin.
	protected $required_plugins = array(
		'WooCommerce' => '3.0',
		'Aelia Foundation Classes for WooCommerce' => array(
			'version' => '2.1.8.210518',
			'extra_info' => 'You can <a href="https://bit.ly/WC_AFC_S3">get the plugin from our site</a>, free of charge.',
			'autoload' => true,
			'url' => 'https://bit.ly/WC_AFC_S3',
		),
		'Aelia Currency Switcher for WooCommerce' => array(
			'version' => '4.7.0.190307',
			'extra_info' => 'You can buy the plugin <a href="https://aelia.co/shop/currency-switcher-woocommerce/">from our shop</a>. If you already bought the plugin, please make sure that you download the latest version, using the link you received with your order.',
		),
	);

	/**
	 * Factory method. It MUST be copied to every descendant class, as it has to
	 * be compatible with PHP 5.2 and earlier, so that the class can be instantiated
	 * in any case and and gracefully tell the user if PHP version is insufficient.
	 *
	 * @return Aelia_WC_AFC_RequirementsChecks
	 */
	public static function factory() {
		return new static();
	}

	// /**
	//  * Performs requirement checks
	//  *
	//  * @return bool
	//  * @since 1.4.0.210517
	//  */
	// public function check_requirements() {
	// 	$result = parent::check_requirements();

	// 	if($result) {
	// 		// If the requirements are met, check the Currency Switcher version. Starting from version 4.11.0.210517,
	// 		// the Currency Switcher includes the Shipping Pricing plugin as an embedded addon, therefore the
	// 		// plugin should no longer be installed and loaded separately
	// 		// @since 1.4.0.210517
	// 		$currency_switcher_info = $this->get_wp_plugin_info('Aelia Currency Switcher for WooCommerce');

	// 		// If the Currency Switcher version is 4.11.0.210517 or higher, prevent the Shipping Pricing addon from
	// 		// loading and show the notice to indicate that the Currency Switcher includes it out of the box
	// 		if(version_compare($currency_switcher_info['version'], '4.11.0.210517', '>=')) {
	// 			$result = false;
	// 			add_action('admin_notices', array($this, 'plugin_retired_notice'));
	// 		}
	// 	}
	// 	return $result;
	// }

	/**
	 * Displays a "plugin retired" notice, to inform administrators that the plugin is now
	 * embedded in the Aelia Currency Switcher and no longer has to be installed separately.
	 *
	 * @Since 1.4.0.210517
	 */
	public function plugin_retired_notice(): void {
		?>
		<div class="wc_aelia message error fade">
			<h3 class="wc_aeliamessage_header" style="margin: 1em 0 0 0"><?php
				echo wp_kses_post(sprintf(__('The plugin "%s" is now included with the Aelia Currency Switcher', $this->text_domain), $this->plugin_name));
			?></h3>
			<p class="info"><?php
				echo wp_kses_post(implode(' ', array(
					__('This plugin is now included in the Aelia Currency Switcher and no longer has to be installed separately.', $this->text_domain),
					sprintf(__('You can <a href="%2$s" target="_blank">go to the Plugins page</a>, then disable and remove plugin "%1$s".', $this->text_domain), $this->plugin_name, admin_url('/plugins.php', true)),
					__('Its features will remain available, as they were before.', $this->text_domain),
				)));
			?></p>
		</div>
		<?php
	}
}
