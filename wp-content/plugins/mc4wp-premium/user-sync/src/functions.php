<?php

namespace MC4WP\User_Sync;

function get_settings() {
	$settings = (array) get_option( 'mc4wp_user_sync', array() );
	$defaults = array(
		'list' => '',
		'role' => '',
		'enabled' => 1,
		'field_map' => array(),
		'skip_empty_user_fields' => 0,
		'webhook_enabled' => 0,
		'webhook_secret' => '',
	);

	$settings = array_merge( $defaults, $settings );
	$settings['enabled'] = (int) $settings['enabled'];
	$settings['webhook_enabled'] = (int) $settings['webhook_enabled'];

	/**
	 * Filters Mailchimp Sync settings
	 *
	 * @param array $settings
	 */
	return (array) apply_filters( 'mc4wp_user_sync_settings', $settings );
}


/**
 * Sets up the schedule to run Mailchimp User Sync hourly
 *
 * @hooked plugin activation
 */
function setup_schedule() {
	$next_run = wp_next_scheduled( 'mc4wp_user_sync_process_queue' );
	if ( $next_run && ( $next_run - time() ) < 600 ) {
		return;
	}

	wp_schedule_event( time() + 600, 'every-10-minutes', 'mc4wp_user_sync_process_queue' );
}

/**
 * Clears the schedule to run Mailchimp User Sync every hour
 *
 * @hooked plugin deactivation
 */
function clear_schedule() {
	wp_clear_scheduled_hook( 'mc4wp_user_sync_process_queue' );
}
