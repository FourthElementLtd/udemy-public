<?php
namespace Aelia\WC\AFC\Scheduler\Tasks;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\Base_Data_Object;

/**
 * Describes the result generated by a scheduled task.
 *
 * @since 2.4.9.230616
 */
class Task_Result extends Base_Data_Object {
	/**
	 * The result code returned by the task.
	 *
	 * @var int
	 */
	protected $code;

	/**
	 * Stores a copy of the settings that can be used to schedule another
	 * instance of the task, e.g. to process another chunk of data.
	 *
	 * @var Scheduled_Task_Settings
	 */
	protected $settings;
}

