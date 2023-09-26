<?php

class MC4WP_Ecommerce_Order_Observer extends MC4WP_Ecommerce_Object_Observer
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
        // update or delete orders
        add_action('save_post_shop_order', array( $this, 'on_order_change' ));
        add_action('woocommerce_order_status_changed', array( $this, 'on_order_change' ));

        // delete products & orders when they're deleted in WP
        add_action('delete_post', array( $this, 'on_post_delete'));

        // updating users
        add_action('profile_update', array( $this, 'on_user_update' ));

        // user meta update
        add_action('update_user_meta', array($this, 'on_user_meta_update'), 10, 4);
    }

    // hook: save_post_shop_order
    public function on_order_change($post_id)
    {
        // skip auto saves
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $statuses = mc4wp_ecommerce_get_order_statuses();
        $post = get_post($post_id);
        $method = in_array($post->post_status, $statuses) ? 'add_order' : 'delete_order';
        $reversed_method = $method === 'add_order' ? 'delete_order' : 'add_order';

        // remove all previous pending jobs which would be reversed by this new job anyway.
        $this->remove_pending_jobs($reversed_method, $post_id);

        // add new job
        $this->add_pending_job($method, $post_id);
        $this->queue->save();
    }

    // hook: delete_post
    public function on_post_delete($post_id)
    {
        $post = get_post($post_id);

        if ($post->post_type === 'shop_order') {
            $this->remove_pending_jobs('add_order', $post_id);
            $this->add_pending_job('delete_order', $post_id);
            $this->queue->save();
        }
    }

    // hook: profile_update
    public function on_user_update($user_id)
    {
        $user = get_userdata($user_id);

        // do nothing if user is not a WC Customer
        if (! $user || ! in_array('customer', $user->roles)) {
            return;
        }

        // don't update if user has no known email address
        $has_email_address = ! empty($user->billing_email) || ! empty($user->user_email);
        if (! $has_email_address) {
            return;
        }
        
        $this->add_pending_job('update_customer', $user_id);
        $this->queue->save();
    }

    public function on_user_meta_update($meta_id, $user_id, $meta_key, $new_meta_value)
    {
        if ($meta_key !== 'billing_email') {
            return;
        }

        if (empty($new_meta_value)) {
            return;
        }

        // if old billing_email matches new billing_email, do nothing
        $old_meta_value = get_user_meta($user_id, $meta_key, true);
        if ($old_meta_value == $new_meta_value) {
            return;
        }

        $this->queue->put(array(
            'method' => 'update_subscriber_email',
            'args' => array(
                $old_meta_value,
                $new_meta_value,
            )
        ));
    }

}
