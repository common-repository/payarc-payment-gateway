<?php

use SkyVerge\WooCommerce\PluginFramework\v5_11_0 as Framework;

defined('ABSPATH') || exit;

class WC_PayArc_Gateway_Hosted extends WC_PayArc_Gateway
{
    const PAYMENT_TYPE_HOSTED_CHECKOUT = 'payarc_hosted';
    const KEY_GATEWAY_ORDER_ID = 'payarc_gateway_order_id';

    const CHECKOUT_TYPE_REDIRECT = 'redirect';
    const CHECKOUT_TYPE_POPUP = 'popup';

    const PAYARC_CHECKOUT_JS_URL = 'https://payarc-checkout.s3.us-east-2.amazonaws.com/prod/payarc.js';
    const TEST_PAYARC_CHECKOUT_JS_URL = 'https://payarc-checkout.s3.us-east-2.amazonaws.com/test/payarc.js';

    /**
     * @var \WC_PayArc_Gateway_Hosted_Handler
     */
    private $payment_handler;

    /**
     * @var string
     */
    protected $checkout_type;

    /**
     * @return void
     */
    public function __construct()
    {
        add_filter('wc_payment_gateway_payarc_hosted_form_fields', [$this, 'adjust_form_fields']);
        add_filter('woocommerce_after_checkout_validation', [$this, 'payarc_hosted_after_checkout_validation'], 99, 2);

        parent::__construct(
            WC_PayArc::HOSTED_GATEWAY_ID,
            WC_PayArc::instance(),
            [
                'method_title' => __('PAYARC Hosted Checkout'),
                'method_description' => __('Allow customers to securely pay using their credit card with PAYARC Hosted Checkout.'),
                'supports' => [
                    self::FEATURE_PAYMENT_FORM,
                    self::FEATURE_PRODUCTS,
                    self::FEATURE_REFUNDS,
                    self::FEATURE_DETAILED_CUSTOMER_DECLINE_MESSAGES
                ],
                'shared_settings' => ['access_token', 'environment'],
                'payment_type' => self::PAYMENT_TYPE_HOSTED_CHECKOUT,
                'environments' => [
                    self::ENVIRONMENT_PRODUCTION => __('Live', 'woocommerce'),
                    self::ENVIRONMENT_TEST => __('Test', 'woocommerce')
                ]
            ]
        );

        $this->payment_handler = new WC_PayArc_Gateway_Hosted_Handler_Ajax($this);
    }

    /**
     * @param WC_Order $order
     * @return WC_PayArc_API_Order_Details_Response
     * @throws \Exception
     */
    public function do_payarc_hosted_transaction(WC_Order $order)
    {
        if (!$gateway_order_id = WC()->session->get(self::KEY_GATEWAY_ORDER_ID)) {
            throw new Framework\SV_WC_Plugin_Exception('Order Id should be provided.');
        }

        try {
            $gateway_order_response = $this->get_api()->retrieve_gateway_order($gateway_order_id);
            if ($gateway_order_response->get_amount() < $order->get_total('edit')) {
                throw new Framework\SV_WC_Plugin_Exception('Wrong order amount.');
            }
        } catch (\Exception $e) {
            throw $e;
        } finally {
            // cleanup session regardless of result
            WC()->session->set(self::KEY_GATEWAY_ORDER_ID, null);
        }

        return $gateway_order_response;
    }

    /**
     * @param \WC_Order $order
     * @param Framework\SV_WC_Payment_Gateway_API_Response $response
     * @throws Exception
     */
    public function add_payment_gateway_transaction_data($order, $response)
    {
        if (!$response instanceof \WC_PayArc_API_Order_Details_Response) {
            return;
        }

        $this->update_order_meta($order, 'payarc_gateway_order_id', $response->get_id());
        $this->update_order_meta($order, 'charge_captured', 'yes');
        if ($auth_code = $response->get_authorization_code()) {
            $this->update_order_meta($order, 'authorization_code', $auth_code);
        }

        // trying to import payarc billing address
        if (!$billing_address = $response->get_billing_address()) {
            return;
        }

        foreach ($billing_address as $k => $v) {
            try {
                $order->{"set_billing_{$k}"}($v);
            } catch (\Exception $e) {
            }
        }

    }

    /**
     * @param array $fields
     * @return array
     */
    public function adjust_form_fields($fields)
    {
        $fields = $this->array_rearrange($fields, 'environment', 'inherit_settings');
        $fields['inherit_settings']['label'] = __('Use connection/authentication settings from PAYARC Payment Gateway');

        return $fields;
    }

    /**
     * @param array $data
     * @param \WP_Error $errors
     */
    public function payarc_hosted_after_checkout_validation($data, $errors)
    {
        if ($data['payment_method'] !== WC_PayArc::HOSTED_GATEWAY_ID) {
            return;
        }

        // skipping any validation errors because payment might be captured already
        foreach ($errors->get_error_codes() as $code) {
            $errors->remove($code);
        }
    }

    /**
     * @return string
     */
    protected function get_default_title()
    {
        return __('PAYARC Hosted Checkout');
    }

    /**
     * @return string
     */
    protected function get_default_description()
    {
        return __('You\'ll be asked to provide your credit card details');
    }

    /**
     * @return void
     */
    protected function enqueue_gateway_assets()
    {
        if ($this->is_checkout_type_redirect()) {
            return;
        }

        if (!is_checkout() && !is_checkout_pay_page()) {
            return;
        }

        $handle = $this->get_gateway_js_handle();
        $checkout_js_url = $this->is_test_environment() ? self::TEST_PAYARC_CHECKOUT_JS_URL : self::PAYARC_CHECKOUT_JS_URL;
        wp_enqueue_script($handle . '-checkout-js', $checkout_js_url, [], null, true);

        $js_url = $this->get_plugin()->get_plugin_url() . '/assets/js/frontend/wc-payarc-hosted.js';
        wp_enqueue_script($handle, $js_url, [], null, true);
    }

    /**
     * @return WC_PayArc_Gateway_Hosted_Form
     */
    protected function init_payment_form_instance()
    {
        return new WC_PayArc_Gateway_Hosted_Form($this);
    }

    /**
     * @return mixed|void
     */
    protected function get_order_button_text()
    {
        $text = __('Continue to Payment', 'woocommerce');
        return apply_filters('wc_payment_gateway_' . $this->get_id() . '_order_button_text', $text, $this);
    }

    /**
     * @return string
     */
    protected function get_gateway_js_handle()
    {
        return 'wc-payarc-hosted';
    }

    /**
     * @return bool
     */
    private function is_checkout_type_redirect()
    {
        return false;
    }
}
