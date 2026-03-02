<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WHAH_REST_API {

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
    }

    public function check_permission() {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $allowed_roles = get_option( 'whah_allowed_roles', array( 'administrator', 'editor', 'author' ) );
        $user = wp_get_current_user();

        foreach ( $allowed_roles as $role ) {
            if ( in_array( $role, (array) $user->roles, true ) ) {
                return true;
            }
        }

        return false;
    }

    public function check_admin_permission() {
        return current_user_can( 'manage_options' );
    }

    public function humanize( $request ) {
        $text = $request->get_param( 'text' );
        $mode = $request->get_param( 'mode' );
        $tone = $request->get_param( 'tone' );

        if ( empty( $text ) ) {
            return new WP_Error( 'missing_text', __( 'Text is required.', 'writehumane-ai-humanizer' ), array( 'status' => 400 ) );
        }

        $api = WHAH_API::instance();
        $result = $api->humanize( $text, array(
            'mode'    => $mode ? $mode : 'balanced',
            'tone'    => $tone ? $tone : 'professional',
            'post_id' => $request->get_param( 'post_id' ),
        ) );

        if ( is_wp_error( $result ) ) {
            return new WP_Error( 'humanize_error', $result->get_error_message(), array( 'status' => 500 ) );
        }

        return rest_ensure_response( array(
            'success'      => true,
            'text'         => $result['text'],
            'input_words'  => $result['input_words'],
            'output_words' => $result['output_words'],
            'provider'     => $result['provider'],
        ) );
    }

    public function get_usage() {
        $tracker = WHAH_Usage_Tracker::instance();
        $stats = $tracker->get_stats();

        return rest_ensure_response( array(
            'success' => true,
            'stats'   => $stats,
        ) );
    }

    public function test_connection( $request ) {
        $test_text = 'The implementation of artificial intelligence in modern business environments has fundamentally transformed operational workflows, enabling organizations to achieve unprecedented levels of efficiency and productivity.';

        $api = WHAH_API::instance();
        $result = $api->humanize( $test_text, array(
            'mode' => 'balanced',
            'tone' => 'professional',
        ) );

        if ( is_wp_error( $result ) ) {
            return rest_ensure_response( array(
                'success' => false,
                'error'   => $result->get_error_message(),
            ) );
        }

        return rest_ensure_response( array(
            'success'      => true,
            'message'      => __( 'Connection successful!', 'writehumane-ai-humanizer' ),
            'input_words'  => $result['input_words'],
            'output_words' => $result['output_words'],
            'provider'     => $result['provider'],
            'sample'       => substr( $result['text'], 0, 200 ) . '...',
        ) );
    }
}
