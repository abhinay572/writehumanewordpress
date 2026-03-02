<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WHAH_Admin {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    public function add_menu() {
        add_options_page(
            __( 'AI Humanizer Settings', 'writehumane-ai-humanizer' ),
            '&#9997;&#65039; AI Humanizer',
            'manage_options',
            'whah-settings',
            array( $this, 'render_settings_page' )
        );
    }

    public function enqueue_admin_assets( $hook ) {
        if ( 'settings_page_whah-settings' !== $hook ) {
            return;
        }

        wp_enqueue_style( 'whah-admin', WHAH_PLUGIN_URL . 'assets/css/admin.css', array(), WHAH_VERSION );
        wp_enqueue_script( 'whah-admin', WHAH_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), WHAH_VERSION, true );
        wp_localize_script( 'whah-admin', 'whahAdmin', array(
            'restUrl' => rest_url( 'writehumane/v1/' ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'i18n'    => array(
                'testing'    => __( 'Testing...', 'writehumane-ai-humanizer' ),
                'testBtn'    => __( 'Test Connection', 'writehumane-ai-humanizer' ),
                'success'    => __( 'Connection successful!', 'writehumane-ai-humanizer' ),
                'failed'     => __( 'Connection failed:', 'writehumane-ai-humanizer' ),
            ),
        ) );
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $tracker = WHAH_Usage_Tracker::instance();
        $stats = $tracker->get_stats();

        $used_percent = $stats['monthly_limit'] > 0 ? min( 100, ( $stats['words_this_month'] / $stats['monthly_limit'] ) * 100 ) : 0;
        ?>
        <div class="wrap whah-admin-wrap">
            <h1><?php esc_html_e( 'WriteHumane AI Humanizer', 'writehumane-ai-humanizer' ); ?></h1>

            <!-- Dashboard Cards -->
            <div class="whah-dashboard-cards">
                <div class="whah-card whah-card-gradient">
                    <div class="whah-card-label"><?php esc_html_e( 'Words This Month', 'writehumane-ai-humanizer' ); ?></div>
                    <div class="whah-card-value"><?php echo esc_html( number_format( $stats['words_this_month'] ) ); ?></div>
                    <div class="whah-progress-bar">
                        <div class="whah-progress-fill" style="width: <?php echo esc_attr( $used_percent ); ?>%;"></div>
                    </div>
                    <div class="whah-card-sub"><?php echo esc_html( round( $used_percent ) ); ?>% of <?php echo esc_html( number_format( $stats['monthly_limit'] ) ); ?> limit</div>
                </div>
                <div class="whah-card">
                    <div class="whah-card-label"><?php esc_html_e( 'Total Words', 'writehumane-ai-humanizer' ); ?></div>
                    <div class="whah-card-value"><?php echo esc_html( number_format( $stats['total_words'] ) ); ?></div>
                    <div class="whah-card-sub"><?php esc_html_e( 'All time', 'writehumane-ai-humanizer' ); ?></div>
                </div>
                <div class="whah-card">
                    <div class="whah-card-label"><?php esc_html_e( 'Total Requests', 'writehumane-ai-humanizer' ); ?></div>
                    <div class="whah-card-value"><?php echo esc_html( number_format( $stats['total_requests'] ) ); ?></div>
                    <div class="whah-card-sub"><?php esc_html_e( 'All time', 'writehumane-ai-humanizer' ); ?></div>
                </div>
                <div class="whah-card">
                    <div class="whah-card-label"><?php esc_html_e( 'Active Provider', 'writehumane-ai-humanizer' ); ?></div>
                    <div class="whah-card-value whah-card-value-sm"><?php echo esc_html( ucfirst( $stats['active_provider'] ) ); ?></div>
                    <div class="whah-card-sub">
                        <button type="button" id="whah-test-connection" class="button button-small">
                            <?php esc_html_e( 'Test Connection', 'writehumane-ai-humanizer' ); ?>
                        </button>
                    </div>
                    <div id="whah-test-result" style="margin-top:8px;"></div>
                </div>
            </div>

            <!-- Settings Form -->
            <form method="post" action="options.php" class="whah-settings-form">
                <?php settings_fields( 'whah_settings' ); ?>

                <div class="whah-settings-section">
                    <h2><?php esc_html_e( 'API Provider', 'writehumane-ai-humanizer' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Provider', 'writehumane-ai-humanizer' ); ?></th>
                            <td>
                                <select name="whah_api_provider" id="whah-api-provider">
                                    <option value="writehumane" <?php selected( get_option( 'whah_api_provider' ), 'writehumane' ); ?>><?php esc_html_e( 'WriteHumane API (Recommended)', 'writehumane-ai-humanizer' ); ?></option>
                                    <option value="openai" <?php selected( get_option( 'whah_api_provider' ), 'openai' ); ?>><?php esc_html_e( 'OpenAI (Direct)', 'writehumane-ai-humanizer' ); ?></option>
                                    <option value="anthropic" <?php selected( get_option( 'whah_api_provider' ), 'anthropic' ); ?>><?php esc_html_e( 'Anthropic (Direct)', 'writehumane-ai-humanizer' ); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- WriteHumane Settings -->
                <div class="whah-settings-section whah-provider-fields" id="whah-writehumane-fields">
                    <h2><?php esc_html_e( 'WriteHumane API Settings', 'writehumane-ai-humanizer' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'API URL', 'writehumane-ai-humanizer' ); ?></th>
                            <td>
                                <input type="url" name="whah_writehumane_api_url" value="<?php echo esc_attr( get_option( 'whah_writehumane_api_url', 'https://writehumane.com' ) ); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'API Key', 'writehumane-ai-humanizer' ); ?></th>
                            <td>
                                <input type="password" name="whah_writehumane_api_key" value="<?php echo esc_attr( get_option( 'whah_writehumane_api_key' ) ); ?>" class="regular-text" />
                                <p class="description"><?php esc_html_e( 'Get your key at writehumane.com/register', 'writehumane-ai-humanizer' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- OpenAI Settings -->
                <div class="whah-settings-section whah-provider-fields" id="whah-openai-fields" style="display:none;">
                    <h2><?php esc_html_e( 'OpenAI Settings', 'writehumane-ai-humanizer' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'API Key', 'writehumane-ai-humanizer' ); ?></th>
                            <td><input type="password" name="whah_openai_api_key" value="<?php echo esc_attr( get_option( 'whah_openai_api_key' ) ); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Model', 'writehumane-ai-humanizer' ); ?></th>
                            <td>
                                <select name="whah_openai_model">
                                    <option value="gpt-4o-mini" <?php selected( get_option( 'whah_openai_model' ), 'gpt-4o-mini' ); ?>>gpt-4o-mini (Cheapest)</option>
                                    <option value="gpt-4o" <?php selected( get_option( 'whah_openai_model' ), 'gpt-4o' ); ?>>gpt-4o</option>
                                    <option value="gpt-4-turbo" <?php selected( get_option( 'whah_openai_model' ), 'gpt-4-turbo' ); ?>>gpt-4-turbo</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Anthropic Settings -->
                <div class="whah-settings-section whah-provider-fields" id="whah-anthropic-fields" style="display:none;">
                    <h2><?php esc_html_e( 'Anthropic Settings', 'writehumane-ai-humanizer' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'API Key', 'writehumane-ai-humanizer' ); ?></th>
                            <td><input type="password" name="whah_anthropic_api_key" value="<?php echo esc_attr( get_option( 'whah_anthropic_api_key' ) ); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Model', 'writehumane-ai-humanizer' ); ?></th>
                            <td>
                                <select name="whah_anthropic_model">
                                    <option value="claude-sonnet-4-20250514" <?php selected( get_option( 'whah_anthropic_model' ), 'claude-sonnet-4-20250514' ); ?>>Claude Sonnet 4</option>
                                    <option value="claude-3-5-haiku-20241022" <?php selected( get_option( 'whah_anthropic_model' ), 'claude-3-5-haiku-20241022' ); ?>>Claude 3.5 Haiku</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- General Settings -->
                <div class="whah-settings-section">
                    <h2><?php esc_html_e( 'Humanization Settings', 'writehumane-ai-humanizer' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Default Mode', 'writehumane-ai-humanizer' ); ?></th>
                            <td>
                                <select name="whah_humanize_mode">
                                    <option value="light" <?php selected( get_option( 'whah_humanize_mode' ), 'light' ); ?>><?php esc_html_e( 'Light', 'writehumane-ai-humanizer' ); ?></option>
                                    <option value="balanced" <?php selected( get_option( 'whah_humanize_mode' ), 'balanced' ); ?>><?php esc_html_e( 'Balanced (Recommended)', 'writehumane-ai-humanizer' ); ?></option>
                                    <option value="aggressive" <?php selected( get_option( 'whah_humanize_mode' ), 'aggressive' ); ?>><?php esc_html_e( 'Aggressive', 'writehumane-ai-humanizer' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Preserve Keywords', 'writehumane-ai-humanizer' ); ?></th>
                            <td><label><input type="checkbox" name="whah_preserve_keywords" value="1" <?php checked( get_option( 'whah_preserve_keywords' ), '1' ); ?> /> <?php esc_html_e( 'Keep SEO keywords intact', 'writehumane-ai-humanizer' ); ?></label></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Monthly Word Limit', 'writehumane-ai-humanizer' ); ?></th>
                            <td><input type="number" name="whah_monthly_word_limit" value="<?php echo esc_attr( get_option( 'whah_monthly_word_limit', 50000 ) ); ?>" min="0" class="small-text" /></td>
                        </tr>
                    </table>
                </div>

                <!-- Feature Toggles -->
                <div class="whah-settings-section">
                    <h2><?php esc_html_e( 'Integration Settings', 'writehumane-ai-humanizer' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Integrations', 'writehumane-ai-humanizer' ); ?></th>
                            <td>
                                <label><input type="checkbox" name="whah_enable_gutenberg" value="1" <?php checked( get_option( 'whah_enable_gutenberg' ), '1' ); ?> /> <?php esc_html_e( 'Gutenberg Editor', 'writehumane-ai-humanizer' ); ?></label><br />
                                <label><input type="checkbox" name="whah_enable_classic" value="1" <?php checked( get_option( 'whah_enable_classic' ), '1' ); ?> /> <?php esc_html_e( 'Classic Editor', 'writehumane-ai-humanizer' ); ?></label><br />
                                <label><input type="checkbox" name="whah_enable_shortcode" value="1" <?php checked( get_option( 'whah_enable_shortcode' ), '1' ); ?> /> <?php esc_html_e( '[writehumane] Shortcode', 'writehumane-ai-humanizer' ); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Allowed Roles', 'writehumane-ai-humanizer' ); ?></th>
                            <td>
                                <?php
                                $allowed = get_option( 'whah_allowed_roles', array( 'administrator', 'editor', 'author' ) );
                                $wp_roles = wp_roles()->roles;
                                foreach ( $wp_roles as $role_key => $role_data ) :
                                ?>
                                <label><input type="checkbox" name="whah_allowed_roles[]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, (array) $allowed, true ) ); ?> /> <?php echo esc_html( $role_data['name'] ); ?></label><br />
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button(); ?>
            </form>

            <!-- Quick Start Guide -->
            <div class="whah-quickstart">
                <h2><?php esc_html_e( 'Quick Start Guide', 'writehumane-ai-humanizer' ); ?></h2>
                <div class="whah-quickstart-cards">
                    <div class="whah-qs-card">
                        <h3><?php esc_html_e( 'Gutenberg Editor', 'writehumane-ai-humanizer' ); ?></h3>
                        <p><?php esc_html_e( 'Open any post or page in the block editor. Click the WriteHumane icon in the top toolbar to open the sidebar. Select mode and click "Humanize Content".', 'writehumane-ai-humanizer' ); ?></p>
                    </div>
                    <div class="whah-qs-card">
                        <h3><?php esc_html_e( 'Classic Editor', 'writehumane-ai-humanizer' ); ?></h3>
                        <p><?php esc_html_e( 'Open a post in the Classic Editor. Find the "AI Humanizer" meta box in the sidebar. Click "Humanize Full Content" or select text and click "Humanize Selected Text".', 'writehumane-ai-humanizer' ); ?></p>
                    </div>
                    <div class="whah-qs-card">
                        <h3><?php esc_html_e( 'Shortcode', 'writehumane-ai-humanizer' ); ?></h3>
                        <p><?php esc_html_e( 'Add [writehumane] to any post or page to embed a humanization widget. Customize with attributes: [writehumane mode="aggressive" theme="dark"]', 'writehumane-ai-humanizer' ); ?></p>
                    </div>
                    <div class="whah-qs-card">
                        <h3><?php esc_html_e( 'REST API', 'writehumane-ai-humanizer' ); ?></h3>
                        <p><?php esc_html_e( 'POST to /wp-json/writehumane/v1/humanize with { "text": "...", "mode": "balanced" }. Requires authentication via WordPress nonce or application password.', 'writehumane-ai-humanizer' ); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
