<?php
include(__DIR__ . '../../../../../wp-config.php');
global $wpdb;
error_log($_GET['getSalt']);
$getDiver = $wpdb->query("DELETE FROM wp_fe_divers WHERE id = ".$_GET['getSalt']."");
print_r($result);