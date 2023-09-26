<?php

namespace MC4WP\User_Sync;

use WP_CLI;
use WP_CLI_Command;

class CLI_Command extends WP_CLI_Command {

	/**
	 * Synchronize all users (using the specified role from settings)
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * ## EXAMPLES
	 *
	 *     wp mc4wp-user-sync all
	 *
	 * @subcommand all
	 */
	public function all( $args, $assoc_args ) {
		/** @var User_Handler $handler */
		$handler = mc4wp('user_sync.handler');

		/** @var Users $user_query */
		$user_query = mc4wp('user_sync.users');

		// get users matching role from settings
		$users = $user_query->get();
		$count = count( $users );
		WP_CLI::line( "$count users found." );
		if( $count <= 0 ) {
			return;
		}

		// show progress bar
		$notify = \WP_CLI\Utils\make_progress_bar( 'Working', $count );
		$user_ids = wp_list_pluck( $users, 'ID' );
		$errors = array();

		foreach( $user_ids as $user_id ) {

			try {
				$result = $handler->handle_user( $user_id );
			} catch( \Exception $e ) {
				$errors[$user_id] = $e->getMessage();
			}

			$notify->tick();
		}

		$notify->finish();

		if( ! empty( $errors ) ) {
			$errors_msg =  'The following errors occurred: ';
			foreach( $errors as $user_id => $e ) {
				$errors_msg .= PHP_EOL . sprintf( " - User #%d: %s", $user_id, $e );
			}
			WP_CLI::warning( $errors_msg );
		}

		WP_CLI::success( sprintf( "Synchronized %d users.", $count ) );
	}

	/**
	 * Synchronize a single user
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * ## OPTIONS
	 *
	 * <user_id>
	 * : ID of the user to synchronize
	 *
	 * ## EXAMPLES
	 *
	 *     wp mc4wp-user-sync user 5
	 *
	 * @synopsis <user_id>
	 *
	 * @subcommand user
	 */
	public function user( $args, $assoc_args ) {

		$user_id = absint( $args[0] );

		/** @var User_Handler $handler */
		$handler = mc4wp('user_sync.handler');

		try {
			$handler->handle_user( $user_id );
		} catch( \Exception $e ) {
			WP_CLI::error( $e->getMessage() );
			return;
		}

		WP_CLI::success( sprintf( "User %d synchronized.", $user_id ) );
	}

	/**
	* @subcommand process-queue
	*/
	public function process_queue( $args, $assoc_args ) {
		do_action( 'mc4wp_user_sync_process_queue' );
	}	
}
