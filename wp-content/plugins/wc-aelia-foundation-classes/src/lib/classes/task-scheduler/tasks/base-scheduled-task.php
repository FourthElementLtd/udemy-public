<?php
namespace Aelia\WC\AFC\Scheduler\Tasks;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\AFC\Traits\Logger_Trait;
use Aelia\WC\Definitions;
use Aelia\WC\WC_AeliaFoundationClasses;

/**
 * Base class for scheduled tasks.
 *
 * @since 2.4.9.230616
 */
abstract class Base_Scheduled_Task implements IScheduled_Task {
	use Logger_Trait;

	/**
	 * The prefix that will be used to generate the scheduled hooks.
	 *
	 * @var string
	 */
	const TASK_HOOK_PREFIX = '';

	/**
	 * The task ID.
	 *
	 * @var string
	 */
	protected static $id = 'base_scheduled_task';

	/**
	 * Stores the task settings.
	 *
	 * @var Scheduled_Task_Settings
	 */
	protected $settings;

	/**
	 * Returns the ID of the task.
	 *
	 * @return string
	 */
	public static function get_id(): string {
		return static::$id;
	}

	/**
	 * Returns the hook that will be triggered by this scheduled task.
	 *
	 * @return string
	 */
	public static function get_task_hook(): string {
		return static::TASK_HOOK_PREFIX . static::$id;
	}

	/**
	 * Constructor.
	 *
	 * @param Task_Instance_Settings $settings
	 */
	public function __construct(Task_Instance_Settings $settings) {
		$this->settings = $settings;

		// Initialise the logger, using the instance passed with the settings
		$this->set_logger($settings->logger_instance);
		self::$_debug_mode = $settings->debug_mode;
	}

	/**
	 * Initialises the event handlers required by the task.
	 *
	 * @return void
	 */
	public static function initialize_event_handlers(): void {
		add_action(static::get_task_hook(), [get_called_class(), 'task_event_callback']);
	}

	/**
	 * Given some settings passed via the scheduler hook, returns an instance of the settings
	 * that can be used to instantiate this class.
	 *
	 * @param array $args
	 * @return Task_Instance_Settings
	 */
	abstract protected static function get_task_settings_from_callback_args(array $args): Task_Instance_Settings;

	/**
	 * This method is used as the callback to the scheduled event.
	 *
	 * @param array $event_args The arguments that will be used to instantiate and run the task.
	 * @return void
	 */
	public static function task_event_callback($event_args = []): void {
		// Ensure that the task arguments are an array
		$event_args = is_array($event_args) ? $event_args : [$event_args];

		try {
			// Initialise the task
			$task = new static(static::get_task_settings_from_callback_args($event_args));

			$task->get_logger()->info(__('Running scheduled task.', Definitions::TEXT_DOMAIN), [
				'Task ID' => self::get_id(),
				'Task Callback Args' => $event_args,
			]);

			// Run the task
			$task_result = $task->run();

			$task->get_logger()->notice(__('Scheduled task completed.', Definitions::TEXT_DOMAIN), [
				'Task ID' => self::get_id(),
				'Task Callback Args' => $event_args,
				'Task Result' => $task_result,
			]);
		}
		catch(\Exception $e) {
			// Use the global AFC logger to log a critical error
			WC_AeliaFoundationClasses::instance()->get_logger()->critical(
				__('Exception occurred while attempting to run a scheduled task.', Definitions::TEXT_DOMAIN), [
					'Task ID' => self::get_id(),
					'Task Callback Args' => $event_args,
					'Exception Message' => $e->getMessage(),
				]
			);
		}
	}

	/**
	 * Runs the task and returns the result. The settings for the task are passed to
	 * the class constructor and stored during the initialisation.
	 *
	 * @return Task_Result
	 * @throws \Aelia\WC\Exceptions\NotImplementedException
	 */
	public function run(): Task_Result {
		throw new \Aelia\WC\Exceptions\NotImplementedException();
	}
}
