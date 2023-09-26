<?php
namespace Aelia\WC\AFC\Scheduler\Tasks;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\Base_Data_Object;

/**
 * Describes the settings to be used by a scheduled task.
 *
 * @since 2.4.9.230616
 */
class Task_Instance_Settings extends Base_Data_Object {
	/**
	 * Indicates if the debug mode is enabled.
	 *
	 * @var boolean
	 */
	protected $debug_mode = false;

	/**
	 * The number of times the task can be retried, if it fails.
	 *
	 * @var int
	 */
	protected $max_retries = 0;

	/**
	 * Tracks the number of times that a failed task has been retried.
	 *
	 * @var int
	 */
	protected $retry_count = 0;

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