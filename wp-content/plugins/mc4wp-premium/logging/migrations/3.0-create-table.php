<?php
defined('ABSPATH') or exit;

/** @var WPDB $wpdb */
global $wpdb;

$table_name = $wpdb->prefix . 'mc4wp_log';
$charset_collate = $wpdb->get_charset_collate();

// Create table if it does not exist
$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
	        ID INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	        email VARCHAR(100) NOT NULL,
	        list_ids VARCHAR(100) NOT NULL,
	        type VARCHAR(40) NOT NULL,
	        success TINYINT(1) DEFAULT 1,
			data TEXT NULL,
	        related_object_ID INT UNSIGNED NULL,
	        url TEXT NULL,
	        datetime timestamp DEFAULT CURRENT_TIMESTAMP
		) $charset_collate";

$wpdb->query($sql);
