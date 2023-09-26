<?php
namespace Aelia\WC\Shortcodes;

use Aelia\WC\AFC\Traits\Logger_Trait;
use Aelia\WC\Definitions;
use Aelia\WC\Exceptions\Invalid_Shortcode_Exception;

if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

/**
 * Base shortcode class.
 *
 * @since 2.1.8.210518
 */
abstract class Base_Shortcode {
	use Logger_Trait;

	/**
	 * Keeps track of the instances of the registered shortcodes. Each shortcode must
	 * be registered only once.
	 *
	 * @var array
	 */
	private static $_instances = [];

	/**
	 * The shortcode ID, which will be passed to the add_shortcode() function.
	 *
	 * @var string
	 */
	protected static $shortcode = __NAMESPACE__ . '_base_shortcode';

	/**
	 * The shortcode settings;
	 *
	 * @var Aelia\WC\Shortcodes\Shortcode_Settings
	 */
	protected $settings;

	/**
	 * Returns the default arguments expected by the shortcode.
	 *
	 * @return array
	 */
	protected function get_default_shortcode_args(): array {
		return [];
	}

	/**
	 * Given a set of arguments, returns the arguments to be used by the shortcode,
	 * merged with the default ones.
	 *
	 * @param array|string $args The arguments to be used by the shortcode. This can also be a string,
	 * as that's what do_shortcode() passes to the callback when no arguments are used.
	 * @return array
	 */
	protected function get_shortcode_args($args): array {
		$args = is_array($args) ? $args : [];

		return array_merge($this->get_default_shortcode_args(), $args);
	}

	/**
	 * Class constructor.
	 *
	 * @param Shortcode_Settings $settings
	 * @return void
	 */
	public function __construct(Shortcode_Settings $settings) {
		$this->settings = $settings;

		// Register the shortcode to
		add_shortcode(static::$shortcode, [$this, 'render']);
	}

	/**
	 * Initialises the shortcode class, registering the shortcode for later use.
	 *
	 * @return Aelia\WC\Shortcodes\Base_Shortcode
	 */
	public static function init(Shortcode_Settings $settings): Base_Shortcode {
		if(isset(self::$_instances[static::$shortcode])) {
			throw new Invalid_Shortcode_Exception(sprintf(
				__('Shortcode "%1$s" already registered by class "%2$s".', Definitions::TEXT_DOMAIN),
				static::$shortcode,
				get_class(self::$_instances[static::$shortcode])
			));
		}

		return self::$_instances[static::$shortcode] = new static($settings);
	}

	/**
	 * Renders the shortcode. This method must be implemented by descendant classes.
	 *
	 * @param array $args
	 * @return string
	 */
	public abstract function render($args): string;
}