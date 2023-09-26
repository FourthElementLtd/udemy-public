<?php
/** @var MC4WP_Ecommerce_Object_Count $order_count */
?>
<h3>
    <?php _e('Orders', 'mc4wp-ecommerce'); ?>
    <?php printf('<span class="mc4wp-status-label">%d/%d</span>', $order_count->tracked, $order_count->all); ?>
</h3>

<p>
    <?php _e('Adding your orders to Mailchimp will allow you to see purchases made by your list subscribers.', 'mc4wp-ecommerce'); ?>
</p>

<div data-wizard="orders" data-object-ids="<?php echo esc_attr( json_encode($untracked_order_ids) ); ?>"></div>
<noscript><?php esc_html_e('Please enable JavaScript to use this feature.', 'mc4wp-ecommerce'); ?></noscript>

