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
        if ( get_option( 'whah_enable_shortcode', '1' ) === '1' ) {
            add_shortcode( 'writehumane', array( $this, 'render_shortcode' ) );
        }
    }

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'mode'        => 'balanced',
            'tone'        => 'professional',
            'placeholder' => __( 'Paste your AI-generated text here...', 'writehumane-ai-humanizer' ),
            'button_text' => __( 'Humanize Text', 'writehumane-ai-humanizer' ),
            'max_words'   => 2000,
            'theme'       => 'light',
        ), $atts, 'writehumane' );

        // Enqueue assets
        wp_enqueue_style( 'whah-frontend', WHAH_PLUGIN_URL . 'assets/css/frontend.css', array(), WHAH_VERSION );
        wp_enqueue_script( 'whah-frontend', WHAH_PLUGIN_URL . 'assets/js/frontend.js', array(), WHAH_VERSION, true );
        wp_localize_script( 'whah-frontend', 'whahFrontend', array(
            'restUrl'  => rest_url( 'writehumane/v1/' ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
            'maxWords' => (int) $atts['max_words'],
            'i18n'     => array(
                'humanizing'   => __( 'Humanizing...', 'writehumane-ai-humanizer' ),
                'humanize'     => $atts['button_text'],
                'copied'       => __( 'Copied!', 'writehumane-ai-humanizer' ),
                'copy'         => __( 'Copy to Clipboard', 'writehumane-ai-humanizer' ),
                'error'        => __( 'An error occurred. Please try again.', 'writehumane-ai-humanizer' ),
                'empty'        => __( 'Please enter some text to humanize.', 'writehumane-ai-humanizer' ),
                'tooLong'      => sprintf( __( 'Text exceeds %d word limit.', 'writehumane-ai-humanizer' ), (int) $atts['max_words'] ),
                'wordsLabel'   => __( 'words', 'writehumane-ai-humanizer' ),
                'inputLabel'   => __( 'Input', 'writehumane-ai-humanizer' ),
                'outputLabel'  => __( 'Output', 'writehumane-ai-humanizer' ),
            ),
        ) );

        $theme_class = $atts['theme'] === 'dark' ? 'whah-widget-dark' : 'whah-widget-light';

        ob_start();
        ?>
        <div class="whah-widget <?php echo esc_attr( $theme_class ); ?>" data-mode="<?php echo esc_attr( $atts['mode'] ); ?>" data-tone="<?php echo esc_attr( $atts['tone'] ); ?>">
            <div class="whah-widget-header">
                <span class="whah-widget-logo">&#9997;&#65039; WriteHumane</span>
                <span class="whah-widget-word-count"><span class="whah-input-count">0</span> <?php esc_html_e( 'words', 'writehumane-ai-humanizer' ); ?></span>
            </div>

            <div class="whah-widget-body">
                <textarea class="whah-input" placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>" rows="6"></textarea>

                <div class="whah-widget-controls">
                    <select class="whah-mode-select">
                        <option value="light" <?php selected( $atts['mode'], 'light' ); ?>><?php esc_html_e( 'Light', 'writehumane-ai-humanizer' ); ?></option>
                        <option value="balanced" <?php selected( $atts['mode'], 'balanced' ); ?>><?php esc_html_e( 'Balanced', 'writehumane-ai-humanizer' ); ?></option>
                        <option value="aggressive" <?php selected( $atts['mode'], 'aggressive' ); ?>><?php esc_html_e( 'Aggressive', 'writehumane-ai-humanizer' ); ?></option>
                    </select>

                    <button type="button" class="whah-humanize-btn">
                        <span class="whah-btn-text"><?php echo esc_html( $atts['button_text'] ); ?></span>
                        <span class="whah-spinner" style="display:none;"></span>
                    </button>
                </div>

                <div class="whah-output-area" style="display:none;">
                    <textarea class="whah-output" rows="6" readonly></textarea>
                    <div class="whah-output-footer">
                        <span class="whah-output-stats"></span>
                        <button type="button" class="whah-copy-btn"><?php esc_html_e( 'Copy to Clipboard', 'writehumane-ai-humanizer' ); ?></button>
                    </div>
                </div>

                <div class="whah-error" style="display:none;"></div>

                <div class="whah-loading" style="display:none;">
                    <div class="whah-loading-spinner"></div>
                    <span><?php esc_html_e( 'Humanizing your text...', 'writehumane-ai-humanizer' ); ?></span>
                </div>
            </div>

            <div class="whah-widget-footer">
                <a href="https://writehumane.com" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e( 'Powered by WriteHumane.com', 'writehumane-ai-humanizer' ); ?>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
