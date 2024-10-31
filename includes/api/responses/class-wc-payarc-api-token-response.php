<?php

use SkyVerge\WooCommerce\PluginFramework\v5_11_0 as Framework;

defined('ABSPATH') || exit;

class WC_PayArc_API_Token_Response extends WC_PayArc_API_Response implements Framework\SV_WC_Payment_Gateway_API_Create_Payment_Token_Response
{
    const TYPE_CREDIT_CARD = 'credit_card';

    /**
     * @return Framework\SV_WC_Payment_Gateway_Payment_Token
     * @throws Exception
     */
    public function get_payment_token()
    {
        if (!$token_id = $this->response['data']['id'] ?? false) {
            throw new Exception('Unable to read token_id from response.');
        }

        return new Framework\SV_WC_Payment_Gateway_Payment_Token(
            $token_id,
            [
                'default' => true,
                'type' => self::TYPE_CREDIT_CARD,
                'last_four' => $this->get_last4(),
                'card_type' => $this->get_card_type(),
                'exp_month' => $this->get_exp_month(),
                'exp_year' => $this->get_exp_year()
            ]
        );
    }

    /**
     * @return string
     */
    public function get_card_type()
    {
        return Framework\SV_WC_Payment_Gateway_Helper::card_type_from_account_number($this->get_bin());
    }

    /**
     * @return string
     */
    public function get_bin()
    {
        return $this->response['data']['card']['data']['first6digit'] ?? null;
    }

    /**
     * @return string
     */
    public function get_last4()
    {
        return $this->response['data']['card']['data']['last4digit'] ?? null;
    }

    /**
     * @return string
     */
    public function get_exp_month()
    {
        return $this->response['data']['card']['data']['exp_month'] ?? null;
    }

    /**
     * @return string
     */
    public function get_exp_year()
    {
        return $this->response['data']['card']['data']['exp_year'] ?? null;
    }
}
