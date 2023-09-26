<?php
namespace Aelia\WC\AFC\Scheduler;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\AFC\Traits\Logger_Trait;
use Aelia\WC\Definitions;

/**
 * Base class for scheduled tasks.
 *
 * @since 2.4.9.230616
 */
class Task_Scheduler {
	use Logger_Trait;

	/**
	 * The singleton instance of this class.
	 *
	 * @var Task_Scheduler
	 */
	protected static $_instance;

	/**
	 * Stores the task scheduler settings.
	 *
	 * @var Task_Scheduler_Settings
	 */
	protected $settings;

	/**
	 * Stores a list of registered tasks, which can be scheduled using this class.
	 *
	 * @var array
	 */
	protected $registered_task_classes = [];

	/**
	 * Instantiates and initialises the task scheduler.
	 *
	 * @return Task_Scheduler
	 */
	public static function init(Task_Scheduler_Settings $settings): Task_Scheduler {
		return self::$_instance = new static($settings);
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return Task_Scheduler
	 */
	public static function instance(): Task_Scheduler {
		if(empty(self::$_instance)) {
			throw new \Aelia\WC\Exceptions\NotInitializedException(sprintf(__('Class not initialized. You must call %1$s::init() before calling %1$s::instance().', Definitions::TEXT_DOMAIN), __CLASS__));
		}

		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct(Task_Scheduler_Settings $settings) {
		$this->settings = $settings;

		// Initialise the logger, using the instance passed with the settings
		$this->set_logger($settings->logger_instance);
		self::$_debug_mode = $settings->debug_mode;

		// Trigger an event when the task scheduler has been initialised, so that
		// 3rd parties can register their tasks
		do_action('aelia_task_scheduler_initialized', $this);
	}

	/**
	 * Given a class, it verifies that it represents a valid scheduled task.
	 *
	 * @param string $task_class
	 * @return boolean
	 */
	protected function is_task_class_valid(string $task_class_name): bool {
		$task_class_valid = true;
		// Skip non-existent classes
		if(!class_exists($task_class_name)) {
			$this->get_logger()->error(sprintf(__('Scheduled task registration - Task class does not exist.', Definitions::TEXT_DOMAIN), $task_class_name), [
				'Task Class' => $task_class_name,
			]);
			$task_class_valid = false;
		}

		// Skip classes that don't implement the correct interface
		if($task_class_valid && !(new \ReflectionClass($task_class_name))->isSubclassOf('\Aelia\WC\AFC\Scheduler\Tasks\IScheduled_Task')) {
			$this->get_logger()->error(sprintf(__('Scheduled task registration - Invalid task class.', Definitions::TEXT_DOMAIN), $task_class_name), [
				'Task Class' => $task_class_name,
			]);
			$task_class_valid = false;
		}

		return $task_class_valid;
	}

	/**
	 * Registers the handlers that will take care of processing the the scheduled tasks.
	 *
	 * @param array $task_handlers
	 * @return void
	 */
	public function register_task_handlers(array $task_handlers = []): void {
		$task_classes = apply_filters('wc_aelia_task_scheduler_task_handlers', $task_handlers);

		// Register all the task classes
		foreach($task_classes as $task_class) {
			// Register all valid task classes
			if($this->is_task_class_valid($task_class)) {
				$this->registered_task_classes[$task_class::get_id()] = $task_class;
			}
		}

		// Initialize the registered task classes, so that they can automatically intercept the
		// scheduled events that they are supposed to handle
		foreach($this->registered_task_classes as $task_class) {
			$task_class::initialize_event_handlers();
		}

		$this->get_logger()->debug(__('Scheduled tasks registered.', Definitions::TEXT_DOMAIN), [
			'Registered Classes' => $this->registered_task_classes,
		]);
	}

	/**
	 * Given a task ID, returns the event (action) that should be used to schedule the task.
	 *
	 * @param string $task_id
	 * @return string
	 */
	protected function get_task_event(string $task_id): string {
		$task_class = $this->registered_task_classes[$task_id] ?? '';
		if(empty($task_class)) {
			$err_msg = esc_html(implode(' ', [
				sprintf(__('Scheduled task ID not registered: %1$s.', Definitions::TEXT_DOMAIN), $task_id),
				__('Please check that the task ID is correct and that it was registered by calling Task_Scheduler::register_task_handlers().', Definitions::TEXT_DOMAIN),
			]));

			$this->get_logger()->error($err_msg, [
				'Task ID' => $task_id,
			]);

			throw new \InvalidArgumentException($err_msg, Definitions::ERR_INVALID_SCHEDULED_TASK);
		}
		return $task_class::get_task_hook();
	}

	/**
	 * Schedules the task, returning the action ID.
	 *
	 * @param Scheduled_Task_Settings $task_settings
	 * @return string
	 */
	public function schedule_task(Scheduled_Task_Settings $task_settings): string {
		try {
			// Fetch the event to be scheduled using the task ID
			$task_event = $this->get_task_event($task_settings->task_id);
		}
		catch(\InvalidArgumentException $e) {
			// If a task is not found, an exception is raised. Let it go through, as this
			// is an error worth noticing immediately
			throw $e; // NOSONAR
		}

		// Cancel the task schedules if it was already scheduled
		if($task_settings->reset_existing_schedule) {
			$this->cancel_all_task_events($task_settings);
		}

		// If the interval is greater than zero, schedule a recurring task. If it's zero,
		// schedule a once-off task
		if($task_settings->interval > 0) {
			// @see WC_Action_Queue::schedule_recurring()
			$scheduled_action_id = $this->settings->queue->schedule_recurring($task_settings->start_timestamp, $task_settings->interval, $task_event, $task_settings->scheduled_event_args, $task_settings->group);
		}
		else {
			// @see WC_Action_Queue::schedule_single()
			$scheduled_action_id = $this->settings->queue->schedule_single($task_settings->start_timestamp, $task_event, $task_settings->scheduled_event_args, $task_settings->group);
		}

		$this->get_logger()->info(__('Task scheduled successfully.', Definitions::TEXT_DOMAIN), [
			'Scheduled Action ID' => $scheduled_action_id,
			'Task Event' => $task_event,
			'Task Settings' => $task_settings,
		]);

		return $scheduled_action_id;
	}

	/**
	 * Dequeue the next scheduled instance of the task.
	 *
	 * @param Scheduled_Task_Settings $task_settings
	 * @param bool $include_args If set, the task arguments passed with the task settings will be used to find the events to cancel.
	 * @return void
	 * @see WC_Queue_Interface::cancel()
	 */
	public function cancel_task_events(Scheduled_Task_Settings $task_settings, $include_args = false): void {
		try {
			// Fetch the event to be scheduled using the task ID
			$task_event = $this->get_task_event($task_settings->task_id);
		}
		catch(\InvalidArgumentException $e) {
			// If a task is not found, an exception is raised. We can log the error and stop here
			$this->get_logger()->warning(__('Could not cancel scheduled events for an invalid task.', Definitions::TEXT_DOMAIN), [
				'Task Settings' => $task_settings,
			]);
			return;
		}

		// If needed, include the task arguments in the search for the events to cancel
		$event_search_args = $include_args ? $task_settings->scheduled_event_args : null;
		$this->settings->queue->cancel($task_event, $event_search_args, $task_settings->group);

		$this->get_logger()->info(__('Cancelled scheduled events for task.', Definitions::TEXT_DOMAIN), [
			'Task Event' => $task_event,
			'Task Settings' => $task_settings,
		]);
	}

	/**
	 * Dequeue all scheduled instances of the task.
	 *
	 * @param Scheduled_Task_Settings $task_settings
	 * @param bool $include_args If set, the task arguments passed with the task settings will be used to find the events to cancel.
	 * @return void
	 * @see WC_Queue_Interface::cancel_all()
	 */
	public function cancel_all_task_events(Scheduled_Task_Settings $task_settings, $include_args = false): void {
		try {
			// Fetch the event to be scheduled using the task ID
			$task_event = $this->get_task_event($task_settings->task_id);
		}
		catch(\InvalidArgumentException $e) {
			// If a task is not found, an exception is raised. We can log the error and stop here
			$this->get_logger()->warning(__('Could not cancel scheduled events for an invalid task.', Definitions::TEXT_DOMAIN), [
				'Task Settings' => $task_settings,
			]);
			return;
		}

		// If needed, include the task arguments in the search for the events to cancel
		$event_search_args = $include_args ? $task_settings->scheduled_event_args : null;
		$this->settings->queue->cancel_all($task_event, $event_search_args, $task_settings->group);

		$this->get_logger()->info(__('Cancelled all scheduled events for task.', Definitions::TEXT_DOMAIN), [
			'Task Event' => $task_event,
			'Task Settings' => $task_settings,
		]);
	}

	/**
	 * Get the date and time for the next scheduled occurence of the task. If some arguments are specified,
	 * they are used to find the scheduled task.
	 *
	 * @param Scheduled_Task_Settings $task_settings
	 * @param bool $include_args If set, the task arguments passed with the task settings will be used to find the events to cancel.
	 * @return WC_DateTime|null The date and time for the next occurrence, or null if there is no pending, scheduled action for the given hook.
	 * @see WC_Queue_Interface::get_next()
	 */
	public function get_next_schedule(Scheduled_Task_Settings $task_settings, $include_args = false): ?\DateTime {
		try {
			// Fetch the event to be scheduled using the task ID
			$task_event = $this->get_task_event($task_settings->task_id);
		}
		catch(\InvalidArgumentException $e) {
			// If a task is not found, an exception is raised. We can log the error and stop here
			$this->get_logger()->warning(__('Could not determine next schedule for an invalid task.', Definitions::TEXT_DOMAIN), [
				'Task Settings' => $task_settings,
			]);
			// If a task is not found, an exception is raised. Let it go through, as this
			// is an error worth noticing immediately
			throw $e;
		}

		// If needed, include the task arguments in the search for the events to cancel
		$event_search_args = $include_args && !empty($task_settings->scheduled_event_args) ? $task_settings->scheduled_event_args : null;

		return $this->settings->queue->get_next($task_event, $event_search_args, $task_settings->group);
	}

	/**
	 * Indicates if a specific action is already scheduled.
	 *
	 * @param Scheduled_Task_Settings $task_settings
	 * @param bool $include_args If set, the task arguments passed with the task settings will be used to find the events to cancel.
	 * @return bool The date and time for the next occurrence, or null if there is no pending, scheduled action for the given hook.
	 * @since 2.4.9.230616
	 * @see Aelia\WC\AFC\Scheduler\Queues\Base_Queue::has_scheduled_action()
	 */
	public function is_scheduled(Scheduled_Task_Settings $task_settings, $include_args = false): bool {
		try {
			// Fetch the event to be scheduled using the task ID
			$task_event = $this->get_task_event($task_settings->task_id);
		}
		catch(\InvalidArgumentException $e) {
			// If a task is not found, an exception is raised. We can log the error and stop here
			$this->get_logger()->warning(__('Could not determine the presence of a schedule for an invalid task.', Definitions::TEXT_DOMAIN), [
				'Task Settings' => $task_settings,
			]);
			// If a task is not found, an exception is raised. Let it go through, as this
			// is an error worth noticing immediately
			throw $e;
		}

		// If needed, include the task arguments in the search for the events to cancel
		$event_search_args = $include_args && !empty($task_settings->scheduled_event_args) ? $task_settings->scheduled_event_args : null;

		return $this->settings->queue->has_scheduled_action($task_event, $event_search_args, $task_settings->group);
	}
}





