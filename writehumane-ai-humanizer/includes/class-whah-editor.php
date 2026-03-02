<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WHAH_Editor {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Gutenberg
        if ( '1' === get_option( 'whah_enable_gutenberg', '1' ) ) {
            add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_gutenberg' ) );
        }

        // Classic Editor
        if ( '1' === get_option( 'whah_enable_classic', '1' ) ) {
            add_action( 'add_meta_boxes', array( $this, 'add_classic_meta_box' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_classic' ) );
        }
    }

    /**
     * Gutenberg sidebar plugin
     */
    public function enqueue_gutenberg() {
        wp_enqueue_style(
            'whah-editor',
            WHAH_PLUGIN_URL . 'assets/css/editor.css',
            array(),
            WHAH_VERSION
        );

        wp_enqueue_script(
            'whah-gutenberg',
            WHAH_PLUGIN_URL . 'assets/js/gutenberg.js',
            array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-compose', 'wp-i18n' ),
            WHAH_VERSION,
            true
        );

        wp_localize_script( 'whah-gutenberg', 'whahEditor', array(
            'restUrl'    => rest_url( 'writehumane/v1/' ),
            'nonce'      => wp_create_nonce( 'wp_rest' ),
            'brandName'  => get_option( 'whah_brand_name', 'WriteHumane' ),
            'defaultMode'=> get_option( 'whah_humanize_mode', 'balanced' ),
            'defaultTone'=> get_option( 'whah_default_tone', 'professional' ),
        ) );
    }

    /**
     * Classic Editor meta box
     */
    public function add_classic_meta_box() {
        $post_types = get_post_types( array( 'public' => true ), 'names' );
        foreach ( $post_types as $pt ) {
            add_meta_box(
                'whah-classic-humanizer',
                get_option( 'whah_brand_name', 'WriteHumane' ) . ' AI Humanizer',
                array( $this, 'render_classic_meta_box' ),
                $pt,
                'side',
                'high'
            );
        }
    }

    public function render_classic_meta_box() {
        $mode = get_option( 'whah_humanize_mode', 'balanced' );
        $tone = get_option( 'whah_default_tone', 'professional' );
        ?>
        <div class="whah-classic-box">
            <p>
                <label><strong><?php esc_html_e( 'Mode:', 'writehumane-ai-humanizer' ); ?></strong></label>
                <select id="whah-classic-mode" style="width:100%;margin-top:4px;">
                    <option value="light" <?php selected( $mode, 'light' ); ?>><?php esc_html_e( 'Light', 'writehumane-ai-humanizer' ); ?></option>
                    <option value="balanced" <?php selected( $mode, 'balanced' ); ?>><?php esc_html_e( 'Balanced', 'writehumane-ai-humanizer' ); ?></option>
                    <option value="aggressive" <?php selected( $mode, 'aggressive' ); ?>><?php esc_html_e( 'Aggressive', 'writehumane-ai-humanizer' ); ?></option>
                </select>
            </p>
            <p>
                <label><strong><?php esc_html_e( 'Tone:', 'writehumane-ai-humanizer' ); ?></strong></label>
                <select id="whah-classic-tone" style="width:100%;margin-top:4px;">
                    <option value="professional" <?php selected( $tone, 'professional' ); ?>><?php esc_html_e( 'Professional', 'writehumane-ai-humanizer' ); ?></option>
                    <option value="casual" <?php selected( $tone, 'casual' ); ?>><?php esc_html_e( 'Casual', 'writehumane-ai-humanizer' ); ?></option>
                    <option value="academic" <?php selected( $tone, 'academic' ); ?>><?php esc_html_e( 'Academic', 'writehumane-ai-humanizer' ); ?></option>
                    <option value="friendly" <?php selected( $tone, 'friendly' ); ?>><?php esc_html_e( 'Friendly', 'writehumane-ai-humanizer' ); ?></option>
                </select>
            </p>
            <p>
                <button type="button" id="whah-classic-full" class="button button-primary" style="width:100%;margin-bottom:6px;">
                    <?php esc_html_e( 'Humanize Full Content', 'writehumane-ai-humanizer' ); ?>
                </button>
                <button type="button" id="whah-classic-selection" class="button" style="width:100%;">
                    <?php esc_html_e( 'Humanize Selected Text', 'writehumane-ai-humanizer' ); ?>
                </button>
            </p>
            <div id="whah-classic-status" style="display:none;margin-top:8px;padding:8px;border-radius:4px;font-size:12px;"></div>
        </div>
        <?php
    }

    public function enqueue_classic( $hook ) {
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }
        // Don't load if Gutenberg is active for this post
        if ( function_exists( 'use_block_editor_for_post' ) && use_block_editor_for_post( get_the_ID() ) ) {
            return;
        }

        wp_enqueue_style( 'whah-editor', WHAH_PLUGIN_URL . 'assets/css/editor.css', array(), WHAH_VERSION );

        wp_enqueue_script(
            'whah-classic-editor',
            WHAH_PLUGIN_URL . 'assets/js/classic-editor.js',
            array( 'jquery' ),
            WHAH_VERSION,
            true
        );

        wp_localize_script( 'whah-classic-editor', 'whahEditor', array(
            'restUrl' => rest_url( 'writehumane/v1/' ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
        ) );
    }
}
