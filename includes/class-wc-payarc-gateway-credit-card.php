<?php

use SkyVerge\WooCommerce\PluginFramework\v5_11_0 as Framework;

defined('ABSPATH') || exit;

class WC_PayArc_Gateway_CreditCard extends WC_PayArc_Gateway
{
    const DEFAULT_CARD_TYPES = ['VISA', 'MC', 'AMEX'];

    /**
     * Card should be authorized to use its token w/o entering cvv
     *
     * - Forced on subscription purchase
     * - May be enabled on regular checkout by default
     * - Disabled on Add Payment Method page in My Account regardless of this property to avoid mass cc checking attacks
     *
     * This may become an admin option sometime. Explicitly set to false for now
     *
     * @var bool
     */
    private $authorize_card = false;

    /**
     * @return void
     */
    public function __construct()
    {
        add_filter('wc_payment_gateway_payarc_credit_card_form_fields', [$this, 'adjust_form_fields']);

        if (!$this->card_authorization_enabled()) {
            add_action('woocommerce_payment_token_set_default', [$this, 'prevent_default_payment_token_change_notice'], 5, 2);
        }

        parent::__construct(
            WC_PayArc::CREDIT_CARD_GATEWAY_ID,
            WC_PayArc::instance(),
            [
                'method_title' => __('PAYARC Payment Gateway'),
                'method_description' => __('Allow customers to securely pay using their credit card with PAYARC Payment Gateway.'),
                'supports' => [
                    self::FEATURE_PRODUCTS,
                    self::FEATURE_CARD_TYPES,
                    self::FEATURE_PAYMENT_FORM,
                    self::FEATURE_TOKENIZATION,
                    self::FEATURE_CREDIT_CARD_CHARGE,
                    self::FEATURE_CREDIT_CARD_AUTHORIZATION,
                    self::FEATURE_CREDIT_CARD_CAPTURE,
                    self::FEATURE_DETAILED_CUSTOMER_DECLINE_MESSAGES,
                    self::FEATURE_REFUNDS,
                    self::FEATURE_VOIDS,
                    self::FEATURE_ADD_PAYMENT_METHOD,
                    self::FEATURE_TOKEN_EDITOR
                ],
                'payment_type' => self::PAYMENT_TYPE_CREDIT_CARD,
                'environments' => [
                    self::ENVIRONMENT_PRODUCTION => __('Live', 'woocommerce'),
                    self::ENVIRONMENT_TEST => __('Test', 'woocommerce')
                ],
                'currencies' => ['USD'],
                //'countries' => ['US'],
                'card_types' => [
                    'VISA' => 'Visa',
                    'MC' => 'MasterCard',
                    'AMEX' => 'American Express',
                    'DISC' => 'Discover',
                    'DINERS' => 'Diners',
                    'MAESTRO' => 'Maestro',
                    'JCB' => 'JCB'
                ]
            ]
        );

        if (!$this->card_authorization_enabled()) {
            $this->remove_support(
                [
                    'subscription_payment_method_delayed_change',
                    'subscription_payment_method_change_customer',
                    'subscription_payment_method_change_admin'
                ]
            );
        }
    }

    /**
     * @return string[]
     */
    public function get_payment_method_defaults()
    {
        $defaults = parent::get_payment_method_defaults();
        $defaults['expiry'] = '';
        $defaults['csc'] = '';

        return $defaults;
    }

    /**
     * @param array $fields
     * @return array
     */
    public function adjust_form_fields($fields)
    {
        $fields['connection_settings'] = [
            'title' => __('Connection Settings', 'woocommerce'),
            'type'  => 'title'
        ];

        $fields = $this->array_rearrange($fields, 'environment', 'connection_settings');
        $fields = $this->array_rearrange($fields, 'access_token', 'connection_settings');

        return $fields;
    }

    /**
     * @param string $default_token_id
     * @param \WC_Payment_Token $default_token
     */
    public function prevent_default_payment_token_change_notice($default_token_id, $default_token)
    {
        global $wp;
        if ($default_token->get_gateway_id() == WC_PayArc::CREDIT_CARD_GATEWAY_ID) {
            unset($wp->query_vars['set-default-payment-method']);
        }
    }

    /**
     * @param \WC_Order $order
     * @param Framework\SV_WC_Payment_Gateway_API_Response $response
     */
    public function add_payment_gateway_transaction_data($order, $response)
    {
        if (!$response instanceof \WC_PayArc_API_Transaction_Response) {
            return;
        }

        $this->update_order_meta($order, 'avs_result', $response->get_avs_result());
        $this->update_order_meta($order, 'csc_result', $response->get_csc_result());
    }

    /**
     * @return bool
     */
    public function card_authorization_enabled()
    {
        return $this->authorize_card && !is_add_payment_method_page();
    }

    /**
     * @return bool
     */
    public function csc_enabled_for_tokens()
    {
        return !$this->card_authorization_enabled();
    }

    /**
     * @return bool
     */
    public function tokenize_after_sale()
    {
		return true;
	}

	/**
     * Always require CVV for CCs
     *
     * @return bool
     */
	public function csc_enabled()
    {
        return true;
    }

    /**
     * @param array $form_fields
     * @return array
     */
    protected function add_csc_form_fields($form_fields)
    {
        if ($this->card_authorization_enabled()) {
            $form_fields['enable_token_csc'] = [
                'title' => __('Saved Card Verification', 'woocommerce'),
                'label' => __('Display the Card Security Code field when paying with a saved card', 'woocommerce'),
                'type' => 'checkbox',
                'default' => 'no'
            ];
        }

        return $form_fields;
    }

    /**
     * @return \WC_PayArc_Gateway_CreditCard_Form
     */
    protected function init_payment_form_instance()
    {
        return new WC_PayArc_Gateway_CreditCard_Form($this);
    }

    /**
     * @param array $form_fields
     * @return array
     */
    protected function add_card_types_form_fields($form_fields)
    {
        $result = parent::add_card_types_form_fields($form_fields);
        $result['card_types']['default'] = self::DEFAULT_CARD_TYPES;
        $result['card_types']['title'] = __('Accepted Credit Cards', 'woocommerce');
        $result['card_types']['desc_tip'] = __('These are the card types accepted during checkout.', 'woocommerce');
        unset($result['card_types']['description']);

        return $result;
    }

    /**
     * @param WC_Order $order
     * @return bool
     * @throws Exception
     */
    protected function do_transaction($order)
    {
        if (!$order->payment->token) {
            $this->validateCardType($order->payment->card_type);
        }
        return parent::do_transaction($order);
    }

    /**
     * @param WC_Order $order
     * @param Framework\SV_WC_Payment_Gateway_API_Create_Payment_Token_Response|null $response
     * @return array
     * @throws Exception
     */
    protected function do_add_payment_method_transaction(
        \WC_Order $order,
        Framework\SV_WC_Payment_Gateway_API_Create_Payment_Token_Response $response = null
    ) {
        $this->validateCardType($order->payment->card_type);
        return parent::do_add_payment_method_transaction($order, $response);
    }

    /**
     * @param string $card_type
     * @throws Exception
     */
    private function validateCardType($card_type)
    {
        $map = [
            Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_VISA => 'VISA',
            Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_MASTERCARD => 'MC',
            Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_AMEX => 'AMEX',
            Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_DISCOVER => 'DISC',
            Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_DINERSCLUB => 'DINERS',
            Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_MAESTRO => 'MAESTRO',
            Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_JCB => 'JCB'
        ];

        if (!$card_type = $map[$card_type] ?? null) {
            return;
        }

        if (!in_array($card_type, $this->get_card_types())) {
            throw new Framework\SV_WC_Plugin_Exception('This Card Type is not supported.');
        }
    }
}
