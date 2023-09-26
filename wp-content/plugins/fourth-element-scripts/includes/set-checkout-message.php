<?php
include(__DIR__ . '../../../../../wp-config.php');
global $wpdb;
date_default_timezone_set('Europe/London');
$message = $_GET['mess'];
$dts = explode("/",$_GET['dts']);
$dte = explode("/",$_GET['dte']);

$message = "hello this is a test";
$dts = "24/01/2021";
$dte = "26/01/2021";

//convert dates to usa format for storing in DB
$dts = strtotime($dts[1]."/".$dts[0]."/".$dts[2]."00:00:01");
$dte = strtotime($dte[1]."/".$dte[0]."/".$dte[2]."23:59:59");

//load the details into the options table
echo "UPDATE wp_options SET option_value = '".$message."' WHERE option_id = 7325204 and option_name = 'fechkmessage'";
$updateMsg = $wpdb->query("UPDATE wp_options SET option_value = '".$message."' WHERE option_id = 7325204 and option_name = 'fechkmessage'");
echo "UPDATE wp_options SET option_value = '".$dts."' WHERE option_id = 7325205 and option_name = 'fechkdts'";
$updateDts = $wpdb->query("UPDATE wp_options SET option_value = '".$dts."' WHERE option_id = 7325205 and option_name = 'fechkdts'");
echo "UPDATE wp_options SET option_value = '".$dte."' WHERE option_id = 7325206 and option_name = 'fechkdte'";
$updateDte = $wpdb->query("UPDATE wp_options SET option_value = '".$dte."' WHERE option_id = 7325206 and option_name = 'fechkdte'");
$result = 1;
print_r($result);