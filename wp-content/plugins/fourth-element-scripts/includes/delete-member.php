<?php
include(__DIR__ . '../../../../../wp-config.php');
global $wpdb;
$getMember = $wpdb->query("DELETE FROM wp_fe_team WHERE id = ".$_GET['getSalt']."");
print_r($result);