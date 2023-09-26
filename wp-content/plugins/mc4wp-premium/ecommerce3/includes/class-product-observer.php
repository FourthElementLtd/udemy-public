<?php

class MC4WP_Ecommerce_Product_Observer extends MC4WP_Ecommerce_Object_Observer
{
    /**
     * @var MC4WP_Ecommerce
     */
    protected $ecommerce;

    /**
     * MC4WP_Ecommerce_Scheduler constructor.
     *
     * @param MC4WP_Ecommerce $ecommerce
     * @param MC4WP_Queue $queue
     */
    public function __construct(MC4WP_Ecommerce $ecommerce, MC4WP_Queue $queue)
    {
        $this->ecommerce = $ecommerce;
        $this->queue = $queue;
    }

    /**
     * Hook
     */
    public function hook()
    {
        // update products
        add_action('woocommerce_new_product', array($this, 'on_product_save'));
        add_action('save_post_product', array( $this, 'on_product_save' ));

        // delete products & orders when they're deleted in WP
        add_action('delete_post', array( $this, 'on_post_delete'));

        // update promo code
        add_action('save_post_shop_coupon', array( $this, 'on_coupon_update' ));
    }

    // hook: save_post_product
    public function on_product_save($post_id)
    {
        // skip auto saves
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $this->add_pending_job('add_product', $post_id);
        $this->queue->save();
    }

    // hook: delete_post
    public function on_post_delete($post_id)
    {
        $post = get_post($post_id);

        // products
        if ($post->post_type === 'product') {
            $this->remove_pending_jobs('add_product', $post_id);
            $this->add_pending_job('delete_product', $post_id);
            $this->queue->save();
        }

        // coupons / promo codes
        if ($post->post_type === 'shop_coupon') {
            $this->on_coupon_delete($post_id);
        }
    }

    public function on_coupon_update($post_id)
    {
        // skip auto saves
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

		// skip if coupon tracking is disabled
		if (apply_filters('mc4wp_ecommerce_enable_coupon_tracking', true) === false) {
			return;
		}

        $post_status = get_post_status($post_id);
        switch ($post_status) {
            // only published promos should be sent to Mailchimp
            case 'publish':
                $this->remove_pending_jobs('delete_promo', $post_id);
                $this->add_pending_job('update_promo', $post_id);
                $this->queue->save();
            break;

            default:
                $this->remove_pending_jobs('update_promo', $post_id);
                $this->add_pending_job('delete_promo', $post_id);
                $this->queue->save();
            break;
        }
    }

    public function on_coupon_delete($post_id)
    {
	    // skip if coupon tracking is disabled
	    if (apply_filters('mc4wp_ecommerce_enable_coupon_tracking', true) === false) {
		    return;
	    }

        $this->remove_pending_jobs('update_promo', $post_id);
        $this->add_pending_job('delete_promo', $post_id);
        $this->queue->save();
    }
}
