<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WHAH_Usage_Tracker {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Check if user has word budget remaining.
     * Uses Freemius plan limit if configured, otherwise falls back to admin setting.
     */
    public function has_budget( $word_count ) {
        $limit = $this->get_effective_limit();
        if ( 0 === $limit ) {
            return true; // 0 = unlimited
        }
        $used = $this->get_words_used_this_month();
        return ( $used + $word_count ) <= $limit;
    }

    /**
     * Get the effective word limit based on Freemius plan or admin setting.
     *
     * @return int
     */
    public function get_effective_limit() {
        if ( WHAH_Freemius::is_configured() ) {
            return WHAH_Freemius::get_word_limit();
        }
        return (int) get_option( 'whah_monthly_word_limit', 50000 );
    }

    /**
     * Get words used this month (site-wide)
     */
    public function get_words_used_this_month() {
        global $wpdb;
        $table = $wpdb->prefix . 'whah_usage';
        $first_of_month = gmdate( 'Y-m-01 00:00:00' );

        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(input_words), 0) FROM {$table} WHERE created_at >= %s",
            $first_of_month
        ) );
    }

    /**
     * Track usage
     */
    public function track( $input_words, $output_words, $mode, $post_id = null ) {
        global $wpdb;
        $table = $wpdb->prefix . 'whah_usage';

        $wpdb->insert( $table, array(
            'user_id'      => get_current_user_id(),
            'input_words'  => $input_words,
            'output_words' => $output_words,
            'mode'         => $mode,
            'post_id'      => $post_id,
            'ip_address'   => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
            'created_at'   => current_time( 'mysql', true ),
        ), array( '%d', '%d', '%d', '%s', '%d', '%s', '%s' ) );
    }

    /**
     * Basic stats (for regular users)
     */
    public function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'whah_usage';
        $first_of_month = gmdate( 'Y-m-01 00:00:00' );
        $limit = $this->get_effective_limit();
        $user_id = get_current_user_id();
        $plan = WHAH_Freemius::is_configured() ? WHAH_Freemius::get_plan_name() : 'free';

        $monthly = $wpdb->get_row( $wpdb->prepare(
            "SELECT COALESCE(SUM(input_words), 0) as words, COUNT(*) as requests FROM {$table} WHERE user_id = %d AND created_at >= %s",
            $user_id, $first_of_month
        ) );

        return array(
            'words_this_month'    => (int) $monthly->words,
            'requests_this_month' => (int) $monthly->requests,
            'monthly_limit'       => $limit,
            'words_remaining'     => ( 0 === $limit ) ? 999999 : max( 0, $limit - (int) $monthly->words ),
            'plan'                => $plan,
            'upgrade_url'         => WHAH_Freemius::get_upgrade_url(),
        );
    }

    /**
     * Admin stats — full overview
     */
    public function get_admin_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'whah_usage';
        $first_of_month = gmdate( 'Y-m-01 00:00:00' );
        $limit = (int) get_option( 'whah_monthly_word_limit', 50000 );

        // This month
        $monthly = $wpdb->get_row( $wpdb->prepare(
            "SELECT COALESCE(SUM(input_words), 0) as words, COALESCE(SUM(output_words), 0) as output_words, COUNT(*) as requests, COUNT(DISTINCT user_id) as active_users FROM {$table} WHERE created_at >= %s",
            $first_of_month
        ) );

        // All time
        $total = $wpdb->get_row(
            "SELECT COALESCE(SUM(input_words), 0) as words, COALESCE(SUM(output_words), 0) as output_words, COUNT(*) as requests, COUNT(DISTINCT user_id) as total_users FROM {$table}"
        );

        // Today
        $today_start = gmdate( 'Y-m-d 00:00:00' );
        $today = $wpdb->get_row( $wpdb->prepare(
            "SELECT COALESCE(SUM(input_words), 0) as words, COUNT(*) as requests FROM {$table} WHERE created_at >= %s",
            $today_start
        ) );

        // Last 7 days daily breakdown
        $daily = $wpdb->get_results(
            "SELECT DATE(created_at) as day, SUM(input_words) as words, COUNT(*) as requests FROM {$table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY day ASC"
        );

        // Mode breakdown
        $modes = $wpdb->get_results( $wpdb->prepare(
            "SELECT mode, COUNT(*) as count, SUM(input_words) as words FROM {$table} WHERE created_at >= %s GROUP BY mode",
            $first_of_month
        ) );

        return array(
            'monthly_limit'   => $limit,
            'this_month'      => array(
                'words'        => (int) $monthly->words,
                'output_words' => (int) $monthly->output_words,
                'requests'     => (int) $monthly->requests,
                'active_users' => (int) $monthly->active_users,
            ),
            'today'           => array(
                'words'    => (int) $today->words,
                'requests' => (int) $today->requests,
            ),
            'all_time'        => array(
                'words'       => (int) $total->words,
                'output_words'=> (int) $total->output_words,
                'requests'    => (int) $total->requests,
                'total_users' => (int) $total->total_users,
            ),
            'daily_chart'     => $daily,
            'mode_breakdown'  => $modes,
        );
    }

    /**
     * Per-user breakdown for admin
     */
    public function get_user_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'whah_usage';
        $first_of_month = gmdate( 'Y-m-01 00:00:00' );

        $users = $wpdb->get_results( $wpdb->prepare(
            "SELECT u.user_id, SUM(u.input_words) as words_this_month, COUNT(*) as requests_this_month,
                    (SELECT SUM(input_words) FROM {$table} WHERE user_id = u.user_id) as total_words,
                    (SELECT COUNT(*) FROM {$table} WHERE user_id = u.user_id) as total_requests,
                    MAX(u.created_at) as last_used
             FROM {$table} u
             WHERE u.created_at >= %s
             GROUP BY u.user_id
             ORDER BY words_this_month DESC
             LIMIT 100",
            $first_of_month
        ) );

        // Enrich with WordPress user data
        $result = array();
        foreach ( $users as $row ) {
            $wp_user = get_userdata( $row->user_id );
            $result[] = array(
                'user_id'            => (int) $row->user_id,
                'display_name'       => $wp_user ? $wp_user->display_name : __( 'Unknown', 'writehumane-ai-humanizer' ),
                'email'              => $wp_user ? $wp_user->user_email : '',
                'role'               => $wp_user ? implode( ', ', $wp_user->roles ) : '',
                'words_this_month'   => (int) $row->words_this_month,
                'requests_this_month'=> (int) $row->requests_this_month,
                'total_words'        => (int) $row->total_words,
                'total_requests'     => (int) $row->total_requests,
                'last_used'          => $row->last_used,
            );
        }

        return $result;
    }

    /**
     * Recent logs for admin
     */
    public function get_recent_logs( $page = 1, $per_page = 25 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'whah_usage';
        $offset = ( $page - 1 ) * $per_page;

        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

        $logs = $wpdb->get_results( $wpdb->prepare(
            "SELECT l.*, u.display_name, u.user_email
             FROM {$table} l
             LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
             ORDER BY l.created_at DESC
             LIMIT %d OFFSET %d",
            $per_page, $offset
        ) );

        return array(
            'logs'       => $logs,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $per_page,
            'total_pages'=> ceil( $total / $per_page ),
        );
    }
}
