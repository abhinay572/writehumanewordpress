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
        if ( get_option( 'whah_enable_gutenberg', '1' ) === '1' ) {
            add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_gutenberg_assets' ) );
        }

        // Classic Editor
        if ( get_option( 'whah_enable_classic', '1' ) === '1' ) {
            add_action( 'add_meta_boxes', array( $this, 'add_classic_meta_box' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_classic_assets' ) );
        }
    }

    /**
     * Gutenberg sidebar plugin
     */
    public function enqueue_gutenberg_assets() {
        wp_enqueue_script(
            'whah-gutenberg',
            WHAH_PLUGIN_URL . 'assets/js/gutenberg.js',
            array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-compose', 'wp-i18n' ),
            WHAH_VERSION,
            true
        );

        wp_enqueue_style(
            'whah-editor',
            WHAH_PLUGIN_URL . 'assets/css/editor.css',
            array(),
            WHAH_VERSION
        );

        wp_localize_script( 'whah-gutenberg', 'whahEditor', array(
            'restUrl' => rest_url( 'writehumane/v1/' ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'mode'    => get_option( 'whah_humanize_mode', 'balanced' ),
        ) );
    }

    /**
     * Classic Editor meta box
     */
    public function add_classic_meta_box() {
        $post_types = get_post_types( array( 'public' => true ), 'names' );
        foreach ( $post_types as $post_type ) {
            add_meta_box(
                'whah-humanizer',
                '&#9997;&#65039; AI Humanizer',
                array( $this, 'render_classic_meta_box' ),
                $post_type,
                'side',
                'high'
            );
        }
    }

    public function render_classic_meta_box( $post ) {
        ?>
        <div id="whah-classic-editor">
            <p>
                <label for="whah-mode"><strong><?php esc_html_e( 'Mode:', 'writehumane-ai-humanizer' ); ?></strong></label>
                <select id="whah-mode" style="width:100%;margin-top:4px;">
                    <option value="light"><?php esc_html_e( 'Light', 'writehumane-ai-humanizer' ); ?></option>
                    <option value="balanced" selected><?php esc_html_e( 'Balanced', 'writehumane-ai-humanizer' ); ?></option>
                    <option value="aggressive"><?php esc_html_e( 'Aggressive', 'writehumane-ai-humanizer' ); ?></option>
                </select>
            </p>
            <p>
                <label for="whah-tone"><strong><?php esc_html_e( 'Tone:', 'writehumane-ai-humanizer' ); ?></strong></label>
                <select id="whah-tone" style="width:100%;margin-top:4px;">
                    <option value="professional"><?php esc_html_e( 'Professional', 'writehumane-ai-humanizer' ); ?></option>
                    <option value="casual"><?php esc_html_e( 'Casual', 'writehumane-ai-humanizer' ); ?></option>
                    <option value="academic"><?php esc_html_e( 'Academic', 'writehumane-ai-humanizer' ); ?></option>
                    <option value="friendly"><?php esc_html_e( 'Friendly', 'writehumane-ai-humanizer' ); ?></option>
                </select>
            </p>
            <p>
                <button type="button" id="whah-humanize-full" class="button button-primary" style="width:100%;margin-bottom:6px;">
                    <?php esc_html_e( 'Humanize Full Content', 'writehumane-ai-humanizer' ); ?>
                </button>
                <button type="button" id="whah-humanize-selected" class="button" style="width:100%;">
                    <?php esc_html_e( 'Humanize Selected Text', 'writehumane-ai-humanizer' ); ?>
                </button>
            </p>
            <div id="whah-classic-status" style="margin-top:8px;"></div>
        </div>
        <?php
    }

    public function enqueue_classic_assets( $hook ) {
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        // Only enqueue for classic editor (when block editor is not active)
        $screen = get_current_screen();
        if ( $screen && method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() ) {
            return;
        }

        wp_enqueue_script(
            'whah-classic-editor',
            WHAH_PLUGIN_URL . 'assets/js/classic-editor.js',
            array( 'jquery' ),
            WHAH_VERSION,
            true
        );

        wp_enqueue_style(
            'whah-editor',
            WHAH_PLUGIN_URL . 'assets/css/editor.css',
            array(),
            WHAH_VERSION
        );

        wp_localize_script( 'whah-classic-editor', 'whahClassic', array(
            'restUrl' => rest_url( 'writehumane/v1/' ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'postId'  => get_the_ID(),
        ) );
    }
}
