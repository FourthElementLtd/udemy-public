<?php

global $wpdb;

$table_name = $wpdb->prefix . 'mc4wp_log';

$wpdb->suppress_errors(true);
$wpdb->hide_errors();

$wpdb->query("ALTER TABLE `{$table_name}` CHANGE COLUMN `url` `url` TEXT DEFAULT ''");
