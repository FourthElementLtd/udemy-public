<?php
namespace MC4WP\PostCampaign;

use Exception;

class Gutenberg_Editor {

	/**
	 * @var string
	 */
	public $file;

	/**
	 * Inject the dependencies
	 *
	 * @param string                  $file
	 */
	public function __construct( string $file ) {
		$this->file = $file;
	}

	/**
	 * Hooks
	 */
	public function hook() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_mc4wp_create_campaign_of_post', array( $this, 'ajax_create_campaign' ) );
		add_action( 'init', array( $this, 'register_mc4wp_meta_data' ) );
	}

	/**
	 * Register all the scripts which belong to the gutenberg editor
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		// Only enqueue script on the "edit post" screen (because Block Editor is also used for Widgets now)
		global $pagenow;
		if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) === false || get_post_type() !== 'post' ) {
			return;
		}

		wp_enqueue_script(
			'mc4wp-post-campaign',
			plugins_url( SCRIPT_DEBUG ? '/dist/js/admin/post-campaign.js' : '/dist/js/admin/post-campaign.min.js', $this->file ),
			array( 'wp-plugins', 'wp-edit-post', 'wp-components' ),
			MC4WP_PREMIUM_VERSION
		);
	}

	/**
	 * Create a campaign.
	 *
	 * @return void
	 * @throws Exception in really rare cases
	 */
	public function ajax_create_campaign() {

		if ( ! current_user_can( 'edit_posts' ) ) {

			wp_send_json_error();

			return;
		}

		$post_id = intval( $_GET['post_id'] );

		$post = get_post( $post_id );

		try {
			$template_file           = sprintf( '%s/template.html', dirname( $this->file ) );
			$post_mailchimp_campaign = new Post_Mailchimp_Campaign( mc4wp_get_api_v3(), $template_file );
			$campaign                = $post_mailchimp_campaign->post_campaign( $post );

			wp_send_json_success(
				array(
				    'web_id' => $campaign->web_id,
					'id' => $campaign->id,
				)
			);
		} catch ( Exception $e ) {

			wp_send_json_error( array( 'error' => $e ) );

			mc4wp( 'log' )->warning(
				sprintf( 'Post to campaign exception: %s in %s : %s', $e->getMessage(), $e->getFile(), $e->getLine() )
			);
		}

	}

	/**
	 * Add meta data to the api.
	 *
	 * @return void
	 */
	public function register_mc4wp_meta_data() {
		register_post_meta(
			'post',
			Post_Mailchimp_Campaign::META_KEY,
			array(
				'single'       => true,
				'type'         => 'object',
				'show_in_rest' => array(
					'schema' => array(
						'type'       => 'object',
						'properties' => array(
							'id'     => array(
								'type' => 'string',
							),
							'web_id' => array(
								'type' => 'string',
							),
						),
					),
				),
			)
		);
	}
}
