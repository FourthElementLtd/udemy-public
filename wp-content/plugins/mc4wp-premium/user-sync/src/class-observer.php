<?php


namespace MC4WP\User_Sync;

use Error;
use MC4WP_Queue as Queue;

class Observer {

    /**
     * @var Queue
     */
    private $queue;

    /**
     * @var Users
     */
    private $users;

    /**
     * Worker constructor.
     *
     * @param Queue      $queue
     * @param Users 	$users
     */
    public function __construct( Queue $queue, Users $users ) {
        $this->queue = $queue;
        $this->users = $users;
    }

    /**
     * Add hooks
     */
    public function add_hooks() {
        add_action( 'profile_update', array( $this, 'on_profile_update' ), 10, 2);
        add_action( 'updated_user_meta', array( $this, 'on_updated_user_meta' ), 10, 4 );
    }

    public function on_profile_update($user_id) {
        $this->maybe_schedule( $user_id );
    }

    public function on_updated_user_meta( $meta_id, $user_id, $meta_key, $meta_value  ) {
        //  Don't act on our own keys or hidden meta keys.
        if( strpos( $meta_key, 'mailchimp' ) === 0
            || strpos( $meta_key, 'mc4wp_' ) === 0
            || strpos( $meta_key, '_' ) === 0 ) {
            return;
        }

		/*
		 * Don't act on list of ignored meta keys
		 */
        $ignored_meta_keys = array(
			'rich_editing',
			'syntax_highlighting',
			'comment_shortcuts',
			'admin_color',
			'use_ssl',
			'show_admin_bar_front',
			'wp-last-login',
			'wp_yoast_notifications'
		);
        $ignored_meta_keys = apply_filters( 'mc4wp_user_sync_ignored_user_meta_keys', $ignored_meta_keys );
        if( in_array( $meta_key, $ignored_meta_keys, true ) ) {
            return;
        }

        $this->maybe_schedule( $user_id );
    }

    private function maybe_schedule( $user_id ) {
        $user = $this->users->user( $user_id );

        // only schedule user to be updated if a critical field really changed
        $hash = $this->users->user_to_hash( $user );
        $old_hash = $this->users->get_hash( $user_id );
        if ($hash === $old_hash) {
            return;
        }

        $this->schedule( $user->ID );
    }

    /**
     * Adds a task to the queue
     *
     * @param int $user_id
     * @throws \Exception
     */
    private function schedule( $user_id ) {
        // Don't schedule anything when doing webhook
        if( defined( 'MC4WP_SYNC_DOING_WEBHOOK' ) && MC4WP_SYNC_DOING_WEBHOOK ) {
            return;
        }

		// schedule only if needed
		$user = $this->users->user( $user_id );
		$handle = $this->users->should( $user );
		if (!$handle) {
			return;
		}

        $this->queue->put($user_id);
    }

}
