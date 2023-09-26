<?php

namespace MC4WP\Premium\AFTP;

require __DIR__ . '/includes/class-plugin.php';
$plugin = new Plugin();
$plugin->hook();

if (is_admin() && (! defined('DOING_AJAX') || ! DOING_AJAX)) {
    require __DIR__ . '/includes/class-admin.php';
    $admin = new Admin();
    $admin->hook();
}
