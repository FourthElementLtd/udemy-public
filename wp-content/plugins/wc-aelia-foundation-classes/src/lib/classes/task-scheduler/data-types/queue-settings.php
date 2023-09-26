<?php
namespace Aelia\WC\AFC\Scheduler\Queues;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\Base_Data_Object;
use Aelia\WC\Definitions;

/**
 * Describes the settings to be used by a sheduler queue, such as the
 * Action Scheduler.
 *
 * @since 2.4.9.230616
 */
class Queue_Settings extends Base_Data_Object {
	/**
	 * Indicates if the debug mode is enabled.
	 *
	 * @var boolean
	 */
	protected $debug_mode = false;

	/**
	 * The logger instance that the queue should use.
	 *
	 * IMPORTANT
	 * This property can't be called "logger", because the Base_Data_Object
	 * class already has such a property, as it includes the Logger_Trait
	 * class for its internal logging. Two loggers, for two different purposes.
	 *
	 * @var Aelia\WC\Logger
	 */
	protected $logger_instance;
}

