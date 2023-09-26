<?php

namespace MC4WP\User_Sync;

use Exception;
use MC4WP_MailChimp_Subscriber;
use MC4WP_API_v3;
use MC4WP_Debug_Log;
use WP_User;

class User_Handler {

	/**
	 * @var string The List ID to sync with
	 */
	private $list_id;

    /**
     * @var Users
     */
	private $users;

	const SKIPPED = -1;
	const NOT_ON_LIST = 0;
	const UPDATED = 1;
	const EMAIL_ALREADY_EXISTS_IN_LIST = 20;

	/**
	 * Constructor
	 *
	 * @param string $list_id
	 * @param Users $users
	 */
	public function __construct( $list_id, Users $users )
	{
		$this->list_id = $list_id;
		$this->users = $users;
	}

	/**
	 * Add hooks to call the subscribe, update & unsubscribe methods automatically
	 */
	public function add_hooks() {
		// custom actions for people to use if they want to call the class actions
		add_action( 'mc4wp_user_sync_handle_user', array( $this, 'handle_user' ) );
	}

	/**
	 * Updates the given user, based on the User Sync settings.
	 * This does not change a subscriber's status (ie does not subscribe or unsubscribe), just updates their fields in Mailchimp.
	 *
	 * @param int $user_id
	 * @return int
	 * @throws Exception
	 */
	public function handle_user( $user_id ) {
		$user = $this->users->user( $user_id );

		// update only if user matches given criteria (role, filter)
		$handle = $this->users->should( $user );
		if (!$handle) {
			// Indicates user should be skipped, eg because user role didn't match settings or user has no email address
		    return self::SKIPPED;
        }

        $api = $this->get_api();
        try {
            $subscriber = $this->users->user_to_subscriber( $user );

            // send request to old email address if we have it
            $mailchimp_email_address = $this->users->get_mailchimp_email_address( $user_id );
            if ( empty( $mailchimp_email_address ) ) {
                $mailchimp_email_address = $subscriber->email_address;
            }

            $member = $api->update_list_member($this->list_id, $mailchimp_email_address, $subscriber->to_array());
        } catch(\MC4WP_API_Resource_Not_Found_Exception $e) {
           return self::NOT_ON_LIST; // Indicates user is not on list
        } catch(\MC4WP_API_Exception $e) {
			if ($e->title === 'Invalid Resource'
			    && is_array($e->errors)
			    && count($e->errors) > 0
			    && isset($e->errors[0]->field)
			    && $e->errors[0]->field === 'email address'
			    && stripos($e->errors[0]->message, 'already in this list') !== false) {
				return self::EMAIL_ALREADY_EXISTS_IN_LIST;
			}

			throw $e;

        }

        // Success!
        $this->get_log()->info( sprintf( 'User Sync > Updated user %d (%s)', $user->ID, $user->user_email ) );

        // Store remote email address
        $this->users->set_mailchimp_id( $user_id, $member->unique_email_id );
        $this->users->set_mailchimp_email_address( $user_id, $member->email_address );
        $this->users->set_hash( $user_id, $this->users->subscriber_to_hash( $subscriber ) );
        return self::UPDATED;
	}



    /**
     * @return MC4WP_API_v3
     */
    private function get_api() {
        return mc4wp('api');
    }

	/**
	 * Returns an instance of the Debug Log
	 *
	 * @return MC4WP_Debug_Log
	 */
	private function get_log() {
		return mc4wp( 'log' );
	}


}


