<?php

global $wpdb;

$table_name = $wpdb->prefix . 'mc4wp_log';
$wpdb->query("ALTER TABLE {$table_name} CHANGE COLUMN `datetime` `datetime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");