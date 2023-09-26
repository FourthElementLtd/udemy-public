<?php
namespace Aelia\WC\AFC\Scheduler;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\Base_Data_Object;
use Aelia\WC\Definitions;

/**
 * Describes the settings to be used by a scheduled task.
 *
 * @since 2.4.9.230616
 */
class Scheduled_Task_Settings extends Base_Data_Object {
	/**
	 * Indicates if the debug mode is enabled.
	 *
	 * @var boolean
	 */
	protected $debug_mode = false;

	/**
	 * The group to which the task belong.s
	 *
	 * @var string
	 */
	protected $group = Definitions::PLUGIN_SLUG;

	/**
	 * The ID of the task to schedule.
	 *
	 * @var string
	 */
	protected $task_id = '';

	/**
	 * Indicates the interval between each task run, in seconds. If set to zero,
	 * the task will run only once.
	 *
	 * @var int
	 * @see \WC_Action_Queue::schedule_recurring()
	 * @see \WC_Action_Queue::schedule_single()
	 */
	protected $interval = Definitions::DEFAULT_SCHEDULED_TASK_INTERVAL;

	/**
	 * Indicates if all the existing actions for the task should be reset
	 * before scheduling the task.
	 *
	 * @var boolean
	 */
	protected $reset_existing_schedule = false;

	/**
	 * The timestamp when the task should run the first time. If left empty,
	 * it's automatically set to the current timestamp, to indicate "now".
	 *
	 * @var int
	 */
	protected $start_timestamp = 0;

	/**
	 * Returns the timestamp when the task should run the first time.
	 *
	 * @return int
	 */
	protected function get_start_timestamp(): int {
		return is_numeric($this->start_timestamp) && ($this->start_timestamp > 0) ? $this->start_timestamp : time();
	}

	/**
	 * An array of arguments that will be passed to the scheduled task when its
	 * event is triggered.
	 *
	 * @var array
	 */
	protected $scheduled_event_args = [];

	/**
	 * Stores the list of arguments that will be passes to the scheduled event (action),
	 * ensuring that it's an array
	 *
	 * @param array $event_args
	 * @return void
	 */
	protected function set_scheduled_event_args($event_args): void {
		// Ensure that the arguments are returned as an array
		$this->scheduled_event_args = is_array($event_args) ? $event_args : [$event_args];
	}

	/**
	 * Returns the list of arguments that will be passes to the scheduled event (action).
	 *
	 * @return array
	 */
	protected function get_scheduled_event_args(): array {
		$event_args = $this->scheduled_event_args;
		if(!isset($event_args['debug_mode'])) {
			$event_args['debug_mode'] = $this->debug_mode;
		}

		return $event_args;
	}
}
