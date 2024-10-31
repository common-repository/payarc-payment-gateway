<?php

use SkyVerge\WooCommerce\PluginFramework\v5_11_0 as Framework;

defined('ABSPATH') || exit;

class WC_PayArc_Gateway extends Framework\SV_WC_Payment_Gateway_Direct
{
    /**
     * @var WC_PayArc_API
     */
    protected $api;

    /**
     * @var string
     */
    protected $access_token;

    /**
     * @param string $id
	 * @param Framework\SV_WC_Payment_Gateway_Plugin $plugin
	 * @param array $args
     */
    public function __construct($id, $plugin, $args)
    {
        add_action('woocommerce_admin_order_data_after_order_details', [$this, 'payarc_add_payment_details_admin_order']);
        add_action('woocommerce_email_order_meta', [$this, 'payarc_add_payment_details_email_order_meta'], 10, 3);
        add_action('woocommerce_thankyou_' . $id, [$this, 'payarc_add_payment_details_thankyou']);

        parent::__construct($id, $plugin, $args);
    }

    /**
     * @return WC_PayArc_API
     */
    public function get_api()
    {
        if ($this->api) {
            return $this->api;
        }

        require_once(WC_PAYARC_PLUGIN_PATH . 'includes/api/class-wc-payarc-api.php');

        // rest
        require_once(WC_PAYARC_PLUGIN_PATH . 'includes/api/rest/trait-wc-payarc-formatter.php');
        require_once(WC_PAYARC_PLUGIN_PATH . 'includes/api/rest/class-wc-payarc-request-builder.php');
        require_once(WC_PAYARC_PLUGIN_PATH . 'includes/api/rest/class-wc-payarc-response-validator.php');
        require_once(WC_PAYARC_PLUGIN_PATH . 'includes/api/rest/class-wc-payarc-client.php');
        require_once(WC_PAYARC_PLUGIN_PATH . 'includes/api/rest/class-wc-payarc-command-pool.php');
        require_once(WC_PAYARC_PLUGIN_PATH . 'includes/api/rest/class-wc-payarc-censor.php');

        // responses
        require_once(WC_PAYARC_PLUGIN_PATH . 'includes/api/responses/abstract-wc-payarc-api-response.php');
        require_once(WC_PAYARC_PLUGIN_PATH . 'includes/api/responses/class-wc-payarc-api-token-response.php');
        require_once(WC_PAYARC_PLUGIN_PATH . 'includes/api/responses/class-wc-payarc-api-transaction-response.php');
        require_once(WC_PAYARC_PLUGIN_PATH . 'includes/api/responses/class-wc-payarc-api-customer-response.php');
        require_once(WC_PAYARC_PLUGIN_PATH . 'includes/api/responses/class-wc-payarc-api-order-response.php');
        require_once(WC_PAYARC_PLUGIN_PATH . 'includes/api/responses/class-wc-payarc-api-order-details-response.php');

        $this->api = new WC_PayArc_API($this);
        return $this->api;
    }

    /**
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     */
    public function payarc_add_payment_details_email_order_meta(\WC_Order $order, $sent_to_admin, $plain_text)
    {
        if (!$this->is_valid_order($order)) {
            return;
        }

        $trans_id = $order->get_meta($this->get_order_meta_prefix() . 'trans_id') ?: 'N/A';
        $auth_code = $order->get_meta($this->get_order_meta_prefix() . 'authorization_code') ?: 'N/A';
        $plain_text_details = __('PAYMENT DETAILS') . "\n\n"
            . __('Transaction ID') . ': ' . $trans_id . "\n"
            . __('Auth Code') . ': ' . $auth_code . "\n";

        if ($plain_text) {
            echo esc_html($plain_text_details);
            return;
        }
        ?>

        <h2><?php echo __('Payment details') ?></h2>
        <ul>
            <li><?php echo __('Transaction ID') ?>: <strong><?php echo esc_html($trans_id) ?></strong></li>
            <li><?php echo __('Auth Code') ?>: <strong><?php echo esc_html($auth_code) ?></strong></li>
        </ul>

        <?php
    }

    /**
     * @param int $order_id
     */
    public function payarc_add_payment_details_thankyou($order_id)
    {
        if (!$order = wc_get_order($order_id)) {
            return;
        }

        if (!$this->is_valid_order($order)) {
            return;
        }

        $trans_id = $order->get_meta($this->get_order_meta_prefix() . 'trans_id');
        $auth_code = $order->get_meta($this->get_order_meta_prefix() . 'authorization_code');
        ?>

        <ul class="order_details">
            <li><?php echo __('Transaction ID') ?>: <strong><?php echo esc_html($trans_id) ?></strong></li>
            <li><?php echo __('Auth Code') ?>: <strong><?php echo esc_html($auth_code) ?></strong></li>
        </ul>

        <?php
    }

    /**
     * @param WC_Order $order
     */
    public function payarc_add_payment_details_admin_order(\WC_Order $order)
    {
        if (!$this->is_valid_order($order)) {
            return;
        }

        $trans_id = $order->get_meta($this->get_order_meta_prefix() . 'trans_id') ?: 'N/A';
        $auth_code = $order->get_meta($this->get_order_meta_prefix() . 'authorization_code') ?: 'N/A';
        $captured = $order->get_meta($this->get_order_meta_prefix() . 'charge_captured') ?: 'no';
        $last4 = $order->get_meta($this->get_order_meta_prefix() . 'account_four');
        $card_type = $order->get_meta($this->get_order_meta_prefix() . 'card_type');
        $avs_result = $order->get_meta($this->get_order_meta_prefix() . 'avs_result');
        $csc_result = $order->get_meta($this->get_order_meta_prefix() . 'csc_result');

        $card_types = Framework\SV_WC_Payment_Gateway_Helper::get_card_types();
        $card_type = $card_types[$card_type]['name'] ?? $card_type;
        ?>

        <div class="form-field form-field-wide payarc_payment_details">
            <h3><?php echo __('Payment details'); ?></h3>
            <p><?php
                echo __('Transaction ID') . ': <strong>' . esc_html($trans_id) . '</strong><br/>';
                echo __('Auth Code') . ': <strong>' . esc_html($auth_code) . '</strong><br/>';
                echo __('Charge Captured') . ': <strong>' . ucfirst(esc_html($captured)) . '</strong><br/>';
                if ($card_type) {
                    echo __('Card Type') . ': <strong>' . esc_html($card_type) . '</strong><br/>';
                }
                if ($last4) {
                    echo __('Card Ending') . ': <strong>' . esc_html($last4) . '</strong><br/>';
                }
                if ($avs_result) {
                    echo __('AVS Result') . ': <strong>' . esc_html($avs_result) . '</strong><br/>';
                }
                if ($csc_result) {
                    echo __('CSC Result') . ': <strong>' . esc_html($csc_result) . '</strong><br/>';
                }
            ?></p>
        </div>

        <?php
    }

    /**
     * @return bool
     */
    public function cart_contains_subscription()
    {
        if (!$this->is_subscription_plugin_active()) {
            return false;
        }

        return WC_Subscriptions_Cart::cart_contains_subscription() || wcs_cart_contains_renewal();
    }

    /**
     * @param \WC_Order $order
     * @return bool
     */
    public function order_contains_subscription(WC_Order $order)
    {
        if (!$this->is_subscription_plugin_active() || !$order) {
            return false;
        }

        return wcs_order_contains_subscription($order);
    }

    /**
     * @param string $message
     * @return string
     */
    public function prepare_error_message($message)
    {
        if (is_admin() || $this->is_detailed_customer_decline_messages_enabled()) {
            return $message;
        }
        return __('Something went wrong. Please try again or try an alternate form of payment.');
    }

    /**
     * @return bool
     */
    public function is_subscription_plugin_active()
    {
        return class_exists('WC_Subscriptions');
    }

    /**
     * @return string
     */
    public function get_access_token()
    {
        return $this->access_token;
    }

    /**
     * @return bool
     */
    public function is_configured()
    {
        return !empty($this->access_token);
    }

    /**
     * @param WC_Order $order
     * @return bool
     */
    public function is_valid_order(WC_Order $order)
    {
        return $this->get_id() == $order->get_payment_method();
    }

    /**
     * @return array[]
     */
    protected function get_method_form_fields()
    {
        return [
            'access_token' => [
                'title' => __('Access Token', 'woocommerce'),
                'description' => nl2br(
                    "Login to PAYARC Dashboard (https://dashboard.payarc.net) to obtain Access Token.
                    Click on the 'API' link in the left navigation bar.
                    Click on the 'Reveal Token' link on the API page."
                ),
                'desc_tip' => true,
                'type' => 'password',
                'default' => '',
                'custom_attributes' => [
                    'autocomplete' => 'new-password',
                    'autocorrect' => 'no',
                    'autocapitalize' => 'no',
                    'spellcheck' => 'no'
                ]
            ]
        ];
    }

    /**
     * @param array $array
     * @param string $key
     * @param string $afterKey
     * @return array
     */
    protected function array_rearrange(&$array, $key, $afterKey)
    {
        $elementIndex = array_search($key, array_keys($array), true);
        $element = array_splice($array, $elementIndex, 1);

        $moveToIndex = array_search($afterKey, array_keys($array), true);
        $moveToIndex = min(++$moveToIndex, count($array));

        if (!$element || !$moveToIndex) {
            return $array;
        }

        return array_slice($array, 0, $moveToIndex, true)
        + $element
        + array_slice($array, $moveToIndex, null, true);
    }
}
