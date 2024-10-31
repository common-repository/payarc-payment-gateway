<?php

class WC_PayArc_API_Rest_Command_Pool
{
    /**
     * @var \WC_PayArc_API_Rest_Client
     */
    private $client;

    /**
     * @var \WC_PayArc_API_Rest_Request_Builder
     */
    private $builder;

    /**
     * @var WC_PayArc_Gateway $gateway
     */
    private $gateway;

    /**
     * @param WC_PayArc_Gateway $gateway
     */
    public function __construct($gateway)
    {
        $this->client = new WC_PayArc_API_Rest_Client();
        $this->builder = new WC_PayArc_API_Rest_Request_Builder();
        $this->gateway = $gateway;

        // client init
        $this->client->set_is_test_mode($gateway->is_test_environment());
        $this->client->set_access_token($gateway->get_access_token());
        $this->client->set_prevent_log_flag(!$gateway->debug_log());
	}

    /**
     * @param \WC_Order $order
     * @param bool $authorize_card
     * @return array
     * @throws \Exception
     */
    public function tokenize_card(WC_Order $order, $authorize_card = false)
    {
        $payload = array_merge(
            $this->builder->build_address_data($order),
            $this->builder->build_card_data($order, $authorize_card)
        );

        $payload = array_diff_key(
            $payload,
            array_flip(['name', 'address1', 'address2', 'phone', 'email'])
        );

        $request = [
            'payload' => $payload,
            'method' => WC_PayArc_API_Rest_Client::REQUEST_METHOD_POST,
            'path' => '/v1/tokens'
        ];

        return $this->client->execute($request);
    }

    /**
     * @param \WC_Order $order
     * @return array
     * @throws \Exception
     */
    public function charge(WC_Order $order)
    {
        $payload = array_merge(
            $this->builder->build_amount($order->get_total()),
            $this->builder->build_payment_data($order),
            $this->builder->build_email($order),
            $order->payment->ot_token
                ? $this->builder->build_token_id($order->payment->ot_token)
                : $this->builder->build_customer_id(
                    $order->payment->token,
                    $order->payment->csc
                )
        );

        $request = [
            'payload' => $payload,
            'method' => WC_PayArc_API_Rest_Client::REQUEST_METHOD_POST,
            'path' => '/v1/charges'
        ];

        return $this->client->execute($request);
    }

    /**
     * @param string $trans_id
     * @param string|float|int $amount
     * @return array
     * @throws \Exception
     */
    public function capture($trans_id, $amount)
    {
        if (!$trans_id) {
            throw new Exception('trans_id should be provided.');
        }

        $request = [
            'payload' => $this->builder->build_amount($amount),
            'method' => WC_PayArc_API_Rest_Client::REQUEST_METHOD_POST,
            'path' => '/v1/charges/'. $trans_id .'/capture'
        ];

        return $this->client->execute($request);
    }

    /**
     * @param string $trans_id
     * @param string|float|int $amount
     * @param string|null $description
     * @return array
     * @throws \Exception
     */
    public function refund($trans_id, $amount, $description = null)
    {
        if (!$trans_id) {
            throw new Exception('trans_id should be provided.');
        }

        $payload = array_merge(
            $this->builder->build_amount($amount),
            $this->builder->build_description($description)
        );

        $request = [
            'payload' => $payload,
            'method' => WC_PayArc_API_Rest_Client::REQUEST_METHOD_POST,
            'path' => '/v1/charges/'. $trans_id .'/refunds'
        ];

        return $this->client->execute($request);
    }

    /**
     * @param string $trans_id
     * @param string|null $description
     * @return array
     * @throws \Exception
     */
    public function void($trans_id, $description = null)
    {
        if (!$trans_id) {
            throw new Exception('trans_id should be provided.');
        }

        $request = [
            'payload' => $this->builder->build_void($description),
            'method' => WC_PayArc_API_Rest_Client::REQUEST_METHOD_POST,
            'path' => '/v1/charges/'. $trans_id .'/void'
        ];

        return $this->client->execute($request);
    }

    /**
     * @param float $amount
     * @return array
     * @throws \Exception
     */
    public function create_order($amount)
    {
        $request = [
            'payload' => $this->builder->build_amount($amount),
            'method' => WC_PayArc_API_Rest_Client::REQUEST_METHOD_POST,
            'path' => '/v1/orders'
        ];

        return $this->client->execute($request);
    }

    /**
     * @param string $order_id
     * @return array
     * @throws \Exception
     */
    public function get_order($order_id)
    {
        if (!$order_id) {
            throw new Exception('order_id should be provided.');
        }

        $request = [
            'method' => WC_PayArc_API_Rest_Client::REQUEST_METHOD_GET,
            'path' => '/v1/orders/'. $order_id . '/charge'
        ];

        return $this->client->execute($request);
    }

    /**
     * @param \WC_Order $order
     * @return array
     * @throws \Exception
     */
    public function create_customer(WC_Order $order)
    {
        $payload = $this->builder->build_address_data($order);
        $payload = array_diff_key(
            $payload,
            array_flip(['address_line1', 'address_line2'])
        );

        $request = [
            'payload' => $payload,
            'method' => WC_PayArc_API_Rest_Client::REQUEST_METHOD_POST,
            'path' => '/v1/customers'
        ];

        return $this->client->execute($request);
    }

    /**
     * @param string $customer_id
     * @return array
     * @throws \Exception
     */
    public function remove_customer($customer_id)
    {
        if (!$customer_id) {
            throw new Exception('customer_id should be provided.');
        }

        $request = [
            'method' => WC_PayArc_API_Rest_Client::REQUEST_METHOD_DELETE,
            'path' => '/v1/customers/' . $customer_id
        ];

        return $this->client->execute($request);
    }

    /**
     * @param string $token
     * @param string $customer_id
     * @return array
     * @throws \Exception
     */
    public function attach_token($token, $customer_id)
    {
        if (!$customer_id) {
            throw new Exception('customer_id should be provided.');
        }

        $request = [
            'payload' => $this->builder->build_token_id($token),
            'method' => WC_PayArc_API_Rest_Client::REQUEST_METHOD_PATCH,
            'path' => '/v1/customers/' . $customer_id
        ];

        return $this->client->execute($request);
    }

    /**
     * @param string $token_id
     * @param string $customer_id
     * @return array
     * @throws \Exception
     */
    public function detach_token($token_id, $customer_id)
    {
        if (!$token_id || !$customer_id) {
            throw new Exception('token_id and customer_id should be provided.');
        }

        $request = [
            'method' => WC_PayArc_API_Rest_Client::REQUEST_METHOD_DELETE,
            'path' => '/v1/customers/' . $customer_id . '/cards/' . $token_id
        ];

        return $this->client->execute($request);
    }
}
