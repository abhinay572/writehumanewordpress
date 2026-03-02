<?php
/**
 * Fired when the plugin is uninstalled.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete all plugin options
$options = array(
    'whah_gemini_api_key',
    'whah_gemini_model',
    'whah_humanize_mode',
    'whah_default_tone',
    'whah_preserve_keywords',
    'whah_monthly_word_limit',
    'whah_enable_gutenberg',
    'whah_enable_classic',
    'whah_enable_shortcode',
    'whah_allowed_roles',
    'whah_brand_name',
    'whah_backend_url',
    'whah_backend_key',
);

foreach ( $options as $option ) {
    delete_option( $option );
}

// Drop usage table
global $wpdb;
$table = $wpdb->prefix . 'whah_usage';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
