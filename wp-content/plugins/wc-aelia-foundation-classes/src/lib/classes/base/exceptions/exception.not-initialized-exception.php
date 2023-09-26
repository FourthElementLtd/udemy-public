<?php
namespace Aelia\WC\Exceptions;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\Definitions;
/**
 * Exception to be raised when a variable or a class instance is accessed
 * before having been initialized.
 */
class NotInitializedException extends \Exception {
	/**
	 * Constructor.
	 *
	 * @param string $message
	 * @param integer $code
	 * @param Exception $previous
	 * @since 2.2.0.211117
	 */
	public function __construct($message = null, $code = Definitions::ERR_NOT_IMPLEMENTED, \Exception $previous = null) {
		$message = $message ?: __('Variable or class instance not initialized.', Definitions::TEXT_DOMAIN);
		parent::__construct($message, $code, $previous);
	}
}
