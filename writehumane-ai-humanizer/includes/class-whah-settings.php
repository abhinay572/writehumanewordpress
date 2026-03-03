<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WHAH_Settings {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function register_settings() {
        // API Settings
        register_setting( 'whah_settings', 'whah_gemini_api_key', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        register_setting( 'whah_settings', 'whah_gemini_model', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'gemini-2.0-flash',
        ) );

        // Humanizer settings
        register_setting( 'whah_settings', 'whah_humanize_mode', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'balanced',
        ) );
        register_setting( 'whah_settings', 'whah_default_tone', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'professional',
        ) );
        register_setting( 'whah_settings', 'whah_preserve_keywords', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '1',
        ) );

        // Limits
        register_setting( 'whah_settings', 'whah_monthly_word_limit', array(
            'sanitize_callback' => 'absint',
            'default'           => 50000,
        ) );

        // Features
        register_setting( 'whah_settings', 'whah_enable_gutenberg', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '1',
        ) );
        register_setting( 'whah_settings', 'whah_enable_classic', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '1',
        ) );
        register_setting( 'whah_settings', 'whah_enable_shortcode', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '1',
        ) );

        // Roles
        register_setting( 'whah_settings', 'whah_allowed_roles', array(
            'sanitize_callback' => array( $this, 'sanitize_roles' ),
            'default'           => array( 'administrator', 'editor', 'author' ),
        ) );

        // Brand
        register_setting( 'whah_settings', 'whah_brand_name', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'WriteHumane',
        ) );

        // Backend tracking
        register_setting( 'whah_settings', 'whah_backend_url', array(
            'sanitize_callback' => 'esc_url_raw',
        ) );
        register_setting( 'whah_settings', 'whah_backend_key', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );

        // Domain Connection (separate settings group for Connect page)
        register_setting( 'whah_domain_settings', 'whah_domain_url', array(
            'sanitize_callback' => 'esc_url_raw',
        ) );
        register_setting( 'whah_domain_settings', 'whah_domain_api_key', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        register_setting( 'whah_domain_settings', 'whah_domain_status', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'disconnected',
        ) );
        register_setting( 'whah_domain_settings', 'whah_domain_last_check', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
    }

    public function sanitize_roles( $input ) {
        if ( ! is_array( $input ) ) {
            return array( 'administrator' );
        }
        return array_map( 'sanitize_text_field', $input );
    }
}
