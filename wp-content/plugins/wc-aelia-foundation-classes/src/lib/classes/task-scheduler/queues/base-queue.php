<?php
namespace Aelia\WC\AFC\Scheduler\Queues;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\AFC\Traits\Logger_Trait;
use Aelia\WC\Traits\Singleton;

/**
 * Base queue class.
 *
 * @since 2.4.9.230616
 */
abstract class Base_Queue extends \WC_Action_Queue {
	use Logger_Trait;

	/**
	 * The singleton instance of this class.
	 *
	 * @var Base_Queue
	 */
	protected static $_instance;

	/**
	 * This constant is used to indicate a "max retries" value of "infinite". A task
	 * scheduled with this argument will always be retried, every time it fails.
	 *
	 * @var int
	 * @link https://bitbucket.org/businessdad/aelia-freemius-integration-for-woocommerce/issues/14
	 */
	const ALWAYS_RETRY_FAILED_TASK = -1;

	/**
	 * Tasks that should be retried "starting immediately" will be scheduled
	 * after the interval specified by this offst. Value in seconds.
	 *
	 * @var int
	 */
	const DEFAULT_RETRY_DELAY = 600;

	/**
	 * Stores the queue settings.
	 *
	 * @var Queue_Settings
	 */
	protected $settings;

	/**
	 * Returns the singleton instance of this queue.
	 *
	 * @param Queue_Settings $settings
	 * @return Base_Queue
	 */
	public static function instance(Queue_Settings $settings): Base_Queue {
		return static::$_instance ?? static::$_instance = new static($settings);
	}

	/**
	 * Constructor.
	 */
	public function __construct(Queue_Settings $settings) {
		$this->settings = $settings;

		// Initialise the logger, using the instance passed with the settings
		$this->set_logger($settings->logger_instance);
		self::$_debug_mode = $settings->debug_mode;

		$this->set_hooks();
	}

	/**
	 * Sets the actions and filters used by the class.
	 *
	 * @return void
	 */
	protected function set_hooks(): void {
		// Placeholder. To be extended by descendant classes
	}

	/**
	 * Check if there is a scheduled action in the queue, but more efficiently than as_next_scheduled_action().
	 *
	 * @param string $hook The hook that the job will trigger.
	 * @param array  $args Filter to a hook with matching args that will be passed to the job when it runs.
	 * @param string $group Filter to only actions assigned to a specific group.
	 * @return bool
	 * @since 2.4.9.230616
	 * @see as_has_scheduled_action()
	 */
	public function has_scheduled_action($hook, $args = null, $group = '') {
		if(method_exists('\WC_Action_Queue', __FUNCTION__)) {
			return parent::has_scheduled_action($hook, $args, $group);
		}

		return (bool)as_has_scheduled_action($hook, $args, $group);
	}
}