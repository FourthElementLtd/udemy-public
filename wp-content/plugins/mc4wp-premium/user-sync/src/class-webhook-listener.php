<?php

namespace MC4WP\User_Sync;

use WP_User;
use MC4WP_Debug_Log;

/**
 * Class Webhook_Listener
 *
 * This class listens on your-site.com/mc4wp-sync-api/webhook-listener for Mailchimp webhook events.
 *
 * Once triggered, it will look for the corresponding WP user and update it using the field map defined in the settings of the Sync plugin.
 */
class Webhook_Listener {

	/**
	 * @var array
	 */
	private $settings;

	/**
	 * @var string
	 */
	const URL = '/mc4wp-sync-api/webhook-listener';

	/**
	 * @param array $settings
	 */
	public function __construct( array $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Add hooks
	 */
	public function add_hooks() {
		add_action( 'init', array( $this, 'listen' ) );
	}

	/**
	 * Listen for webhook requests
	 */
	public function listen() {
		if( $this->is_triggered() ) {
			$this->handle();
			exit;
		}
	}

	/**
	 * Yes?
	 *
	 * @return bool
	 */
	public function is_triggered() {
		return strpos( $_SERVER['REQUEST_URI'], self::URL ) !== false;
	}

	/**
	 * Handle the request
	 */
	public function handle() {
		// no parameters = Mailchimp webhook validator
		// we skip webhook secret validation here
		if( empty( $_POST['data'] ) || empty( $_POST['type'] ) ) {
			status_header( 200 );
			echo "Listening..";
			return;
		}

		if (! $this->settings['webhook_enabled']) {
			return;
		}

		// check for secret key
		if( $this->settings['webhook_secret'] === '' || ! isset( $_GET[ $this->settings['webhook_secret'] ] ) ) {
			status_header( 403 );
			return;
		}

		$log = $this->get_log();
		define( 'MC4WP_SYNC_DOING_WEBHOOK', true );

		$user = null;
		$data = stripslashes_deep( $_REQUEST['data'] );
		$type = (string) $_REQUEST['type'];

        /**
         * Filter webhook data that is received by Mailchimp.
         *
         * @param array $data
         * @param string $type
         */
        $data = apply_filters( 'mc4wp_user_sync_webhook_data', $data, $type );

		/**
		 * @deprecated
		 * @see mc4wp_user_sync_webhook_data
		 */
		$data = apply_filters( 'mailchimp_sync_webhook_data', $data, $type );

		// parameters but incorrect: throw error status
		if (empty($data['web_id']) && empty($data['id']) && empty($data['old_email']) && empty($data['email'])) {
			status_header( 400 );
			return;
		}

		/** @var Users $users */
		$users = mc4wp('user_sync.users');

		// find WP user by Mailchimp unique email ID
        if (isset($data['id'])) {
            $user = $users->get_user_by_mailchimp_id($data['id']);
        }

		// Still no user found? Try "old_email" property used in "upemail" requests
		if (! $user instanceof WP_User && isset($data['old_email'])) {
            $user = $users->get_user_by_email_address( $data['old_email'] );
        }

		// Still no user found? Try "email" property 
		if (! $user instanceof WP_User && isset($data['email'])) {
            $user = $users->get_user_by_email_address( $data['email'] );
        }
		/**
		 * Filters the WordPress user that is found by the webhook request
		 *
		 * @param WP_User|null $user
		 * @param array $data
		 */
		$user = apply_filters( 'mc4wp_user_sync_webhook_user', $user, $data );

		/**
		 * @deprecated
		 * @see mc4wp_user_sync_webhook_user
		 */
		$user = apply_filters( 'mailchimp_sync_webhook_user', $user, $data );

		if( ! $user instanceof WP_User ) {
			// log a warning
			$log->info( sprintf( "Webhook: No user found for Mailchimp ID: %s", $data['id'] ) );

			// fire event when no user is found
			do_action( 'mc4wp_user_sync_webhook_no_user', $data );
			echo 'No corresponding user found for this subscriber.';

			// exit early
			status_header( 200 );
			return;
		}

		// we have a user at this point
        $log->info( sprintf( "Webhook: Request of type %s received for user #%d", $type, $user->ID ) );

		$updated = false;

		// User might not have correct sync key (if supplied by filter)
		$mailchimp_id = $users->get_mailchimp_id( $user->ID );
		if (isset($data['id']) && $mailchimp_id !== $data['id']) {
		    $users->set_mailchimp_id( $user->ID, $data['id']);
			$updated = true;
		}

		// update user email if it's given, valid and different
		if( $type === 'upemail' && isset( $data['new_email'] ) && is_email( $data['new_email'] ) && $data['new_email'] !== $user->user_email ) {
			add_filter( 'send_email_change_email', '__return_false', 99 );
			wp_update_user(
				array(
					'ID'         => $user->ID,
					'user_email' => $data['new_email']
				)
			);
            $users->set_mailchimp_id( $user->ID, $data['new_id']);
			$users->set_mailchimp_email_address( $user->ID, $data['new_email']);
			$updated = true;
		}

		if ($type === 'profile') {
            // update WP user with data (use reversed field map)
            // loop through mapping rules
            foreach ($this->settings['field_map'] as $rule) {

                // is this field present in the request data? do not use empty here
                if (isset($data['merges'][$rule['mailchimp_field']])) {

                    // is scalar value?
                    $value = $data['merges'][$rule['mailchimp_field']];
                    if (!is_scalar($value)) {
                        continue;
                    }

                    // update user property if it changed
                    if ($user->{$rule['user_field']} !== $value) {
                        update_user_meta($user->ID, $rule['user_field'], $value);
                        $updated = true;
                    }
                }

            }
        }

		if( $updated ) {
			$log->info( sprintf( "Webhook: Updated user #%d", $user->ID ) );
		}

		/**
		 * Fire an event to allow custom actions, like deleting the user if this is an unsubscribe ping.
		 *
		 * @param array $data
		 * @param WP_User $user
		 */
		do_action( 'mc4wp_user_sync_webhook', $data, $user );

		/**
		 * Fire type specific event.
		 *
		 * The dynamic portion of the hook, $type, regers to the webhook event type.
		 *
		 * Example: mc4wp_user_sync_webhook_unsubscribe
		 *
		 * @param array $data
		 * @param WP_User $user
		 */
		do_action( 'mc4wp_user_sync_webhook_' . $type, $data, $user );

		status_header(200);
		return;
	}

	/**
	 * @return MC4WP_Debug_Log
	 */
	private function get_log() {
		return mc4wp('log');
	}

}