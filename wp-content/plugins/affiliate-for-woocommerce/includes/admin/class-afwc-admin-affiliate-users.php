<?php
/**
 * Main class for Affiliate search on users page
 *
 * @since       1.4.0
 * @version     1.1.1
 *
 * @package     affiliate-for-woocommerce/includes/admin/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_Admin_Affiliate_Users' ) ) {

	/**
	 * Class for Admin Affiliate User Filter
	 */
	class AFWC_Admin_Affiliate_Users {

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'pre_get_users', array( $this, 'search_by_users_query' ), 20 );
			add_action( 'init', array( $this, 'afwc_register_user_tags_taxonomy' ) );
			add_action( 'admin_menu', array( $this, 'afwc_add_user_tags_admin_page' ) );
			add_filter( 'parent_file', array( $this, 'afwc_set_submenu_active' ) );

		}

		/**
		 * Get single instance of this class
		 *
		 * @return AFWC_Admin_Affiliate_Users Singleton object of this class
		 */
		public function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Function to search for affiliates on Users Dashboard
		 *
		 * @param Object $query WP_User_Query.
		 */
		public function search_by_users_query( $query ) {
			global $pagenow;

			if ( 'users.php' !== $pagenow ) {
				return;
			}

			if ( empty( $query->query_vars['search'] ) ) {
				return;
			}

			// Remove trailing and starting empty spaces.
			$search_term = trim( $query->query_vars['search'] );

			// Remove * from the search term.
			$search_term = trim( $query->query_vars['search'], '*' );

			if ( 'affiliate:' === strtolower( substr( $search_term, 0, 10 ) ) ) {
				$is_user_afiliate = trim( substr( $search_term, 11 ) );

				if ( 'no' === $is_user_afiliate ) {
					return;
				} elseif ( 'yes' === $is_user_afiliate ) {
					$query->set( 'meta_key', 'afwc_is_affiliate' );
					$query->set( 'meta_value', $is_user_afiliate );
					$query->set( 'meta_compare', 'LIKE' );
					$query->set( 'search', '' );
				}
			}
		}

		/**
		 * Function to register affiliate tags taxonomy
		 */
		public function afwc_register_user_tags_taxonomy() {
			register_taxonomy(
				'afwc_user_tags', // taxonomy name.
				'user', // object for which the taxonomy is created.
				array( // taxonomy details.
					'public'                => true,
					'labels'                => array(
						'name'          => 'Affiliate Tags',
						'singular_name' => 'Affiliate Tag',
						'menu_name'     => 'Affiliate Tags',
						'search_items'  => 'Search Affiliate Tag',
						'popular_items' => 'Popular Affiliate Tags',
						'all_items'     => 'All Affiliate Tags',
						'edit_item'     => 'Edit Affiliate Tag',
						'update_item'   => 'Update Affiliate Tag',
						'add_new_item'  => 'Add New Affiliate Tag',
						'new_item_name' => 'New Affiliate Tag Name',
						'show_in_menu'  => false,
					),
					'update_count_callback' => function() {
						return;
					},
				)
			);

			$default_affiliate_tags    = array( 'Gold', 'Silver', 'Bronze', 'Platinum', 'Dormant', 'Active', 'Promoter', 'Influencer' );
			$afwc_default_tags_created = get_option( 'afwc_default_tags_created', false );
			if ( ! $afwc_default_tags_created ) {
				foreach ( $default_affiliate_tags  as $value ) {
					wp_insert_term( $value, 'afwc_user_tags' );
				}
				update_option( 'afwc_default_tags_created', true );
			}
		}


		/**
		 * Function to add page for affiliate tags
		 */
		public function afwc_add_user_tags_admin_page() {
			$taxonomy = get_taxonomy( 'afwc_user_tags' );
			add_submenu_page( 'users', esc_attr( $taxonomy->labels->menu_name ), esc_attr( $taxonomy->labels->menu_name ), $taxonomy->cap->manage_terms, 'edit-tags.php?taxonomy=' . $taxonomy->name );
		}


		/**
		 * Function to set affiliate submenu active
		 *
		 * @param String $parent_file file reference for menu.
		 */
		public function afwc_set_submenu_active( $parent_file ) {
			global $current_screen;

			$id = $current_screen->id;
			if ( 'edit-afwc_user_tags' === $id ) {
				$parent_file = 'woocommerce';
				?>
				<script type="text/javascript">
					jQuery( function(){
						jQuery('#toplevel_page_woocommerce').find('a[href$="admin.php?page=affiliate-for-woocommerce"]').addClass('current');
						jQuery('#toplevel_page_woocommerce').find('a[href$="admin.php?page=affiliate-for-woocommerce"]').parent().addClass('current');
					});
				</script>
				<?php
			}

			return $parent_file;
		}

	}
}

return new AFWC_Admin_Affiliate_Users();
