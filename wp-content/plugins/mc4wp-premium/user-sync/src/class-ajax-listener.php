<?php

namespace MC4WP\User_Sync;

class Ajax_Listener {

	/**
	 * @var User_Handler
	 */
	protected $user_handler;

	/**
	 * @var Users
	 */
	protected $users;

	/**
	 * Constructor
	 *
	 * @param User_Handler $user_handler
	 * @param Users $users
	 */
	public function __construct( User_Handler $user_handler, Users $users  ) {
		$this->user_handler = $user_handler;
		$this->users = $users;
	}

	/**
	 * Add hooks
	 */
	public function add_hooks() {
		add_action( 'wp_ajax_mc4wp_user_sync_get_user_count', array( $this, 'get_user_count' ) );
		add_action( 'wp_ajax_mc4wp_user_sync_get_users', array( $this, 'get_users' ) );
		add_action( 'wp_ajax_mc4wp_user_sync_handle_user', array( $this, 'handle_user' ) );
	}

	/**
	 * Get user count
	 */
	public function get_user_count() {
		$this->authorize();
		$count = $this->users->count();
		$this->respond( $count );
	}

	/**
	 * Responds with an array of all user ID's
	 */
	public function get_users() {
		$this->authorize();
		$offset = ( isset( $_REQUEST['offset'] ) ? intval( $_REQUEST['offset'] ) : 0 );
		$limit = ( isset( $_REQUEST['limit'] ) ? intval( $_REQUEST['limit'] ) : 0 );

		// get users
		$users = $this->users->get( array( 'fields' => array( 'ID', 'user_login', 'user_email' ), 'offset' => $offset, 'number' => $limit ));

		// send response
		$this->respond( $users );
	}

	/**
	 * Subscribes the provided user ID
	 */
	public function handle_user() {
		$this->authorize();

		$user_id = (int) $_REQUEST['user_id'];

		try {
			$status = $this->user_handler->handle_user( $user_id );
		} catch( \Exception $e ) {
			$this->respond(
				array(
					'success' => 0,
					'message' =>  $e->getMessage(),
				)
			);
			return;
		}

		switch ($status) {
			case User_Handler::UPDATED:
				$message = sprintf( __( 'Updated user %d', 'mailchimp-sync' ), $user_id );
				break;

			case User_Handler::SKIPPED:
				$message = sprintf( __( 'Skipped user %d', 'mailchimp-sync' ), $user_id );
				break;

			case User_Handler::NOT_ON_LIST:
				$message = sprintf( __( 'User %d was not found on Mailchimp list', 'mailchimp-sync' ), $user_id );
				break;

			case User_Handler::EMAIL_ALREADY_EXISTS_IN_LIST:
				$message = sprintf( __( 'New email address of user already exists in Mailchimp', 'mailchimp-sync' ), $user_id );
				break;

			default:
				throw new \LogicException();
		}

		$data = array(
			'success' => 1,
			'message' => $message,
		);
		$this->respond( $data );
	}

	private function authorize() {
		if ( ! current_user_can( 'manage_options' ) ) {
			exit;
		}
	}

	/**
	 * Send a JSON response
	 *
	 * @param mixed $data
	 */
	private function respond( $data ) {
		send_origin_headers();
		@header( 'X-Content-Type-Options: nosniff' );
		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		send_nosniff_header();
		nocache_headers();

		// clear output, some plugins might have thrown errors by now.
		if( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		wp_send_json( $data );
		exit;
	}

}
