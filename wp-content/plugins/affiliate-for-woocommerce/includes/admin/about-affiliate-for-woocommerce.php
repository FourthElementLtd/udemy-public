<?php
/**
 * Affiliate For WooCommerce About/Landing page
 *
 * @since       1.0.0
 * @version     1.1.5
 *
 * @package     affiliate-for-woocommerce/includes/admin/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$plugin_data = Affiliate_For_WooCommerce::get_plugin_data();
?>
<style type="text/css">
	.wrap.about-wrap,
	.afw-faq .has-3-columns.feature-section.col.three-col {
		max-width: unset !important;
	}
</style>
<script type="text/javascript">
	jQuery( function(){
		jQuery('#toplevel_page_woocommerce').find('a[href$="admin.php?page=affiliate-for-woocommerce"]').addClass('current');
		jQuery('#toplevel_page_woocommerce').find('a[href$="admin.php?page=affiliate-for-woocommerce"]').parent().addClass('current');
	});
</script>
<div class="wrap about-wrap">
	<h1><?php echo esc_html__( 'Thank you for installing Affiliate for WooCommerce', 'affiliate-for-woocommerce' ) . ' ' . esc_html( $plugin_data['Version'] ) . '!'; ?></h1>
	<p class="about-text"><?php echo esc_html__( 'Glad to have you onboard. We hope adds to your success 🏆', 'affiliate-for-woocommerce' ); ?></p>
	<?php
	if ( ( afwc_is_plugin_active( 'affiliates/affiliates.php' ) || afwc_is_plugin_active( 'affiliates-pro/affiliates-pro.php' ) ) && defined( 'AFFILIATES_TP' ) ) {
		$tables            = $wpdb->get_results( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . AFFILIATES_TP ) . '%' ), ARRAY_A ); // phpcs:ignore
		$show_notification = get_option( 'show_migrate_affiliates_notification', 'yes' );
		// Note: To test migration uncomment following code.
		if ( ! empty( $tables ) && 'no' !== $show_notification ) {
			?>
				<div>
					<div>
				<?php echo esc_html__( 'We discovered that you are using another "Affiliates" plugin. Do you want to migrate your existing data to this new Affiliates for WooCommerce plugin?', 'affiliate-for-woocommerce' ); ?>
							<span class="migrate_affiliates_actions">
								<a href="
							<?php
							echo esc_url(
								add_query_arg(
									array(
										'page'         => 'affiliate-for-woocommerce-settings',
										'migrate'      => 'affiliates',
										'is_from_docs' => 1,
									),
									admin_url( 'admin.php' )
								)
							);
							?>
											" class="button-primary" id="migrate_yes" ><?php echo esc_html__( 'Yes, Migrate existing data.', 'affiliate-for-woocommerce' ); ?></a>
								<a href="
								<?php
								echo esc_url(
									add_query_arg(
										array(
											'page'         => 'affiliate-for-woocommerce-settings',
											'migrate'      => 'ignore_affiliates',
											'is_from_docs' => 1,
										),
										admin_url( 'admin.php' )
									)
								);
								?>
											" class="button" id="migrate_no" ><?php echo esc_html__( 'No, I want to start afresh.', 'affiliate-for-woocommerce' ); ?></a>
							</span>
						<p><?php echo esc_html__( 'Note: Once you migrate from Affiliates plugin, please deactivate it. Affiliates and Affiliate for WooCommerce can\'t work simultaneously.', 'affiliate-for-woocommerce' ); ?></p>
					</div>
				</div>
				<?php
		}
	}
	?>
	<div class="changelog">
		<div class="about-text">
			<?php
				echo sprintf(
					/* translators: Link to the Affiliate For WooCommerce Settings */
					esc_html__( 'To get started: review and update your %s', 'affiliate-for-woocommerce' ),
					'<a class="button-primary" target="_blank" href="' . esc_url(
						add_query_arg(
							array(
								'page' => 'wc-settings',
								'tab'  => 'affiliate-for-woocommerce-settings',
							),
							admin_url( 'admin.php' )
						)
					) . '">' . esc_html__( 'Affiliate Program Settings &rarr;', 'affiliate-for-woocommerce' ) . '</a>'
				);
				?>
				<br>
				<?php
					echo sprintf(
						/* translators: Link to the Affiliate For WooCommerce Settings */
						esc_html__( 'Access affiliate dashboard from %s.', 'affiliate-for-woocommerce' ),
						'<a href="' . esc_url(
							add_query_arg(
								array(
									'page' => 'affiliate-for-woocommerce',
								),
								admin_url( 'admin.php' )
							)
						) . '">' . esc_html__( 'here', 'affiliate-for-woocommerce' ) . '</a>'
					);
					?>
		</div>
		<hr>
		<div class="afw-faq">
			<h3><?php echo esc_html__( 'FAQ', 'affiliate-for-woocommerce' ); ?></h3>
			<div class="has-3-columns feature-section col three-col">
				<div class="column col">
					<h4><?php echo esc_html__( 'How do I add an affiliate?', 'affiliate-for-woocommerce' ); ?></h4>
					<p><?php echo esc_html__( "1. If you want to make all users of specific role as an affiliate, go to Affiliate Settings and include those  user roles for automatic affiliation. 2. Use the Affiliate registration form we've created automatically (link for this is in Settings). 3. To make an individual user an affiliate, go to Users - All Users and select a user. You will see 'Is Affiliate' option. Check that option and that user will become your affiliate.", 'affiliate-for-woocommerce' ); ?></p>
				</div>
				<div class="column col">
						<h4><?php echo esc_html__( 'Where do Affiliates login / get their stats from?', 'affiliate-for-woocommerce' ); ?></h4>
						<p><?php echo esc_html__( 'Your affiliates can login to your My Account area (just like other users). And navigate to "Affiliate" link in the navigation there. Reports, profile settings, campaigns... everything is available there.', 'affiliate-for-woocommerce' ); ?></p>
				</div>
				<div class="column col last-feature">
					<h4><?php echo esc_html__( "Where's the link an affiliate will use to link to my site?", 'affiliate-for-woocommerce' ); ?></h4>
					<p><?php echo esc_html__( "You can see any affiliate's 'affiliate link' under their name in the Affiliate for WooCommerce dashboard. Additionally, go to Users - All Users and select a user. You will see their affiliate link on their Profile page. Copy it and pass it on to the affiliate.", 'affiliate-for-woocommerce' ); ?></p>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
