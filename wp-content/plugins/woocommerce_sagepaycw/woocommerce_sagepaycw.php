<?php

/**
 * Plugin Name: WooCommerce SagePayCw
 * Plugin URI: http://www.customweb.ch
 * Description: This plugin adds the SagePayCw payment gateway to your WooCommerce.
 * Version: 5.0.56
 * Author: customweb GmbH
 * Author URI: http://www.customweb.ch
 */

/**
 *  * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2018 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *
 */

// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
	echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
	exit();
}

// Load Language Files
load_plugin_textdomain('woocommerce_sagepaycw', false, basename(dirname(__FILE__)) . '/translations');

require_once dirname(__FILE__) . '/lib/loader.php';
require_once 'classes/SagePayCw/Util.php';
require_once 'SagePayCw/TranslationResolver.php';

require_once 'SagePayCw/Util.php';
require_once 'Customweb/Payment/ExternalCheckout/IContext.php';
require_once 'Customweb/Core/Util/Rand.php';
require_once 'SagePayCw/LoggingListener.php';
require_once 'SagePayCw/Cron.php';
require_once 'Customweb/Core/Exception/CastException.php';
require_once 'SagePayCw/ContextRequest.php';
require_once 'SagePayCw/ConfigurationAdapter.php';
require_once 'SagePayCw/Dispatcher.php';
require_once 'SagePayCw/Entity/ExternalCheckoutContext.php';
require_once 'Customweb/Payment/ExternalCheckout/IProviderService.php';
require_once 'Customweb/Core/Logger/Factory.php';



if (is_admin()) {
	// Get all admin functionality
	require_once SagePayCw_Util::getBasePath() . '/admin.php';
}
/**
 * Register plugin activation hook
 */
register_activation_hook(__FILE__, array(
	'SagePayCw_Util',
	'installPlugin' 
));

/**
 * Register plugin deactivation hook
 */
register_deactivation_hook(__FILE__, array(
	'SagePayCw_Util',
	'uninstallPlugin' 
));

/**
 * Add the payment methods with a filter
 */
add_filter('woocommerce_payment_gateways', array(
	'SagePayCw_Util',
	'addPaymentMethods' 
));

if (!is_admin()) {
	function woocommerce_sagepaycw_add_frontend_scripts(){
		if(is_cart() || is_checkout()){
			wp_register_style('woocommerce_sagepaycw_frontend_styles', plugins_url('resources/css/frontend.css', __FILE__));
			wp_enqueue_style('woocommerce_sagepaycw_frontend_styles');
			
			wp_register_script('sagepaycw_frontend_script', plugins_url('resources/js/frontend.js', __FILE__), array(
				'jquery' 
			));
			wp_enqueue_script('sagepaycw_frontend_script');
			wp_localize_script('sagepaycw_frontend_script', 'woocommerce_sagepaycw_ajax', 
					array(
						'ajax_url' => admin_url('admin-ajax.php') 
					));
		}
	}
	add_action('wp_enqueue_scripts', 'woocommerce_sagepaycw_add_frontend_scripts');
}

/**
 * Adds error message during checkout to the top of the page
 * WP action: wp_head
 */
function woocommerce_sagepaycw_add_errors(){
	if (!function_exists('is_ajax') || is_ajax()) {
		return;
	}
	if (isset($_GET['sagepaycwftid']) && isset($_GET['sagepaycwftt'])) {
		$dbTransaction = SagePayCw_Util::getTransactionById($_GET['sagepaycwftid']);
		$validateHash = SagePayCw_Util::computeTransactionValidateHash($dbTransaction);
		if ($validateHash == $_GET['sagepaycwftt']) {
			wc_add_notice((string) current($dbTransaction->getTransactionObject()->getErrorMessages()), 'error');
		}
	}
	if (isset($_GET['sagepaycwove'])) {
		wc_add_notice((string) $_GET['sagepaycwove'], 'error');
	}
	
}
add_action('woocommerce_before_checkout_form', 'woocommerce_sagepaycw_add_errors', 8);
add_action('woocommerce_before_cart', 'woocommerce_sagepaycw_add_errors', 8);

/**
 * Add action to modify billing/shipping form during checkout
 */
add_action('woocommerce_before_checkout_billing_form', array(
	'SagePayCw_Util',
	'actionBeforeCheckoutBillingForm' 
));
add_action('woocommerce_before_checkout_shipping_form', array(
	'SagePayCw_Util',
	'actionBeforeCheckoutShippingForm' 
));

/**
 * Add Cron hooks and actions
 */
function createSagePayCwCronInterval($schedules){
	$schedules['SagePayCwCronInterval'] = array(
		'interval' => 120,
		'display' => __('SagePayCw Interval', 'woocommerce_sagepaycw') 
	);
	return $schedules;
}

function createSagePayCwCron(){
	$timestamp = wp_next_scheduled('SagePayCwCron');
	if ($timestamp == false) {
		wp_schedule_event(time() + 120, 'SagePayCwCronInterval', 'SagePayCwCron');
	}
}

function deleteSagePayCwCron(){
	wp_clear_scheduled_hook('SagePayCwCron');
}

function runSagePayCwCron(){
	SagePayCw_Cron::run();
}

//Cron Functions to pull update
register_activation_hook(__FILE__, 'createSagePayCwCron');
register_deactivation_hook(__FILE__, 'deleteSagePayCwCron');

add_filter('cron_schedules', 'createSagePayCwCronInterval');
add_action('SagePayCwCron', 'runSagePayCwCron');

/**
 * Action to add payment information to order confirmation page, and email
 */
add_action('woocommerce_thankyou', array(
	'SagePayCw_Util',
	'thankYouPageHtml' 
));
add_action('woocommerce_email_before_order_table', array(
	'SagePayCw_Util',
	'orderEmailHtml'
), 10, 3);


/**
 * Updates the payment fields of the payment methods
 * WP action: wp_ajax_woocommerce_sagepaycw_update_payment_form
 * WP action: wp_ajax_nopriv_woocommerce_sagepaycw_update_payment_form
 */
function woocommerce_sagepaycw_ajax_update_payment_form(){
	if (!isset($_POST['payment_method'])) {
		die();
	}
	$length = strlen('SagePayCw');
	if (substr($_POST['payment_method'], 0, $length) != 'SagePayCw') {
		die();
	}
	try {
		$paymentMethod = SagePayCw_Util::getPaymentMehtodInstance($_POST['payment_method']);
		$paymentMethod->payment_fields();
		die();
	}
	catch (Exception $e) {
		die();
	}
}
add_action('wp_ajax_woocommerce_sagepaycw_update_payment_form', 'woocommerce_sagepaycw_ajax_update_payment_form');
add_action('wp_ajax_nopriv_woocommerce_sagepaycw_update_payment_form', 'woocommerce_sagepaycw_ajax_update_payment_form');

/**
 * Form fields validation through ajax call -> prevents creating an order if validation fails
 * WP action: wp_ajax_woocommerce_sagepaycw_validate_payment_form
 * WP action: wp_ajax_nopriv_woocommerce_sagepaycw_validate_payment_form
 */
function woocommerce_sagepaycw_validate_payment_form(){
	$result = array(
		'result' => 'failure',
		'message' => '<ul class="woocommerce-error"><li>' . __('Invalid Request', 'woocommercesagepaycw') .
		'</li></ul>'
	);
	if (!isset($_POST['payment_method'])) {
		echo json_encode($result);
		die();
	}
	$length = strlen('SagePayCw');
	if (substr($_POST['payment_method'], 0, $length) != 'SagePayCw') {
		echo json_encode($result);
		die();
	}
	try {
		if ( !defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}
		$paymentMethod = SagePayCw_Util::getPaymentMehtodInstance($_POST['payment_method']);
		$paymentMethod->validate(SagePayCw_ContextRequest::getInstance()->getParameters());
		$result = array(
			'result' => 'success');
		echo json_encode($result);
		die();
	}
	catch (Exception $e) {
		$result = array(
			'result' => 'failure',
			'message' => '<ul class="woocommerce-error"><li>' . $e->getMessage() .
			'</li></ul>'
		);
		echo json_encode($result);
		die();
	}
}
add_action('wp_ajax_woocommerce_sagepaycw_validate_payment_form', 'woocommerce_sagepaycw_validate_payment_form');
add_action('wp_ajax_nopriv_woocommerce_sagepaycw_validate_payment_form', 'woocommerce_sagepaycw_validate_payment_form');

//Fix to avoid multiple cart calculations
function woocommerce_sagepaycw_before_calculate_totals($cart){
	$cart->disableValidationCw = true;
	return;
}
add_action('woocommerce_before_calculate_totals', 'woocommerce_sagepaycw_before_calculate_totals');


function woocommerce_sagepaycw_after_calculate_totals($cart){
	
	if (defined( 'WOOCOMMERCE_CHECKOUT' ) || defined( 'WOOCOMMERCE_CART' )||  is_checkout() || is_cart()) {
		//Fix to avoid multiple cart calculations, only if total really was computed
		$cart->totalCalculatedCw = true;
	}
	$cart->disableValidationCw = false;
	return;
}
add_action('woocommerce_after_calculate_totals', 'woocommerce_sagepaycw_after_calculate_totals');





//Fix to not send cancel subscription mail (if initial paymet fails)
function woocommerce_sagepaycw_unhook_subscription_cancel($email){
	remove_action('cancelled_subscription_notification', array(
		$email->emails['WCS_Email_Cancelled_Subscription'],
		'trigger' 
	));
}


/**
 * Email hooks
 * This hooks ensure the on_hold/processing/completed email are only sent once.
 * If we move the state to uncertain and back.
 */
function woocommerce_sagepaycw_on_hold_email($enabled, $order){
	return woocommerce_sagepaycw_check_email($enabled, $order, 'woocommerce_sagepaycw_on_hold_email');
}
add_filter('woocommerce_email_enabled_customer_on_hold_order', 'woocommerce_sagepaycw_on_hold_email', 5000, 2);

function woocommerce_sagepaycw_processing_email($enabled, $order){
	return woocommerce_sagepaycw_check_email($enabled, $order, 'woocommerce_sagepaycw_processing_email');
}
add_filter('woocommerce_email_enabled_customer_processing_order', 'woocommerce_sagepaycw_processing_email', 5000, 2);


function woocommerce_sagepaycw_completed_email($enabled, $order){
	return woocommerce_sagepaycw_check_email($enabled, $order, 'woocommerce_sagepaycw_completed_email');
}
add_filter('woocommerce_email_enabled_customer_completed_order', 'woocommerce_sagepaycw_completed_email', 5000, 2);


function woocommerce_sagepaycw_check_email($enabled, $order, $metaKey){
	if (!($order instanceof WC_Order)) {
		return $enabled;
	}
	if (isset($GLOBALS['woocommerce_sagepaycw__resend_email']) && $GLOBALS['woocommerce_sagepaycw__resend_email']) {
		return $enabled;
	}
	if(!isset($GLOBALS['woocommerce_sagepaycw__status_change']) ||  !$GLOBALS['woocommerce_sagepaycw__status_change']){
		return $enabled;
	}
	$orderId= $order->get_id();
	$alreadySent = get_post_meta($orderId, $metaKey, true);
	if(!empty($alreadySent)){
		return false;
	}
	if($enabled){
		update_post_meta($orderId, $metaKey, true);
	}
	return $enabled;
}

function woocommerce_sagepaycw_before_resend_email($order){
	$GLOBALS['woocommerce_sagepaycw__resend_email'] = true;
}

function woocommerce_sagepaycw_after_resend_email($order, $email){
	unset($GLOBALS['woocommerce_sagepaycw__resend_email']);
}
add_filter('woocommerce_before_resend_order_emails', 'woocommerce_sagepaycw_before_resend_email', 10, 1);
add_filter('woocommerce_after_resend_order_emails', 'woocommerce_sagepaycw_after_resend_email', 10, 2);
	


/**
 * Avoid redirects if our page is called, fixes some problem introduced by other plugins
 * WP filter: redirect_canonical
 *
 * @param string $redirectUrl
 * @param string $requestUrl
 * @return false|string
 */
function woocommerce_sagepaycw_redirect_canonical($redirectUrl, $requestUrl){
	if (woocommerce_sagepaycw_is_plugin_page()) {
		return false;
	}
	return $redirectUrl;
}
add_filter('redirect_canonical', 'woocommerce_sagepaycw_redirect_canonical', 10, 2);

/**
 * Removes our page/post, from appearing in breadcrumbs or navigation
 * WP filter: get_pages
 *
 * @param array $pages
 * @return array
 */
function woocommerce_sagepaycw_get_pages($pages){
	$pageFound = -1;
	$pageId = get_option('woocommerce_sagepaycw_page');
	
	foreach ($pages as $key => $post) {
		$postId = $post->ID;
		if ($postId == $pageId) {
			$pageFound = $key;
			break;
		}
	}
	if ($pageFound != -1) {
		unset($pages[$pageFound]);
	}
	return $pages;
}
add_filter('get_pages', 'woocommerce_sagepaycw_get_pages', 10, 2);

/**
 * Replaces our shortcode string with the actual content
 * WP shortcode: woocommerce_sagepaycw
 */
function woocommerce_sagepaycw_shortcode_handling(){
	if (isset($GLOBALS['woo_sagepaycwContent'])) {
		return $GLOBALS['woo_sagepaycwContent'];
	}
}
add_shortcode('woocommerce_sagepaycw', 'woocommerce_sagepaycw_shortcode_handling');

/**
 * Initialies our context request, before wordpress messes up the parameters with it's magic quotes functions
 * WP action: plugins_loaded
 */
function woocommerce_sagepaycw_loaded(){
	SagePayCw_ContextRequest::getInstance();
	Customweb_Core_Logger_Factory::addListener(new SagePayCw_LoggingListener());
}
//We need to execute this early as other plugins modify the $_GET and $_POST variables in this step.
add_action('plugins_loaded', 'woocommerce_sagepaycw_loaded', -5);

/**
 * Filter for the get_locale function, this is activated before authorizing a transaction.
 * 
 * 
 * WP Filter : locale
 */
function woocommerce_sagepaycw_locale($locale){
	if(isset($GLOBALS['woo_sagepaycwAuthorizeLanguage'])){
		
		$languages = get_available_languages();
		
		$possible = null;
		foreach($languages as $language){
			if(strncmp($language, $GLOBALS['woo_sagepaycwAuthorizeLanguage'], strlen($GLOBALS['woo_sagepaycwAuthorizeLanguage'])) === 0){
				return $language;
			}
			if(stripos($GLOBALS['woo_sagepaycwAuthorizeLanguage'], $language) !== false){
				$possible = $language;
			}
		}
		if(!empty($possible)){
			return $possible;
		}
		//Could not match an official IETF code to a language, return default
	}
	return $locale;
}

/**
 * Generates our content, handles request to our enpoint,
 * writes possible content to $GLOBALS['woo_sagepaycwContent']
 * sets default title for our pages in $GLOBALS['woo_sagepaycwTitle']
 * WP action: wp_loaded -> most of wordpress is loaded and headers are not yet sent
 */
function woocommerce_sagepaycw_init(){
	if (woocommerce_sagepaycw_is_plugin_page()) {

		if (isset($_REQUEST['woo-sagepaycw-lang'])) {
			do_action( 'wpml_switch_language', $_REQUEST['woo-sagepaycw-lang']);
		}
		$dispatcher = new SagePayCw_Dispatcher();
		$GLOBALS['woo_sagepaycwTitle'] = __('Payment', 'woocommerce_sagepaycw');
		try {
			$result = $dispatcher->dispatch();
		}
		catch (Exception $e) {
			$result = '<strong>' . $e->getMessage() . '</strong> <br />';
		}
		$GLOBALS['woo_sagepaycwContent'] = $result;
	}
}
add_action('wp_loaded', 'woocommerce_sagepaycw_init', 50);

/**
 * Echos additional JS and CSS file urls during the html head generation
 * WP action: wp_head -> is triggered while wordpress is echoing the html head
 */
function woocommerce_sagepaycw_additional_files_header(){
	if (isset($GLOBALS['woo_sagepaycwCSS'])) {
		echo $GLOBALS['woo_sagepaycwCSS'];
	}
	if (isset($GLOBALS['woo_sagepaycwJS'])) {
		echo $GLOBALS['woo_sagepaycwJS'];
	}
}
add_action('wp_head', 'woocommerce_sagepaycw_additional_files_header');

/**
 * Replaces the title of our page, if it is set in $GLOBALS['woo_sagepaycwTitle']
 * WP filter: the_title
 *
 * @param string $title
 * @param int $id
 * @return string
 */
function woocommerce_sagepaycw_get_page_title($title, $id = null){
	if(woocommerce_sagepaycw_check_pageid($id)){
		if (isset($GLOBALS['woo_sagepaycwTitle'])) {
			return $GLOBALS['woo_sagepaycwTitle'];
		}
	}
	return $title;
}
add_filter('the_title', 'woocommerce_sagepaycw_get_page_title', 10, 2);

/**
 * Never do unforce SSL redirect on our page
 * WP Filter : woocommerce_unforce_ssl_checkout
 */
function woocommerce_sagepaycw_unforce_ssl_checkout($unforce){
	if (woocommerce_sagepaycw_is_plugin_page()) {
		return false;
	}
	return $unforce;
}
add_filter('woocommerce_unforce_ssl_checkout', 'woocommerce_sagepaycw_unforce_ssl_checkout', 10, 2);

/**
 * Remove get variables to avoid wordpress redirecting to 404,
 * if our page is called and
 * WP Filter : request
 */
function woocommerce_sagepaycw_alter_the_query($request){
	if (woocommerce_sagepaycw_is_plugin_page()) {
		unset($request['year']);
		unset($request['day']);
		unset($request['w']);
		unset($request['m']);
		unset($request['name']);
		unset($request['hour']);
		unset($request['minute']);
		unset($request['second']);
		unset($request['order']);
		unset($request['term']);
		unset($request['error']);
	}
	return $request;
}
add_filter('request', 'woocommerce_sagepaycw_alter_the_query');


/**
 * We define our sites as checkout, so we are not unforced from SSL
 *
 * @param boolean $isCheckout
 * @return boolean
 */
function woocommerce_sagepaycw_is_checkout($isCheckout){
	
	if (woocommerce_sagepaycw_is_plugin_page()) {
		return true;
	}
	return $isCheckout;
}
add_filter('woocommerce_is_checkout', 'woocommerce_sagepaycw_is_checkout', 10, 2);

/**
 * This function returns true if the page id ($pid) belongs to the plugin.
 *
 * @param integer $pid
 * @return boolean
 */
function woocommerce_sagepaycw_check_pageid($pid){
	if ($pid == get_option('woocommerce_sagepaycw_page')) {
		return true;
	}
	return woocommerce_sagepaycw_check_page_translations($pid);
}

/**
 * This function returns true if the page id ($pid) belongs to the plugin page endpoint.
 * If no page id is provided, the function determines it with the
 * woocommerce_sagepaycw_get_page_id function
 *
 * @return boolean
 */
function woocommerce_sagepaycw_is_plugin_page(){
	
	if((get_queried_object() !== null && !is_singular(array('page')))) {
		return false;
	}
	if(is_archive()){
		return false;
	}	
	if (is_admin()) {
		return false;
	}
	if(wp_doing_ajax()){
		return false;
	}
	if ( function_exists( 'ux_builder_is_active' ) && ux_builder_is_active() ) {
		//UX Builder compatibility
		return false;
	}
	if(defined('FACETWP_VERSION')){
		//WPFacet compatibility
		$getKeys = array_keys($_GET);
		foreach($getKeys as $key){
			if(strncmp($key, 'fwp_', strlen('fwp_')) === 0){
				return false;
			}
		}
	}	
	$isPage = apply_filters('woocommerce_sagepaycw_is_plugin_page', true);
	if(!$isPage){
		return false;
	}	
	$pid = woocommerce_sagepaycw_get_page_id();
	if ($pid == get_option('woocommerce_sagepaycw_page')) {
		return true;
	}
	return woocommerce_sagepaycw_check_page_translations($pid);
}

function woocommerce_sagepaycw_check_page_translations($pid){
	$page = get_option('woocommerce_sagepaycw_page');
	if (defined('ICL_SITEPRESS_VERSION')) {
		$meta = get_post_meta($pid, '_icl_lang_duplicate_of', true);
		if ($meta != '' && $meta == $page) {
			return true;
		}
	}
	if(defined('POLYLANG_VERSION')) {
		$terms = wp_get_post_terms($pid, 'post_translations');
		if($terms && !is_wp_error($terms)) {
			$current = current($terms);
			$languages = unserialize( $current->description);
			if($languages && is_array($languages)){
				foreach($languages as $key => $postId){
					if($key != 'snyc' && $postId == $page){
						return true;
					}
				}
			}
		}
	}
	return false;
}

/**
 * Returns the current page id,
 * Uses the wordpress function url_to_postid
 *
 * @return number
 */
function woocommerce_sagepaycw_get_page_id(){
	
	if(isset($GLOBALS['woocommerce_sagepaycw_page_id_running']) && $GLOBALS['woocommerce_sagepaycw_page_id_running']){
		//There is the possibility, that we get an infinite loop with other modules adding filters to the url_to_post_id
		//This is a simple check, if this function is called recursivly. If we detect this, we simply return 0
		return 0;
	}
	$GLOBALS['woocommerce_sagepaycw_page_id_running'] = true;
	
	/**
	 * WPML (Version < 3.3) has problems with calling ur_to_postid during this stage.
	 * It looks like Version 3.5 introduced the issue again.
	 * We remove their filter for our call and re add it afterwards. (WMPL > 3.3)
	 * We need to backup and restore the registred filters. (WPML Versions <= 3.2)
	 */
	$pid = 0;
	
	$serverRequestURI = $_SERVER['REQUEST_URI'];
	if(isset($_POST['page_id']) && !isset($_GET['page_id'])){
		if(strpos($serverRequestURI, '?') !== false){
			$serverRequestURI .= "&page_id=".$_POST['page_id'];
		}
		else{
			$serverRequestURI .= "?page_id=".$_POST['page_id'];
		}
	}
	elseif(isset($_POST['p']) && !isset($_GET['p'])){
		if(strpos($serverRequestURI, '?') !== false){
			$serverRequestURI .= "&p=".$_POST['p'];
		}
		else{
			$serverRequestURI .= "?p=".$_POST['p'];
		}
	}
	
	if (defined('ICL_SITEPRESS_VERSION')) {
		if (version_compare(ICL_SITEPRESS_VERSION, '3.2') < 0) {
			$backup = $GLOBALS['wp_filter'];
			$pid = url_to_postid($serverRequestURI);
			$GLOBALS['wp_filter'] = $backup;
		}
		elseif (version_compare(ICL_SITEPRESS_VERSION, '3.3') < 0 || version_compare(ICL_SITEPRESS_VERSION, '3.5') > 0) {
			global $sitepress;
			$removedFilter = false;
			if (isset($sitepress) && has_filter('url_to_postid', array(
				$sitepress,
				'url_to_postid' 
			))) {
				remove_filter('url_to_postid', array(
					$sitepress,
					'url_to_postid' 
				));
				$removedFilter = true;
			}
			$pid = url_to_postid($serverRequestURI);
			if ($removedFilter) {
				add_filter('url_to_postid', array(
					$sitepress,
					'url_to_postid' 
				));
			}
		}
		else {
			$pid = url_to_postid($serverRequestURI);
		}
	}
	else {
		$pid = url_to_postid($serverRequestURI);
	}
	$GLOBALS['woocommerce_sagepaycw_page_id_running'] = false;
	return $pid;
}


/**
 * Disable product state check for subscription during the processing of the transaction
 *
 * @param boolean $state
 * @param Object $product
 * @return boolean
 */
function woocommerce_sagepaycw_is_subscription_purchasable($state, $product){
	if(woocommerce_sagepaycw_is_plugin_page()){
		return true;
	}
	return $state;
}

add_filter('woocommerce_subscription_is_purchasable', 'woocommerce_sagepaycw_is_subscription_purchasable', 10, 2);


/**
 * Check if the order is already in processing of the module, if so we disable the option to pay the order again.
 *
 * @param boolean $needed
 * @param Object $order
 * @return boolean
 */
function woocommerce_sagepaycw_needs_payment($needed, $order, $validStates){
	$order_id = $order->get_id();
	if ( 'yes' == get_post_meta( $order_id, '_sagepaycw_pending_state', true ) ) {
		return false;
	}
	return $needed;
}
add_filter('woocommerce_order_needs_payment', 'woocommerce_sagepaycw_needs_payment', 10, 3);

/**
 * Check if the order is already in processing of the module, if so we disable the cancel for the order.
 *
 * @param array $validStates
 * @param Object $order
 * @return boolean
 */
function woocommerce_sagepaycw_valid_cancel( $validStates, $order = null){
	
	if($order === null){
		return $validStates;
	}
	$order_id = $order->get_id();
	if ( 'yes' == get_post_meta( $order_id, '_sagepaycw_pending_state', true ) ) {
		return array();
	}
	return $validStates;
}
add_filter('woocommerce_valid_order_statuses_for_cancel', 'woocommerce_sagepaycw_valid_cancel', 10, 2);


function woocommerce_sagepaycw_after_checkout_validation($post, $errors = null){
	
	$length = strlen('SagePayCw');
	if (substr($post['payment_method'], 0, $length) != 'SagePayCw') {
		return;
	}
	
	$existingErrors = 0;
	if(is_wp_error($errors)){
		$existingErrors = count($errors->get_error_codes());
	}
	else{
		$existingErrors = wc_notice_count( 'error' );
	}
	
	if ( !empty( $post['woocommerce_checkout_update_totals'] ) || $existingErrors != 0) {
		return;
	}
	
	try {
		if ( !defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}
		$paymentMethod = SagePayCw_Util::getPaymentMehtodInstance($post['payment_method']);
		$paymentMethod->validate(SagePayCw_ContextRequest::getInstance()->getParameters());
		
	}
	catch (Exception $e) {
		if(is_wp_error($errors)){
			$errors->add( 'payment', $e->getMessage());
		}
		else{
			wc_add_notice( $e->getMessage(), 'error' );
		}
	}
}

add_action('woocommerce_after_checkout_validation','woocommerce_sagepaycw_after_checkout_validation', 10, 2);


/**
 * This sets the order status within the payment complete call
 *
 * @param string $status
 * @param int $orderid
 * @param object $order 
 * @return string
 */
function woocommerce_sagepaycw_complete_order_status($status, $orderId = null, $order = null){
	if(isset($GLOBALS['woocommerce_sagepaycw_complete_order_status'])){
		return $GLOBALS['woocommerce_sagepaycw_complete_order_status'];
	}
	return $status;
}
