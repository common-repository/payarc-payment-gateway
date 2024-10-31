<?php

defined('ABSPATH') || exit;

class WC_PayArc_API_Order_Details_Response extends WC_PayArc_API_Transaction_Response
{
    const STATUS_SUCCESS = 'SUCCESS';

    /**
     * @return string
     * @throws \Exception
     */
    public function get_id()
    {
        if (!$id = $this->response['data']['id'] ?? false) {
            throw new Exception('Unable to read order id from response.');
        }

        return $id;
    }

    /**
     * @return bool
     */
    public function transaction_approved()
    {
        return ($this->response['data']['status'] ?? null) == self::STATUS_SUCCESS;
    }

    /**
     * @return float|int
     */
    public function get_amount()
    {
        return $this->response['data']['amount'] / 100;
    }

    /**
     * @return string|null
     */
    public function get_transaction_id()
    {
        return $this->response['data']['charge']['data']['id'] ?? null;
    }

    /**
     * @return string|null
     */
    public function get_authorization_code()
    {
        return $this->response['data']['charge']['data']['auth_code'] ?? null;
    }

    /**
     * @return string
     */
    public function get_avs_result()
    {
        return $this->response['data']['charge']['data']['card']['data']['avs_status'] ?? 'N/A';
    }

    /**
     * @return string
     */
    public function get_csc_result()
    {
        return $this->response['data']['charge']['data']['card']['data']['cvc_status'] ?? 'N/A';
    }

    /**
     * @return array
     */
    public function get_billing_address()
    {
        $cardholder = $this->response['data']['charge']['data']['card']['data']['card_holder_name'] ?? '';
        $cardholder = explode(' ', $cardholder);

        $address = [
            'first_name' => $cardholder[0] ?? null,
            'last_name' => $cardholder[1] ?? null,
            'address_1' => $this->response['data']['charge']['data']['card']['data']['address1'] ?? null,
            'address_2' => $this->response['data']['charge']['data']['card']['data']['address2'] ?? null,
            'city' => $this->response['data']['charge']['data']['card']['data']['city'] ?? null,
            'state' => $this->response['data']['charge']['data']['card']['data']['state'] ?? null,
            'postcode' => $this->response['data']['charge']['data']['card']['data']['zip'] ?? null,
            'country' => $this->response['data']['charge']['data']['card']['data']['country'] ?? null
        ];

        return $this->is_valid_address($address) ? $address : [];
    }

    /**
     * @param array $address
     * @return bool
     */
    private function is_valid_address(array $address)
    {
        $skip_fields = ['address_2'];
        foreach ($address as $k => $v) {
            if (in_array($k, $skip_fields)) {
                continue;
            }
            if (!$v) {
                return false;
            }
        }
        return true;
    }
}
