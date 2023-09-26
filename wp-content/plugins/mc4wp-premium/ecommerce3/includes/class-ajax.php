<?php

class MC4WP_Ecommerce_Admin_Ajax
{
    public function hook()
    {
        add_action('wp_ajax_mc4wp_ecommerce_process_queue', array( $this, 'process_queue' ));
        add_action('wp_ajax_mc4wp_ecommerce_reset_queue', array( $this, 'reset_queue' ));
        add_action('wp_ajax_mc4wp_ecommerce_sync_orders', array( $this, 'sync_objects') );
	    add_action('wp_ajax_mc4wp_ecommerce_sync_products', array( $this, 'sync_objects') );
    }

    /**
     * Checks if current user has `manage_options` capability or kills the request.
     */
    private function authorize()
    {
        if (! current_user_can('manage_options')) {
            status_header(401);
            exit;
        }
    }

    /**
     * Process the background queue.
     */
    public function process_queue()
    {
        $this->authorize();
        do_action('mc4wp_ecommerce_process_queue');
        wp_send_json(true);
        exit;
    }

    /**
    * Process the background queue.
    */
    public function reset_queue()
    {
        $this->authorize();
        $queue = mc4wp('ecommerce.queue');
        $queue->reset();
        $queue->save();
        wp_send_json(true);
        exit;
    }

    public function sync_objects() {
	    /** @var MC4WP_Ecommerce $ecommerce */
	    $ecommerce = mc4wp('ecommerce');

	    // Read request data
	    $type = $_GET['action'] === 'mc4wp_ecommerce_sync_orders' ? 'orders' : 'products';
	    $ids = (array) json_decode(file_get_contents('php://input'));
	    if (count($ids) === 0) {
	    	wp_send_json(array());
	    	exit;
	    }
	    $current = 0;
	    $results = array();

	    do {
		    $object_id = $ids[ $current++ ];

		    switch ( $type ) {
			    case 'orders':
				    // unset tracking cookies temporarily because these would be the admin's cookie
				    unset( $_COOKIE[ 'mc_tc' ] );
				    unset( $_COOKIE[ 'mc_cid' ] );

				    try {
					    $ecommerce->update_order( $object_id );
					    $results[] = sprintf( 'Added order %d', $object_id );
				    } catch ( Exception $e ) {
					    if ( $e->getCode() === MC4WP_Ecommerce::ERR_NO_ITEMS ) {
						    $results[] = sprintf( "Skipped order #%d: %s", $object_id, $e->getMessage() );
					    } else if ( $e->getCode() === MC4WP_Ecommerce::ERR_NO_EMAIL_ADDRESS ) {
						    $results[] = sprintf( "Skipped order #%d: %s", $object_id, __( 'Order has no email address', 'mc4wp-premium' ) );
					    } else {
						    $results[] = sprintf( "Error adding order #%d: %s", $object_id, $e );
					    }
				    }
				    break;

			    case 'products':
				    try {
					    $ecommerce->update_product( $object_id );
					    $results[] = sprintf( 'Added product #%d', $object_id );
				    } catch ( Exception $e ) {
					    $results[] = sprintf( "Error adding product #%d: %s", $object_id, $e );
				    }
				    break;
		    }

		// keep going as long as there are ID's and while we have more than 5 seconds left in this request
	    } while ( $current < count($ids) && $this->get_execution_time_left() > 5.00 );

	    // return the results array
	    wp_send_json($results); ;
    }

    private function get_execution_time_left() {
	    $max_execution_time = (int) ini_get('max_execution_time');
	    $request_time = microtime(true) - WP_START_TIMESTAMP;
	    return $max_execution_time - $request_time;
    }

    /**
     * @return MC4WP_Ecommerce
     */
    public function get_ecommerce()
    {
        return mc4wp('ecommerce');
    }
}
