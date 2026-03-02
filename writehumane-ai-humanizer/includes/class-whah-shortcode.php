<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WHAH_Shortcode {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if ( '1' === get_option( 'whah_enable_shortcode', '1' ) ) {
            add_shortcode( 'writehumane', array( $this, 'render' ) );
        }
    }

    public function render( $atts ) {
        $atts = shortcode_atts( array(
            'mode'        => get_option( 'whah_humanize_mode', 'balanced' ),
            'tone'        => get_option( 'whah_default_tone', 'professional' ),
            'placeholder' => __( 'Paste your AI-generated text here...', 'writehumane-ai-humanizer' ),
            'button_text' => __( 'Humanize', 'writehumane-ai-humanizer' ),
            'max_words'   => 2000,
            'theme'       => 'light',
        ), $atts, 'writehumane' );

        // Enqueue assets
        wp_enqueue_style( 'whah-frontend', WHAH_PLUGIN_URL . 'assets/css/frontend.css', array(), WHAH_VERSION );
        wp_enqueue_script( 'whah-frontend', WHAH_PLUGIN_URL . 'assets/js/frontend.js', array(), WHAH_VERSION, true );

        $brand = get_option( 'whah_brand_name', 'WriteHumane' );

        wp_localize_script( 'whah-frontend', 'whahFrontend', array(
            'restUrl'  => rest_url( 'writehumane/v1/' ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
            'maxWords' => (int) $atts['max_words'],
            'i18n'     => array(
                'humanize'  => $atts['button_text'],
                'humanizing'=> __( 'Humanizing...', 'writehumane-ai-humanizer' ),
                'copy'      => __( 'Copy', 'writehumane-ai-humanizer' ),
                'copied'    => __( 'Copied!', 'writehumane-ai-humanizer' ),
                'error'     => __( 'Something went wrong. Please try again.', 'writehumane-ai-humanizer' ),
                'empty'     => __( 'Please enter some text first.', 'writehumane-ai-humanizer' ),
                'tooLong'   => sprintf( __( 'Text exceeds %d word limit.', 'writehumane-ai-humanizer' ), (int) $atts['max_words'] ),
                'wordsLabel'=> __( 'words', 'writehumane-ai-humanizer' ),
            ),
        ) );

        $theme_class = 'dark' === $atts['theme'] ? 'whah-widget-dark' : 'whah-widget-light';

        ob_start();
        ?>
        <div class="whah-widget <?php echo esc_attr( $theme_class ); ?>" data-mode="<?php echo esc_attr( $atts['mode'] ); ?>" data-tone="<?php echo esc_attr( $atts['tone'] ); ?>">
            <div class="whah-widget-header">
                <span class="whah-widget-logo"><?php echo esc_html( $brand ); ?></span>
                <span class="whah-widget-word-count"><span class="whah-input-count">0</span> <?php esc_html_e( 'words', 'writehumane-ai-humanizer' ); ?></span>
            </div>
            <div class="whah-widget-body">
                <textarea class="whah-input" rows="6" placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"></textarea>

                <div class="whah-widget-controls">
                    <select class="whah-mode-select">
                        <option value="light" <?php selected( $atts['mode'], 'light' ); ?>><?php esc_html_e( 'Light', 'writehumane-ai-humanizer' ); ?></option>
                        <option value="balanced" <?php selected( $atts['mode'], 'balanced' ); ?>><?php esc_html_e( 'Balanced', 'writehumane-ai-humanizer' ); ?></option>
                        <option value="aggressive" <?php selected( $atts['mode'], 'aggressive' ); ?>><?php esc_html_e( 'Aggressive', 'writehumane-ai-humanizer' ); ?></option>
                    </select>
                    <button type="button" class="whah-humanize-btn">
                        <span class="whah-spinner" style="display:none;"></span>
                        <span class="whah-btn-text"><?php echo esc_html( $atts['button_text'] ); ?></span>
                    </button>
                </div>

                <div class="whah-loading" style="display:none;">
                    <span class="whah-loading-spinner"></span>
                    <?php esc_html_e( 'Humanizing your content...', 'writehumane-ai-humanizer' ); ?>
                </div>

                <div class="whah-error" style="display:none;"></div>

                <div class="whah-output-area" style="display:none;">
                    <textarea class="whah-output" rows="6" readonly></textarea>
                    <div class="whah-output-footer">
                        <span class="whah-output-stats"></span>
                        <button type="button" class="whah-copy-btn"><?php esc_html_e( 'Copy', 'writehumane-ai-humanizer' ); ?></button>
                    </div>
                </div>
            </div>
            <div class="whah-widget-footer">
                <a href="https://writehumane.com" target="_blank" rel="noopener"><?php printf( esc_html__( 'Powered by %s', 'writehumane-ai-humanizer' ), esc_html( $brand ) ); ?></a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
