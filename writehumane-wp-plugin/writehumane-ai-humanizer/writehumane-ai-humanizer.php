<?php
/**
 * Plugin Name: WriteHumane AI Humanizer
 * Plugin URI: https://writehumane.com
 * Description: Humanize AI-generated content to pass AI detectors. Supports WriteHumane API, OpenAI, and Anthropic. Integrates with Gutenberg, Classic Editor, and provides a shortcode widget.
 * Version: 1.0.0
 * Author: VIIONR INFOTECH
 * Author URI: https://writehumane.com
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Tested up to: 6.7
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: writehumane-ai-humanizer
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WHAH_VERSION', '1.0.0' );
define( 'WHAH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WHAH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WHAH_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class — Singleton pattern
 */
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
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        add_action( 'init', array( $this, 'load_textdomain' ) );

        // Initialize components
        WHAH_Settings::instance();
        WHAH_REST_API::instance();
        WHAH_Editor::instance();
        WHAH_Shortcode::instance();
        WHAH_Admin::instance();
    }

    public function activate() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'whah_usage';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            input_words int(11) NOT NULL DEFAULT 0,
            output_words int(11) NOT NULL DEFAULT 0,
            api_provider varchar(50) NOT NULL DEFAULT '',
            post_id bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        // Set default options
        $defaults = array(
            'whah_api_provider'       => 'writehumane',
            'whah_writehumane_api_url' => 'https://writehumane.com',
            'whah_writehumane_api_key' => '',
            'whah_openai_api_key'     => '',
            'whah_openai_model'       => 'gpt-4o-mini',
            'whah_anthropic_api_key'  => '',
            'whah_anthropic_model'    => 'claude-sonnet-4-20250514',
            'whah_humanize_mode'      => 'balanced',
            'whah_preserve_keywords'  => '1',
            'whah_monthly_word_limit' => 50000,
            'whah_enable_gutenberg'   => '1',
            'whah_enable_classic'     => '1',
            'whah_enable_shortcode'   => '1',
            'whah_allowed_roles'      => array( 'administrator', 'editor', 'author' ),
        );

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value );
            }
        }
    }

    public function deactivate() {
        // Clean up transients
        delete_transient( 'whah_usage_stats' );
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'writehumane-ai-humanizer', false, dirname( WHAH_PLUGIN_BASENAME ) . '/languages' );
    }
}

/**
 * Initialize the plugin
 */
function writehumane_ai_humanizer() {
    return WriteHumane_AI_Humanizer::instance();
}

writehumane_ai_humanizer();
