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
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
    }

    public function add_menu() {
        // Main menu item — Dashboard
        add_menu_page(
            __( 'WriteHumane', 'writehumane-ai-humanizer' ),
            __( 'WriteHumane', 'writehumane-ai-humanizer' ),
            'manage_options',
            'whah-dashboard',
            array( $this, 'render_dashboard' ),
            'dashicons-edit-large',
            30
        );

        // Sub: Dashboard
        add_submenu_page(
            'whah-dashboard',
            __( 'Dashboard', 'writehumane-ai-humanizer' ),
            __( 'Dashboard', 'writehumane-ai-humanizer' ),
            'manage_options',
            'whah-dashboard',
            array( $this, 'render_dashboard' )
        );

        // Sub: Users
        add_submenu_page(
            'whah-dashboard',
            __( 'Users', 'writehumane-ai-humanizer' ),
            __( 'Users', 'writehumane-ai-humanizer' ),
            'manage_options',
            'whah-users',
            array( $this, 'render_users' )
        );

        // Sub: Logs
        add_submenu_page(
            'whah-dashboard',
            __( 'Usage Logs', 'writehumane-ai-humanizer' ),
            __( 'Usage Logs', 'writehumane-ai-humanizer' ),
            'manage_options',
            'whah-logs',
            array( $this, 'render_logs' )
        );

        // Sub: Connect Domain
        add_submenu_page(
            'whah-dashboard',
            __( 'Connect Domain', 'writehumane-ai-humanizer' ),
            __( 'Connect Domain', 'writehumane-ai-humanizer' ),
            'manage_options',
            'whah-connect',
            array( $this, 'render_connect' )
        );

        // Sub: Settings
        add_submenu_page(
            'whah-dashboard',
            __( 'Settings', 'writehumane-ai-humanizer' ),
            __( 'Settings', 'writehumane-ai-humanizer' ),
            'manage_options',
            'whah-settings',
            array( $this, 'render_settings' )
        );
    }

    public function enqueue_admin( $hook ) {
        if ( false === strpos( $hook, 'whah-' ) ) {
            return;
        }

        wp_enqueue_style( 'whah-admin', WHAH_PLUGIN_URL . 'assets/css/admin.css', array(), WHAH_VERSION );
        wp_enqueue_script( 'whah-admin', WHAH_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), WHAH_VERSION, true );
        wp_localize_script( 'whah-admin', 'whahAdmin', array(
            'restUrl' => rest_url( 'writehumane/v1/' ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'i18n'    => array(
                'testing'       => __( 'Testing...', 'writehumane-ai-humanizer' ),
                'testSuccess'   => __( 'Connection successful!', 'writehumane-ai-humanizer' ),
                'testFailed'    => __( 'Connection failed: ', 'writehumane-ai-humanizer' ),
                'testBtn'       => __( 'Test Connection', 'writehumane-ai-humanizer' ),
            ),
        ) );
    }

    /**
     * DASHBOARD PAGE — main overview for the admin/owner
     */
    public function render_dashboard() {
        $tracker = WHAH_Usage_Tracker::instance();
        $stats = $tracker->get_admin_stats();
        $limit = $stats['monthly_limit'];
        $used_pct = $limit > 0 ? min( 100, round( ( $stats['this_month']['words'] / $limit ) * 100 ) ) : 0;
        ?>
        <div class="wrap whah-wrap">
            <h1><?php esc_html_e( 'WriteHumane Dashboard', 'writehumane-ai-humanizer' ); ?></h1>

            <!-- Stat Cards -->
            <div class="whah-stats-grid">
                <div class="whah-stat-card whah-stat-primary">
                    <div class="whah-stat-label"><?php esc_html_e( 'Words This Month', 'writehumane-ai-humanizer' ); ?></div>
                    <div class="whah-stat-value"><?php echo esc_html( number_format( $stats['this_month']['words'] ) ); ?></div>
                    <div class="whah-progress-bar">
                        <div class="whah-progress-fill" style="width: <?php echo esc_attr( $used_pct ); ?>%;"></div>
                    </div>
                    <div class="whah-stat-sub"><?php echo esc_html( $used_pct ); ?>% of <?php echo esc_html( number_format( $limit ) ); ?> limit</div>
                </div>

                <div class="whah-stat-card">
                    <div class="whah-stat-label"><?php esc_html_e( 'Requests This Month', 'writehumane-ai-humanizer' ); ?></div>
                    <div class="whah-stat-value"><?php echo esc_html( number_format( $stats['this_month']['requests'] ) ); ?></div>
                    <div class="whah-stat-sub"><?php echo esc_html( $stats['this_month']['active_users'] ); ?> <?php esc_html_e( 'active users', 'writehumane-ai-humanizer' ); ?></div>
                </div>

                <div class="whah-stat-card">
                    <div class="whah-stat-label"><?php esc_html_e( 'Today', 'writehumane-ai-humanizer' ); ?></div>
                    <div class="whah-stat-value"><?php echo esc_html( number_format( $stats['today']['words'] ) ); ?></div>
                    <div class="whah-stat-sub"><?php echo esc_html( $stats['today']['requests'] ); ?> <?php esc_html_e( 'requests', 'writehumane-ai-humanizer' ); ?></div>
                </div>

                <div class="whah-stat-card">
                    <div class="whah-stat-label"><?php esc_html_e( 'All Time', 'writehumane-ai-humanizer' ); ?></div>
                    <div class="whah-stat-value"><?php echo esc_html( number_format( $stats['all_time']['words'] ) ); ?></div>
                    <div class="whah-stat-sub"><?php echo esc_html( number_format( $stats['all_time']['requests'] ) ); ?> <?php esc_html_e( 'requests by', 'writehumane-ai-humanizer' ); ?> <?php echo esc_html( $stats['all_time']['total_users'] ); ?> <?php esc_html_e( 'users', 'writehumane-ai-humanizer' ); ?></div>
                </div>
            </div>

            <!-- 7-Day Chart -->
            <div class="whah-card">
                <h2><?php esc_html_e( 'Last 7 Days', 'writehumane-ai-humanizer' ); ?></h2>
                <div class="whah-chart">
                    <?php
                    $max_words = 1;
                    foreach ( $stats['daily_chart'] as $day ) {
                        if ( (int) $day->words > $max_words ) {
                            $max_words = (int) $day->words;
                        }
                    }
                    foreach ( $stats['daily_chart'] as $day ) {
                        $height = round( ( (int) $day->words / $max_words ) * 100 );
                        $date_label = gmdate( 'M j', strtotime( $day->day ) );
                        ?>
                        <div class="whah-chart-bar-wrap">
                            <div class="whah-chart-bar" style="height: <?php echo esc_attr( max( 4, $height ) ); ?>%;" title="<?php echo esc_attr( number_format( $day->words ) . ' words, ' . $day->requests . ' requests' ); ?>">
                                <span class="whah-chart-value"><?php echo esc_html( number_format( $day->words ) ); ?></span>
                            </div>
                            <span class="whah-chart-label"><?php echo esc_html( $date_label ); ?></span>
                        </div>
                        <?php
                    }
                    if ( empty( $stats['daily_chart'] ) ) {
                        echo '<p class="whah-empty">' . esc_html__( 'No data yet.', 'writehumane-ai-humanizer' ) . '</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- Mode Breakdown -->
            <div class="whah-card">
                <h2><?php esc_html_e( 'Mode Usage This Month', 'writehumane-ai-humanizer' ); ?></h2>
                <?php if ( ! empty( $stats['mode_breakdown'] ) ) : ?>
                    <table class="whah-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Mode', 'writehumane-ai-humanizer' ); ?></th>
                                <th><?php esc_html_e( 'Requests', 'writehumane-ai-humanizer' ); ?></th>
                                <th><?php esc_html_e( 'Words', 'writehumane-ai-humanizer' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $stats['mode_breakdown'] as $m ) : ?>
                                <tr>
                                    <td><span class="whah-mode-badge whah-mode-<?php echo esc_attr( $m->mode ); ?>"><?php echo esc_html( ucfirst( $m->mode ) ); ?></span></td>
                                    <td><?php echo esc_html( number_format( $m->count ) ); ?></td>
                                    <td><?php echo esc_html( number_format( $m->words ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p class="whah-empty"><?php esc_html_e( 'No usage data yet.', 'writehumane-ai-humanizer' ); ?></p>
                <?php endif; ?>
            </div>

            <!-- Quick Setup Check -->
            <div class="whah-card">
                <h2><?php esc_html_e( 'Setup Status', 'writehumane-ai-humanizer' ); ?></h2>
                <ul class="whah-checklist">
                    <?php
                    $api_key = get_option( 'whah_gemini_api_key', '' );
                    $gutenberg = get_option( 'whah_enable_gutenberg', '1' );
                    $classic = get_option( 'whah_enable_classic', '1' );
                    $shortcode = get_option( 'whah_enable_shortcode', '1' );
                    ?>
                    <li class="<?php echo ! empty( $api_key ) ? 'whah-check-ok' : 'whah-check-fail'; ?>">
                        <?php esc_html_e( 'API Key configured', 'writehumane-ai-humanizer' ); ?>
                        <?php if ( empty( $api_key ) ) : ?>
                            — <a href="<?php echo esc_url( admin_url( 'admin.php?page=whah-settings' ) ); ?>"><?php esc_html_e( 'Configure now', 'writehumane-ai-humanizer' ); ?></a>
                        <?php endif; ?>
                    </li>
                    <li class="<?php echo '1' === $gutenberg ? 'whah-check-ok' : 'whah-check-off'; ?>">
                        <?php esc_html_e( 'Gutenberg integration', 'writehumane-ai-humanizer' ); ?>
                    </li>
                    <li class="<?php echo '1' === $classic ? 'whah-check-ok' : 'whah-check-off'; ?>">
                        <?php esc_html_e( 'Classic Editor integration', 'writehumane-ai-humanizer' ); ?>
                    </li>
                    <li class="<?php echo '1' === $shortcode ? 'whah-check-ok' : 'whah-check-off'; ?>">
                        <?php esc_html_e( 'Shortcode [writehumane] enabled', 'writehumane-ai-humanizer' ); ?>
                    </li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * USERS PAGE — see who is using the plugin and how much
     */
    public function render_users() {
        $tracker = WHAH_Usage_Tracker::instance();
        $users = $tracker->get_user_stats();
        ?>
        <div class="wrap whah-wrap">
            <h1><?php esc_html_e( 'User Usage', 'writehumane-ai-humanizer' ); ?></h1>

            <?php if ( ! empty( $users ) ) : ?>
                <table class="whah-table whah-table-full">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'User', 'writehumane-ai-humanizer' ); ?></th>
                            <th><?php esc_html_e( 'Email', 'writehumane-ai-humanizer' ); ?></th>
                            <th><?php esc_html_e( 'Role', 'writehumane-ai-humanizer' ); ?></th>
                            <th><?php esc_html_e( 'Words (Month)', 'writehumane-ai-humanizer' ); ?></th>
                            <th><?php esc_html_e( 'Requests (Month)', 'writehumane-ai-humanizer' ); ?></th>
                            <th><?php esc_html_e( 'Total Words', 'writehumane-ai-humanizer' ); ?></th>
                            <th><?php esc_html_e( 'Total Requests', 'writehumane-ai-humanizer' ); ?></th>
                            <th><?php esc_html_e( 'Last Used', 'writehumane-ai-humanizer' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $users as $u ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $u['display_name'] ); ?></strong></td>
                                <td><?php echo esc_html( $u['email'] ); ?></td>
                                <td><span class="whah-role-badge"><?php echo esc_html( $u['role'] ); ?></span></td>
                                <td><?php echo esc_html( number_format( $u['words_this_month'] ) ); ?></td>
                                <td><?php echo esc_html( number_format( $u['requests_this_month'] ) ); ?></td>
                                <td><?php echo esc_html( number_format( $u['total_words'] ) ); ?></td>
                                <td><?php echo esc_html( number_format( $u['total_requests'] ) ); ?></td>
                                <td><?php echo esc_html( $u['last_used'] ? human_time_diff( strtotime( $u['last_used'] ) ) . ' ago' : '—' ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="whah-card">
                    <p class="whah-empty"><?php esc_html_e( 'No usage data yet. Users will appear here once they start humanizing content.', 'writehumane-ai-humanizer' ); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * LOGS PAGE — every single humanization request
     */
    public function render_logs() {
        $tracker = WHAH_Usage_Tracker::instance();
        $page = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
        $data = $tracker->get_recent_logs( $page );
        ?>
        <div class="wrap whah-wrap">
            <h1><?php esc_html_e( 'Usage Logs', 'writehumane-ai-humanizer' ); ?></h1>

            <?php if ( ! empty( $data['logs'] ) ) : ?>
                <p class="whah-log-summary">
                    <?php printf(
                        esc_html__( 'Showing page %1$d of %2$d (%3$s total requests)', 'writehumane-ai-humanizer' ),
                        $data['page'],
                        $data['total_pages'],
                        number_format( $data['total'] )
                    ); ?>
                </p>
                <table class="whah-table whah-table-full">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'ID', 'writehumane-ai-humanizer' ); ?></th>
                            <th><?php esc_html_e( 'User', 'writehumane-ai-humanizer' ); ?></th>
                            <th><?php esc_html_e( 'Input Words', 'writehumane-ai-humanizer' ); ?></th>
                            <th><?php esc_html_e( 'Output Words', 'writehumane-ai-humanizer' ); ?></th>
                            <th><?php esc_html_e( 'Mode', 'writehumane-ai-humanizer' ); ?></th>
                            <th><?php esc_html_e( 'Post', 'writehumane-ai-humanizer' ); ?></th>
                            <th><?php esc_html_e( 'IP Address', 'writehumane-ai-humanizer' ); ?></th>
                            <th><?php esc_html_e( 'Date', 'writehumane-ai-humanizer' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $data['logs'] as $log ) : ?>
                            <tr>
                                <td>#<?php echo esc_html( $log->id ); ?></td>
                                <td><?php echo esc_html( $log->display_name ? $log->display_name : __( 'Unknown', 'writehumane-ai-humanizer' ) ); ?></td>
                                <td><?php echo esc_html( number_format( $log->input_words ) ); ?></td>
                                <td><?php echo esc_html( number_format( $log->output_words ) ); ?></td>
                                <td><span class="whah-mode-badge whah-mode-<?php echo esc_attr( $log->mode ); ?>"><?php echo esc_html( ucfirst( $log->mode ) ); ?></span></td>
                                <td>
                                    <?php if ( ! empty( $log->post_id ) ) : ?>
                                        <a href="<?php echo esc_url( get_edit_post_link( $log->post_id ) ); ?>">#<?php echo esc_html( $log->post_id ); ?></a>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( $log->ip_address ); ?></td>
                                <td><?php echo esc_html( gmdate( 'M j, Y g:ia', strtotime( $log->created_at ) ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ( $data['total_pages'] > 1 ) : ?>
                    <div class="whah-pagination">
                        <?php if ( $page > 1 ) : ?>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=whah-logs&paged=' . ( $page - 1 ) ) ); ?>" class="button">&laquo; <?php esc_html_e( 'Previous', 'writehumane-ai-humanizer' ); ?></a>
                        <?php endif; ?>
                        <span class="whah-page-info"><?php printf( esc_html__( 'Page %1$d of %2$d', 'writehumane-ai-humanizer' ), $page, $data['total_pages'] ); ?></span>
                        <?php if ( $page < $data['total_pages'] ) : ?>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=whah-logs&paged=' . ( $page + 1 ) ) ); ?>" class="button"><?php esc_html_e( 'Next', 'writehumane-ai-humanizer' ); ?> &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else : ?>
                <div class="whah-card">
                    <p class="whah-empty"><?php esc_html_e( 'No logs yet. Requests will appear here as users humanize content.', 'writehumane-ai-humanizer' ); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * CONNECT DOMAIN PAGE — connect and manage external domain
     */
    public function render_connect() {
        $domain_url    = get_option( 'whah_domain_url', '' );
        $domain_key    = get_option( 'whah_domain_api_key', '' );
        $domain_status = get_option( 'whah_domain_status', 'disconnected' );
        $domain_last   = get_option( 'whah_domain_last_check', '' );
        $site_url      = home_url();
        $site_name     = get_bloginfo( 'name' );
        $wp_version    = get_bloginfo( 'version' );
        $is_connected  = ( 'connected' === $domain_status && ! empty( $domain_url ) );
        ?>
        <div class="wrap whah-wrap">
            <div class="whah-connect-header">
                <div class="whah-connect-header-text">
                    <h1><?php esc_html_e( 'Connect Domain', 'writehumane-ai-humanizer' ); ?></h1>
                    <p class="whah-connect-subtitle"><?php esc_html_e( 'Link this WordPress site to your WriteHumane backend domain for centralized tracking, analytics, and license management.', 'writehumane-ai-humanizer' ); ?></p>
                </div>
                <div class="whah-connect-status-pill <?php echo $is_connected ? 'whah-pill-connected' : 'whah-pill-disconnected'; ?>">
                    <span class="whah-pill-dot"></span>
                    <?php echo $is_connected ? esc_html__( 'Connected', 'writehumane-ai-humanizer' ) : esc_html__( 'Not Connected', 'writehumane-ai-humanizer' ); ?>
                </div>
            </div>

            <!-- Connection Status Card -->
            <div class="whah-connect-grid">
                <div class="whah-connect-main">
                    <!-- Domain Connection Form -->
                    <div class="whah-card whah-card-connect">
                        <div class="whah-card-header">
                            <span class="dashicons dashicons-admin-links whah-card-icon"></span>
                            <div>
                                <h2><?php esc_html_e( 'Domain Configuration', 'writehumane-ai-humanizer' ); ?></h2>
                                <p class="whah-card-desc"><?php esc_html_e( 'Enter your WriteHumane backend domain URL and API key to establish the connection.', 'writehumane-ai-humanizer' ); ?></p>
                            </div>
                        </div>

                        <form method="post" action="options.php" id="whah-connect-form">
                            <?php settings_fields( 'whah_domain_settings' ); ?>

                            <div class="whah-field-group">
                                <label for="whah_domain_url" class="whah-field-label">
                                    <?php esc_html_e( 'Domain URL', 'writehumane-ai-humanizer' ); ?>
                                    <span class="whah-required">*</span>
                                </label>
                                <div class="whah-input-wrap">
                                    <span class="whah-input-icon dashicons dashicons-admin-site-alt3"></span>
                                    <input type="url" name="whah_domain_url" id="whah_domain_url"
                                        value="<?php echo esc_attr( $domain_url ); ?>"
                                        class="whah-input" placeholder="https://your-domain.com" />
                                </div>
                                <p class="whah-field-hint"><?php esc_html_e( 'The full URL of your WriteHumane backend (e.g. your Firebase Cloud Function or custom API endpoint).', 'writehumane-ai-humanizer' ); ?></p>
                            </div>

                            <div class="whah-field-group">
                                <label for="whah_domain_api_key" class="whah-field-label">
                                    <?php esc_html_e( 'API Key', 'writehumane-ai-humanizer' ); ?>
                                    <span class="whah-required">*</span>
                                </label>
                                <div class="whah-input-wrap">
                                    <span class="whah-input-icon dashicons dashicons-lock"></span>
                                    <input type="password" name="whah_domain_api_key" id="whah_domain_api_key"
                                        value="<?php echo esc_attr( $domain_key ); ?>"
                                        class="whah-input" placeholder="Enter your API key" autocomplete="off" />
                                    <button type="button" class="whah-toggle-pass" title="<?php esc_attr_e( 'Show/Hide', 'writehumane-ai-humanizer' ); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </button>
                                </div>
                                <p class="whah-field-hint"><?php esc_html_e( 'Your secret API key for authenticating with the backend domain.', 'writehumane-ai-humanizer' ); ?></p>
                            </div>

                            <div class="whah-connect-actions">
                                <?php submit_button( __( 'Save Connection', 'writehumane-ai-humanizer' ), 'primary whah-btn-save', 'submit', false ); ?>
                                <button type="button" id="whah-test-domain-btn" class="button whah-btn-test">
                                    <span class="dashicons dashicons-update whah-btn-icon"></span>
                                    <?php esc_html_e( 'Test Connection', 'writehumane-ai-humanizer' ); ?>
                                </button>
                            </div>

                            <!-- Test Result -->
                            <div id="whah-domain-test-result" class="whah-test-result-box" style="display:none;"></div>
                        </form>
                    </div>

                    <!-- Connection Log -->
                    <?php if ( $is_connected ) : ?>
                    <div class="whah-card">
                        <div class="whah-card-header">
                            <span class="dashicons dashicons-yes-alt whah-card-icon whah-icon-success"></span>
                            <div>
                                <h2><?php esc_html_e( 'Connection Active', 'writehumane-ai-humanizer' ); ?></h2>
                                <p class="whah-card-desc"><?php esc_html_e( 'Your site is successfully sending usage data to the connected domain.', 'writehumane-ai-humanizer' ); ?></p>
                            </div>
                        </div>
                        <div class="whah-connection-details">
                            <div class="whah-detail-row">
                                <span class="whah-detail-label"><?php esc_html_e( 'Connected Domain', 'writehumane-ai-humanizer' ); ?></span>
                                <span class="whah-detail-value">
                                    <code><?php echo esc_html( wp_parse_url( $domain_url, PHP_URL_HOST ) ); ?></code>
                                </span>
                            </div>
                            <?php if ( $domain_last ) : ?>
                            <div class="whah-detail-row">
                                <span class="whah-detail-label"><?php esc_html_e( 'Last Verified', 'writehumane-ai-humanizer' ); ?></span>
                                <span class="whah-detail-value"><?php echo esc_html( human_time_diff( strtotime( $domain_last ) ) . ' ago' ); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="whah-detail-row">
                                <span class="whah-detail-label"><?php esc_html_e( 'Data Sent', 'writehumane-ai-humanizer' ); ?></span>
                                <span class="whah-detail-value"><?php esc_html_e( 'Usage stats, user info, site analytics', 'writehumane-ai-humanizer' ); ?></span>
                            </div>
                        </div>
                        <div class="whah-connect-actions" style="margin-top:16px;">
                            <button type="button" id="whah-disconnect-btn" class="button whah-btn-disconnect">
                                <span class="dashicons dashicons-dismiss whah-btn-icon"></span>
                                <?php esc_html_e( 'Disconnect', 'writehumane-ai-humanizer' ); ?>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="whah-connect-sidebar">
                    <!-- Site Info Card -->
                    <div class="whah-card whah-card-info">
                        <h2>
                            <span class="dashicons dashicons-wordpress whah-card-icon-sm"></span>
                            <?php esc_html_e( 'Site Information', 'writehumane-ai-humanizer' ); ?>
                        </h2>
                        <div class="whah-info-list">
                            <div class="whah-info-item">
                                <span class="whah-info-label"><?php esc_html_e( 'Site Name', 'writehumane-ai-humanizer' ); ?></span>
                                <span class="whah-info-value"><?php echo esc_html( $site_name ); ?></span>
                            </div>
                            <div class="whah-info-item">
                                <span class="whah-info-label"><?php esc_html_e( 'Site URL', 'writehumane-ai-humanizer' ); ?></span>
                                <span class="whah-info-value"><code><?php echo esc_html( $site_url ); ?></code></span>
                            </div>
                            <div class="whah-info-item">
                                <span class="whah-info-label"><?php esc_html_e( 'WordPress', 'writehumane-ai-humanizer' ); ?></span>
                                <span class="whah-info-value"><?php echo esc_html( $wp_version ); ?></span>
                            </div>
                            <div class="whah-info-item">
                                <span class="whah-info-label"><?php esc_html_e( 'PHP', 'writehumane-ai-humanizer' ); ?></span>
                                <span class="whah-info-value"><?php echo esc_html( PHP_VERSION ); ?></span>
                            </div>
                            <div class="whah-info-item">
                                <span class="whah-info-label"><?php esc_html_e( 'Plugin', 'writehumane-ai-humanizer' ); ?></span>
                                <span class="whah-info-value">v<?php echo esc_html( WHAH_VERSION ); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- How It Works Card -->
                    <div class="whah-card whah-card-help">
                        <h2>
                            <span class="dashicons dashicons-info-outline whah-card-icon-sm"></span>
                            <?php esc_html_e( 'How It Works', 'writehumane-ai-humanizer' ); ?>
                        </h2>
                        <ol class="whah-steps-list">
                            <li><?php esc_html_e( 'Enter your backend domain URL below.', 'writehumane-ai-humanizer' ); ?></li>
                            <li><?php esc_html_e( 'Add your API key for authentication.', 'writehumane-ai-humanizer' ); ?></li>
                            <li><?php esc_html_e( 'Click "Test Connection" to verify.', 'writehumane-ai-humanizer' ); ?></li>
                            <li><?php esc_html_e( 'Save to start sending usage data.', 'writehumane-ai-humanizer' ); ?></li>
                        </ol>
                    </div>

                    <!-- Data Sent Card -->
                    <div class="whah-card">
                        <h2>
                            <span class="dashicons dashicons-shield whah-card-icon-sm"></span>
                            <?php esc_html_e( 'Data We Send', 'writehumane-ai-humanizer' ); ?>
                        </h2>
                        <ul class="whah-data-list">
                            <li><span class="dashicons dashicons-admin-users whah-data-icon"></span> <?php esc_html_e( 'User email, name & role', 'writehumane-ai-humanizer' ); ?></li>
                            <li><span class="dashicons dashicons-chart-bar whah-data-icon"></span> <?php esc_html_e( 'Word counts & request stats', 'writehumane-ai-humanizer' ); ?></li>
                            <li><span class="dashicons dashicons-admin-site whah-data-icon"></span> <?php esc_html_e( 'Site URL & WordPress version', 'writehumane-ai-humanizer' ); ?></li>
                            <li><span class="dashicons dashicons-admin-settings whah-data-icon"></span> <?php esc_html_e( 'Mode & tone used per request', 'writehumane-ai-humanizer' ); ?></li>
                        </ul>
                        <p class="whah-card-desc" style="margin-top:12px;margin-bottom:0;"><?php esc_html_e( 'All data is sent non-blocking in the background. It does not slow down users.', 'writehumane-ai-humanizer' ); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * SETTINGS PAGE
     */
    public function render_settings() {
        ?>
        <div class="wrap whah-wrap">
            <h1><?php esc_html_e( 'WriteHumane Settings', 'writehumane-ai-humanizer' ); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields( 'whah_settings' ); ?>

                <!-- API Configuration -->
                <div class="whah-card">
                    <h2><?php esc_html_e( 'API Configuration', 'writehumane-ai-humanizer' ); ?></h2>
                    <p class="whah-card-desc"><?php esc_html_e( 'Enter your Google Gemini API key. Users will never see which AI model powers the humanizer.', 'writehumane-ai-humanizer' ); ?></p>

                    <table class="form-table">
                        <tr>
                            <th><label for="whah_gemini_api_key"><?php esc_html_e( 'Gemini API Key', 'writehumane-ai-humanizer' ); ?></label></th>
                            <td>
                                <input type="password" name="whah_gemini_api_key" id="whah_gemini_api_key"
                                    value="<?php echo esc_attr( get_option( 'whah_gemini_api_key', '' ) ); ?>"
                                    class="regular-text" autocomplete="off" />
                                <p class="description">
                                    <?php printf(
                                        esc_html__( 'Get your key from %s', 'writehumane-ai-humanizer' ),
                                        '<a href="https://aistudio.google.com/apikey" target="_blank">Google AI Studio</a>'
                                    ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="whah_gemini_model"><?php esc_html_e( 'Model', 'writehumane-ai-humanizer' ); ?></label></th>
                            <td>
                                <select name="whah_gemini_model" id="whah_gemini_model">
                                    <option value="gemini-2.0-flash" <?php selected( get_option( 'whah_gemini_model' ), 'gemini-2.0-flash' ); ?>>Gemini 2.0 Flash (Fast, Cheap)</option>
                                    <option value="gemini-2.0-flash-lite" <?php selected( get_option( 'whah_gemini_model' ), 'gemini-2.0-flash-lite' ); ?>>Gemini 2.0 Flash Lite (Fastest, Cheapest)</option>
                                    <option value="gemini-1.5-pro" <?php selected( get_option( 'whah_gemini_model' ), 'gemini-1.5-pro' ); ?>>Gemini 1.5 Pro (Best Quality)</option>
                                    <option value="gemini-1.5-flash" <?php selected( get_option( 'whah_gemini_model' ), 'gemini-1.5-flash' ); ?>>Gemini 1.5 Flash</option>
                                </select>
                                <p class="description"><?php esc_html_e( 'Users will never see this model name. They only see your brand.', 'writehumane-ai-humanizer' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Connection Test', 'writehumane-ai-humanizer' ); ?></th>
                            <td>
                                <button type="button" id="whah-test-btn" class="button button-secondary"><?php esc_html_e( 'Test Connection', 'writehumane-ai-humanizer' ); ?></button>
                                <span id="whah-test-result" style="margin-left:10px;"></span>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Humanizer Defaults -->
                <div class="whah-card">
                    <h2><?php esc_html_e( 'Humanizer Defaults', 'writehumane-ai-humanizer' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="whah_humanize_mode"><?php esc_html_e( 'Default Mode', 'writehumane-ai-humanizer' ); ?></label></th>
                            <td>
                                <select name="whah_humanize_mode" id="whah_humanize_mode">
                                    <option value="light" <?php selected( get_option( 'whah_humanize_mode' ), 'light' ); ?>><?php esc_html_e( 'Light — subtle polish', 'writehumane-ai-humanizer' ); ?></option>
                                    <option value="balanced" <?php selected( get_option( 'whah_humanize_mode' ), 'balanced' ); ?>><?php esc_html_e( 'Balanced — best all-around', 'writehumane-ai-humanizer' ); ?></option>
                                    <option value="aggressive" <?php selected( get_option( 'whah_humanize_mode' ), 'aggressive' ); ?>><?php esc_html_e( 'Aggressive — full rewrite', 'writehumane-ai-humanizer' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="whah_default_tone"><?php esc_html_e( 'Default Tone', 'writehumane-ai-humanizer' ); ?></label></th>
                            <td>
                                <select name="whah_default_tone" id="whah_default_tone">
                                    <option value="professional" <?php selected( get_option( 'whah_default_tone' ), 'professional' ); ?>><?php esc_html_e( 'Professional', 'writehumane-ai-humanizer' ); ?></option>
                                    <option value="casual" <?php selected( get_option( 'whah_default_tone' ), 'casual' ); ?>><?php esc_html_e( 'Casual', 'writehumane-ai-humanizer' ); ?></option>
                                    <option value="academic" <?php selected( get_option( 'whah_default_tone' ), 'academic' ); ?>><?php esc_html_e( 'Academic', 'writehumane-ai-humanizer' ); ?></option>
                                    <option value="friendly" <?php selected( get_option( 'whah_default_tone' ), 'friendly' ); ?>><?php esc_html_e( 'Friendly', 'writehumane-ai-humanizer' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="whah_monthly_word_limit"><?php esc_html_e( 'Monthly Word Limit', 'writehumane-ai-humanizer' ); ?></label></th>
                            <td>
                                <input type="number" name="whah_monthly_word_limit" id="whah_monthly_word_limit"
                                    value="<?php echo esc_attr( get_option( 'whah_monthly_word_limit', 50000 ) ); ?>"
                                    min="0" step="1000" class="small-text" />
                                <p class="description"><?php esc_html_e( 'Total words allowed per month site-wide. Set to 0 for unlimited.', 'writehumane-ai-humanizer' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Feature Toggles -->
                <div class="whah-card">
                    <h2><?php esc_html_e( 'Features', 'writehumane-ai-humanizer' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Integrations', 'writehumane-ai-humanizer' ); ?></th>
                            <td>
                                <label><input type="hidden" name="whah_enable_gutenberg" value="0" />
                                    <input type="checkbox" name="whah_enable_gutenberg" value="1" <?php checked( get_option( 'whah_enable_gutenberg' ), '1' ); ?> />
                                    <?php esc_html_e( 'Gutenberg Editor sidebar', 'writehumane-ai-humanizer' ); ?></label><br>
                                <label><input type="hidden" name="whah_enable_classic" value="0" />
                                    <input type="checkbox" name="whah_enable_classic" value="1" <?php checked( get_option( 'whah_enable_classic' ), '1' ); ?> />
                                    <?php esc_html_e( 'Classic Editor meta box', 'writehumane-ai-humanizer' ); ?></label><br>
                                <label><input type="hidden" name="whah_enable_shortcode" value="0" />
                                    <input type="checkbox" name="whah_enable_shortcode" value="1" <?php checked( get_option( 'whah_enable_shortcode' ), '1' ); ?> />
                                    <?php esc_html_e( '[writehumane] shortcode', 'writehumane-ai-humanizer' ); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php esc_html_e( 'Allowed Roles', 'writehumane-ai-humanizer' ); ?></label></th>
                            <td>
                                <?php
                                $allowed = get_option( 'whah_allowed_roles', array( 'administrator', 'editor', 'author' ) );
                                $all_roles = wp_roles()->get_names();
                                foreach ( $all_roles as $slug => $name ) : ?>
                                    <label>
                                        <input type="checkbox" name="whah_allowed_roles[]" value="<?php echo esc_attr( $slug ); ?>"
                                            <?php checked( in_array( $slug, (array) $allowed, true ) ); ?> />
                                        <?php echo esc_html( $name ); ?>
                                    </label><br>
                                <?php endforeach; ?>
                                <p class="description"><?php esc_html_e( 'Which WordPress roles can use the humanizer.', 'writehumane-ai-humanizer' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Branding -->
                <div class="whah-card">
                    <h2><?php esc_html_e( 'Branding', 'writehumane-ai-humanizer' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="whah_brand_name"><?php esc_html_e( 'Brand Name', 'writehumane-ai-humanizer' ); ?></label></th>
                            <td>
                                <input type="text" name="whah_brand_name" id="whah_brand_name"
                                    value="<?php echo esc_attr( get_option( 'whah_brand_name', 'WriteHumane' ) ); ?>"
                                    class="regular-text" />
                                <p class="description"><?php esc_html_e( 'Shown to users in the shortcode widget and editor panels.', 'writehumane-ai-humanizer' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Backend Tracking (YOUR centralized database) -->
                <div class="whah-card">
                    <h2><?php esc_html_e( 'Backend Tracking', 'writehumane-ai-humanizer' ); ?></h2>
                    <p class="whah-card-desc"><?php esc_html_e( 'Connect to your centralized backend to track all users, emails, and behavior across all sites. This data is sent in the background and does not slow down users.', 'writehumane-ai-humanizer' ); ?></p>
                    <table class="form-table">
                        <tr>
                            <th><label for="whah_backend_url"><?php esc_html_e( 'Backend URL', 'writehumane-ai-humanizer' ); ?></label></th>
                            <td>
                                <input type="url" name="whah_backend_url" id="whah_backend_url"
                                    value="<?php echo esc_attr( get_option( 'whah_backend_url', '' ) ); ?>"
                                    class="regular-text" placeholder="https://us-central1-writehumanewordpress.cloudfunctions.net/track" />
                                <p class="description"><?php esc_html_e( 'Your Firebase Cloud Function URL for the track endpoint.', 'writehumane-ai-humanizer' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="whah_backend_key"><?php esc_html_e( 'Backend API Key', 'writehumane-ai-humanizer' ); ?></label></th>
                            <td>
                                <input type="password" name="whah_backend_key" id="whah_backend_key"
                                    value="<?php echo esc_attr( get_option( 'whah_backend_key', '' ) ); ?>"
                                    class="regular-text" autocomplete="off" />
                                <p class="description"><?php esc_html_e( 'The PLUGIN_API_KEY you set in Firebase Functions config.', 'writehumane-ai-humanizer' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button( __( 'Save Settings', 'writehumane-ai-humanizer' ) ); ?>
            </form>
        </div>
        <?php
    }
}
