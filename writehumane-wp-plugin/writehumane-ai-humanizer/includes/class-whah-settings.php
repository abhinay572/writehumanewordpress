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
        // API Provider
        register_setting( 'whah_settings', 'whah_api_provider', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'writehumane',
        ) );

        // WriteHumane settings
        register_setting( 'whah_settings', 'whah_writehumane_api_url', array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => 'https://writehumane.com',
        ) );
        register_setting( 'whah_settings', 'whah_writehumane_api_key', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );

        // OpenAI settings
        register_setting( 'whah_settings', 'whah_openai_api_key', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
        register_setting( 'whah_settings', 'whah_openai_model', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'gpt-4o-mini',
        ) );

        // Anthropic settings
        register_setting( 'whah_settings', 'whah_anthropic_api_key', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
        register_setting( 'whah_settings', 'whah_anthropic_model', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'claude-sonnet-4-20250514',
        ) );

        // General settings
        register_setting( 'whah_settings', 'whah_humanize_mode', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'balanced',
        ) );
        register_setting( 'whah_settings', 'whah_preserve_keywords', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '1',
        ) );
        register_setting( 'whah_settings', 'whah_monthly_word_limit', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 50000,
        ) );

        // Feature toggles
        register_setting( 'whah_settings', 'whah_enable_gutenberg', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '1',
        ) );
        register_setting( 'whah_settings', 'whah_enable_classic', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '1',
        ) );
        register_setting( 'whah_settings', 'whah_enable_shortcode', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '1',
        ) );

        // Allowed roles
        register_setting( 'whah_settings', 'whah_allowed_roles', array(
            'type'              => 'array',
            'sanitize_callback' => array( $this, 'sanitize_roles' ),
            'default'           => array( 'administrator', 'editor', 'author' ),
        ) );
    }

    public function sanitize_roles( $roles ) {
        if ( ! is_array( $roles ) ) {
            return array( 'administrator' );
        }
        return array_map( 'sanitize_text_field', $roles );
    }
}
