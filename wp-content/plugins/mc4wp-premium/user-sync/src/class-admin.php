<?php

namespace MC4WP\User_Sync;

use MC4WP_MailChimp;
use MC4WP_Queue;
use Mockery\Exception;

class Admin {

	/**
	 * @const string
	 */
	const SETTINGS_CAP = 'manage_options';

	/**
	 * @var array $options
	 */
	private $options;

	/**
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * Constructor
	 *
	 * @param array $options
	 */
	public function __construct( array $options ) {
		$this->options = $options;
		$this->plugin_slug = plugin_basename( MC4WP_USER_SYNC_PLUGIN_FILE );
	}

	/**
	 * Add hooks
	 */
	public function add_hooks() {
		add_action( 'admin_init', array( $this, 'init' ) );
		add_filter( 'mc4wp_admin_menu_items', array( $this, 'add_menu_items' ) );
		add_action( 'mc4wp_admin_process_user_sync_queue', array( $this, 'process_queue' ) );
		add_action( 'mc4wp_admin_save_user_sync_settings', array( $this, 'save_settings' ) );
	}

	/**
	 * Runs on `admin_init`
	 */
	public function init() {
		// only run for administrators
		if( ! current_user_can( self::SETTINGS_CAP ) ) {
			return false;
		}

		// add link to settings page from plugins page
		add_filter( 'plugin_action_links_' . $this->plugin_slug, array( $this, 'add_plugin_settings_link' ) );
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_meta_links'), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'load_assets' ) );
	}

	/**
	 * Register menu pages
	 *
	 * @param array $items
	 *
	 * @return array
	 */
	public function add_menu_items( $items ) {
		$item = array(
			'title' => esc_html__( 'MailChimp User Sync', 'mailchimp-sync' ),
			'text' => esc_html__( 'User Sync', 'mailchimp-sync' ),
			'slug' => 'user-sync',
			'callback' => array( $this, 'show_settings_page' )
		);

		$items[] = $item;
		return $items;
	}

	/**
	 * Add the settings link to the Plugins overview
	 *
	 * @param array $links
	 * @return array
	 */
	public function add_plugin_settings_link( $links ) {
		$settings_link = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=mailchimp-for-wp-user-sync' ) ), esc_html__( 'Settings', 'mailchimp-for-wp' ) );
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Adds meta links to the plugin in the WP Admin > Plugins screen
	 *
	 * @param array $links
	 * @param string $file
	 * @return array
	 */
	public function add_plugin_meta_links( $links, $file ) {
		if( $file !== $this->plugin_slug ) {
			return $links;
		}

		$links[] = sprintf( esc_html__( 'An add-on for %s', 'mailchimp-sync' ), '<a href="https://www.mc4wp.com/#utm_source=wp-plugin&utm_medium=mailchimp-user-sync&utm_campaign=plugins-page">Mailchimp for WordPress</a>' );
		return $links;
	}

	/**
	 * Load assets if we're on the settings page of this plugin
	 */
	public function load_assets() {
		if( ! isset( $_GET['page'] ) || $_GET['page'] !== 'mailchimp-for-wp-user-sync' ) {
			return;
		}

		wp_enqueue_style( 'mailchimp-user-sync-admin', $this->asset_url( "/css/admin.css" ) );
		wp_enqueue_script( 'mailchimp-user-sync-wizard', $this->asset_url( "/js/admin.js" ), array(), MC4WP_USER_SYNC_PLUGIN_VERSION, true );
	}

	/**
	 * Outputs the settings page
	 */
	public function show_settings_page() {
		if (isset($_GET['debug-field-map'])) {
			$this->show_debug_field_map_page();
			return;
		}

	    $mailchimp = new MC4WP_MailChimp();
		$lists = $mailchimp->get_lists();

		/** @var MC4WP_Queue $queue */
		$queue = mc4wp('user_sync.queue');

		if ($this->options['list'] !== '') {
			$selected_list = $mailchimp->get_list($this->options['list']);
			$available_mailchimp_fields = $mailchimp->get_list_merge_fields($this->options['list']);
		}

		// query meta keys for use in a <select> element in the field map
		$meta_keys = $this->get_available_user_meta_keys();

		// reset index on field map
		$this->options['field_map'] = array_values( $this->options['field_map'] );

		// make sure field map always has at least 1 element
		if (count($this->options['field_map']) === 0) {
			$this->options['field_map'] = array(
				array( 'user_field' => '', 'mailchimp_field' => ''),
			);
		}

		require MC4WP_USER_SYNC_PLUGIN_DIR  . '/views/settings-page.php';
	}

	/** @return array */
	private function get_available_user_meta_keys() {
		global $wpdb;

		$meta_keys = get_transient('mc4wp_user_sync_available_meta_keys');
		if (empty($meta_keys)) {
			$meta_keys = $wpdb->get_col("SELECT DISTINCT(meta_key) FROM {$wpdb->usermeta};");
			set_transient('mc4wp_user_sync_available_meta_keys', $meta_keys, HOUR_IN_SECONDS);
		}

		return $meta_keys;
	}

	// GET /wp-admin/admin.php?page=mailchimp-for-wp-user-sync&debug-field-map&user_id=123
	private function show_debug_field_map_page() {
		/** @var Users $users */
		$users = mc4wp('user_sync.users');

		echo '<h2>Mailchimp User Sync: Debug user data</h2>';
		try {
			$user_id = (int) $_GET['user_id'];
			$user = $users->user($user_id);
			$subscriber = $users->user_to_subscriber($user);
			echo '<pre>';
			print_r( $subscriber );
			echo '</pre>';
		} catch (\Exception $e) {
			echo '<p>' , sprintf( __( 'No user found with ID %d.', 'mailchimp-sync'), $user_id ), '</p>';
		}

		echo '<p><a href="', admin_url('admin.php?page=mailchimp-for-wp-user-sync') ,'">', __( 'Go back', 'mailchimp-sync'), '</a></p>';
		exit;
	}

	/**
	 * @param $url
	 *
	 * @return string
	 */
	protected function asset_url( $url ) {
		return plugins_url( '/assets' . $url, MC4WP_USER_SYNC_PLUGIN_FILE );
	}

	/**
	 * @param $option_name
	 *
	 * @return string
	 */
	protected function name_attr( $option_name ) {

		if( substr( $option_name, -1 ) !== ']' ) {
			return 'mc4wp_user_sync[' . $option_name . ']';
		}

		return 'mc4wp_user_sync' . $option_name;
	}

	public function save_settings() {
		$redirect_url = admin_url('admin.php?page=mailchimp-for-wp-user-sync');
		$old_settings = $this->options;
		$new_settings = $_POST['mc4wp_user_sync'];
		$settings = array_merge( $old_settings, $new_settings );

		$settings['enabled'] = (int) $settings['enabled'];
		$list_changed = $old_settings['list'] !== $settings['list'];

		// empty field mappers if list changed
		if ( $list_changed  ) {
			$settings['field_map'] = array();
		}

		if( isset( $settings['field_map'] ) ) {
			foreach( $settings['field_map'] as $key => $mapper ) {
				if( empty( $mapper['user_field'] ) || empty( $mapper['mailchimp_field'] ) ) {
					unset( $settings['field_map'][ $key ] );
					continue;
				}

				// trim values
				$settings['field_map'][ $key ] = array(
					'user_field' => trim( $mapper['user_field'] ),
					'mailchimp_field' => trim( $mapper['mailchimp_field'] )
				);
			}
		} else {
			$settings['field_map'] = array();
		}

		$settings['webhook_enabled'] = (int) $settings['webhook_enabled'];
		if ($settings['webhook_enabled'] === 1 && ( $old_settings['webhook_enabled'] === 0 || $list_changed ) ) {
			$webhook_secret = $this->create_webhook( $settings['list'] );

			// if webhook creation failed, don't update setting
			if ( $webhook_secret !== '' ) {
				$settings['webhook_secret'] = $webhook_secret;
			} else {
				$settings['webhook_enabled'] = false;
				$settings['webhook_secret'] = '';
				$redirect_url = add_query_arg( array( 'webhook-created' => 0 ), $redirect_url );
			}
		}

		if ($old_settings['webhook_enabled'] === 1 && ( $settings['webhook_enabled'] === 0 || $list_changed ) ) {
			$this->delete_webhook( $old_settings['list'] );
		}

		// reschedule action if needed
        setup_schedule();

		update_option( 'mc4wp_user_sync', $settings, true );
		wp_redirect( $redirect_url );
		exit;
	}

	private function create_webhook( $list_id ) {
		/** @var \MC4WP_API_V3 $api */
		$api = mc4wp('api');
		$client = $api->get_client();
		$secret_key = wp_generate_password( 20, false, false );
		$webhook_url = get_home_url( null, Webhook_Listener::URL . '?' . urlencode( $secret_key ) );

		// create webhook in Mailchimp
		try {
			$resource = sprintf( 'lists/%s/webhooks', $list_id );
			$client->post( $resource, array(
				'url' => $webhook_url,
				'events' => (object)array(
					'profile' => true,
					'upemail' => true,
					'subscribe' => true,
					'unsubscribe' => true,
					'campaign' => false,
					'cleaned' => false,
				),
				'sources' => (object)array(
					'user' => true,
					'admin' => true,
					'api' => false,
				),
			) );
		} catch(\MC4WP_API_Exception $e) {
			mc4wp('log')->error(sprintf("User Sync: Error creating webhook. Mailchimp returned the following response: \n%s", $e));
			// Most likely the URL was rejected because Mailchimp could not resolve it (eg when on localhost)
			return '';
		}

		return $secret_key;
	}

	private function delete_webhook( $list_id ) {
		/** @var \MC4WP_API_V3 $api */
		$api = mc4wp('api');
		$client = $api->get_client();

		try {
			$resource = sprintf( '/lists/%s/webhooks', $list_id );
			$data     = $client->get( $resource );
		} catch (\MC4WP_API_Resource_Not_Found_Exception $e) {
			// list already deleted, incl. all of its webhooks
			return;
		}

		foreach ( $data->webhooks as $webhook ) {
			if ( strpos( $webhook->url, Webhook_Listener::URL ) !== false ) {
				$resource = sprintf( '/lists/%s/webhooks/%s', $list_id, $webhook->id );

				try {
					$client->delete( $resource );
				} catch (\MC4WP_API_Resource_Not_Found_Exception $e) {
					// webhook already deleted
				}
			}
		}

	}

	/**
	 * Returns a HEX color from a percentage (red to green)
	 *
	 * @param        $value
	 * @param int    $brightness
	 * @param int    $max
	 * @param int    $min
	 * @param string $thirdColorHex
	 *
	 * @return string
	 */
	protected function percentage_to_color( $value, $brightness = 255, $max = 100, $min = 0, $thirdColorHex = '00') {
		// Calculate first and second color (Inverse relationship)
		$first = (1-($value/$max))*$brightness;
		$second = ($value/$max)*$brightness;
		// Find the influence of the middle color (yellow if 1st and 2nd are red and green)
		$diff = abs($first-$second);
		$influence = ($brightness-$diff)/2;
		$first = intval($first + $influence);
		$second = intval($second + $influence);
		// Convert to HEX, format and return
		$firstHex = str_pad(dechex($first),2,0,STR_PAD_LEFT);
		$secondHex = str_pad(dechex($second),2,0,STR_PAD_LEFT);
		return $firstHex . $secondHex . $thirdColorHex ;
	}

	/**
	* Processes all queued background jobs
	*/
	public function process_queue() {
		do_action( 'mc4wp_user_sync_process_queue' );
	}


}
