<?php

use SkyVerge\WooCommerce\PluginFramework\v5_11_0 as Framework;

defined('ABSPATH') || exit;

class WC_PayArc_API extends Framework\SV_WC_API_Base implements Framework\SV_WC_Payment_Gateway_API
{
    /**
     * @var \WC_PayArc_API_Rest_Command_Pool
     */
    private $rest_command_pool;

    /**
     * @var WC_PayArc_Gateway
     */
    private $gateway;

    /**
     * @var WC_Order
     */
    private $order;

    /**
     * @param WC_PayArc_Gateway $gateway
     */
    public function __construct($gateway)
    {
        $this->rest_command_pool = new WC_PayArc_API_Rest_Command_Pool($gateway);
        $this->gateway = $gateway;
    }

    /**
     * @param \WC_Order $order
     * @return \WC_PayArc_API_Transaction_Response
     * @throws Framework\SV_WC_Plugin_Exception
     */
    public function credit_card_authorization(\WC_Order $order)
    {
        return $this->credit_card_charge($order, true);
    }

    /**
     * @param \WC_Order $order
     * @param bool $auth_only
     * @return \WC_PayArc_API_Transaction_Response
     * @throws Framework\SV_WC_Plugin_Exception
     */
    public function credit_card_charge(\WC_Order $order, $auth_only = false)
    {
        $order->payment->auth_only = $auth_only;

        try {
            if (!$order->payment->token) {
                $order->payment->ot_token = $this->tokenize_payment_method($order, true)
                    ->get_payment_token()->get_id();
            }
            $response = $this->rest_command_pool->charge($order);
        } catch (Exception $e) {
            throw new Framework\SV_WC_Plugin_Exception(
                $this->get_gateway()->prepare_error_message($e->getMessage())
            );
        }

        return new WC_PayArc_API_Transaction_Response($response);
    }

    /**
     * @param \WC_Order $order
     * @return \WC_PayArc_API_Transaction_Response
     * @throws Framework\SV_WC_Plugin_Exception
     */
    public function credit_card_capture(\WC_Order $order)
    {
        try {
            $response = $this->rest_command_pool->capture(
                $order->capture->trans_id,
                $order->capture->amount
            );
        } catch (Exception $e) {
            throw new Framework\SV_WC_Plugin_Exception(
                $this->get_gateway()->prepare_error_message($e->getMessage())
            );
        }

        return new WC_PayArc_API_Transaction_Response($response);
    }

    /**
     * @param \WC_Order $order
     * @return \WC_PayArc_API_Transaction_Response
     * @throws Framework\SV_WC_Plugin_Exception
     */
    public function refund(\WC_Order $order)
    {
        try {
            $response = $this->rest_command_pool->refund(
                $order->refund->trans_id,
                $order->refund->amount,
                $order->refund->reason
            );
        } catch (Exception $e) {
            throw new Framework\SV_WC_Plugin_Exception(
                $this->get_gateway()->prepare_error_message($e->getMessage())
            );
        }

        return new WC_PayArc_API_Transaction_Response($response);
    }

    /**
     * @param \WC_Order $order
     * @return \WC_PayArc_API_Transaction_Response
     * @throws Framework\SV_WC_Plugin_Exception
     */
    public function void(\WC_Order $order)
    {
        try {
            $response = $this->rest_command_pool->void(
                $order->refund->trans_id,
                $order->refund->reason
            );
        } catch (Exception $e) {
            throw new Framework\SV_WC_Plugin_Exception(
                $this->get_gateway()->prepare_error_message($e->getMessage())
            );
        }

        return new WC_PayArc_API_Transaction_Response($response);
    }

    /**
     * @param WC_Order $order
     * @param bool $ot_token
     * @return WC_PayArc_API_Customer_Response|WC_PayArc_API_Token_Response
     * @throws Framework\SV_WC_Plugin_Exception
     */
    public function tokenize_payment_method(\WC_Order $order, $ot_token = false)
    {
        $authorize_card = !$ot_token
            && (
                $this->get_gateway()->card_authorization_enabled()
                || $this->get_gateway()->order_contains_subscription($order)
            );

        try {
            $token_response = new WC_PayArc_API_Token_Response(
                $this->rest_command_pool->tokenize_card($order, $authorize_card)
            );

            if ($ot_token) {
                return $token_response;
            }

            $customer_response = $this->rest_command_pool->create_customer($order);
            if (!$customer_id = $customer_response['data']['customer_id'] ?? false) {
                throw new Exception('Unable to read customer_id from response.');
            }

            $this->rest_command_pool->attach_token(
                $token_response->get_payment_token()->get_id(),
                $customer_id
            );

            // substitute token_id with payarc customer_id
            $payment_token = $token_response->get_payment_token();
            $payment_token->set_id($customer_id);

            $customer_response = new WC_PayArc_API_Customer_Response($customer_response);
            $customer_response->set_payment_token($payment_token);
        } catch (Exception $e) {
            throw new Framework\SV_WC_Plugin_Exception(
                $this->get_gateway()->prepare_error_message($e->getMessage())
            );
        }

        return $customer_response;
    }

    /**
     * @param string $token
     * @param string $customer_id
     * @return \WC_PayArc_API_Customer_Response
     * @throws Framework\SV_WC_Plugin_Exception
     */
    public function remove_tokenized_payment_method($token, $customer_id)
    {
        try {
            $response = new WC_PayArc_API_Customer_Response(
                $this->rest_command_pool->remove_customer($token)
            );
        } catch (Exception $e) {
            throw new Framework\SV_WC_Plugin_Exception(
                $this->get_gateway()->prepare_error_message($e->getMessage())
            );
        }

        return $response;
    }

    /**
     * @param float $amount
     * @return \WC_PayArc_API_Order_Response
     * @throws Framework\SV_WC_Plugin_Exception
     */
    public function init_gateway_order($amount)
    {
        try {
            $response = new WC_PayArc_API_Order_Response(
                $this->rest_command_pool->create_order($amount)
            );
        } catch (Exception $e) {
            throw new Framework\SV_WC_Plugin_Exception(
                $this->get_gateway()->prepare_error_message($e->getMessage())
            );
        }

        return $response;
    }

    /**
     * @param string $order_id
     * @return \WC_PayArc_API_Order_Details_Response
     * @throws Framework\SV_WC_Plugin_Exception
     */
    public function retrieve_gateway_order($order_id)
    {
        try {
            $response = new WC_PayArc_API_Order_Details_Response(
                $this->rest_command_pool->get_order($order_id)
            );
        } catch (Exception $e) {
            throw new Framework\SV_WC_Plugin_Exception(
                $this->get_gateway()->prepare_error_message($e->getMessage())
            );
        }

        return $response;
    }

    /**
     * @return \WC_Order
     */
    public function get_order()
    {
        return $this->order;
    }

    /**
     * @return Framework\SV_WC_Payment_Gateway_Plugin
     */
    public function get_plugin()
    {
        return $this->get_gateway()->get_plugin();
    }

    /**
     * @return WC_PayArc_Gateway
     */
    public function get_gateway()
    {
        return $this->gateway;
    }

    /**
     * @return bool
     */
    public function supports_remove_tokenized_payment_method()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function supports_update_tokenized_payment_method()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function supports_get_tokenized_payment_methods()
    {
        return false;
    }

    /**
     * @param \WC_Order $order
     * @throws \Exception
     */
    public function update_tokenized_payment_method(\WC_Order $order)
    {
        throw new Exception('update_tokenized_payment_method() is not implemented.');
    }

    /**
     * @param string $customer_id
     * @throws \Exception
     */
    public function get_tokenized_payment_methods($customer_id)
    {
        throw new Exception('get_tokenized_payment_methods() is not implemented.');
    }

    /**
     * @param \WC_Order $order
     * @throws \Exception
     */
    public function check_debit(\WC_Order $order)
    {
        throw new Exception('check_debit() is not implemented.');
    }

    /**
     * @param array $args
     * @throws \Exception
     */
    protected function get_new_request($args = [])
    {
        throw new Exception('get_new_request() is not implemented.');
    }
}
