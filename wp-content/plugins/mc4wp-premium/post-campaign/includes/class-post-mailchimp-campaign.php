<?php
namespace MC4WP\PostCampaign;

use Exception;
use InvalidArgumentException;
use MC4WP_API_Exception;
use MC4WP_API_Resource_Not_Found_Exception;
use MC4WP_API_V3;
use MC4WP_MailChimp;
use stdClass;
use WP_Post;

/**
 * Class Ajax_Mailchimp_Campaign
 *
 * @package MC4WP\PostCampaign
 */
class Post_Mailchimp_Campaign {

    const META_KEY = 'mc4wp_mailchimp_campaign';

    const OPTION_KEY = 'mc4wp_mailchimp_template_id';

    /**
     * @var string
     */
    private $template_file;
    /**
     * @var MC4WP_API_V3
     */
    private $api;

    /**
     * Inject the dependencies
     *
     * @param MC4WP_API_V3 $api
     * @param string       $template_file
     */
    public function __construct( MC4WP_API_V3 $api, string $template_file ) {

        if ( ! file_exists( $template_file ) ) {
            throw new InvalidArgumentException( sprintf( "File %s doesn't exist, expected a existing file", $template_file ) );
        }

        $this->template_file = $template_file;
        $this->api           = $api;
    }

    /**
     * @param WP_Post $post
     *
     * @return object
     * @throws MC4WP_API_Exception
     * @throws Exception
     */
    public function post_campaign( WP_Post $post ) {
        $campaign = get_post_meta( $post->ID, self::META_KEY, true );

        if ( empty( $campaign ) ) {
            $campaign = $this->create_new_campaign_of_post( $post );
        }

        $template_id = $this->get_template_id();

        // prepare post content for campaign
        $content = sprintf('<h1>%s</h1>', $post->post_title );
        $content .= has_blocks( $post->post_content ) ? do_blocks( $post->post_content ) : wpautop( $post->post_content );
        $content = strip_shortcodes( $content );

        /**
         * Filters the campaign content.
         *
         * @param string $content
         * @param \WP_Post $post
         */
        $content = apply_filters( 'mc4wp_post_campaign_content', $content, $post );

        try {
            $this->api->update_campaign_content(
                $campaign->id,
                array(
                    'template' => array(
                        'id'       => $template_id,
                        'sections' => array(
                            'std_content00' => $content,
                        ),
                    ),
                )
            );
        } catch ( MC4WP_API_Resource_Not_Found_Exception $e ) {
            $deleted = delete_post_meta( $post->ID, self::META_KEY );

            if ( ! $deleted ) {
                throw new Exception( sprintf( 'Could not delete post meta for post %s with key %s', $post->ID, self::META_KEY ) );
            }

            $campaign = $this->post_campaign( $post );
        }

        return $campaign;
    }

    /**
     * @return int
     * @throws MC4WP_API_Exception
     */
    private function get_template_id() {
        $template_id = get_option( self::OPTION_KEY, null );

        if ( null === $template_id ) {
            $template    = $this->create_template();
            $template_id = $template->id;
        }

        return (int) $template_id;
    }


    /**
     * Create a new campaign op the post
     *
     * @param WP_Post $post
     *
     * @throws MC4WP_API_Exception
     * @throws Exception
     *
     * @return object
     */
    private function create_new_campaign_of_post( WP_Post $post ) {
        $body_parameter = array(
            'type'     => 'regular',
            'settings' => array(
                'subject_line' => $post->post_title,
                'title'        => $post->post_title,
                'template_id'  => $this->get_template_id(),
            ),
        );

        $lists = ( new MC4WP_MailChimp() )->get_lists();

        if ( is_array( $lists ) && count( $lists ) === 1 ) {
            $list = reset( $lists );

            $body_parameter['recipients'] = array(
                'list_id' => $list->id,
            );
        }

        $campaign = $this->api->add_campaign( $body_parameter );

        // Cache the meta data
        $meta_value = (object) array(
            'id'     => (string) $campaign->id,
            'web_id' => (string) $campaign->web_id,
        );

        $meta_id = update_post_meta( $post->ID, self::META_KEY, $meta_value );

        if ( false === $meta_id ) {
            throw new Exception(
                sprintf( '%s meta key could not be saved.', self::META_KEY )
            );
        }

        return $meta_value;
    }

    /**
     * Create a template in mailchimp
     *
     * @throws MC4WP_API_Exception
     * @throws Exception
     *
     * @return stdClass
     */
    private function create_template() {
        $template = file_get_contents( $this->template_file );

        if ( false === $template ) {
            throw new Exception( error_get_last() );
        }

        $template = $this->api->add_template(
            array(
                'name' => get_bloginfo( 'name' ),
                'html' => $template,
            )
        );

        $option = add_option( self::OPTION_KEY, $template->id, '', false );

        if ( false === $option ) {
            throw new Exception( sprintf( 'The option could not be saved for template %s.', $template->id ) );
        }

        return $template;
    }
}
