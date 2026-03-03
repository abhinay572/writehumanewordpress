<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WHAH_Rest_API {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( 'writehumane/v1', '/humanize', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'humanize' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( 'writehumane/v1', '/usage', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_usage' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( 'writehumane/v1', '/test', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'test_connection' ),
            'permission_callback' => array( $this, 'check_admin_permission' ),
        ) );

        register_rest_route( 'writehumane/v1', '/admin/stats', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_admin_stats' ),
            'permission_callback' => array( $this, 'check_admin_permission' ),
        ) );

        register_rest_route( 'writehumane/v1', '/admin/users', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_admin_users' ),
            'permission_callback' => array( $this, 'check_admin_permission' ),
        ) );

        register_rest_route( 'writehumane/v1', '/admin/logs', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_admin_logs' ),
            'permission_callback' => array( $this, 'check_admin_permission' ),
        ) );

        register_rest_route( 'writehumane/v1', '/domain/test', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'test_domain_connection' ),
            'permission_callback' => array( $this, 'check_admin_permission' ),
        ) );

        register_rest_route( 'writehumane/v1', '/domain/disconnect', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'disconnect_domain' ),
            'permission_callback' => array( $this, 'check_admin_permission' ),
        ) );
    }

    public function check_permission() {
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'not_logged_in', __( 'You must be logged in.', 'writehumane-ai-humanizer' ), array( 'status' => 401 ) );
        }
        $allowed = get_option( 'whah_allowed_roles', array( 'administrator', 'editor', 'author' ) );
        $user = wp_get_current_user();
        $has_role = array_intersect( $allowed, $user->roles );
        if ( empty( $has_role ) ) {
            return new WP_Error( 'forbidden', __( 'Your role does not have permission.', 'writehumane-ai-humanizer' ), array( 'status' => 403 ) );
        }
        return true;
    }

    public function check_admin_permission() {
        return current_user_can( 'manage_options' );
    }

    /**
     * POST /writehumane/v1/humanize
     */
    public function humanize( $request ) {
        $text = $request->get_param( 'text' );
        $mode = $request->get_param( 'mode' );
        $tone = $request->get_param( 'tone' );

        if ( empty( $text ) ) {
            return new WP_Error( 'empty_text', __( 'Please enter some text to humanize.', 'writehumane-ai-humanizer' ), array( 'status' => 400 ) );
        }

        $word_count = str_word_count( wp_strip_all_tags( $text ) );
        if ( $word_count > 5000 ) {
            return new WP_Error( 'too_long', __( 'Text exceeds 5,000 word limit.', 'writehumane-ai-humanizer' ), array( 'status' => 400 ) );
        }

        $api = WHAH_API::instance();
        $result = $api->humanize( $text, array(
            'mode' => $mode ? $mode : 'balanced',
            'tone' => $tone ? $tone : 'professional',
        ) );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( array(
            'success'      => true,
            'text'         => $result['text'],
            'input_words'  => $result['input_words'],
            'output_words' => $result['output_words'],
            'mode'         => $result['mode'],
        ) );
    }

    /**
     * GET /writehumane/v1/usage
     */
    public function get_usage() {
        $tracker = WHAH_Usage_Tracker::instance();
        return rest_ensure_response( $tracker->get_stats() );
    }

    /**
     * POST /writehumane/v1/test
     */
    public function test_connection() {
        $api = WHAH_API::instance();
        $result = $api->test_connection();

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( $result );
    }

    /**
     * GET /writehumane/v1/admin/stats — overview stats for admin dashboard
     */
    public function get_admin_stats() {
        $tracker = WHAH_Usage_Tracker::instance();
        return rest_ensure_response( $tracker->get_admin_stats() );
    }

    /**
     * GET /writehumane/v1/admin/users — per-user breakdown
     */
    public function get_admin_users() {
        $tracker = WHAH_Usage_Tracker::instance();
        return rest_ensure_response( $tracker->get_user_stats() );
    }

    /**
     * GET /writehumane/v1/admin/logs — recent logs
     */
    public function get_admin_logs( $request ) {
        $page = (int) $request->get_param( 'page' );
        if ( $page < 1 ) {
            $page = 1;
        }
        $tracker = WHAH_Usage_Tracker::instance();
        return rest_ensure_response( $tracker->get_recent_logs( $page ) );
    }

    /**
     * POST /writehumane/v1/domain/test — test domain connection
     */
    public function test_domain_connection( $request ) {
        $url = $request->get_param( 'url' );
        $key = $request->get_param( 'key' );

        if ( empty( $url ) ) {
            $url = get_option( 'whah_domain_url', '' );
        }
        if ( empty( $key ) ) {
            $key = get_option( 'whah_domain_api_key', '' );
        }

        if ( empty( $url ) ) {
            return new WP_Error( 'missing_url', __( 'Please enter a domain URL.', 'writehumane-ai-humanizer' ), array( 'status' => 400 ) );
        }

        $response = wp_remote_post( $url, array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $key,
            ),
            'body' => wp_json_encode( array(
                'action'   => 'ping',
                'site_url' => home_url(),
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            update_option( 'whah_domain_status', 'disconnected' );
            return rest_ensure_response( array(
                'success' => false,
                'message' => $response->get_error_message(),
            ) );
        }

        $code = wp_remote_retrieve_response_code( $response );

        if ( $code >= 200 && $code < 300 ) {
            update_option( 'whah_domain_status', 'connected' );
            update_option( 'whah_domain_last_check', current_time( 'mysql' ) );
            return rest_ensure_response( array(
                'success' => true,
                'message' => __( 'Connection successful! Domain is reachable.', 'writehumane-ai-humanizer' ),
                'code'    => $code,
            ) );
        }

        update_option( 'whah_domain_status', 'disconnected' );
        return rest_ensure_response( array(
            'success' => false,
            'message' => sprintf(
                __( 'Domain returned HTTP %d. Please check the URL and API key.', 'writehumane-ai-humanizer' ),
                $code
            ),
            'code' => $code,
        ) );
    }

    /**
     * POST /writehumane/v1/domain/disconnect — disconnect domain
     */
    public function disconnect_domain() {
        update_option( 'whah_domain_status', 'disconnected' );
        update_option( 'whah_domain_last_check', '' );
        return rest_ensure_response( array(
            'success' => true,
            'message' => __( 'Domain disconnected.', 'writehumane-ai-humanizer' ),
        ) );
    }
}
