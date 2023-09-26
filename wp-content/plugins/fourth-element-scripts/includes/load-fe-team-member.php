<?php
include(__DIR__ . '../../../../../wp-config.php');
global $wpdb;
$getMember = $wpdb->get_row("SELECT * FROM wp_fe_team WHERE id = ".$_GET['femember']."");
$sendResult = $getMember->firstname.'|'.$getMember->lastname.'|'.$getMember->email.'|'.$getMember->emailonoff.'|'.$getMember->office.'|'.$getMember->jobtitle.'|'.$getMember->imgurl.'|'.$getMember->bio.'|'.$getMember->id;
print_r($sendResult);