<?php
/*
Plugin Name: WooCommerce Opayo Payment Suite
Plugin URI: http://woothemes.com/woocommerce
Description: Extends WooCommerce. Provides Opayo Form, Direct and Pi gateways for WooCommerce. Includes Opayo Reporting http://www.opayo.com.
Version: 5.9.1
Author: Andrew Benbow
Author URI: http://www.chromeorange.co.uk
WC requires at least: 3.3.0
WC tested up to: 7.0.0
Woo: 18599:6bc0cca47d0274d8ef9b164f6fbec1cc
*/

/*  Copyright 2013  Andrew Benbow (email : support@chromeorange.co.uk)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
    require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '6bc0cca47d0274d8ef9b164f6fbec1cc', '18599' );

/**
 * Defines
 */
define( 'SAGESUPPORTURL' , 'http://support.woocommerce.com/' );
define( 'SAGEDOCSURL' , 'https://docs.woocommerce.com/document/sagepay-form/');
define( 'SAGEPLUGINPATH', plugin_dir_path( __FILE__ ) );
define( 'SAGEPLUGINURL', plugin_dir_url( __FILE__ ) );
define( 'OPAYOPLUGINVERSION', '5.9.1' );

// Payment Method for order notes
define( 'OPAYOORDERNOTETITLEFORM', 'Opayo Form' );
define( 'OPAYOORDERNOTETITLESERVER', 'Opayo Server' );
define( 'OPAYOORDERNOTETITLEDIRECT', 'Opayo Direct' );
define( 'OPAYOORDERNOTETITLEPI', 'Opayo Pi' );

/**
 * Init SagePay Gateway after WooCommerce has loaded
 */
add_action( 'plugins_loaded', 'init_opayo_gateway', 0 );

/**
 * Localization
 */
$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-gateway-sagepay-form' );
load_textdomain( 'woocommerce-gateway-sagepay-form', WP_LANG_DIR . "/woocommerce-gateway-sagepay-form/woocommerce-gateway-sagepay-form-$locale.mo" );
load_plugin_textdomain( 'woocommerce-gateway-sagepay-form', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

function init_opayo_gateway() {

    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
    	return;
    }

    /**
     * Load common functions
     */
    include('classes/sagepay-common-functions-class.php');

    /**
     * Load common 3D Secure functions
     */
    include('classes/opayo-common-threeds-functions.php');

    /**
     * Load common Order Notes functions
     */
    // include('classes/opayo-common-order-notes-class.php');

    /**
     * add_sagepay_form_gateway function.
     *
     * @access public
     * @param mixed $methods
     * @return void
     */
	include('classes/form/sagepay-form-class.php');

    function add_opayo_form_gateway( $methods ) {
        $methods[] = 'WC_Gateway_Sagepay_Form';
        return $methods;
    }

    add_filter( 'woocommerce_payment_gateways', 'add_opayo_form_gateway' );

    // Get settings
    $opayo_form_settings = get_option( 'woocommerce_sagepayform_settings' );

    // If the site supports Gutenberg Blocks, support the Checkout block
    if( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) && isset( $opayo_form_settings['enabled'] ) && $opayo_form_settings['enabled'] == "yes" ) {
        $file = dirname(__FILE__) . "/classes/form/blocks/blocks-class.php";
        require_once( $file );
        Automattic\WooCommerce\Blocks\Payments\Integrations\Wc_OpayoForm_Blocks::register();
    }

    /**
     * add_sagepay_direct_gateway function.
     *
     * @access public
     * @param mixed $methods
     * @return void
     */
    include('classes/direct/opayo-direct-class.php');

    function add_opayo_direct_gateway( $methods ) {
        $methods[] = 'WC_Gateway_Sagepay_Direct';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_opayo_direct_gateway' );

    /**
     * add_sopayo_pi_gateway function.
     *
     * @access public
     * @param mixed $methods
     * @return void
     */
    include('classes/pi/opayo-pi-class.php');

    function add_opayo_pi_gateway( $methods ) {
        $methods[] = 'WC_Gateway_Opayo_Pi';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_opayo_pi_gateway' );

    /**
     * Opayo Dismissible Notices
     */
    // Enqueue Admin Scripts and CSS
    add_action( 'admin_enqueue_scripts', 'opayo_admin_scripts' );

    function opayo_admin_scripts() {

        // wp_enqueue_style( 'opayo-admin-fa', "//netdna.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.css" , array(), OPAYOPLUGINVERSION );
        wp_enqueue_style( 'opayo-admin-wp', SAGEPLUGINURL.'assets/css/admin-css.css' , array(), OPAYOPLUGINVERSION );

        wp_enqueue_script( 'sagepay-nag-dismiss', plugins_url( '/', __FILE__ ) . '/assets/js/dismiss.js', array( 'jquery' ), OPAYOPLUGINVERSION, true  );
    }
    
    // SSL Certificate Notice
    add_action( 'wp_ajax_dismiss_sagepaydirect_ssl_nag', 'dismiss_sagepaydirect_ssl_nag' );
    function dismiss_sagepaydirect_ssl_nag() {
        update_option( 'sagepaydirect-ssl-nag-dismissed', 1 );
    }

    // SagePay to Opayo rebrand notice
    add_action( 'wp_ajax_dismiss_opayo_rebrand_nag', 'dismiss_opayo_rebrand_nag' );
    function dismiss_opayo_rebrand_nag() {
        update_option( 'opayo-rebrand-nag-dismissed', 1 );
    }

    // Proctocol 4 is live notice
    add_action( 'wp_ajax_dismiss_sagepaydirect_protocol4_nag', 'dismiss_sagepaydirect_protocol4_nag' );
    function dismiss_sagepaydirect_protocol4_nag() {
        update_option( 'sagepaydirect-protocol4-nag-dismissed', 1 );
    }

    // Check 3D Secure settings for 3DS2.0 notice
    add_action( 'wp_ajax_dismiss_sagepaydirect_threeds2_nag', 'dismiss_sagepaydirect_threeds2_nag' );
    function dismiss_sagepaydirect_threeds2_nag() {
        update_option( 'sagepaydirect-threeds2-nag-dismissed', 1 );
    }

    // Add 'Capture Authorised Payment' to WooCommerce Order Actions
    add_filter( 'woocommerce_order_actions', 'opayo_woocommerce_order_actions' );

    // Opayo Reporting class
    if( function_exists( 'simplexml_load_string' ) ) {   

        include('classes/reporting/opayo-reporting.php'); 

        // Opayo reporting settings includes for admin
        if( is_admin() ) {
            include('classes/reporting/opayo-reporting-settings.php');

            // Only load metabox files if there is a username
            $options = get_option( 'woocommerce_opayo_reporting_options' );
            if( ( isset( $options['live_opayo_reporting_username'] ) && $options['live_opayo_reporting_username'] != '' ) || ( isset( $options['test_opayo_reporting_username'] )  && $options['test_opayo_reporting_username'] != '' ) ) {
                include('classes/reporting/opayo-reporting-metaboxes.php');
            }

        }

    }

    // Token removal 
    include('classes/sharedapi/opayo-remove-token-class.php');

    /**
     * [sage_woocommerce_order_actions description]
     * Add Capture option to the Order Actions dropdown.
     */
    function opayo_woocommerce_order_actions( $orderactions ) {
        global $post;
        $id     = $post->ID;
        $order  = new WC_Order( $id );

        // WC 3.0 compatibility
        $payment_method = $order->get_payment_method();

        // New method using an Order Status - Opayo Direct
        if ( in_array( $payment_method, array( 'sagepaydirect', ) ) && $order->get_status() === 'authorised' ) {
            $orderactions['opayo_process_payment'] = 'Capture Authorised Payment';
        }

        // New method using an Order Status - Opayo Pi
        if ( in_array( $payment_method, array( 'opayopi' ) ) && $order->get_status() === 'authorised' ) {
            $orderactions['opayopi_process_payment'] = 'Capture Authorised Payment';
        }

        // Old Method using post_meta
        $payment_status = get_post_meta( $id, '_SagePayDirectPaymentStatus', TRUE );
        if( isset($payment_status) && $payment_method === 'sagepaydirect' && ( $payment_status === 'AUTHENTICATED' || $payment_status === 'REGISTERED' ) ) {
            $orderactions['opayo_process_payment'] = 'Capture Authorised Payment';
        }

        // Void payment
        if ( in_array( $payment_method, array( 'sagepaydirect', 'opayopi' ) ) && $order->get_status() === 'processing' ) {
            $orderactions['opayo_process_void'] = 'Void Payment (can not be undone!)';
        }

        // Form Void payment
        if ( in_array( $payment_method, array( 'sagepayform' ) ) && $order->get_status() === 'processing' ) {
            $woocommerce_opayo_reporting_options   = get_option( 'woocommerce_opayo_reporting_options' );
            if( isset( $woocommerce_opayo_reporting_options['live_opayo_reporting_username'] ) || isset( $woocommerce_opayo_reporting_options['test_opayo_reporting_username'] ) ) {

                $opayo_reporting_output = get_post_meta( $id, '_opayo_reporting_output', TRUE );

                if( isset( $opayo_reporting_output['released'] ) || 
                    ( isset( $opayo_reporting_output['refunded'] ) && in_array( $opayo_reporting_output['refunded'], array( 'PARTIAL', 'YES' ) ) ) 
                ) {
                    // Do nothing
                } else {
                    $orderactions['opayo_form_void_order'] = 'Void Payment (can not be undone!)';
                }
                
            }
            
        }

        return array_unique( $orderactions);
    }

    add_action( 'init', 'opayo_register_fraud_order_status' );
    add_action( 'init', 'opayo_register_authorised_order_status' );

    /**
     * New order status for WooCommerce 2.2 or later
     *
     * @return void
     */
    function opayo_register_fraud_order_status() {
        register_post_status( 
            'wc-fraud-screen', array(
                'label'                     => _x( 'Fraud Screen', 'Order status', 'woocommerce-gateway-sagepay-form' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( 'Fraud Screening Required <span class="count">(%s)</span>', 'Fraud Screening Required <span class="count">(%s)</span>', 'woocommerce-gateway-sagepay-form' )
            ) );
    }

    /**
     * [opayo_register_authorised_order_status description]
     * @return [type] [description]
     */
    function opayo_register_authorised_order_status() {
        register_post_status( 
            'wc-authorised', array(
                'label'                     => _x( 'Authorised Payments', 'Order status', 'woocommerce-gateway-sagepay-form' ),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( 'Authorised, not captured <span class="count">(%s)</span>', 'Authorised, not captured <span class="count">(%s)</span>', 'woocommerce-gateway-sagepay-form' )
            ) );
    }

    add_filter( 'wc_order_statuses', 'opayo_order_statuses' );
    /**
     * Set wc-fraud-screen in WooCommerce order statuses.
     *
     * @param  array $order_statuses
     * @return array
     */
    function opayo_order_statuses( $order_statuses ) {
        $order_statuses['wc-authorised']    = _x( 'Authorised', 'Order status', 'woocommerce-gateway-sagepay-form' );
        $order_statuses['wc-fraud-screen']  = _x( 'Fraud Screen', 'Order status', 'woocommerce-gateway-sagepay-form' );

        return $order_statuses;
    }

    add_action( 'woocommerce_email_customer_details', 'opayo_woocommerce_email_customer_details', 99, 2 );
    function opayo_woocommerce_email_customer_details ( $order, $sent_to_admin ) {

       $payment_method = $order->get_payment_method();

        // Get settings
        $woocommerce_sagepaydirect_settings = get_option( 'woocommerce_sagepaydirect_settings' );

        // sagepaytransinfo
        $sagepaytransinfo   =  'no';
        if( isset($woocommerce_sagepaydirect_settings['sagepaytransinfo']) ) {
            $sagepaytransinfo   = $woocommerce_sagepaydirect_settings['sagepaytransinfo'];
        }

        if ( $payment_method === 'sagepaydirect' && $sagepaytransinfo === 'yes' ) {

            $sageresult = $order->get_meta( '_sageresult', true );

            $sageresult_output = '';

            if( isset( $sageresult ) && $sageresult !== '' && $sent_to_admin ) {

                $sageresult_output = '<h3>Opayo Transaction Details</h3>';

                foreach ( $sageresult as $key => $value ) {
                    $sageresult_output .= $key . ' : ' . $value . "\r\n" . "<br />";
                }

                echo $sageresult_output;

            }

        }

    }

}

    register_deactivation_hook( __FILE__, 'opayo_deactivate' ); 

    /**
     * [opayo_deactivate description]
     * @return [type] [description]
     */
    function opayo_deactivate() {
        // Remove Cron Tasks
        $timestamp = wp_next_scheduled( 'woocommerce_opayo_reporting_get_transaction_report' );
        wp_unschedule_event( $timestamp, 'woocommerce_opayo_reporting_get_transaction_report' );

        // Remove Scheduled Actions
        WC()->queue()->cancel_all( 'woocommerce_opayo_reporting_get_transaction_report' );
    }

    /**
     * Load Admin Class
     * Used for plugin links, seems to break if added to an include file
     * so it's got it's own class for now.
     */
    class WC_opayo_admin {

        public function __construct() {
            add_action( 'init', array( __CLASS__, 'check_version' ), 5 );

            add_action( 'admin_init', array( __CLASS__, 'include_admin_files' ) );
        }

        public static function include_admin_files() {

            // Add Opayo Form admin notices
            include('classes/form/sagepay-form-admin-notice-class.php');

            // Add Opayo Reporting admin notices
            include('classes/reporting/opayo-reporting-admin-notice-class.php');

            // Add Opayo/SagePay Name change notice
            include('classes/opayo/class-opayo-admin-notice.php');

            // Opayo Direct Order Actions
            include('classes/direct/opayo-order-actions.php');

            // Add System Status additions
            include('classes/systemstatus/opayo-system-status-class.php');
            
        }

        /**
         * Check WooCommerce version and run the updater is required.
         *
         * This check is done on all requests and runs if the versions do not match.
         */
        public static function check_version() {
            $woocommerce_sagepaydirect_version = get_option( 'woocommerce_sagepaydirect_version' );

            if ( version_compare( $woocommerce_sagepaydirect_version, '4.7.0', '<' ) ) {
                self::activate();
            }
        }

        /**
         * [activate description]
         * @return [type] [description]
         */
        public static function activate() {

            // Get settings
            $settings = get_option( 'woocommerce_sagepaydirect_settings' );
                
            // Reset Protocol to 3.00.
            if( isset( $settings['enabled'] ) && $settings['enabled'] == "yes" && isset( $settings['status'] ) && $settings['status'] == 'live' ) {
                $settings['vpsprotocol'] = '3.00';
                update_option( 'woocommerce_sagepaydirect_settings', $settings );
            }

            update_option( 'woocommerce_sagepaydirect_version', OPAYOPLUGINVERSION );

            // Redirect to Direct settings.
            wp_redirect( get_admin_url('admin.php?page=wc-settings&tab=checkout&section=sagepaydirect' ) );
            exit;
                        
        }

        /**
         * [deactivate description]
         * @return [type] [description]
         */
        public static function deactivate() {
            // empty
        }

    } // WC_opayo_admin

    if ( is_admin() ) {
        // Load the admin class
        $GLOBALS['WC_opayo_admin'] = new WC_opayo_admin();

    }

    /**
     * [opayo_die_output description]
     * @param  [type] $message [description]
     * @param  [type] $title   [description]
     * @param  [type] $args    [description]
     * @return [type]          [description]
     */
    function opayo_die_output( $message, $title, $args ) {

        list( $message, $title, $parsed_args ) = _wp_die_process_input( $message, $title, $args );
?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $parsed_args['charset']; ?>" />
            <meta name="viewport" content="width=device-width">
            <title><?php echo $title; ?></title>
            <style type="text/css">
                html {
                    background: #FFFFFF;
                }
                body {
                    background: #fff;
                    color: #444;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                    margin: 2em auto;
                    padding: 1em 2em;
                    max-width: 700px;
                    -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
                    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
                }
            </style>
        </head>

        <body id="error-page">
            <?php echo $message; ?>
        </body>

        </html>
        <?php
        if ( $parsed_args['exit'] ) {
            die();
        }

    }