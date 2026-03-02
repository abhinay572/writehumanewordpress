<?php
/**
 * Uninstall WriteHumane AI Humanizer
 *
 * Removes all plugin data when uninstalled via WordPress admin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete all plugin options
$options = array(
    'whah_api_provider',
    'whah_writehumane_api_url',
    'whah_writehumane_api_key',
    'whah_openai_api_key',
    'whah_openai_model',
    'whah_anthropic_api_key',
    'whah_anthropic_model',
    'whah_humanize_mode',
    'whah_preserve_keywords',
    'whah_monthly_word_limit',
    'whah_enable_gutenberg',
    'whah_enable_classic',
    'whah_enable_shortcode',
    'whah_allowed_roles',
);

foreach ( $options as $option ) {
    delete_option( $option );
}

// Drop the usage tracking table
global $wpdb;
$table_name = $wpdb->prefix . 'whah_usage';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

// Clean up transients
delete_transient( 'whah_usage_stats' );
