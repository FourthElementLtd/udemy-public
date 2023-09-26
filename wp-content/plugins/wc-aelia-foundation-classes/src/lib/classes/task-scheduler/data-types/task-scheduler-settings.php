<?php
namespace Aelia\WC\AFC\Scheduler;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\Base_Data_Object;
use Aelia\WC\Definitions;
use Aelia\WC\AFC\Scheduler\Queues\Queue_Settings;

/**
 * Describes the settings to be used by a scheduled task.
 *
 * @since 2.4.9.230616
 */
class Task_Scheduler_Settings extends Base_Data_Object {
	/**
	 * Indicates if the debug mode is enabled.
	 *
	 * @var boolean
	 */
	protected $debug_mode = false;

	/**
	 * The queue handler to be used by the task scheduler. If not specified, the default
	 * queue used by WooCommerce will be used automatically.
	 *
	 * @var Queues\Queue_Interface
	 */
	protected $queue = null;

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

	/**
	 * Returns the queue handler to be used by the task. If a queue wasn't included with
	 * this settings class, the method will automatically return an instance of the Action
	 * Scheduler queue, passing to it the same settings that were used for the Task Scheduler.
	 *
	 * @return Queues\Base_Queue
	 */
	protected function get_queue(): Queues\Base_Queue {
		return is_object($this->queue) ? $this->queue : Queues\Action_Scheduler_Queue::instance(new Queue_Settings([
			'debug_mode' => $this->debug_mode,
			'logger_instance' => $this->logger_instance,
		]));
	}

	/**
	 * Sets the queue handler to be used by the task scheduler. This method was
	 * implemented to enforce the type of the $queue attribute.
	 *
	 * @param Queues\Queue_Interface $queue
	 * @return void
	 */
	protected function set_queue($queue): void {
		if(!empty($queue) && (!$queue instanceof Queues\Base_Queue)) {
			throw new \InvalidArgumentException(__('The "queue" attribute must be a descendant of "Aelia\WC\AFC\Scheduler\Queues\Base_Queue".', Definitions::TEXT_DOMAIN));
		}
		$this->queue = $queue;
	}
}

