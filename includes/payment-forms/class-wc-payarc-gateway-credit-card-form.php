<?php

use SkyVerge\WooCommerce\PluginFramework\v5_11_0 as Framework;

defined('ABSPATH') || exit;

class WC_PayArc_Gateway_CreditCard_Form extends Framework\SV_WC_Payment_Gateway_Payment_Form
{
    /**
     * @return string
     */
    public function get_payment_form_description_html()
    {
        $description = '';
        if ($this->get_gateway()->get_description()) {
            $description .= '<p>' . wp_kses_post($this->get_gateway()->get_description()) . '</p>';
        }
        return apply_filters('wc_' . $this->get_gateway()->get_id() . '_payment_form_description', $description, $this);
    }

    /**
     * @return array
     */
    protected function get_tokens()
    {
        if ($this->get_gateway()->cart_contains_subscription()
            && !$this->get_gateway()->card_authorization_enabled()
        ) {
            // @TODO: show already authorized tokens?
            return [];
        }

        return parent ::get_tokens();
    }
}
