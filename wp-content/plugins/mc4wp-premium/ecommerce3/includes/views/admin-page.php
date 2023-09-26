<?php
defined('ABSPATH') or exit;
?>

<div id="mc4wp-admin" class="wrap ecommerce">

	<h1 class="mc4wp-page-title">
		<?php echo __('Mailchimp for WordPress', 'mc4wp-ecommerce') . ': ' . __('E-Commerce', 'mc4wp-ecommerce'); ?>
	</h1>

	<?php
	if (isset($_GET['order-sync-started']) || isset($_GET['product-sync-started'])) {
		echo '<div class="notice notice-info">';
		echo '<p>', sprintf( __( 'Synchronization process started. Please keep an eye on <a href="%s">the debug log</a> for any issues.', 'mc4wp-premium' ), admin_url( 'admin.php?page=mailchimp-for-wp-other' ) ), '</p>';
		echo '</div>';
	}
	?>

	<?php if ($connected_list) {
    ?>
    <form method="POST">
		<input type="hidden" name="_mc4wp_action" value="save_ecommerce_settings" />
	    <?php wp_nonce_field( '_mc4wp_action', '_wpnonce' ); ?>
		<input type="hidden" name="_redirect_to" value="<?php echo admin_url( 'admin.php?page=mailchimp-for-wp-ecommerce' ); ?>" />

		<table class="form-table">

            <tr valign="top">
                <th scope="row">
                    <label><?php _e('Synchronize products?', 'mc4wp-ecommerce'); ?></label>
                </th>
                <td>
                    <label class="choice-wrap"><input type="radio" name="mc4wp_ecommerce[enable_product_tracking]" value="1" <?php checked($settings['enable_product_tracking'], 1); ?> />&rlm; <?php _e('Yes'); ?></label>
                    <label class="choice-wrap"><input type="radio" name="mc4wp_ecommerce[enable_product_tracking]" value="0" <?php checked($settings['enable_product_tracking'], 0); ?> />&rlm; <?php _e('No'); ?></label>
                    <p class="description"><?php _e('This synchronizes all product data with Mailchimp.', 'mc4wp-ecommerce'); ?></p>
                </td>
            </tr>

            <?php $config = array( 'element' => 'mc4wp_ecommerce[enable_product_tracking]', 'value' => 1, 'hide' => false ); ?>
            <tr valign="top" data-showif="<?php echo esc_attr(json_encode($config)); ?>">
				<th scope="row">
					<label><?php _e('Synchronize orders?', 'mc4wp-ecommerce'); ?></label>
				</th>
				<td>
					<label class="choice-wrap"><input type="radio" name="mc4wp_ecommerce[enable_order_tracking]" value="1" <?php checked($settings['enable_order_tracking'], 1); ?> />&rlm; <?php _e('Yes'); ?></label>
					<label class="choice-wrap"><input type="radio" name="mc4wp_ecommerce[enable_order_tracking]" value="0" <?php checked($settings['enable_order_tracking'], 0); ?> />&rlm; <?php _e('No'); ?></label>
					<p class="description"><?php _e('This synchronizes all customer & order data with Mailchimp.', 'mc4wp-ecommerce'); ?></p>
				</td>
			</tr>

			<!-- Track all order statuses -->
			<?php $config = array( 'element' => 'mc4wp_ecommerce[enable_order_tracking]', 'value' => 1, 'hide' => false ); ?>
			<tr valign="top" data-showif="<?php echo esc_attr(json_encode($config)); ?>">
				<th scope="row">
					<label><?php _e('Include all order statuses?', 'mc4wp-ecommerce'); ?></label>
				</th>
				<td>
					<label class="choice-wrap"><input type="radio" name="mc4wp_ecommerce[include_all_order_statuses]" value="1" <?php checked($settings['include_all_order_statuses'], 1); ?> />&rlm; <?php _e('Yes'); ?></label>
					<label class="choice-wrap"><input type="radio" name="mc4wp_ecommerce[include_all_order_statuses]" value="0" <?php checked($settings['include_all_order_statuses'], 0); ?> />&rlm; <?php _e('No'); ?></label>
					<p class="description"><?php
    _e('By default, only completed orders are sent to Mailchimp. Select "Yes" to send refunded, cancelled and pending orders too. This is only needed if you use the Order Notifications automation.', 'mc4wp-ecommerce'); ?>
					</p>
				</td>
			</tr>

			<?php $config = array( 'element' => 'mc4wp_ecommerce[enable_order_tracking]', 'value' => 1, 'hide' => false ); ?>
			<tr valign="top" data-showif="<?php echo esc_attr(json_encode($config)); ?>">
				<th scope="row">
					<label><?php _e('Enable cart tracking?', 'mc4wp-ecommerce'); ?></label>
				</th>
				<td>
					<label class="choice-wrap"><input type="radio" name="mc4wp_ecommerce[enable_cart_tracking]" value="1" <?php checked($settings['enable_cart_tracking'], 1); ?> />&rlm; <?php _e('Yes'); ?></label>
					<label class="choice-wrap"><input type="radio" name="mc4wp_ecommerce[enable_cart_tracking]" value="0" <?php checked($settings['enable_cart_tracking'], 0); ?> />&rlm; <?php _e('No'); ?></label>
					<p class="description"><?php printf(__('This allows you to <a href="%s">setup an abandoned cart recovery workflow in Mailchimp</a>.', 'mc4wp-ecommerce'), 'https://www.mc4wp.com/kb/enabling-abandoned-cart-recovery/#utm_source=wp-plugin&utm_medium=mc4wp-premium&utm_campaign=ecommerce-settings-page'); ?></p>
				</td>
			</tr>

			<?php $config = array( 'element' => 'mc4wp_ecommerce[enable_product_tracking]', 'value' => 1, 'hide' => false ); ?>
			<tr valign="top" data-showif="<?php echo esc_attr(json_encode($config)); ?>">
				<th scope="row">
					<label><?php _e('Load MC.js?', 'mc4wp-ecommerce'); ?></label>
				</th>
				<td>
					<label class="choice-wrap"><input type="radio" name="mc4wp_ecommerce[load_mcjs_script]" value="1" <?php checked($settings['load_mcjs_script'], 1); ?> />&rlm; <?php _e('Yes'); ?></label>
					<label class="choice-wrap"><input type="radio" name="mc4wp_ecommerce[load_mcjs_script]" value="0" <?php checked($settings['load_mcjs_script'], 0); ?> />&rlm; <?php _e('No'); ?></label>
					<p class="description"><?php _e('Enabling this loads a JavaScript file from Mailchimp that allows for product retargeting & pop-ups.', 'mc4wp-ecommerce'); ?></p>
				</td>
			</tr>

		</table>

		<?php submit_button(); ?>
	</form>

	<div class="mc4wp-margin-m"></div>

	<div>
		<h2><?php _e('Manage Mailchimp data', 'mc4wp-ecommerce'); ?></h2>


		<p>
			<?php printf(__('Your store is currently connected to <strong>%s</strong> in Mailchimp as <strong>%s</strong>. (<a href="%s">edit store settings</a>)', 'mc4wp-ecommerce'), sprintf('<a href="https://admin.mailchimp.com/lists/members/?id=%s">%s</a>', $connected_list->web_id, esc_html($connected_list->name)), esc_html($settings['store']['name']), add_query_arg(array( 'edit' => 'store' ))); ?>
		</p>

<?php
// show last updated timestamp
if (! empty($settings['last_updated'])) {
    $formatted_date = gmdate(get_option('date_format') . ' ' . get_option('time_format'), $settings['last_updated'] + (get_option('gmt_offset', 0) * 3600));
    printf('<p><strong>' . __('Last updated:', 'mc4wp-ecommerce') . '</strong> %s</p>', $formatted_date);
}

    if ($queue) {
        $next_run = wp_next_scheduled('mc4wp_ecommerce_process_queue');
        $seconds_until_next_run = $next_run - time();
        $count = count($queue->all());

        echo '<div class="mc4wp-well mc4wp-margin-s">';
        echo '<h3>' . __('Queued background jobs', 'mc4wp-ecommerce') . '</h3>';
        echo '<p>';

        echo sprintf(__('<strong id="mc4wp-pending-background-jobs-count">%d</strong> background jobs waiting to be processed.', 'mc4wp-ecommerce'), $count);

        if ($count > 0) {
            echo ' ' . sprintf(__('Pending jobs will be processed in <strong data-countdown="true">%s</strong> seconds.'), max(0, $seconds_until_next_run));

            if ($seconds_until_next_run <= 0) {
                add_action('shutdown', 'spawn_cron');
            }
        }

        echo '</p>';

        if ($count > 0) {
            echo '<div id="queue-processor"></div>';
        }

        echo '<p class="description">' . sprintf(__('Please keep an eye on the <a href="%s">debug log</a> for any errors.', 'mc4wp-ecommerce'), admin_url('admin.php?page=mailchimp-for-wp-other')) . '</p>';

        echo sprintf('<pre class="mc4wp-margin-s" style="display: %s;">', isset($_GET['debug']) || isset($_GET['debug_queue']) ? '' : 'none');
        var_dump($queue->all());
        echo '</pre>';


        echo '</div>';
    } ?>

		<!-- products wizard -->
		<div class="mc4wp-well mc4wp-margin-s">
			<?php require __DIR__ . '/parts/config-products.php'; ?>
		</div>
		<!-- / End products wizard -->

		<!-- orders wizard -->
		<div class="mc4wp-well mc4wp-margin-s">
			<?php require __DIR__ . '/parts/config-orders.php'; ?>
		</div>
		<!-- / End orders wizard -->

		<div class="mc4wp-margin-l mc4wp-well" style="background-color: rgba(255, 50, 50, 0.10);">
			<h3><?php _e('Reset Mailchimp connection', 'mc4wp-ecommerce'); ?></h3>
			<p>	<?php printf(__('Your store is currently connected to <strong>%s</strong> in Mailchimp as <strong>%s</strong>.', 'mc4wp-ecommerce'), sprintf('<a href="https://admin.mailchimp.com/lists/members/?id=%s">%s</a>', $connected_list->web_id, esc_html($connected_list->name)), esc_html($settings['store']['name'])); ?>
		<?php _e('Use the button below to disconnect your store.', 'mc4wp-ecommerce'); ?>
			</p>
			<form method="POST" data-confirm="<?php esc_attr_e('Are you sure you want to reset all of your e-commerce data?', 'malchimp-for-wp'); ?>">
				<input type="hidden" name="_mc4wp_action" value="ecommerce_reset">
                <?php wp_nonce_field( '_mc4wp_action', '_wpnonce' ); ?>
                <p>
					<label><input type="checkbox" name="delete_store_in_mailchimp" value="1" checked /> <?php _e('Delete store data from Mailchimp.', 'mc4wp-ecommerce'); ?></label>
				</p>
				<p>
					<input type="submit" value="<?php esc_attr_e('Reset store', 'mc4wp-ecommerce'); ?>" class="button button-secondary" />
				</p>
			</form>
		</div>

	</div> <!-- / End store data overview -->
	<?php
} else {
        ?>
		<div class="mc4wp-margin-l">
			<h3><?php _e('Connect your store to Mailchimp', 'mc4wp-ecommerce'); ?></h3>
			<p><?php printf(__('To use the e-commerce features, please start by <a href="%s">connecting your store to Mailchimp</a>.', 'mc4wp-ecommerce'), add_query_arg(array( 'wizard' => 1 ))); ?></p>
		</div>
	<?php
    } ?>

    <div class="mc4wp-margin-m"></div>

	<!-- help link -->
	<p>
		<?php printf(__('For more information on <a href="%s">using Mailchimp e-commerce</a>, please refer to our knowledge base.', 'mc4wp-ecommerce'), 'https://www.mc4wp.com/kb/what-is-ecommerce/'); ?>
	</p>
	<!-- / help link -->

    <div class="mc4wp-margin-m"></div>

</div><!-- / End page wrap -->
