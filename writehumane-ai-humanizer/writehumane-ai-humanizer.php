<?php
/**
 * Plugin Name: WriteHumane AI Humanizer
 * Plugin URI: https://writehumane.com
 * Description: Humanize AI-generated content with one click. Works in Gutenberg, Classic Editor, and via shortcode. Passes all AI detectors.
 * Version: 1.0.0
 * Author: VIIONR INFOTECH
 * Author URI: https://writehumane.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: writehumane-ai-humanizer
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Tested up to: 6.7
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WHAH_VERSION', '1.0.0' );
define( 'WHAH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WHAH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WHAH_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

final class WriteHumane_AI_Humanizer {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    private function includes() {
        require_once WHAH_PLUGIN_DIR . 'includes/class-whah-freemius.php';
        require_once WHAH_PLUGIN_DIR . 'includes/class-whah-api.php';
        require_once WHAH_PLUGIN_DIR . 'includes/class-whah-rest-api.php';
        require_once WHAH_PLUGIN_DIR . 'includes/class-whah-settings.php';
        require_once WHAH_PLUGIN_DIR . 'includes/class-whah-editor.php';
        require_once WHAH_PLUGIN_DIR . 'includes/class-whah-shortcode.php';
        require_once WHAH_PLUGIN_DIR . 'includes/class-whah-usage-tracker.php';
        require_once WHAH_PLUGIN_DIR . 'admin/class-whah-admin.php';
    }

    private function init_hooks() {
        register_activation_hook( __FILE__, array( $this, 'activate' ) );

        WHAH_Settings::instance();
        WHAH_Rest_API::instance();
        WHAH_Editor::instance();
        WHAH_Shortcode::instance();
        WHAH_Admin::instance();
    }

    public function activate() {
        global $wpdb;
        $table = $wpdb->prefix . 'whah_usage';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            input_words int(11) NOT NULL DEFAULT 0,
            output_words int(11) NOT NULL DEFAULT 0,
            mode varchar(20) DEFAULT 'balanced',
            post_id bigint(20) unsigned DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        // Default options
        $defaults = array(
            'whah_gemini_api_key'     => '',
            'whah_gemini_model'       => 'gemini-2.0-flash',
            'whah_humanize_mode'      => 'balanced',
            'whah_default_tone'       => 'professional',
            'whah_preserve_keywords'  => '1',
            'whah_monthly_word_limit' => 50000,
            'whah_enable_gutenberg'   => '1',
            'whah_enable_classic'     => '1',
            'whah_enable_shortcode'   => '1',
            'whah_allowed_roles'      => array( 'administrator', 'editor', 'author' ),
            'whah_brand_name'         => 'WriteHumane',
            'whah_backend_url'        => '',
            'whah_backend_key'        => '',
            'whah_domain_url'         => '',
            'whah_domain_api_key'     => '',
            'whah_domain_status'      => 'disconnected',
            'whah_domain_last_check'  => '',
        );

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value );
            }
        }
    }
}

function writehumane_ai_humanizer() {
    return WriteHumane_AI_Humanizer::instance();
}

writehumane_ai_humanizer();
