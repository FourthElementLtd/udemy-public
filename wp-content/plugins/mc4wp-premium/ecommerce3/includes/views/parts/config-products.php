<?php
/** @var MC4WP_Ecommerce_Object_Count $product_count */
?>

<h3>
    <?php _e('Products', 'mc4wp-ecommerce'); ?>
    <?php printf('<span class="mc4wp-status-label">%d/%d</span>', $product_count->tracked, $product_count->all); ?>
</h3>

<p>
    <?php _e('Your products have to be synchronized to Mailchimp before we can proceed to tracking your orders.', 'mc4wp-ecommerce'); ?>
</p>

<div data-wizard="products" data-object-ids="<?php echo esc_attr( json_encode($untracked_product_ids) ); ?>"></div>
<noscript><?php esc_html_e('Please enable JavaScript to use this feature.', 'mc4wp-ecommerce'); ?></noscript>

