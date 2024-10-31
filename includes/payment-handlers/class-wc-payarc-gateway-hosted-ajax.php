<?php

defined('ABSPATH') || exit;

class WC_PayArc_Gateway_Hosted_Handler_Ajax
{
    /**
     * @var WC_PayArc_Gateway_Hosted
     */
    private $gateway;

    /**
     * @param WC_PayArc_Gateway_Hosted $gateway
     */
    public function __construct(WC_PayArc_Gateway_Hosted $gateway)
    {
        $this->gateway = $gateway;

        add_action('wp_ajax_wc_' . $this->gateway->get_id() . '_init_payarc_order', [$this, 'init_payarc_order']);
        add_action('wp_ajax_nopriv_wc_' . $this->gateway->get_id() . '_init_payarc_order', [$this, 'init_payarc_order']);
    }

    /**
     * @return void
     */
    public function init_payarc_order()
    {
        check_ajax_referer('wc_' . $this->gateway->get_id() . '_init_payarc_order', 'ajax_nonce');

        try {
            $amount = WC()->cart->get_total('edit');
            $response = $this->gateway->get_api()->init_gateway_order($amount);
            WC()->session->set(\WC_PayArc_Gateway_Hosted::KEY_GATEWAY_ORDER_ID, $response->get_id());

            wp_send_json_success(
                [
                    'order_id' => $response->get_id(),
                    'token' => $response->get_token(),
                    'amount' => $amount
                ]
            );
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
