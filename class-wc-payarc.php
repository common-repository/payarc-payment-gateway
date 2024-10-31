<?php

use SkyVerge\WooCommerce\PluginFramework\v5_11_0 as Framework;

defined('ABSPATH') || exit;

class WC_PayArc extends Framework\SV_WC_Payment_Gateway_Plugin
{
    const VERSION = '1.1.0';
    const PLUGIN_ID = 'payarc';
    const CREDIT_CARD_GATEWAY_ID = 'payarc_credit_card';
    const HOSTED_GATEWAY_ID = 'payarc_hosted';
    const CREDIT_CARD_GATEWAY_CLASS_NAME = 'WC_PayArc_Gateway_CreditCard';
    const HOSTED_GATEWAY_CLASS_NAME = 'WC_PayArc_Gateway_Hosted';

    /**
     * @var \WC_PayArc
     */
    protected static $instance;

    public function __construct()
    {
        parent::__construct(
            self::PLUGIN_ID,
            self::VERSION,
            [
                'text_domain' => 'woocommerce',
                'require_ssl'  => true,
                'gateways' => [
                    self::CREDIT_CARD_GATEWAY_ID => self::CREDIT_CARD_GATEWAY_CLASS_NAME,
                    self::HOSTED_GATEWAY_ID => self::HOSTED_GATEWAY_CLASS_NAME
                ],
                'supports' => [
                    self::FEATURE_CAPTURE_CHARGE,
                    self::FEATURE_MY_PAYMENT_METHODS,
                    self::FEATURE_CUSTOMER_ID
                ],
                'dependencies' => [
                    'php_extensions' => ['curl', 'json']
                ]
            ]
        );

        require_once(WC_PAYARC_PLUGIN_PATH . 'includes/class-wc-payarc-gateway.php');
        require_once(WC_PAYARC_PLUGIN_PATH . 'includes/class-wc-payarc-gateway-credit-card.php');
        require_once(WC_PAYARC_PLUGIN_PATH . 'includes/class-wc-payarc-gateway-hosted.php');
        require_once(WC_PAYARC_PLUGIN_PATH . 'includes/payment-forms/class-wc-payarc-gateway-credit-card-form.php');
        require_once(WC_PAYARC_PLUGIN_PATH . 'includes/payment-forms/class-wc-payarc-gateway-hosted-form.php');
        require_once(WC_PAYARC_PLUGIN_PATH . 'includes/payment-handlers/class-wc-payarc-gateway-hosted-ajax.php');
    }

    /**
     * @param string|null $gateway_id
     * @return string
     */
    public function get_settings_link($gateway_id = null)
    {
        return sprintf('<a href="%s">%s</a>',
            esc_url($this->get_settings_url($gateway_id)),
            self::CREDIT_CARD_GATEWAY_ID === $gateway_id
                ? __('Configure Credit Card')
                : __('Configure Hosted Checkout')
        );
    }

    /**
     * @param string $message
     * @param null $log_id
     * @param string $level
     */
    public function log($message, $log_id = null, $level = WC_Log_Levels::INFO)
    {
        $log_id = 'payarc_gateway'; // ignore passed value

        if (is_array($message) || is_object($message)) {
            $message = var_export($message, true);
        }

        parent::log($message, $log_id);
    }

    /**
     * @return string
     */
    public function get_plugin_name()
    {
        return __('PAYARC Payment Gateway');
    }

    /**
     * @return \WC_PayArc
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @return string
     */
    protected function get_file()
    {
        return __FILE__;
    }
}
