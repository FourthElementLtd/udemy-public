<?php
namespace Aelia\WC\Exceptions;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\Definitions;

/**
 * Exception raised when a shortcode's rendering function has not been
 * implemented correctly.
 *
 * @since 2.1.9.210525
 */
class Invalid_Shortcode_Argument_Exception extends \InvalidArgumentException {
	/**
	 * Constructor.
	 *
	 * @param string $message
	 * @param integer $code
	 * @param Exception $previous
	 */
	public function __construct($message, $code = Definitions::ERR_SHORTCODE_INVALID_ARGUMENTS, \Exception $previous = null) {
		$message = $message ?: __('Invalid shortcode argument.', Definitions::TEXT_DOMAIN);
		parent::__construct($message, $code, $previous);
	}
}
