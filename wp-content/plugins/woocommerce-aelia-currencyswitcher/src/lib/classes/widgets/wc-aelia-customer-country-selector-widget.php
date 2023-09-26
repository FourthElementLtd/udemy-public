<?php
namespace Aelia\WC\CurrencySwitcher;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Displays the customer country selector widget.
 */
class WC_Aelia_Customer_Country_Selector_Widget extends \WP_Widget {
	protected $text_domain;

	// Widget types
	const TYPE_DROPDOWN = 'dropdown';
	const TYPE_BUTTONS = 'buttons';
	// @since 4.12.2.210706
	const TYPE_DROPDOWN_FLAGS = 'dropdown_flags';

	protected function WC() {
		global $woocommerce;
		return $woocommerce;
	}

	/**
	 * Returns a list of the available widget types and their attributes.
	 *
	 * @return array
	 */
	protected function widget_types(): array {
		$widget_types = array(
			self::TYPE_DROPDOWN => array(
				'name' => __('Dropdown', $this->text_domain),
				'template' => 'customer-country-selector-widget-dropdown',
				'title' => __('Displays a dropdown with all enabled countries', $this->text_domain),
			),
			// Dropdown widget with flags
			// @since 4.12.2.210706
			self::TYPE_DROPDOWN_FLAGS => array(
				'name' => __('Dropdown with flags', Definitions::TEXT_DOMAIN),
				'template' => 'customer-country-selector-widget-dropdown-flags',
				'title' => __('Displays a dropdown with all the enabled countries, with a flag next to each country', Definitions::TEXT_DOMAIN),
			),
		);

		$widget_types = apply_filters('wc_aelia_cs_customer_country_selector_widget_types', $widget_types);
		return $widget_types;
	}

	/**
	 * Retrieves the template that will be used to render the widget.
	 *
	 * @param string template_type The template type.
	 * @return string
	 */
	protected function get_widget_template($template_type) {
		$widget_types = $this->widget_types();
		$type_info = $widget_types[$template_type] ?? false;
		// If an invalid type is passed, default to a dropdown widget
		if(empty($type_info)) {
			$type_info = $this->widget_types()[self::TYPE_DROPDOWN];
		}

		return $type_info['template'];
	}

	/**
	 *	Class constructor.
	 */
	public function __construct() {
		$this->text_domain = WC_Aelia_CurrencySwitcher::$text_domain;

		parent::__construct(
			'wc_aelia_cs_customer_country_selector_widget',
			'WooCommerce Currency Switcher - Customer Country Selector',
			array('description' => __('Allow to switch country on the fly and ' .
																'set the active currency accordingly.', $this->text_domain),)
		);
	}

	/**
	 * Loads the CSS files required by the Widget.
	 */
	private function load_css() {
	}

	/**
	 * Loads the JavaScript files required by the Widget.
	 */
	private function load_js() {
		// Register and enqueue the CSS used by the widget, if it hasn't been loaded already.
		// This improves compatibility with the new widget editor introduced in WordPress 5.8,
		// which tries to render a preview of the widget in the backend, where the main plugin
		// doesn't load the styles
		// @since 4.12.3.210711
		// @link https://make.wordpress.org/core/2021/06/29/block-based-widgets-editor-in-wordpress-5-8/
		if(!wp_style_is('wc-aelia-cs-frontend', 'registered')) {
			wp_register_style('wc-aelia-cs-frontend',
			WC_Aelia_CurrencySwitcher::instance()->url('plugin') . '/design/css/frontend.css',
			array(),
			WC_Aelia_CurrencySwitcher::$version,
			'all');
		}
		if(!wp_style_is('wc-aelia-cs-frontend', 'enqueued')) {
			wp_enqueue_style('wc-aelia-cs-frontend');
		}
	}

	/**
	 * Front-end display of widget.
	 *
	 * @param array $widget_args Widget arguments.
	 * @param array $instance Saved values from database.
	 * @see WP_Widget::widget()
	 */
	public function widget($widget_args, $instance = array()) {
		$this->load_css();
		$this->load_js();

		if(!is_array($widget_args)) {
			$widget_args = array();
		}

		$widget_args = array_merge(
			$instance,
			$widget_args
		);

		$widget_type = $widget_args['widget_type'] ?? self::TYPE_DROPDOWN;
		$widget_template_name = $this->get_widget_template($widget_type);
		$widget_template_file = WC_Aelia_CurrencySwitcher::instance()->get_template_file($widget_template_name);

		if(empty($widget_template_file)) {
			$this->display_invalid_widget_type_error($widget_type);
		}
		else {
			$widget_args['title'] = apply_filters('wc_aelia_cs_customer_country_selector_widget_title', get_value('title', $widget_args));
			$widget_args['countries'] = apply_filters('wc_aelia_cs_customer_country_selector_widget_countries', WC()->countries->get_allowed_countries());

			// Ensure that the selected country is set
			if(empty($widget_args['selected_country'])) {
				$widget_args['selected_country'] = WC_Aelia_CurrencySwitcher::instance()->get_customer_country();
			}

			// If the selected country is not in the list of allowed countries, select the first one from the list of
			// allowed countries. This will ensure that the visitor will always see one of the allowed countries, and
			// that the country flag will be rendered correctly
			//
			// @since 4.13.11.220615
			// @link https://bitbucket.org/businessdad/woocommerce-currency-switcher/issues/18
			if(!isset($widget_args['countries'][$widget_args['selected_country']])) {
				reset($widget_args['countries']);
				$widget_args['selected_country'] = key($widget_args['countries']);
			}

			// Display the Widget
			include $widget_template_file;
		}
	}

	/**
	 * If an invalid widget type has been set, display an error so that the site
	 * owner is aware of it.
	 *
	 * @param string widget_type The invalid widget type.
	 */
	protected function display_invalid_widget_type_error($widget_type) {
		?>
		<div class="error">
			<h5 class="title"><?= esc_html__('Error', $this->text_domain) ?></h5>
			<?php
				echo sprintf(wp_kses_post('The country selector widget has not been configured properly. A template for ' .
																	'wiget type "%s" could not be found. Please review the widget settings and ensure ' .
																	'that a valid widget type has been selected. If the issue persists, please ' .
																	'<a href="https://aelia.freshdesk.com/helpdesk/tickets/new" title="Contact support">' .
																	'contact support</a>.', $this->text_domain), $widget_type);
			?>
		</div>
		<?php
	}

	/**
	 * Renders Widget's form in Admin section.
	 *
	 * @param array instance Widgets settings passed when submitting the form.
	 */
 	public function form($instance) {
		$title_field_id = $this->get_field_id('title');
		$title_field_name = $this->get_field_name('title');
		?>
		<p>
			<label for="<?= $title_field_id ?>"><?= esc_html__('Title', $this->text_domain) ?></label>
			<input type="text" class="widefat" id="<?= esc_attr($title_field_id) ?>" name="<?= $title_field_name ?>" value="<?= esc_attr(get_value('title', $instance, '')) ?>" />
		</p>
		<?php

		$widget_type_field_id = $this->get_field_id('widget_type');
		$widget_type_field_name = $this->get_field_name('widget_type');
		?>
		<p>
			<label for="<?= $widget_type_field_id ?>"><?= esc_html__('Widget type', $this->text_domain) ?></label>
			<select class="widefat" id="<?= esc_attr($widget_type_field_id) ?>" name="<?= $widget_type_field_name ?>">
				<?php foreach($this->widget_types() as $type_id => $type_info): ?>
					<option value="<?= $type_id ?>" title="<?= $type_info['title'] ?>" <?= selected($instance['widget_type'] ?? self::TYPE_DROPDOWN, $type_id) ?>><?=
						$type_info['name'];
					?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 * @return array Updated safe values to be saved.
	 * @see WP_Widget::update()
	 */
	public function update($new_instance, $old_instance) {
		$instance = array();
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		$instance['widget_type'] = strip_tags(stripslashes($new_instance['widget_type']));

		return $instance;
	}

	/**
	 * Renders the country selector widget when invoked using a shortcode.
	 *
	 * @param array widget_args An array of arguments for the widget.
	 * @return string
	 */
	public static function render_customer_country_selector($widget_args) {
		ob_start();

		$class = get_called_class();
		$widget_instance = new $class();
		$widget_instance->widget($widget_args);

		$output = ob_get_contents();
		@ob_end_clean();

		return $output;
	}
}