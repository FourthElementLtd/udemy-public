<?php
namespace Aelia\WC\AFC\Scheduler\Tasks;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

/**
 * Interface for scheduled tasks.
 *
 * @since 2.4.9.230616
 */
interface IScheduled_Task {
	public static function get_id(): string;
	public static function get_task_hook(): string;
	public static function initialize_event_handlers(): void;
	public function run(): Task_Result;
	public static function task_event_callback($event_args = []): void;
}

