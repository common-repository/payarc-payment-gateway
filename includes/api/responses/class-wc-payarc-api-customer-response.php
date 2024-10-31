<?php

use SkyVerge\WooCommerce\PluginFramework\v5_11_0 as Framework;

defined('ABSPATH') || exit;

class WC_PayArc_API_Customer_Response extends WC_PayArc_API_Token_Response implements Framework\SV_WC_Payment_Gateway_API_Customer_Response
{
    /**
     * @var Framework\SV_WC_Payment_Gateway_Payment_Token
     */
    private $payment_token;

    /**
     * @return Framework\SV_WC_Payment_Gateway_Payment_Token
     * @throws \Exception
     */
    public function get_payment_token()
    {
        if (!$this->payment_token) {
            throw new Exception('Payment token is undefined.');
        }

        return $this->payment_token;
    }

    /**
     * @param Framework\SV_WC_Payment_Gateway_Payment_Token $payment_token
     */
    public function set_payment_token(Framework\SV_WC_Payment_Gateway_Payment_Token $payment_token)
    {
        $this->payment_token = $payment_token;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function get_customer_id()
    {
        return null;
    }
}
