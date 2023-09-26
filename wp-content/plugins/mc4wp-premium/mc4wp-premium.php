<?php
/*
Plugin Name: MC4WP: Mailchimp for WordPress Premium
Plugin URI: https://www.mc4wp.com/
Description: Add-on for the MC4WP: Mailchimp for WordPress plugin. Adds Premium functionality when activated.
Version: 4.8.21
Author: ibericode
Author URI: https://ibericode.com/
License: GPL v3
Text Domain: mailchimp-for-wp

Mailchimp for WordPress Premium alias MC4WP Premium
Copyright (C) 2012-2022, Danny van Kooten, danny@ibericode.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


// Prevent direct file access
defined('ABSPATH') or exit;

// Define some useful constants
define('MC4WP_PREMIUM_VERSION', '4.8.21');
define('MC4WP_PREMIUM_PLUGIN_FILE', __FILE__);

/**
 * Loads the various premium add-on plugins
 *
 * @access private
 * @ignore
 */
function _mc4wp_premium_load()
{
	// bail if not on at least PHP 5.3
	if (version_compare(PHP_VERSION, '5.3', '<')) {
		return;
	}

    // load autoloader
    require_once __DIR__ . '/vendor/autoload.php';

    // make sure core plugin is installed and at version 3.0.8
    if (! defined('MC4WP_VERSION') || version_compare(MC4WP_VERSION, '3.0.8', '<')) {

        // if not, show a notice
        $required_plugins = array(
            'mailchimp-for-wp' => array(
                'url' => 'https://wordpress.org/plugins/mailchimp-for-wp/',
                'name' => 'Mailchimp for WordPress core',
                'version' => '3.0.8'
            )
        );
        $notice = new MC4WP_Required_Plugins_Notice('Mailchimp for WordPress - Premium', $required_plugins);
        $notice->add_hooks();
        return;
    }

    // PHP 5.3 compatible plugins
    $plugins = array(
        'ajax-forms',
        'custom-color-theme',
        'email-notifications',
        'styles-builder',
        'multiple-forms',
        'lucy',
        'logging',
	    'ecommerce-loader',
	    'licensing',
	    'append-form-to-post'
    );

	// PHP 7.0+ plugins
    if (PHP_MAJOR_VERSION >= 7) {
    	$plugins[] = 'user-sync';
	    $plugins[] = 'post-campaign';
	}

    /**
     * Filters which add-on plugins should be loaded
     *
     * Takes an array of plugin slugs, defaults to all plugins.
     *
     * @param array $plugins
     */
    $plugins = apply_filters('mc4wp_premium_enabled_plugins', $plugins);

    // include each plugin
    foreach ($plugins as $plugin) {
        require __DIR__ . "/{$plugin}/{$plugin}.php";
    }
}

add_action('plugins_loaded', '_mc4wp_premium_load', 30);

/**
 * @access private
 * @ignore
 * */
function _mc4wp_premium_activate()
{
    // run logging installer
    require_once __DIR__ . '/logging/includes/class-installer.php';
    MC4WP_Logging_Installer::run();
}

register_activation_hook(__FILE__, '_mc4wp_premium_activate');
