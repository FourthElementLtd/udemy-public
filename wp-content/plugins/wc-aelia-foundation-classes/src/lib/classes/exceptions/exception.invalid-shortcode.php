<?php
namespace Aelia\WC\Exceptions;

use Aelia\WC\Definitions;

if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

/**
 * Exception raised when a shortcode's rendering function has not been
 * implemented correctly.
 *
 * @since 2.1.8.210518
 */
class Invalid_Shortcode_Exception extends \Exception {
	/**
	 * Constructor.
	 *
	 * @param string $message
	 * @param integer $code
	 * @param Exception $previous
	 */
	public function __construct($message, $code = Definitions::ERR_SHORTCODE_NOT_VALID, \Exception $previous = null) {
		$message = $message ?: __('Shortcode not implemented.', Definitions::TEXT_DOMAIN);
		parent::__construct($message, $code, $previous);
	}
}
