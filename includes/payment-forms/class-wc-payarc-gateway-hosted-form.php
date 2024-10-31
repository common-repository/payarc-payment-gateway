<?php

use SkyVerge\WooCommerce\PluginFramework\v5_11_0 as Framework;

defined('ABSPATH') || exit;

class WC_PayArc_Gateway_Hosted_Form extends Framework\SV_WC_Payment_Gateway_Payment_Form
{
	/**
     * @var string
     */
	protected $js_handler_base_class_name = 'WC_PayArc_Hosted_Payment_Form_Handler';

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
	protected function get_js_handler_args()
    {
        return [
            'id' => $this->get_gateway()->get_id(),
            'ajax_url' => admin_url('admin-ajax.php'),
            'ajax_nonce' => wp_create_nonce('wc_' . $this->get_gateway()->get_id() . '_init_payarc_order')
        ];
	}

    /**
     * @return string
     */
    protected function get_js_handler_class_name()
    {
		return $this->js_handler_base_class_name;
	}
}
