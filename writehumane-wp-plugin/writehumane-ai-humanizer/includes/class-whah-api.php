<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WHAH_API {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Humanize text using the configured provider
     */
    public function humanize( $text, $args = array() ) {
        $defaults = array(
            'mode'              => get_option( 'whah_humanize_mode', 'balanced' ),
            'tone'              => 'professional',
            'preserve_keywords' => (bool) get_option( 'whah_preserve_keywords', true ),
            'post_id'           => null,
        );
        $args = wp_parse_args( $args, $defaults );

        // Check word budget
        $word_count = str_word_count( wp_strip_all_tags( $text ) );
        $tracker = WHAH_Usage_Tracker::instance();

        if ( ! $tracker->has_budget( $word_count ) ) {
            return new WP_Error( 'word_limit', __( 'Monthly word limit exceeded. Contact your administrator.', 'writehumane-ai-humanizer' ) );
        }

        $provider = get_option( 'whah_api_provider', 'writehumane' );

        switch ( $provider ) {
            case 'openai':
                $result = $this->call_openai( $text, $args );
                break;
            case 'anthropic':
                $result = $this->call_anthropic( $text, $args );
                break;
            case 'writehumane':
            default:
                $result = $this->call_writehumane( $text, $args );
                break;
        }

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Track usage
        $output_words = str_word_count( wp_strip_all_tags( $result['text'] ) );
        $tracker->track( $word_count, $output_words, $provider, $args['post_id'] );

        return array(
            'text'            => $result['text'],
            'input_words'     => $word_count,
            'output_words'    => $output_words,
            'provider'        => $provider,
            'detection_score' => isset( $result['detection_score'] ) ? $result['detection_score'] : null,
        );
    }

    /**
     * Call WriteHumane API
     */
    private function call_writehumane( $text, $args ) {
        $api_url = rtrim( get_option( 'whah_writehumane_api_url', 'https://writehumane.com' ), '/' );
        $api_key = get_option( 'whah_writehumane_api_key', '' );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'WriteHumane API key is not configured.', 'writehumane-ai-humanizer' ) );
        }

        $response = wp_remote_post( $api_url . '/api/v1/humanize', array(
            'timeout' => 90,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'text'              => $text,
                'mode'              => $args['mode'],
                'tone'              => $args['tone'],
                'preserve_keywords' => $args['preserve_keywords'],
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'api_error', $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 || empty( $body['success'] ) ) {
            $error_msg = isset( $body['error'] ) ? $body['error'] : __( 'Unknown API error.', 'writehumane-ai-humanizer' );
            return new WP_Error( 'api_error', $error_msg );
        }

        // Handle multiple response field names for compatibility
        $humanized = '';
        if ( ! empty( $body['humanized_text'] ) ) {
            $humanized = $body['humanized_text'];
        } elseif ( ! empty( $body['data'] ) ) {
            $humanized = $body['data'];
        } elseif ( ! empty( $body['result'] ) ) {
            $humanized = $body['result'];
        }

        if ( empty( $humanized ) ) {
            return new WP_Error( 'api_error', __( 'Empty response from API.', 'writehumane-ai-humanizer' ) );
        }

        return array(
            'text'            => $humanized,
            'detection_score' => isset( $body['detection_score'] ) ? $body['detection_score'] : null,
        );
    }

    /**
     * Call OpenAI API directly
     */
    private function call_openai( $text, $args ) {
        $api_key = get_option( 'whah_openai_api_key', '' );
        $model   = get_option( 'whah_openai_model', 'gpt-4o-mini' );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'OpenAI API key is not configured.', 'writehumane-ai-humanizer' ) );
        }

        $system_prompt = $this->get_system_prompt( $args['mode'], $args['tone'], $args['preserve_keywords'] );

        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
            'timeout' => 90,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'model'       => $model,
                'messages'    => array(
                    array( 'role' => 'system', 'content' => $system_prompt ),
                    array( 'role' => 'user', 'content' => $text ),
                ),
                'temperature' => $args['mode'] === 'aggressive' ? 0.9 : ( $args['mode'] === 'balanced' ? 0.7 : 0.5 ),
                'max_tokens'  => max( str_word_count( $text ) * 3, 2000 ),
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'api_error', $response->get_error_message() );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['choices'][0]['message']['content'] ) ) {
            $error = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Unknown OpenAI error.', 'writehumane-ai-humanizer' );
            return new WP_Error( 'api_error', $error );
        }

        return array( 'text' => trim( $body['choices'][0]['message']['content'] ) );
    }

    /**
     * Call Anthropic API directly
     */
    private function call_anthropic( $text, $args ) {
        $api_key = get_option( 'whah_anthropic_api_key', '' );
        $model   = get_option( 'whah_anthropic_model', 'claude-sonnet-4-20250514' );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'Anthropic API key is not configured.', 'writehumane-ai-humanizer' ) );
        }

        $system_prompt = $this->get_system_prompt( $args['mode'], $args['tone'], $args['preserve_keywords'] );

        $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
            'timeout' => 90,
            'headers' => array(
                'x-api-key'         => $api_key,
                'anthropic-version'  => '2023-06-01',
                'Content-Type'       => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'model'      => $model,
                'max_tokens' => max( str_word_count( $text ) * 3, 2000 ),
                'system'     => $system_prompt,
                'messages'   => array(
                    array( 'role' => 'user', 'content' => $text ),
                ),
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'api_error', $response->get_error_message() );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['content'][0]['text'] ) ) {
            $error = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Unknown Anthropic error.', 'writehumane-ai-humanizer' );
            return new WP_Error( 'api_error', $error );
        }

        return array( 'text' => trim( $body['content'][0]['text'] ) );
    }

    /**
     * Get the humanization system prompt
     */
    private function get_system_prompt( $mode, $tone, $preserve_keywords ) {
        $prompts = array(
            'light' => 'You are an expert editor who subtly polishes text to sound more naturally human-written. Make minimal changes — fix awkward phrasing, smooth transitions, add slight natural variation. Keep 90%+ of the original wording intact. Vary sentence lengths slightly. Add occasional contractions where natural. Preserve all original meaning, facts, data, and SEO keywords exactly. Preserve any HTML formatting tags if present.',
            'balanced' => 'You are a skilled human writer rewriting AI-generated text to sound completely natural and human-authored. The text must pass AI detectors like GPTZero, Originality.ai, Copyleaks, and Turnitin. Completely restructure sentences while preserving meaning. Vary sentence lengths dramatically: mix short 5-word punches with 20-25 word flowing sentences. Use natural transitions (but, still, though, honestly, here\'s the thing). Add slight imperfections: occasional sentence fragments, rhetorical questions, parenthetical asides. Use contractions naturally. Start some sentences with "And" or "But". Break up long paragraphs. Preserve all facts, data, and SEO keywords. Preserve HTML formatting tags.',
            'aggressive' => 'You are a veteran journalist completely rewriting text from scratch. The result must be indistinguishable from human-written content and defeat ALL AI detection tools. Rewrite every single sentence from scratch. Dramatically vary sentence lengths: some just 3-4 words, others 25-30 words. Inject personality: rhetorical questions, exclamations, direct address. Add natural filler phrases: "honestly," "to be fair," "the thing is". Use sentence fragments deliberately. Start sentences with conjunctions. Add parenthetical asides and em-dashes. Mix paragraph lengths. Use active voice. Preserve all factual content and SEO keywords. Preserve HTML formatting tags.',
        );

        $tone_instructions = array(
            'professional' => 'Maintain a professional, authoritative tone.',
            'casual'       => 'Use a relaxed, conversational tone.',
            'academic'     => 'Keep an academic tone with proper structure.',
            'friendly'     => 'Write in a warm, approachable, friendly tone.',
        );

        $prompt = isset( $prompts[ $mode ] ) ? $prompts[ $mode ] : $prompts['balanced'];
        $prompt .= "\n\n" . ( isset( $tone_instructions[ $tone ] ) ? $tone_instructions[ $tone ] : $tone_instructions['professional'] );

        if ( $preserve_keywords ) {
            $prompt .= "\n\nIMPORTANT: Preserve all SEO keywords and key phrases from the original text.";
        }

        $prompt .= "\n\nABSOLUTELY NEVER use these words/phrases: delve, tapestry, multifaceted, \"it's important to note\", \"in today's world\", landscape, realm, cutting-edge, game-changer, revolutionize, unlock, unleash, elevate, harness, leverage, robust, seamless, pivotal, foster, comprehensive, holistic, paradigm, synergy, innovative, streamline, empower, navigate, spearhead, groundbreaking, transformative, facilitate.";
        $prompt .= "\n\nOUTPUT ONLY the rewritten text. No explanations, no meta-commentary, no preamble.";

        return $prompt;
    }
}
