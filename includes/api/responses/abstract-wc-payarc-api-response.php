<?php

use SkyVerge\WooCommerce\PluginFramework\v5_11_0 as Framework;

defined('ABSPATH') || exit;

abstract class WC_PayArc_API_Response implements Framework\SV_WC_Payment_Gateway_API_Response
{
    /**
     * @var array
     */
    protected $response;

    /**
     * @param array $response
     */
    public function __construct($response)
    {
        $this->response = $response;
    }

    /**
     * @return bool
     */
    public function transaction_approved()
    {
        return true;
    }

    /**
     * @return string|null
     */
    public function get_transaction_id()
    {
        return null;
    }

    /**
     * @return bool
     */
    public function transaction_held()
    {
        return false;
    }

    /**
     * @return string
     */
    public function get_status_code()
    {
        return $this->response['http_code'];
    }

    /**
     * @return string
     */
    public function get_status_message()
    {
        return $this->response['http_message'];
    }

    /**
     * @return string
     */
    public function get_user_message()
    {
        return '';
    }

    /**
     * @return string
     */
    public function get_payment_type()
    {
        return 'credit-card';
    }

    /**
     * @return string
     */
    public function to_string()
    {
        return print_r($this->response, true);
    }

    /**
     * @return string
     */
    public function to_string_safe()
    {
        return $this->to_string();
    }
}
