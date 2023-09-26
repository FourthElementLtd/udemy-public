<?php defined('ABSPATH') or exit;

$current = (int) $_GET['wizard'];

$steps = array(
    '1' => __('Setup your store', 'mc4wp-ecommerce'),
    '2' => __('Add your products', 'mc4wp-ecommerce'),
    '3' => __('Add your orders', 'mc4wp-ecommerce'),
);
?>

<div class="wrap ecommerce" id="mc4wp-admin">
    <div class="mc4wp-wizard">

        <h1 class="mc4wp-page-title">E-Commerce Configuration</h1>

		<?php
		if (isset($_GET['order-sync-started']) || isset($_GET['product-sync-started'])) {
			echo '<div class="notice notice-info">';
			echo '<p>', sprintf( __( 'Synchronization process started. Please keep an eye on <a href="%s">the debug log</a> for any issues.', 'mc4wp-premium' ), admin_url( 'admin.php?page=mailchimp-for-wp-other' ) ), '</p>';
			echo '</div>';
		}
		?>

        <div class="mc4wp-wizard-steps-nav">
            <?php
			foreach ($steps as $number => $label) {
				printf('<div class="step %s">', $number == $current ? 'current' : '');
				if ($current > $number) {
					printf('<a href="%s">%d. %s</a>', add_query_arg(array( 'wizard' => $number )), $number, $label);
				} else {
					printf('<span>%d. %s</span>', $number, $label);
				}
				printf("</div>");
			}
			?>
        </div>

        <div class="mc4wp-well">

            <div class="mc4wp-wizard-step clearfix" style="display: <?php echo $current == 1 ? 'block' : 'none'; ?>">
                <?php require __DIR__ . '/parts/config-store.php'; ?>
                <br style="clear: none;" />
            </div>

            <div class="mc4wp-wizard-step clearfix" style="display: <?php echo $current == 2 ? 'block' : 'none'; ?>">
                <?php require __DIR__ . '/parts/config-products.php'; ?>
                <br style="clear: none;" />

                <p class="submit">
                    <a class="button button-primary next" href="<?php echo add_query_arg(array( 'wizard' => 3 )); ?>"><?php _e('Next', 'mc4wp-ecommerce'); ?></a>
                </p>
            </div>

            <div class="mc4wp-wizard-step clearfix" style="display: <?php echo $current == 3 ? 'block' : 'none'; ?>">
                <?php require __DIR__ . '/parts/config-orders.php'; ?>
                <br style="clear: none;" />

                <p class="submit">
                    <a class="button button-primary next" href="<?php echo remove_query_arg('wizard'); ?>"><?php _e('Finish', 'mc4wp-ecommerce'); ?></a>
                </p>
            </div>


        </div><!-- / .well -->
    </div><!-- / .wizard -->
</div><!-- / .wrap -->
