<?php
include(__DIR__ . '../../../../../wp-config.php');
global $wpdb;

$getRecs = $wpdb->get_results("SELECT ID,post_author FROM wp_3_posts WHERE post_date <= DATE_SUB(SYSDATE(), INTERVAL 30 DAY) AND post_status = 'publish' AND post_type = 'my-wishlist' ORDER BY ID DESC");

$count = 0;

foreach ( $getRecs as $rec ) {
    wp_delete_post( $rec->ID );
    $count = $count + 1;
    //clear wishlist post for any logged in users
    update_user_meta($rec,'wishlist_post','');
}

print_r($count);