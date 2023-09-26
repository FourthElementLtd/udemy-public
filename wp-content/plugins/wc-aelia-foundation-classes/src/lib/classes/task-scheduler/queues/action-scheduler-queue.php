<?php
namespace Aelia\WC\AFC\Scheduler\Queues;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\AFC\Traits\Logger_Trait;
use Aelia\WC\Definitions;

/**
 * Queue based on the Action Scheduler
 *
 * @since 2.4.9.230616
 */
class Action_Scheduler_Queue extends Base_Queue {
	use Logger_Trait;

	/**
	 * The singleton instance of this class.
	 *
	 * @var Base_Queue
	 */
	protected static $_instance;

	/**
	 * Sets the actions and filters used by the class.
	 *
	 * @return void
	 */
	protected function set_hooks(): void {
		add_action('action_scheduler_failed_action', [$this, 'action_scheduler_failed_action'], 10, 2);
	}

	/**
	 * Schedule an action to run once at some time in the future
	 *
	 * @param int $timestamp When the job will run.
	 * @param string $hook The hook to trigger.
	 * @param array $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @return string The action ID.
	 */
	public function schedule_single($timestamp, $hook, $args = [], $group = '') {
		// Warn the developers is they try to use the Action Scheduler before the "init" event
		// @since 2.4.9.230616
		// @link https://actionscheduler.org/api/
		if(!did_action('init')) {
			wc_doing_it_wrong(__FUNCTION__, __('The Action Scheduler is not available before "init" action.', Definitions::TEXT_DOMAIN), '2.4.9.230616');
			return false;
		}

		// Wrapping all the arguments inside an array, with single "event_args" entry will
		// make it possible to implement callbacks that always take a single argument, and
		// extract the actual data using the array keys
		return parent::schedule_single($timestamp, $hook, ['event_args' => $args], $group);
	}

	/**
	 * Schedule a recurring action
	 *
	 * @param int $timestamp When the first instance of the job will run.
	 * @param int $interval_in_seconds How long to wait between runs.
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @return string The action ID.
	 */
	public function schedule_recurring($timestamp, $interval_in_seconds, $hook, $args = array(), $group = '') {
		// Warn the developers is they try to use the Action Scheduler before the "init" event
		// @since 2.4.9.230616
		// @link https://actionscheduler.org/api/
		if(!did_action('init')) {
			wc_doing_it_wrong(__FUNCTION__, __('The Action Scheduler is not available before "init" action.', Definitions::TEXT_DOMAIN), '2.4.9.230616');
			return false;
		}

		// Wrapping all the arguments inside an array, with single "event_args" entry will
		// make it possible to implement callbacks that always take a single argument, and
		// extract the actual data using the array keys
		return parent::schedule_recurring($timestamp, $interval_in_seconds, $hook, ['event_args' => $args], $group);
	}

	/**
	 * Intercepts a failed action.
	 *
	 * @param int $action_id
	 * @param int $timeout
	 * @return void
	 * @link https://github.com/woocommerce/action-scheduler/issues/234#issuecomment-462199033
	 */
	public function action_scheduler_failed_action($action_id, $timeout): void {
		$action = \ActionScheduler::store()->fetch_action($action_id);

		if($action instanceof \ActionScheduler_Action) {
			$this->get_logger()->error(__('Scheduled action failed.', Definitions::TEXT_DOMAIN), [
				'Action ID' => $action_id,
				'Action' => $action,
			]);

			// Check if the task is meant to be retried. In that case, it will be scheduled again
			$this->maybe_reschedule_action($action);
		}
	}

	/**
	 * Given an action, it extracts the arguments that were passed to it.
	 *
	 * @param \ActionScheduler_Action $action
	 * @return array
	 */
	protected function get_action_args(\ActionScheduler_Action $action): array {
		// Extract the arguments associated to the scheduled action
		$action_args = $action->get_args();

		// A convention used in this class is that each event is scheduled using a single
		// argument, which is a list of key/values containing the actual values to be
		// passed by the task callback.
		// This allows to implement callback functions with a single input argument, which
		// can be extended without having to declare each input argument.
		return isset($action_args['event_args']) ? $action_args['event_args'] : $action_args;
	}

	/**
	 * Checks if a failed task needs to be rescheduled. If it does, it creates a new action,
	 * with the same arguments as the original one and the same recurrence.
	 *
	 * @param \ActionScheduler_Action $action
	 * @return void
	 */
	protected function maybe_reschedule_action(\ActionScheduler_Action $action): void {
		// Extract the arguments used by the action
		$action_args = $this->get_action_args($action);

		// Check if the action should be retried upon failure and how many times it has been retried
		$max_retries = isset($action_args['max_retries']) && is_numeric($action_args['max_retries']) ? $action_args['max_retries'] : 0;
		$retry_count = isset($action_args['retry_count']) && is_numeric($action_args['retry_count']) ? $action_args['retry_count'] : 0;

		// If the action should be retried, add it back to the schedule
		if(($retry_count < $max_retries) || ($max_retries === self::ALWAYS_RETRY_FAILED_TASK)) {
			// Update the retry count
			$action_args['retry_count'] = $retry_count + 1;

			// Determine if the action should be rescheduled as a recurring one
			$interval = 0;
			$schedule = $action->get_schedule();

			// If the schedule is recurring and it has a "get_recurrency" method, use it
			// to fetch the recurrence interval
			// @see \ActionScheduler_ActionFactory::repeat()
			if($schedule->is_recurring() && method_existS($schedule, 'get_recurrence')) {
				$recurrence = $schedule->get_recurrence(); // NOSONAR

				if(is_numeric($recurrence) && ($recurrence > 0)) {
					$interval = $recurrence;
				}
			}

			$this->get_logger()->notice(__('Retrying failed task.', Definitions::TEXT_DOMAIN), [
				'Action Hook' => $action->get_hook(),
				'Scheduled Event Args' => $action_args,
				'Action Group' => $action->get_group(),
				'Interval' => $interval,
				'Retry Count' => $retry_count,
			]);

			// Calculate the offset to be added to the starting time
			$start_timestamp = time() + apply_filters('wc_aelia_task_scheduler_action_retry_start_offset', self::DEFAULT_RETRY_DELAY, $action);

			// If the interval is greater than zero, schedule a recurring task. If it's zero, schedule a once-off task
			if($interval > 0) {
				// @see WC_Action_Queue::schedule_recurring()
				$scheduled_action_id = $this->schedule_recurring($start_timestamp, $interval, $action->get_hook(), $action_args, $action->get_group());
			}
			else {
				// @see WC_Action_Queue::schedule_single()
				$scheduled_action_id =$this->schedule_single($start_timestamp, $action->get_hook(), $action_args, $action->get_group());
			}

			if(is_numeric($scheduled_action_id)) {
				$this->get_logger()->notice(__('Task retry scheduled.', Definitions::TEXT_DOMAIN), [
					'Scheduled Action ID' => $scheduled_action_id,
					'Start Timestamp' => $start_timestamp,
					'Action Hook' => $action->get_hook(),
					'Scheduled Event Args' => $action_args,
					'Action Group' => $action->get_group(),
					'Interval' => $interval,
					'Retry Count' => $retry_count,
				]);
			}
			else {
				$this->get_logger()->warning(__('Could not scheduled a task retry.', Definitions::TEXT_DOMAIN), [
					'Start Timestamp' => $start_timestamp,
					'Action Hook' => $action->get_hook(),
					'Scheduled Event Args' => $action_args,
					'Action Group' => $action->get_group(),
					'Interval' => $interval,
					'Retry Count' => $retry_count,
				]);
			}
		}
		// If we reached the maximum number of retries, log an error and stop here
		elseif(($max_retries > 0) && ($retry_count >= $max_retries)) {
			$this->get_logger()->alert(__('Scheduled task failed. Reached the maximum number of retries.', Definitions::TEXT_DOMAIN), [
				'Action Hook' => $action->get_hook(),
				'Scheduled Event Args' => $action_args,
				'Action Group' => $action->get_group(),
				'Max Retries' => $max_retries,
				'Retry Count' => $retry_count,
			]);
		}
	}
}
