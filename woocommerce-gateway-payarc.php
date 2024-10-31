<?php
/**
 * Plugin Name: PAYARC Payment Gateway
 * Description: Allow customers to securely pay using their credit card via PAYARC.
 * Version: 1.1.0
 * Requires PHP: 7.4
 * WP requires at least: 5.3.0
 * WC requires at least: 4.3.0
 */

defined('ABSPATH') || exit;
define('WC_PAYARC_PLUGIN_PATH', plugin_dir_path(__FILE__));

class WC_PayArc_Loader
{
	const MIN_PHP_VER = '7.4';
	const MIN_WP_VER = '5.3';
	const MIN_WC_VER = '4.3.0';

	const FRAMEWORK_VER = 'v5_11_0';
	const PLUGIN_NAME = 'PAYARC';

    /**
     * @var \WC_PayArc_Loader
     */
    private static $instance;

	public function __construct()
    {
        if (!$this->is_woocommerce_active()) {
            return;
        }

		register_activation_hook(__FILE__, [$this, 'activation_check']);
		add_action('plugins_loaded', [$this, 'init']);
	}

    public function init()
    {
        if (!class_exists('\SkyVerge\WooCommerce\PluginFramework\\' . self::FRAMEWORK_VER . '\SV_WC_Plugin')) {
            require_once(WC_PAYARC_PLUGIN_PATH . 'vendor/skyverge/wc-plugin-framework/woocommerce/class-sv-wc-plugin.php');
            require_once(WC_PAYARC_PLUGIN_PATH . 'vendor/skyverge/wc-plugin-framework/woocommerce/payment-gateway/class-sv-wc-payment-gateway-plugin.php');
        }

        require_once(WC_PAYARC_PLUGIN_PATH . 'class-wc-payarc.php');
        WC_PayArc::instance();
	}

    public function activation_check()
    {
        $error_messages = [];

		if (!$this->php_ver_check()) {
			$error_messages[] = sprintf(
                'The minimum PHP version required for this plugin is %1$s. You are running %2$s.',
                self::MIN_PHP_VER,
                PHP_VERSION
            );
		}

        if (!$this->is_wp_compatible()) {
            $error_messages[] = sprintf(
                '%s requires WordPress version %s or higher.',
                '<strong>' . self::PLUGIN_NAME . '</strong>',
                self::MIN_WP_VER
            );
        }

        if (!$this->is_wc_compatible()) {
            $error_messages[] = sprintf(
                '%s requires WooCommerce version %s or higher.',
                '<strong>' . self::PLUGIN_NAME . '</strong>',
                self::MIN_WC_VER
            );
        }

        if (count($error_messages)) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(implode('<br/>', $error_messages));
        }
	}

    /**
     * @return bool|int
     */
    private function is_wp_compatible()
    {
		return version_compare(get_bloginfo('version'), self::MIN_WP_VER, '>=');
	}

    /**
     * @return bool
     */
    private function is_wc_compatible()
    {
		return defined('WC_VERSION') && version_compare(WC_VERSION, self::MIN_WC_VER, '>=');
	}

    /**
     * @return bool|int
     */
    private function php_ver_check()
    {
		return version_compare(PHP_VERSION, self::MIN_PHP_VER, '>=');
	}

    /**
     * @return bool
     */
    private function is_woocommerce_active()
    {
        $active_plugins = (array)get_option('active_plugins', []);
        if (is_multisite()) {
            $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', []));
        }
        return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
    }

    /**
     * @return \WC_PayArc_Loader
     */
    public static function instance()
    {
		if (!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}

WC_PayArc_Loader::instance();
