<?php
/**
 * Class for ugrading Ddatabase of Affiliate For WooCommerce
 *
 * @since       1.2.1
 * @version     1.0.6
 *
 * @package     affiliate-for-woocommerce/includes/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'AFWC_DB_Upgrade' ) ) {

	/**
	 * Class for ugrading Ddatabase of Affiliate For WooCommerce
	 */
	class AFWC_DB_Upgrade {

		/**
		 * Variable to hold instance of this class
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Get single instance of this class
		 *
		 * @return AFWC_DB_Upgrade Singleton object of this class
		 */
		public static function get_instance() {

			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {
			add_action( 'init', array( $this, 'initialize_db_upgrade' ) );
		}

		/**
		 * Inititalize database upgrade
		 * Will only have one entry point to run all upgrades
		 */
		public function initialize_db_upgrade() {

			$current_db_version = get_option( '_afwc_current_db_version' );
			if ( version_compare( $current_db_version, '1.2.3', '<' ) || empty( $current_db_version ) ) {
				$this->do_db_upgrade();
			}

		}

		/**
		 * Do the database upgrade
		 */
		public function do_db_upgrade() {
			global $wpdb, $blog_id;

			// For multisite table prefix.
			if ( is_multisite() ) {
				$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}", 0 ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			} else {
				$blog_ids = array( $blog_id );
			}

			foreach ( $blog_ids as $id ) {

				if ( is_multisite() ) {
					switch_to_blog( $id ); // @codingStandardsIgnoreLine
				}

				// All the DB update functions should be called from here since they should run for each blog id.
				if ( false === get_option( '_afwc_current_db_version' ) || '' === get_option( '_afwc_current_db_version' ) ) {
					$this->upgrade_to_1_2_1();
				}

				if ( '1.2.1' === get_option( '_afwc_current_db_version' ) ) {
					$this->upgrade_to_1_2_2();
				}

				if ( '1.2.2' === get_option( '_afwc_current_db_version' ) ) {
					$this->upgrade_to_1_2_3();
				}

				if ( is_multisite() ) {
					restore_current_blog();
				}
			}

		}

		/**
		 * Function to upgrade the database to version 1.2.1
		 */
		public function upgrade_to_1_2_1() {
			global $wpdb;

			$offset = (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

			$afwc_hits_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . 'afwc_hits' ) . '%' ) ); // phpcs:ignore
			if ( ! empty( $afwc_hits_table ) ) {
				$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"UPDATE {$wpdb->prefix}afwc_hits
							SET datetime = DATE_ADD( datetime, INTERVAL %d SECOND )",
						$offset
					)
				);
			}

			$afwc_payouts_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . 'afwc_payouts' ) . '%' ) ); // phpcs:ignore
			if ( ! empty( $afwc_payouts ) ) {
				$wpdb->query(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"UPDATE {$wpdb->prefix}afwc_payouts
							SET datetime = DATE_ADD( datetime, INTERVAL %d SECOND )",
						$offset
					)
				);
			}

			$afwc_referrals_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . 'afwc_referrals' ) . '%' ) ); // phpcs:ignore
			if ( ! empty( $afwc_referrals_table ) ) {
				$wpdb->query(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"UPDATE {$wpdb->prefix}afwc_referrals
							SET datetime = DATE_ADD( datetime, INTERVAL %d SECOND )",
						$offset
					)
				);
			}

			update_option( '_afwc_current_db_version', '1.2.1', 'no' );
		}

		/**
		 * Function to upgrade the database to version 1.2.2
		 */
		public function upgrade_to_1_2_2() {
			$page_id = create_reg_form_page();

			update_option( '_afwc_current_db_version', '1.2.2', 'no' );
		}

		/**
		 * Function to upgrade the database to version 1.2.3
		 */
		public function upgrade_to_1_2_3() {
			global $wpdb;

			// create campaign table.
			$collate = '';

			if ( $wpdb->has_cap( 'collation' ) ) {
				if ( ! empty( $wpdb->charset ) ) {
					$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
				}
				if ( ! empty( $wpdb->collate ) ) {
					$collate .= " COLLATE $wpdb->collate";
				}
			}
			include_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$afwc_campaign_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}afwc_campaigns (
									  id int(20) UNSIGNED NOT NULL AUTO_INCREMENT,
									  title varchar(255) NOT NULL,
									  slug varchar(255) NOT NULL,
									  target_link varchar(255) NOT NULL,
									  short_description mediumtext NOT NULL,
									  body longtext NOT NULL,
									  status enum ('Active', 'Draft') DEFAULT 'Draft',
									  meta_data longtext NOT NULL,
									  PRIMARY KEY  (id)
									) $collate;
						";
			dbDelta( $afwc_campaign_table );

			// alter tables.
			$cols_from_hits = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}afwc_hits" ); // phpcs:ignore
			if ( ! in_array( 'campaign_id', $cols_from_hits, true ) ) {
				$wpdb->query( "ALTER table {$wpdb->prefix}afwc_hits ADD campaign_id int(20) DEFAULT 0" );// phpcs:ignore
			}

			$cols_from_referrals = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}afwc_referrals" ); // phpcs:ignore
			if ( ! in_array( 'campaign_id', $cols_from_referrals, true ) ) {
				$wpdb->query( "ALTER table {$wpdb->prefix}afwc_referrals ADD campaign_id int(20) DEFAULT 0" );// phpcs:ignore
			}

			// import sample campaign data.
			$sample_campaigns = $this->get_sample_campaigns();
			if ( ! empty( $sample_campaigns ) ) {
				foreach ( $sample_campaigns as $campaign ) {
					$wpdb->insert( // phpcs:ignore
						$wpdb->prefix . 'afwc_campaigns',
						$campaign,
						array( '%s', '%s', '%s', '%s', '%s', '%s' )
					);
				}
			}

			update_option( '_afwc_current_db_version', '1.2.3', 'no' );
		}

		/**
		 * Function to get sample campaign.
		 */
		public function get_sample_campaigns() {
			$sample_campaigns = array();

			$sample                      = array();
			$sample['title']             = 'Start Here: Common Assets, Logo, Branding';
			$sample['slug']              = 'common';
			$sample['target_link']       = '';
			$sample['short_description'] = 'We\'ve included the most important design assets for you here. Please follow style guide and respect the terms of the affiliate program.';
			$sample['body']              = '<section class="mt-8 space-y-2">
<h2 class="text-2xl text-gray-900">Logo &amp; Style Guide</h2>
<p>Our logo and logo variations are our own property and we retain all rights afforded by US and international Law.</p>
<p>As an affiliate partner, you can use our logo on your site to promote our products. But please ensure you follow the color, sizing and other branding guidelines.</p>
<div class="grid grid-cols-4 gap-8">
<div class="flex flex-col p-8 text-center bg-white border border-gray-200"><img class="h-10" alt="logo" src="https://www.storeapps.org/wp-content/uploads/2020/07/storeapps-logo.svg"/> <span class="mt-1 text-xs text-gray-500">Dark on light background</span></div>
<div class="flex flex-col p-8 text-center bg-gray-900 border border-gray-200"><img class="h-10" alt="logo" src="https://www.storeapps.org/wp-content/uploads/2020/07/storeapps-logo-for-dark-bg.svg"/> <span class="mt-1 text-xs text-gray-400">Light on dark background</span></div>
<div>&nbsp;</div>
<div>&nbsp;</div>
</div>
<p><a href="#"><strong>Download logo pack</strong></a><br /><span class="text-sm text-gray-600">(contains .png and .svg versions, both on light and dark backgrounds)</span></p>
<p>Before using, please <a class="underline" href="https://woocommerce.com/style-guide/">read our detailed style guide</a>: what\'s allowed and what\'s not.</p>
</section>
<section class="mt-8 space-y-2">
<h2 class="text-2xl text-gray-900">Color Palette</h2>
<div class="grid grid-cols-4 gap-8">
<div class="p-8 text-white bg-indigo-600">Primary color (Indigo): #5850ec</div>
<div class="p-8 text-white bg-gray-900">Secondary color (Dark Gray): #1a202e</div>
<div>&nbsp;</div>
<div>&nbsp;</div>
</div>
</section>
<section class="mt-8 space-y-2">
<h2 class="text-2xl text-gray-900">Typography</h2>
<p>We use one primary typeface in all our marketing materials - Proxima Nova.</p>
<p>Proxima Nova is available from Adobe Typekit. If you do not have access to it, you may use another Sans Serif font.</p>
</section>
<section class="mt-8 space-y-2">
<h2 class="text-2xl text-gray-900">Banner Ads / Creatives</h2>
<p>Feel free to create your own banners and graphics to promote us. Something your audience will resonate with. We\'ve found that works best.</p>
<p>Here are some banners that you can use as-is, or as an inspiration.</p>
<div class="flex flex-wrap flex-auto space-x-8 space-y-8 text-xs text-gray-500">
<p class="mt-8">Google Small Square (200x200 px) <br /><img class="bg-gray-300 border border-gray-700" border="1" style="height: 200px; width: 200px;" src="https://www.storeapps.org/wp-content/uploads/2013/01/spacer.gif" width="200" height="200" /></p>
<p>Google Vertical Rectangle (240&times;400 px) <br /><img class="bg-gray-300 border border-gray-700" border="1" style="height: 400px; width: 240px;" src="https://www.storeapps.org/wp-content/uploads/2013/01/spacer.gif" width="240" height="400" /></p>
<p>Google Square (250&times;250 px) <br /><img class="bg-gray-300 border border-gray-700" border="1" style="height: 250px; width: 250px;" src="https://www.storeapps.org/wp-content/uploads/2013/01/spacer.gif" width="250" height="250" /></p>
<p>Google Inline Rectangle (300&times;250 px)<br /><img class="bg-gray-300 border border-gray-700" border="1" style="height: 250px; width: 300px;" src="https://www.storeapps.org/wp-content/uploads/2013/01/spacer.gif" width="300" height="250" /></p>
<p>Google Skyscraper (120&times;600 px) <br /><img class="bg-gray-300 border border-gray-700" border="1" style="height: 600px; width: 120px;" src="https://www.storeapps.org/wp-content/uploads/2013/01/spacer.gif" width="120" height="600" /></p>
<p>Google Wide Skyscraper (160&times;600 px) <br /><img class="bg-gray-300 border border-gray-700" border="1" style="height: 600px; width: 160px;" src="https://www.storeapps.org/wp-content/uploads/2013/01/spacer.gif" width="160" height="600" /></p>
<p>Google HalfPage Ad (300&times;600 px)<br /><img class="bg-gray-300 border border-gray-700" border="1" style="height: 600px; width: 300px;" src="https://www.storeapps.org/wp-content/uploads/2013/01/spacer.gif" width="300" height="600" /></p>
<p>Google Banner (468&times;60 px) <br /><img class="bg-gray-300 border border-gray-700" border="1" style="height: 60px; width: 468px;" src="https://www.storeapps.org/wp-content/uploads/2013/01/spacer.gif" width="468" height="60" /></p>
<p>Google Leaderboard (728&times;90 px) <br /><img class="bg-gray-300 border border-gray-700" border="1" style="height: 90px; width: 728px;" src="https://www.storeapps.org/wp-content/uploads/2013/01/spacer.gif" width="728" height="90" /></p>
<p>Google Large Leaderboard (970&times;90 px)<br /><img class="bg-gray-300 border border-gray-700" border="1" style="height: 90px; width: 970px;" src="https://www.storeapps.org/wp-content/uploads/2013/01/spacer.gif" width="970" height="90" /></p>
<p>Google Billboard (970&times;250 px) <br /><img class="bg-gray-300 border border-gray-700" border="1" style="height: 250px; width: 970px;" src="https://www.storeapps.org/wp-content/uploads/2013/01/spacer.gif" width="970" height="250" /></p>
<p>Google Mobile Banner (320&times;50 px) <br /><img class="bg-gray-300 border border-gray-700" border="1" style="height: 50px; width: 320px;" src="https://www.storeapps.org/wp-content/uploads/2013/01/spacer.gif" width="320" height="50" /></p>
<p>Google Large Mobile Banner (320&times;100 px) <br /><img class="bg-gray-300 border border-gray-700" border="1" style="height: 100px; width: 320px;" src="https://www.storeapps.org/wp-content/uploads/2013/01/spacer.gif" width="320" height="100" /></p>
<p>Facebook Ad (1200&times;628 px) <br /><img class="bg-gray-300 border border-gray-700" border="1" style="height: 628px; width: 1200px;" src="https://www.storeapps.org/wp-content/uploads/2013/01/spacer.gif" width="1200" height="628" /></p>
<p>Twitter Lead Generation Card (800&times;200 px) <br /><img class="bg-gray-300 border border-gray-700" border="1" style="height: 200px; width: 800px;" src="https://www.storeapps.org/wp-content/uploads/2013/01/spacer.gif" width="800" height="200" /></p>
<p>Twitter Image App Card (800&times;320 px) <br /><img class="bg-gray-300 border border-gray-700" border="1" style="height: 320px; width: 800px;" src="https://www.storeapps.org/wp-content/uploads/2013/01/spacer.gif" width="800" height="320" /></p>
<p>Youtube Display Ad (300&times;60 px) <br /><img class="bg-gray-300 border border-gray-700" border="1" style="height: 60px; width: 300px;" src="https://www.storeapps.org/wp-content/uploads/2013/01/spacer.gif" width="300" height="60" /></p>
<p>Youtube Display Ad (300&times;250 px) <br /><img class="bg-gray-300 border border-gray-700" border="1" style="height: 250px; width: 300px;" src="https://www.storeapps.org/wp-content/uploads/2013/01/spacer.gif" width="300" height="250" /></p>
<p>Youtube Overlay Ad (480&times;70 px) <br /><img class="bg-gray-300 border border-gray-700" border="1" style="height: 70px; width: 480px;" src="https://www.storeapps.org/wp-content/uploads/2013/01/spacer.gif" width="480" height="70" /></p>
<p>Adroll Rectangle (180&times;150 px)<br /><img class="bg-gray-300 border border-gray-700" border="1" style="height: 150px; width: 180px;" src="https://www.storeapps.org/wp-content/uploads/2013/01/spacer.gif" width="180" height="150" /></p>
</div>
<p><a href="#"><strong>Download banner pack</strong></a><br /><span class="text-sm text-gray-600">(contains optimized image formats)</span></p>
</section>';
			$sample['status']            = 'Draft';
			$sample_campaigns[]          = $sample;

			$sample                      = array();
			$sample['title']             = 'Email Swipes';
			$sample['slug']              = 'email';
			$sample['target_link']       = '';
			$sample['short_description'] = 'Email marketing has very high conversion rates. Emailing your audience about our products can be one of the quickest ways to make money. Here are some ready emails you can use (and tweak as you like).';
			$sample['body']              = '<section class="mt-8 space-y-2">
<h2 class="text-2xl text-gray-900">New Product Launch</h2>
<p><strong>SUBJECT: Just Launched - Awesome Product Name</strong></p>
<textarea class="form-textarea" cols="80" rows="20">Hi,

Want to {your product\'s main benefit}?

I\'ve just discovered the right solution - {your product\'s name}.

It works really well.

{affiliate link here}

Here\'s why I love this company and their products:

* Benefit 1
* Benefit 2
* Unique Feature 1
* Unique Feature 2
* Their attention to detail
+ They\'re just super nice to do business with

If you\'re looking to {another benefit + scarcity}, this is it!

Get it here:

{affiliate link here}

To your success,
{affiliate name}
</textarea></section>
<section class="mt-8 space-y-2">
<h2 class="text-2xl text-gray-900">Solution to their problem</h2>
<p><strong>SUBJECT: Your Solution for {audience\'s big problem}</strong></p>
<textarea class="form-textarea" cols="80" rows="20">Hi,

Hope you\'re doing well.

When it comes to list building and email marketing, there are so many ways to approach it.

Wouldn\'t it be great to just get the meat of it all so you can get started faster?

Well, the good news is, today you can grab my latest course called the \'Email List Building Blueprint\'.

Get one for you here:

{affiliate link here}

Here\'s why you should buy this:

* Benefit 1
* Benefit 2
* Unique Feature 1
* Unique Feature 2
* Their attention to detail
+ They\'re just super nice to do business with

It\'s rather simple. You could either spend hours, even days researching how to {benefit} or get this {product}
and speed up your success.

Get instant access here:

{affiliate link here}

To your success,
{affiliate name}
</textarea></section>';
			$sample['status']            = 'Draft';
			$sample_campaigns[]          = $sample;

			$sample                      = array();
			$sample['title']             = 'Single Product Promotion';
			$sample['slug']              = 'product';
			$sample['target_link']       = '';
			$sample['short_description'] = 'Promoting a single product requires slightly different marketing material than a whole brand. As a matter of fact, individual products would be easier to promote because you can talk about specific benefits and how they relate to your target audience. Here are some resources to promote a single product.';
			$sample['body']              = '<section class="mt-8 space-y-2">
<h2 class="text-2xl text-gray-900">{Awesome Product} Promo</h2>
<p>You may use any of these marketing assets:</p>
<ul>
<li>Product Name: {Awesome Product}</li>
<li>Photos: {link to product\'s main image}, {link to product\'s other images}</li>
<li>Video: {link to product\'s promo video}</li>
<li>Description: {product description}</li>
<li>Price: {product price}</li>
<li>Offer Price: {product discounted price}</li>
<li>Coupon: {coupon code}</li>
</ul>
</section>
<section class="mt-8 space-y-2">
<h2 class="text-2xl text-gray-900">Want to promote another product?</h2>
<ul>
<li>You may use product name, photos, videos, description and other marketing resources from our sales pages in your own promotional material.</li>
<li>To take people directly to a product\'s page, append your affiliate tracking code to the URL, test it works, and then share with your people.</li>
<li>You can use any channel of your choice - email, social media, direct promotions...</li>
<li>If you would like to provide a coupon code, please contact us and we can work something out.</li>
</ul>
</section>';
			$sample['status']            = 'Draft';
			$sample_campaigns[]          = $sample;
			return $sample_campaigns;
		}

	}
}

AFWC_DB_Upgrade::get_instance();
