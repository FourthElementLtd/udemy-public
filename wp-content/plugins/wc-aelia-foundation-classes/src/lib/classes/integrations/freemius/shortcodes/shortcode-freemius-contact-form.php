<?php
namespace Aelia\WC\Freemius_Integration\Shortcodes;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\Definitions;
use Aelia\WC\Exceptions\Invalid_Shortcode_Argument_Exception;
use Aelia\WC\Integrations\Freemius\Freemius_Plugin_Integration;
use Aelia\WC\Shortcodes\Base_Shortcode;

/**
 * Renders the shortcode that displays the details of a product (plugin.)
 *
 * @since 2.1.9.210525
 */
class Freemius_Contact_Form_Shortcode extends Base_Shortcode {
	/**
	 * The shortcode ID, which will be passed to the add_shortcode() function.
	 *
	 * @var string
	 */
	protected static $shortcode = 'aelia_freemius_contact_form';

	/**
	 * Returns the default arguments expected by the shortcode.
	 *
	 * @return array
	 */
	protected function get_default_shortcode_args(): array {
		return [
			'hide_without_active_licence' => true,
		];
	}

	/**
	 * Renders the shortcode.
	 *
	 * @param array|string $args If the shortcode is called without arguments, the $args
	 * parameter is an empty string.
	 * @return string
	 */
	public function render($args): string {
		$args = $this->get_shortcode_args($args);

		try {
			// Ensure that the plugin slug is valid (not empty, and matching a valid integration)
			if(empty($args['plugin_slug'])) {
				// If something goes wrong, return the error message
				$message = implode(' ', [
					__('Freemius Contact Form Shortcode', Definitions::TEXT_DOMAIN),
					__('Argument "plugin_slug" is required.', Definitions::TEXT_DOMAIN),
				]);

				throw new Invalid_Shortcode_Argument_Exception($message);
			}

			$fs_integration = Freemius_Plugin_Integration::get_freemius_integration($args['plugin_slug']);
			if(!Freemius_Plugin_Integration::is_plugin_license_activated($args['plugin_slug']) && $args['hide_without_active_licence']) {
				// If something goes wrong, return the error message
				$message = implode(' ', [
					__('Freemius Contact Form Shortcode', Definitions::TEXT_DOMAIN),
					__('The specified "plugin_slug" does not match a registered Freemius plugin.', Definitions::TEXT_DOMAIN),
				]);
				$this->get_logger()->warning($message, [
					'Shortcode' => self::$shortcode,
					'Shortcode Arguments' => $args,
				]);

				throw new Invalid_Shortcode_Argument_Exception(implode(' ', [
					__('It looks like this site is not covered by an active licence.', Definitions::TEXT_DOMAIN),
					sprintf(
						__('Please <a class="freemius_activation_link" href="%1$s">complete the activation</a> to receive updates and support for the product.', Definitions::TEXT_DOMAIN),
						$fs_integration->get_activation_url(array(), !$fs_integration->is_delegated_connection())
					),
				]));
			}
		}
		catch(\Exception $e) {
			$this->get_logger()->warning($e->getMessage(), [
				'Shortcode' => self::$shortcode,
				'Shortcode Arguments' => $args,
			]);

			return '<div class="freemius_contact_form">' . wp_kses_post($e->getMessage()) . '</div>';
		}

		ob_start();
		?>
		<div class="freemius_contact_form"><?php
			\Freemius::_clean_admin_content_section();
			$fs_integration->_contact_page_render();
		?></div>
		<?php
		return (string)ob_get_clean();
	}
}