<?php

use MC4WP\PostCampaign\Gutenberg_Editor;

defined( 'ABSPATH' ) or exit;

if ( ! function_exists( 'mc4wp' ) ) {
	return;
}

if ( ! method_exists( MC4WP_API_V3::class, 'add_template' ) ) {
	return;
}

if ( ! function_exists( 'register_post_meta' ) ) {
	return;
}

$gutenberg_editor = new Gutenberg_Editor( __FILE__ );
$gutenberg_editor->hook();
