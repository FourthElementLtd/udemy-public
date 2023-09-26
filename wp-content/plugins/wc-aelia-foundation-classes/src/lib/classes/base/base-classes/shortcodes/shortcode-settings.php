<?php
namespace Aelia\WC\Shortcodes;

if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\Base_Data_Object;

/**
 * Describes the base shortcode settings
 *
 * @since 2.1.9.210525
 */
class Shortcode_Settings extends Base_Data_Object {
	protected $debug_mode;
}