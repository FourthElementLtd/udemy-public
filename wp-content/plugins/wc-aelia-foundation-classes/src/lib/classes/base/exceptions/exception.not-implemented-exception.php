<?php
namespace Aelia\WC\Exceptions;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\Definitions;
/**
 * Exception to be raised when a method or function that has not been implemented is called.
 */
class NotImplementedException extends \Exception {
	/**
	 * Constructor.
	 *
	 * @param string $message
	 * @param integer $code
	 * @param Exception $previous
	 * @since 2.1.19.211020
	 */
	public function __construct($message = null, $code = Definitions::ERR_NOT_IMPLEMENTED, \Exception $previous = null) {
		$message = $message ?: __('Method or function not implemented.', Definitions::TEXT_DOMAIN);
		parent::__construct($message, $code, $previous);
	}
}
