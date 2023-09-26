<?php

defined( 'ABSPATH' ) or exit;
global $wpdb;

$table_name = $wpdb->prefix . 'mc4wp_log';

$wpdb->query("ALTER TABLE `{$table_name}` MODIFY `ID` INT UNSIGNED AUTO_INCREMENT");
$wpdb->query("ALTER TABLE `{$table_name}` MODIFY `list_id` VARCHAR(50) NOT NULL");
$wpdb->query("ALTER TABLE `{$table_name}` MODIFY `email_address` VARCHAR(100) NOT NULL");
$wpdb->query("ALTER TABLE `{$table_name}` MODIFY `type` VARCHAR(50) NOT NULL");
$wpdb->query("ALTER TABLE `{$table_name}` MODIFY `language` VARCHAR(50) NULL");
$wpdb->query("ALTER TABLE `{$table_name}` MODIFY `related_object_ID` INT UNSIGNED NULL");
