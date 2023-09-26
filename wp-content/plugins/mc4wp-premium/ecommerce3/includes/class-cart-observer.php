<?php

class MC4WP_Ecommerce_Cart_Observer
{

	/**
	 * @var string
	 */
	private $plugin_file;

	/**
	 * @var MC4WP_Queue
	 */
	protected $queue;

	/**
	 * @var MC4WP_Ecommerce
	 */
	private $ecommerce;

	/**
	 * @var MC4WP_Ecommerce_Object_Transformer
	 */
	private $transformer;

	/**
	 * MC4WP_Ecommerce_Tracker constructor.
	 *
	 * @param string $plugin_file
	 * @param MC4WP_Ecommerce $ecommerce
	 * @param MC4WP_Queue $queue
	 * @param MC4WP_Ecommerce_Object_Transformer $transformer
	 */
	public function __construct($plugin_file, MC4WP_Ecommerce $ecommerce, MC4WP_Queue $queue, MC4WP_Ecommerce_Object_Transformer $transformer)
	{
		$this->plugin_file = $plugin_file;
		$this->ecommerce = $ecommerce;
		$this->queue = $queue;
		$this->transformer = $transformer;
	}

	/**
	 * Add hooks
	 */
	public function hook()
	{
		add_action('parse_request', array( $this, 'repopulate_cart_from_mailchimp' ));
		add_action('wp_enqueue_scripts', array( $this, 'enqueue_assets' ));
		add_action('wp_ajax_mc4wp_ecommerce_schedule_cart', array( $this, 'on_checkout_form_change' ));
		add_action('wp_ajax_nopriv_mc4wp_ecommerce_schedule_cart', array( $this, 'on_checkout_form_change' ));
		add_action('woocommerce_order_status_changed', array($this, 'on_order_status_change'), 10, 4);
		add_action('woocommerce_after_cart_item_restored', array( $this, 'on_cart_updated' ));
		add_action('woocommerce_after_cart_item_quantity_update', array( $this, 'on_cart_updated' ));
		add_action('woocommerce_add_to_cart', array( $this, 'on_cart_updated' ), 9);
		add_action('woocommerce_cart_item_removed', array( $this, 'on_cart_updated' ));
		add_action('woocommerce_cart_emptied', array( $this, 'on_cart_updated' ));
		add_action('wp_login', array( $this, 'on_cart_updated' ), 10, 2);
	}

	/**
	 * Repopulates a cart from Mailchimp if the "mc_cart_id" parameter is set.
	 */
	public function repopulate_cart_from_mailchimp()
	{
		if (empty($_GET['mc_cart_id'])) {
			return;
		}

		$cart_id = $_GET['mc_cart_id'];
		try {
			$cart_data = $this->ecommerce->get_cart($cart_id);
		} catch (Exception $e) {
			return;
		}

		/**
		 * Fires just before an abandoned cart from Mailchimp is added to the WooCommerce cart session.
		 *
		 * If you use this to override the default cart population, make sure to redirect after you are done.
		 *
		 * @param object $cart_data The data retrieved from Mailchimp.
		 * @link http://developer.mailchimp.com/documentation/mailchimp/reference/ecommerce/stores/carts/#read-get_ecommerce_stores_store_id_carts_cart_id
		 */
		do_action('mc4wp_ecommerce_restore_abandoned_cart', $cart_data);

		// empty cart
		$wc_cart = WC()->cart;
		$wc_cart->empty_cart();

		// add items from Mailchimp cart object
		foreach ($cart_data->lines as $line) {
			$variation_id = $line->product_variant_id != $line->product_id ? $line->product_variant_id : 0;
			$wc_cart->add_to_cart($line->product_id, $line->quantity, $variation_id);
		}

		// remove pending update & delete jobs
		$this->remove_pending_jobs('delete_cart', $cart_id);
		$this->remove_pending_jobs('update_cart', $cart_id);
		$this->queue->save();

		wp_redirect(remove_query_arg('mc_cart_id'));
		exit;
	}

	/**
	 * @param string $method
	 * @param int $object_id
	 */
	private function remove_pending_jobs($method, $object_id)
	{
		$jobs = $this->queue->all();
		foreach ($jobs as $job) {
			if ($job->data['method'] === $method && $job->data['args'][0] == $object_id) {
				$this->queue->delete($job);
			}
		}
	}

	/**
	 * @param string $method
	 * @param array $args
	 */
	private function add_pending_job($method, array $args)
	{
		$this->queue->put(
			array(
				'method' => $method,
				'args' => $args
			)
		);
	}

	/**
	 * Enqueue script on checkout page that periodically sends form data for guest checkouts.
	 */
	public function enqueue_assets()
	{
		// do not load script if user is logged in or if not on checkout page
		if (is_user_logged_in() || ! is_checkout()) {
			return;
		}

		// do not load if feature is disabled through constant or filter hook
		if ((defined('MC4WP_ECOMMERCE_DISABLE_GUEST_CART_TRACKING') && MC4WP_ECOMMERCE_DISABLE_GUEST_CART_TRACKING) || apply_filters('mc4wp_ecommerce_disable_guest_cart_tracking', false)) {
			return;
		}

		add_filter( 'script_loader_tag', array( $this, 'add_async_attribute' ), 20, 2 );
		wp_enqueue_script('mc4wp-ecommerce-cart', plugins_url("/assets/js/cart.js", $this->plugin_file), array(), MC4WP_PREMIUM_VERSION, true);

		wp_localize_script(
			'mc4wp-ecommerce-cart',
			'mc4wp_ecommerce_cart',
			array(
				'ajax_url' => admin_url('admin-ajax.php')
			)
		);
	}

	public function add_async_attribute( $tag, $handle )
	{
		if ( $handle !== 'mc4wp-ecommerce-cart' || stripos( $tag, 'defer' ) !== false ) {
			return $tag;
		}

		return str_replace( ' src=', ' defer src=', $tag );
	}

	// triggered via JavaScript hooked into checkoutForm.change
	public function on_checkout_form_change()
	{
		$customer_data = json_decode(stripslashes(file_get_contents("php://input")), false);

		// make sure we have at least an email_address
		if (empty($customer_data->billing_email)) {
			wp_send_json_error();
			exit;
		}

		// get cart, safely.
		$wc_cart = WC()->cart;
		if (! $wc_cart instanceof WC_Cart) {
			wp_send_json_error();
			return;
		}

		// only send cart to Mailchimp if email address looks valid
		if (is_email($customer_data->billing_email)) {
			$cart_id = $this->transformer->get_cart_id($customer_data->billing_email);

			// remove other pending updates from queue
			$this->remove_pending_jobs('update_cart', $cart_id);

			// update remote cart if we have items in cart
			if (! $this->is_cart_empty($wc_cart)) {
				$cart_contents = $this->get_cart_contents($wc_cart);
				$this->add_pending_job('update_cart', array($cart_id, $customer_data, $cart_contents));
			} else {
				$this->add_pending_job('delete_cart', array($cart_id));
			}
		}

		// delete previous cart if email address changed
		if (! empty($customer_data->previous_billing_email) && $customer_data->previous_billing_email !== $customer_data->billing_email) {

			// get previous cart ID
			$previous_cart_id = $this->transformer->get_cart_id($customer_data->previous_billing_email);

			// schedule cart deletion
			$this->add_pending_job('delete_cart', array( $previous_cart_id ));
		}

		$this->queue->save();
		wp_send_json_success();
		exit;
	}

	public function on_order_status_change($order_id, $from, $to, $order)
	{
		// keep cart in Mailchimp if order status is pending or failed
		$order_statuses_to_keep = array('', 'pending', 'failed');
		if (in_array($to, $order_statuses_to_keep)) {
			return;
		}

		// delete cart in Mailchimp if coming from pending or failed status
		if (! in_array($from, $order_statuses_to_keep)) {
			return;
		}

		$billing_email = method_exists($order, 'get_billing_email') ? $order->get_billing_email() : $order->billing_email;
		$cart_id = $this->transformer->get_cart_id($billing_email);
		$this->remove_pending_jobs('update_cart', $cart_id);
		$this->add_pending_job('delete_cart', array( $cart_id ));
		$this->queue->save();
	}


	/**
	 * @param mixed $a
	 * @param mixed|WP_User $b
	 *
	 * @throws Exception
	 *
	 * @see woocommerce_cart_item_removed
	 * @see woocommerce_cart_emptied
	 * @see wp_login
	 * @see woocommerce_after_cart_item_restored
	 * @see woocommerce_after_cart_item_quantity_update
	 * @see woocommerce_add_to_cart
	 */
	public function on_cart_updated( $a = null, $b = null )
	{
		// since WooCommerce 3.0
		if ( !class_exists('WC_Customer')) {
			return;
		}

		$user_id = $b instanceof WP_User ? $b->ID : get_current_user_id();
		$customer = new \WC_Customer($user_id, true);

		// get email address from billing_email or user_email property
		$email_address = $customer->get_billing_email();
		if (empty($email_address)) {
			if ($user_id > 0) {
				$this->get_log()->info( "E-Commerce: Skipping cart update for logged-in user without email address." );
			} else {
				$this->get_log()->debug( "E-Commerce: Skipping cart update for logged-out guest user without email address." );
			}
			return;
		}

		// sanity check, sometimes this returns null apparently?
		$wc_cart = WC()->cart;
		if (! $wc_cart instanceof WC_Cart) {
			return;
		}

		$cart_id = $this->transformer->get_cart_id($email_address);

		// delete cart from Mailchimp if it is now empty
		if ($this->is_cart_empty($wc_cart)) {
			// remove pending updates from queue
			$this->remove_pending_jobs('update_cart', $cart_id);

			// schedule cart deletion
			$this->add_pending_job('delete_cart', array( $cart_id ));
			$this->queue->save();
			return;
		}

		// remove other pending updates from queue
		$this->remove_pending_jobs('update_cart', $cart_id);

		// use user ID if this is a logged-in user, store data in queue otherwise
		$customer = $user_id > 0 ? $user_id : (object) array(
			'billing_email' => $customer->get_billing_email(),
			'first_name' => $customer->get_first_name(),
			'last_name' => $customer->get_last_name(),
		);
		$cart_contents = $this->get_cart_contents($wc_cart);

		// add data to queue
		$this->add_pending_job('update_cart', array($cart_id, $customer, $cart_contents));
		$this->queue->save();
	}

	private function get_cart_contents(WC_Cart $wc_cart)
	{
		// schedule new update with latest data
		$cart_contents = method_exists($wc_cart, 'get_cart_contents') ? $wc_cart->get_cart_contents() : $wc_cart->cart_contents;

		// remove data key as it holds an entire PHP object which we don't want to store
		$cart_contents = array_map(function($item) {
			if (isset($item['data'])) {
				unset($item['data']);
			}
			return $item;
		}, $cart_contents);

		return $cart_contents;
	}

	/**
	 * @param WC_Cart $cart
	 * @return bool
	 */
	private function is_cart_empty(WC_Cart $cart)
	{
		// for backwards compatibility with WooCommerce 2.2
		if (! method_exists($cart, 'is_empty')) {
			return (0 === count($cart->get_cart()));
		}

		return $cart->is_empty();
	}

	/**
	 * @return MC4WP_Debug_Log
	 */
	private function get_log()
	{
		return mc4wp('log');
	}
}
