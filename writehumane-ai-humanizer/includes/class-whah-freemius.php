<?php
/**
 * Freemius SDK Integration for WriteHumane AI Humanizer
 *
 * SETUP INSTRUCTIONS:
 * 1. Sign up at https://freemius.com (free account)
 * 2. Create a new product (plugin) in Freemius dashboard
 * 3. Set up your pricing plans (Free / Pro / Enterprise)
 * 4. Copy your Product ID, Public Key, and Secret Key
 * 5. Replace the placeholder values below
 * 6. Download the Freemius SDK from https://github.com/Freemius/wordpress-sdk
 * 7. Place it in: writehumane-ai-humanizer/freemius/ folder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WHAH_Freemius {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the Freemius SDK instance.
     *
     * @return Freemius
     */
    public static function get_fs() {
        global $whah_fs;

        if ( ! isset( $whah_fs ) ) {

            // Check if Freemius SDK exists
            $sdk_path = WHAH_PLUGIN_DIR . 'freemius/start.php';
            if ( ! file_exists( $sdk_path ) ) {
                return null;
            }

            require_once $sdk_path;

            $whah_fs = fs_dynamic_init( array(
                'id'                  => '', // TODO: Replace with your Freemius Product ID
                'slug'                => 'writehumane-ai-humanizer',
                'type'                => 'plugin',
                'public_key'          => '', // TODO: Replace with your Freemius Public Key
                'is_premium'          => false,
                'premium_suffix'      => 'Pro',
                'has_addons'          => false,
                'has_paid_plans'      => true,
                'has_affiliation'     => 'selected',
                'menu'                => array(
                    'slug'    => 'writehumane-ai-humanizer',
                    'support' => false,
                ),
                'is_live'             => true,
            ) );
        }

        return $whah_fs;
    }

    /**
     * Check if the user is on a paid plan.
     *
     * @return bool
     */
    public static function is_paying() {
        $fs = self::get_fs();
        if ( ! $fs ) {
            return false;
        }
        return $fs->is_paying();
    }

    /**
     * Check if user is on a specific plan or higher.
     *
     * @param string $plan_name Plan name (free, pro, enterprise).
     * @return bool
     */
    public static function is_plan( $plan_name ) {
        $fs = self::get_fs();
        if ( ! $fs ) {
            return $plan_name === 'free';
        }
        return $fs->is_plan( $plan_name, true );
    }

    /**
     * Get the current plan name.
     *
     * @return string
     */
    public static function get_plan_name() {
        $fs = self::get_fs();
        if ( ! $fs || ! $fs->is_paying() ) {
            return 'free';
        }

        $plan = $fs->get_plan();
        return $plan ? $plan->name : 'free';
    }

    /**
     * Get the monthly word limit based on the current plan.
     *
     * @return int
     */
    public static function get_word_limit() {
        $plan = self::get_plan_name();

        $limits = array(
            'free'       => 5000,
            'pro'        => 100000,
            'enterprise' => 500000,
        );

        return isset( $limits[ $plan ] ) ? $limits[ $plan ] : $limits['free'];
    }

    /**
     * Get plan features for display.
     *
     * @return array
     */
    public static function get_plans() {
        return array(
            'free' => array(
                'name'        => 'Free',
                'price'       => '$0',
                'word_limit'  => '5,000',
                'features'    => array(
                    '5,000 words/month',
                    'Balanced mode only',
                    'Community support',
                ),
            ),
            'pro' => array(
                'name'        => 'Pro',
                'price'       => '$9/mo',
                'word_limit'  => '100,000',
                'features'    => array(
                    '100,000 words/month',
                    'All humanization modes',
                    'All tone options',
                    'Priority support',
                ),
            ),
            'enterprise' => array(
                'name'        => 'Enterprise',
                'price'       => '$29/mo',
                'word_limit'  => '500,000',
                'features'    => array(
                    '500,000 words/month',
                    'All humanization modes',
                    'All tone options',
                    'Dedicated support',
                    'API access',
                    'White-label option',
                ),
            ),
        );
    }

    /**
     * Check if the Freemius SDK is configured and available.
     *
     * @return bool
     */
    public static function is_configured() {
        $fs = self::get_fs();
        return $fs !== null;
    }

    /**
     * Get the upgrade URL.
     *
     * @return string
     */
    public static function get_upgrade_url() {
        $fs = self::get_fs();
        if ( ! $fs ) {
            return 'https://writehumane.com/pricing';
        }
        return $fs->get_upgrade_url();
    }

    /**
     * Get the account URL.
     *
     * @return string
     */
    public static function get_account_url() {
        $fs = self::get_fs();
        if ( ! $fs ) {
            return admin_url( 'admin.php?page=writehumane-ai-humanizer' );
        }
        return $fs->get_account_url();
    }
}
