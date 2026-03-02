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
     * Check if user has word budget remaining
     */
    public function has_budget( $word_count ) {
        $limit = (int) get_option( 'whah_monthly_word_limit', 50000 );
        $used = $this->get_words_used_this_month();

        return ( $used + $word_count ) <= $limit;
    }

    /**
     * Get words used this month
     */
    public function get_words_used_this_month() {
        global $wpdb;
        $table = $wpdb->prefix . 'whah_usage';

        $first_of_month = gmdate( 'Y-m-01 00:00:00' );

        $result = $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(input_words), 0) FROM {$table} WHERE created_at >= %s",
            $first_of_month
        ) );

        return (int) $result;
    }

    /**
     * Track usage
     */
    public function track( $input_words, $output_words, $provider, $post_id = null ) {
        global $wpdb;
        $table = $wpdb->prefix . 'whah_usage';

        $wpdb->insert( $table, array(
            'user_id'      => get_current_user_id(),
            'input_words'  => $input_words,
            'output_words' => $output_words,
            'api_provider' => $provider,
            'post_id'      => $post_id,
            'created_at'   => current_time( 'mysql', true ),
        ), array( '%d', '%d', '%d', '%s', '%d', '%s' ) );
    }

    /**
     * Get usage stats
     */
    public function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'whah_usage';

        $first_of_month = gmdate( 'Y-m-01 00:00:00' );
        $limit = (int) get_option( 'whah_monthly_word_limit', 50000 );

        $monthly = $wpdb->get_row( $wpdb->prepare(
            "SELECT COALESCE(SUM(input_words), 0) as words, COUNT(*) as requests FROM {$table} WHERE created_at >= %s",
            $first_of_month
        ) );

        $total = $wpdb->get_row(
            "SELECT COALESCE(SUM(input_words), 0) as words, COUNT(*) as requests FROM {$table}"
        );

        $provider = get_option( 'whah_api_provider', 'writehumane' );

        return array(
            'words_this_month'    => (int) $monthly->words,
            'requests_this_month' => (int) $monthly->requests,
            'monthly_limit'       => $limit,
            'words_remaining'     => max( 0, $limit - (int) $monthly->words ),
            'total_words'         => (int) $total->words,
            'total_requests'      => (int) $total->requests,
            'active_provider'     => $provider,
        );
    }
}
