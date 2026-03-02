<?php
/**
 * API Handler — calls Gemini behind the scenes.
 * Users never see "Gemini" or any model name. They only see "WriteHumane".
 */

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
     * System prompts per mode
     */
    private function get_system_prompt( $mode, $tone ) {
        $prompts = array(
            'light' => 'You are an expert editor who subtly polishes text to sound more naturally human-written. Your job is light editing only.

RULES:
- Make minimal changes — fix awkward phrasing, smooth transitions, add slight natural variation
- Keep 90%+ of the original wording intact
- Vary sentence lengths slightly (mix short punches with longer flowing sentences)
- Add occasional contractions (don\'t, it\'s, we\'re) where natural
- Preserve all original meaning, facts, data, and SEO keywords exactly
- Preserve any HTML formatting tags if present
- Keep the same tone and voice as the original',

            'balanced' => 'You are a skilled human writer rewriting AI-generated text to sound completely natural and human-authored. The text must pass AI detectors like GPTZero, Originality.ai, Copyleaks, and Turnitin.

REWRITING RULES:
- Completely restructure sentences while preserving meaning
- Vary sentence lengths dramatically: mix short 5-word punches with 20-25 word flowing sentences
- Use natural transitions between ideas (but, still, though, honestly, look, here\'s the thing)
- Add slight imperfections: occasional sentence fragments, rhetorical questions, parenthetical asides
- Use contractions naturally throughout (don\'t, won\'t, it\'s, they\'re, we\'ve)
- Start some sentences with "And" or "But" — real humans do this
- Occasionally use informal connectors: "Thing is," "Point being," "The reality?"
- Break up long paragraphs into shorter ones
- Preserve all original meaning, facts, data, statistics, and SEO keywords
- Preserve any HTML formatting tags if present',

            'aggressive' => 'You are a veteran journalist and copywriter completely rewriting text from scratch. The result must be indistinguishable from human-written content and must defeat ALL AI detection tools including GPTZero, Originality.ai, Copyleaks, ZeroGPT, and Turnitin.

AGGRESSIVE REWRITING RULES:
- Rewrite every single sentence from scratch — no original phrasing should remain
- Use a completely different sentence structure than the original
- Dramatically vary sentence lengths: some just 3-4 words. Others stretching to 25-30 words with natural complexity
- Inject personality: use rhetorical questions, exclamations, direct address ("you"), opinions
- Add natural filler phrases real humans use: "honestly," "to be fair," "the thing is," "look,"
- Use sentence fragments deliberately: "Not ideal." "Big difference." "Worth considering."
- Start sentences with conjunctions: And, But, Or, So, Yet
- Add parenthetical asides and em-dashes for natural flow
- Mix paragraph lengths: some single-sentence paragraphs, some 3-4 sentences
- Use active voice predominantly
- Include occasional colloquialisms appropriate to the tone
- Preserve all factual content, data, statistics, quotes, and SEO keywords
- Preserve any HTML formatting tags if present',
        );

        $tone_instructions = array(
            'professional' => 'Maintain a professional, authoritative tone suitable for business or industry content.',
            'casual'       => 'Use a relaxed, conversational tone like talking to a friend. Contractions and informal language encouraged.',
            'academic'     => 'Keep an academic tone with proper structure, but still sound like a human scholar — not an AI.',
            'friendly'     => 'Write in a warm, approachable, friendly tone. Be personable and engaging.',
        );

        $banned = '
ABSOLUTELY NEVER use these AI cliché words/phrases:
delve, tapestry, multifaceted, "it\'s important to note", "in today\'s world", landscape, realm, cutting-edge, game-changer, revolutionize, unlock, unleash, elevate, harness, leverage, robust, seamless, pivotal, foster, comprehensive, holistic, paradigm, synergy, innovative, streamline, empower, navigate, spearhead, groundbreaking, transformative, underpinning, facilitate, "it\'s worth noting", moreover, furthermore, "in conclusion", "as previously mentioned", "in this article", "let\'s explore", "dive into"

OUTPUT ONLY the rewritten text. No explanations, no meta-commentary, no preamble, no "Here is the rewritten text:".';

        $system = ( isset( $prompts[ $mode ] ) ? $prompts[ $mode ] : $prompts['balanced'] );
        $system .= "\n\n" . ( isset( $tone_instructions[ $tone ] ) ? $tone_instructions[ $tone ] : $tone_instructions['professional'] );
        $system .= $banned;

        return $system;
    }

    /**
     * Main humanize method
     */
    public function humanize( $text, $args = array() ) {
        $defaults = array(
            'mode' => get_option( 'whah_humanize_mode', 'balanced' ),
            'tone' => get_option( 'whah_default_tone', 'professional' ),
        );
        $args = wp_parse_args( $args, $defaults );

        $api_key = get_option( 'whah_gemini_api_key', '' );
        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'API key not configured. Please go to Settings → AI Humanizer.', 'writehumane-ai-humanizer' ) );
        }

        // Check word budget
        $tracker = WHAH_Usage_Tracker::instance();
        $word_count = str_word_count( wp_strip_all_tags( $text ) );

        if ( ! $tracker->has_budget( $word_count ) ) {
            return new WP_Error( 'budget_exceeded', __( 'Monthly word limit reached. Please contact the administrator.', 'writehumane-ai-humanizer' ) );
        }

        $result = $this->call_gemini( $text, $args['mode'], $args['tone'], $api_key );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Track usage
        $output_words = str_word_count( wp_strip_all_tags( $result ) );
        $tracker->track( $word_count, $output_words, $args['mode'], null );

        return array(
            'text'         => $result,
            'input_words'  => $word_count,
            'output_words' => $output_words,
            'mode'         => $args['mode'],
        );
    }

    /**
     * Call Gemini API
     */
    private function call_gemini( $text, $mode, $tone, $api_key ) {
        $model = get_option( 'whah_gemini_model', 'gemini-2.0-flash' );
        $system_prompt = $this->get_system_prompt( $mode, $tone );

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $api_key;

        $body = array(
            'systemInstruction' => array(
                'parts' => array(
                    array( 'text' => $system_prompt ),
                ),
            ),
            'contents' => array(
                array(
                    'parts' => array(
                        array( 'text' => $text ),
                    ),
                ),
            ),
            'generationConfig' => array(
                'temperature' => ( 'aggressive' === $mode ) ? 0.9 : ( 'balanced' === $mode ? 0.7 : 0.5 ),
                'maxOutputTokens' => max( $this->count_words( $text ) * 4, 2000 ),
            ),
        );

        $response = wp_remote_post( $url, array(
            'timeout' => 90,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode( $body ),
        ) );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'api_error', __( 'Connection error. Please try again.', 'writehumane-ai-humanizer' ) );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== $code ) {
            $msg = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'API returned an error.', 'writehumane-ai-humanizer' );
            return new WP_Error( 'api_error', $msg );
        }

        if ( empty( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
            return new WP_Error( 'empty_response', __( 'No content returned from API.', 'writehumane-ai-humanizer' ) );
        }

        return $data['candidates'][0]['content']['parts'][0]['text'];
    }

    /**
     * Test API connection
     */
    public function test_connection() {
        $api_key = get_option( 'whah_gemini_api_key', '' );
        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'API key not configured.', 'writehumane-ai-humanizer' ) );
        }

        $result = $this->call_gemini(
            'The quick brown fox jumps over the lazy dog. This is a test sentence to verify the connection works properly.',
            'light',
            'professional',
            $api_key
        );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return array(
            'success' => true,
            'message' => __( 'Connection successful!', 'writehumane-ai-humanizer' ),
            'sample'  => $result,
        );
    }

    private function count_words( $text ) {
        return str_word_count( wp_strip_all_tags( $text ) );
    }
}
