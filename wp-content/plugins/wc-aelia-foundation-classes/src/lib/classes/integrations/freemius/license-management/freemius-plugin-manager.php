<?php
namespace Aelia\WC\Integrations\Freemius;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\Definitions;
use Aelia\WC\Updater;

/**
 * Acts as a bridge between the Aelia Updater class and the Freemius integration, to display
 * elements such as the account pages and contact forms in a single location.
 *
 * @since 2.1.9.210525
 */
class Freemius_Plugins_Manager extends Updater {
	/**
	 * The ID of this class.
	 *
	 * @var string
	 */
	public static $id = 'aelia-freemius-plugin-manager';

	/**
	 * The name of this class. It's called "updater_name" because the parent class
	 * was designed to manage plugin updates as well.
	 */
	public static $updater_name = 'Freemius Licences';

	const URL_USER_DASHBOARD = 'https://aelia.co/my-account';
	const URL_LICENSES_HOW_TO = 'https://aelia.freshdesk.com/solution/articles/3000071127-managing-your-aelia-licences';

	/**
	 * Sets the hooks required by the class.
	 */
	protected function set_hooks() {
		parent::set_hooks();

		// If user can update plugins, show the data about updates and licenses
		if(current_user_can('update_plugins')) {
			// Add callback to render the license field for each plugin
			add_action('woocommerce_admin_field_'  . self::$id . '_license', array($this, 'woocommerce_admin_field_freemius_license'), 10, 1);

			// Add scripts and styles for the Freemius page
			add_action('wc_aelia_afc_load_admin_scripts', array($this, 'wc_aelia_afc_load_admin_scripts'), 10);
			add_filter('wc_aelia_afc_admin_script_params', array($this, 'wc_aelia_afc_admin_script_params'), 10);
		}
	}

	/**
	 * Loads the licenses handled by this class.
	 *
	 * @return array
	 */
	protected function load_product_licenses() {
		return Freemius_Plugin_Integration::get_registered_plugins();
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
				'name' => __('Aelia Freemius plugins', self::$text_domain),
				'type' => 'title',
				'desc' => implode(' ', array(
					'<div class="' . static::$id . ' section_description">',
					__('Here you can manage the licences for your Aelia products distributed via the Freemius platform.', self::$text_domain),
					__('By activating a licence, you will get access to the updates for the products you purchased.', self::$text_domain),
					'<h4 class="help_title">',
					__('Where to find the licence keys', self::$text_domain),
					'</h4>',
					__('You can find your licence keys on the order confirmation email you received when you completed your order.', self::$text_domain),
					sprintf(__('You can also access your keys by going to <a href="%1$s/orders" target="_blank">Aelia > My Account > Orders</a>.', self::$text_domain),
									self::URL_USER_DASHBOARD),
					__('Open the order details page from the list, and you will be able to access the licence keys.', self::$text_domain),
					'<br />',
					sprintf(__('For more information please refer to our documentation: <a href="%s" target="_blank">Aelia - How to manage the licences for your premium plugins</a>.', self::$text_domain),
									self::URL_LICENSES_HOW_TO),
					'</div>',
				)),
			),
		);

		// Prepare the field for each of the plugins whose licence should be managed
		foreach(Freemius_Plugin_Integration::get_registered_plugins() as $plugin_slug => $fs_integration) {
			$field_id = $plugin_slug . '-license';
			$fields[$field_id] = array(
				'id' => $field_id,
				'type' => self::$id . '_license',
				'data' => $fs_integration,
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
		$fs_integration = Freemius_Plugin_Integration::get_freemius_integration($plugin_slug);

		return !empty($fs_integration) ? $fs_integration->get_plugin_name() : sprintf(__('Plugin name not found. Slug: %s', self::$text_domain), $plugin_slug);
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
		$fs_integration = Freemius_Plugin_Integration::get_freemius_integration($plugin_slug);

		return !empty($fs_integration) ? $fs_integration->get_plugin_name() : sprintf(__('Plugin version not found. Slug: %s', self::$text_domain), $plugin_slug);
	}

	/**
	 * Renders a section to manage the license for a specific plugin.
	 *
	 * @param array field A field descriptor, with the license data for a plugin.
	 */
	public function woocommerce_admin_field_freemius_license($field) {
		$fs_integration = $field['data'];

		$license_key = __('<Not Entered>', self::$text_domain);
		$site = $fs_integration->get_site();
		$valid_license_available = !empty($site) && $fs_integration->has_features_enabled_license();

		if($valid_license_available) {
			$license_status = $site->is_active ? 'active' : 'inactive';
			$license = $fs_integration->_get_license();

			if(!empty($license->expiration)) {
				$license_expiration_obj = \DateTime::createFromFormat('Y-m-d H:i:s', $license->expiration);
				$license_expiration = $license_expiration_obj->format('d F Y');
			}
			else {
				$license_expiration = __('Lifetime', self::$text_domain);
			}

			if(!empty($license->secret_key)) {
				$license_key = substr($license->secret_key, 0, 6);
				$license_key .= str_repeat('*', strlen($license->secret_key) - 9);
				$license_key .= substr($license->secret_key, -3, 3);
			}
		}
		else {
			$license_status = 'not-entered';
		}

		$plugin_slug = $fs_integration->get_premium_slug();
		$plugin_name = $fs_integration->get_plugin_name();
		$plugin_version = $fs_integration->get_plugin_version();
		?>
		<div id="<?= $plugin_slug ?>-license-section" class="<?= static::$id ?> license <?= $license_status ?>">
			<div class="plugin_name section">
				<span class="title"><?php
					echo $plugin_name . ' (' . __('Installed version:', self::$text_domain) . ' ' . $plugin_version . ')';
				?></span>
			</div>
			<div class="license_key_section">
				<div class="license_key_wrapper">
					<span class="label"><?= __('Licence Key', self::$text_domain) ?>:</span>
					<span class="license_key"><?= esc_html($license_key) ?></span>
				</div>
			</div>
			<div class="license_data_section">
				<?php if($valid_license_available): ?>
					<div class="license_status_section">
						<span class="label"><?= __('Licence Status for this site', self::$text_domain); ?>:</span>
						<span class="license_status"><?= !empty($site->is_active) ? __('Activated', self::$text_domain) : __('Not activated', self::$text_domain); ?></span>
					</div>
					<div class="license_expiration_section">
						<span class="label"><?= __('Valid until', self::$text_domain); ?>:</span>
						<span class="license_expiration"><?= $license_expiration; ?></span>
					</div>
				<?php endif; ?>
			</div>
			<div class="actions">
				<?php if($valid_license_available): ?>
					<a class="action account button" href="<?= $fs_integration->get_account_url() ?>" target="_blank"><?= esc_html__('View account and licence details', self::$text_domain) ?></a>
					<a class="action contact button" href="<?= $fs_integration->contact_url() ?>" target="_blank"><?= esc_html__('Contact the Support Team', self::$text_domain) ?></a>
				<?php else: ?>
					<span class="description"><?php
						echo wp_kses_post(implode(' ', [
							__('It looks like this site is not covered by an active licence.', Definitions::TEXT_DOMAIN),
							sprintf(
								__('Please <a class="freemius_activation_link" href="%1$s">complete the activation</a> to receive updates and support for the product.', Definitions::TEXT_DOMAIN),
								$fs_integration->get_activation_url(array(), !$fs_integration->is_delegated_connection())
							),
						]));
					?></span>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Indicate if we are on the page to configure the licenses managed by this class.
	 *
	 * @return bool
	 */
	protected function managing_licenses(): bool {
		$active_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
		$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : '';
		$active_section = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : '';

		return ($active_page === 'wc-settings') &&
					 (($active_tab === Updater::$id) || ($active_tab === \Aelia\WC\AFC\Settings::$id && (empty($active_section) || ($active_section === static::$id))));
	}

	/**
	 * Loads the Admin JavaScript required by this class.
	 */
	public function wc_aelia_afc_load_admin_scripts(): void {
		// Prepare parameters for Freemius License Management page
		if(self::managing_licenses()) {
			// Load the JavaScript for the Freemius License Management section
			wp_enqueue_script(Definitions::PLUGIN_SLUG . '-admin-freemius-licenses',
												$this->AFC()->url('js') . '/admin/admin-manage-freemius-licenses.js',
												array('jquery', Definitions::PLUGIN_SLUG . '-admin-common'),
												null,
												true);
			// Load the styles for the Freemius License Management section
			wp_enqueue_style(Definitions::PLUGIN_SLUG . '-admin-freemius-licenses', $this->AFC()->url('css') . '/freemius-licenses-section.css');
		}
	}

	/**
	 * Adds custom admin parameters.
	 *
	 * @param array $params
	 * @return array
	 */
	public function wc_aelia_afc_admin_script_params($params) {
		$params['freemius_plugin_manager'] = array(
		);
		return $params;
	}

	/**
	 * UNUSED. Generates the URL to be used to query the updates server.
	 *
	 * @param array args An array of arguments to be passed to the update server.
	 * @return string The URL to call to check for updates.
	 */
	protected function get_api_call_url(array $args) {
		// Dummy
	}

	/**
	 * UNUSED. Checks for updates for the specified product. This method must be implemented
	 * by descendant classes.
	 *
	 * @param object product A product (plugin, theme) descriptor.
	 */
	protected function check_for_product_updates($product) {
		// Dummy
	}

	/**
	 * UNUSED. Saves the licences managed by this updater.
	 */
	public function save_licenses() {
		// Dummy
	}
}
