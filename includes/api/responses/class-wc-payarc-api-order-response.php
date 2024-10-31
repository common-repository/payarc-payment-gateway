<?php

defined('ABSPATH') || exit;

class WC_PayArc_API_Order_Response extends WC_PayArc_API_Response
{
    /**
     * @return string
     * @throws \Exception
     */
    public function get_id()
    {
        if (!$id = $this->response['id'] ?? false) {
            throw new Exception('Unable to read order id from response.');
        }

        return $id;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function get_token()
    {
        if (!$token = $this->response['token'] ?? false) {
            throw new Exception('Unable to read token from response.');
        }

        return $token;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function get_url()
    {
        if (!$url = $this->response['payment_form_url'] ?? false) {
            throw new Exception('Unable to read payment_form_url from response.');
        }

        return $url;
    }
}
