<?php

class WC_PayArc_API_Rest_Request_Builder
{
    use WC_PayArc_API_Rest_Formatter;

    const CARD_SOURCE_INTERNET = 'INTERNET';
    const CARD_SOURCE_PHONE = 'PHONE';

    const VOID_REASON_CUSTOMER = 'requested_by_customer';
    const VOID_REASON_FRAUD = 'fraudulent';
    const VOID_REASON_DUP = 'duplicate';
    const VOID_REASON_OTHER = 'other';

    const MAX_LENGTH_RULES = [
        'name' => 30,
        'card_holder_name' => 30,
        'address_line1' => 200,
        'address_line2' => 200,
        'address1' => 200,
        'address2' => 200,
        'city' => 50,
        'state' => 50,
        'country' => 50,
        'email' => 40,
        'phone' => 11,
        'description' => 190,
        'void_description' => 190
    ];

    /**
     * @param \WC_Order $order
     * @param bool $authorize_card
     * @return array
     */
    public function build_card_data(WC_Order $order, $authorize_card = false)
    {
        $data = [
            'card_source' => self::CARD_SOURCE_INTERNET,
            'card_holder_name' => $order->get_formatted_billing_full_name(),
            'card_number' => $order->payment->account_number ?? '',
            'cvv' => $order->payment->csc ?? '',
            'exp_month' => $order->payment->exp_month ?? '',
            'exp_year' => $order->payment->exp_year ?? '',
            'authorize_card' => $authorize_card ? 1 : 0
        ];

        return $this->truncate_data($data);
    }

    /**
     * @param \WC_Order $order
     * @return array
     */
    public function build_address_data(WC_Order $order)
    {
        $data = [
            'name' => $order->get_formatted_billing_full_name(),
            'address1' => $order->get_billing_address_1(),
            'address_line1' => $order->get_billing_address_1(),
            'city' => $order->get_billing_city(),
            'state' => $order->get_billing_state(),
            'zip' => $order->get_billing_postcode(),
            'country' => $order->get_billing_country()
        ];

        if ($address2 = $order->get_billing_address_2()) {
            $data['address2'] = $address2;
            $data['address_line2'] = $address2;
        }

        if ($phone = $order->get_billing_phone()) {
            $data['phone'] = $this->formatPhone($phone);
        }

        if ($email = $order->get_billing_email()) {
            $data['email'] = $email;
        }

        return $this->truncate_data($data);
    }

    /**
     * @param \WC_Order $order
     * @return array
     */
    public function build_payment_data(WC_Order $order)
    {
        return [
            'currency' => strtolower($order->get_currency()),
            'capture' => $order->payment->auth_only ? 0 : 1
        ];
    }

    /**
     * @param \WC_Order $order
     * @return array
     */
    public function build_email(WC_Order $order)
    {
        return $this->truncate_data(
            ['email' => $order->get_billing_email()]
        );
    }

    /**
     * @param string|float|int $amount
     * @return array
     * @throws \Exception
     */
    public function build_amount($amount)
    {
        return ['amount' => $this->formatAmount($amount)];
    }

    /**
     * @param string $token
     * @return array
     * @throws \Exception
     */
    public function build_token_id($token)
    {
        if (!$token) {
            throw new Exception('Token should be provided.');
        }

        return ['token_id' => $token];
    }

    /**
     * @param string $customer_id
     * @param string|null $cvv
     * @return array
     * @throws \Exception
     */
    public function build_customer_id($customer_id, $cvv = null)
    {
        if (!$customer_id) {
            throw new Exception('customer_id should be provided.');
        }

        $data = ['customer_id' => $customer_id];
        if ($cvv) {
            $data['cvv'] = $cvv;
        }

        return $data;
    }

    /**
     * @param string|null $description
     * @return array
     */
    public function build_description($description = null)
    {
        if (!$description) {
            return [];
        }

        return $this->truncate_data(
            ['description' => $description]
        );
    }

    /**
     * @param string|null $description
     * @return array
     */
    public function build_void($description = null)
    {
        $data = ['reason' => self::VOID_REASON_CUSTOMER];

        if ($description) {
            $data['description_void'] = $description;
        }

        return $this->truncate_data($data);
    }

    /**
     * @param array $data
     * @return array
     */
    private function truncate_data(array $data)
    {
        foreach ($data as $k => &$v) {
            if ($maxLength = self::MAX_LENGTH_RULES[$k] ?? false) {
                $v = substr((string)$v, 0, $maxLength);
            }
        }
        return $data;
    }
}
