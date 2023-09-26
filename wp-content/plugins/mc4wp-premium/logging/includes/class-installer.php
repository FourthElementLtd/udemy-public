<?php

/**
 * Class MC4WP_Logging_Installer
 *
 * @ignore
 */
class MC4WP_Logging_Installer
{
    public static function run()
    {
        /** @var WPDB $wpdb */
        global $wpdb;

        $table_name = $wpdb->prefix . 'mc4wp_log';
        $charset_collate = $wpdb->get_charset_collate();

        // create TABLE
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `email_address` VARCHAR(100) NOT NULL,
        `list_id` VARCHAR(50) NOT NULL,
        `type` VARCHAR(50) NOT NULL,
        `merge_fields` TEXT NULL,
        `interests` TEXT NULL,
        `status` VARCHAR(60) NULL,
        `email_type` VARCHAR(4) NULL,
        `ip_signup` VARCHAR(255) NULL,
        `language` VARCHAR(50) NULL,
        `vip` TINYINT(1) NULL,
        `related_object_ID` INT UNSIGNED NULL,
        `url` TEXT NULL,
        `datetime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `success` TINYINT(1) DEFAULT 1
		) ENGINE=INNODB $charset_collate";

        $wpdb->query($sql);
    }
}
