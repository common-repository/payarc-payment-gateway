<?php

use SkyVerge\WooCommerce\PluginFramework\v5_11_0 as Framework;

defined('ABSPATH') || exit;

class WC_PayArc_API_Transaction_Response extends WC_PayArc_API_Response implements Framework\SV_WC_Payment_Gateway_API_Authorization_Response
{
    const CVV_MATCH = 'M';

    /**
     * @return string|null
     */
    public function get_transaction_id()
    {
        return $this->response['data']['id'] ?? null;
    }

    /**
     * @return string|null
     */
    public function get_authorization_code()
    {
        return $this->response['data']['auth_code'] ?? null;
    }

    /**
     * @return string
     */
    public function get_avs_result()
    {
        return $this->response['data']['card']['data']['avs_status'] ?? 'N/A';
    }

    /**
     * @return string
     */
    public function get_csc_result()
    {
        return $this->response['data']['card']['data']['cvc_status'] ?? 'N/A';
    }

    /**
     * @return bool
     */
    public function csc_match()
    {
        return $this->get_csc_result() == self::CVV_MATCH;
    }
}
