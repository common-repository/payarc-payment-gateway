jQuery(document).ready(($) => {

    'use strict';

    window.WC_PayArc_Hosted_Payment_Form_Handler =
		class WC_PayArc_Hosted_Payment_Form_Handler extends SV_WC_Payment_Form_Handler_v5_11_0 {

        constructor(args) {
            super(args);

            this.initialized = false;

            this.ajax_url = args.ajax_url;
            this.ajax_nonce = args.ajax_nonce;
        }

        validate_payment_data() {
            let that = this;

            if (!this.validate_required_fields()) {
                return false;
            }

            if (this.initialized) {
                this.initialized = false;
                return true;
            }

            this.block_ui();

            $.post(this.ajax_url, {
                    ajax_nonce: this.ajax_nonce,
                    action: 'wc_' + this.id + '_init_payarc_order'
                }, (response) => {
                    if (!response.success) {
                        that.render_errors([response.data.message]);
                        that.unblock_ui();
                        return;
                    }

                    const modal = that.init_payarc_modal(
                        response.data.amount,
                        response.data.order_id,
                        response.data.token
                    );

                    modal.renderModal();
                    that.initialized = true;
                }
            );

            return false;
        }

        init_payarc_modal(amount, order_id, token) {
            const payarc = new PayArc({
                amount: amount,
                orderId: order_id,
                orderToken: token,
                viewScheme: 'light'
            });

            payarc.on('paymentCompleted', this.success_handler.bind(this, payarc));
            payarc.on('paymentDeclined', this.failure_handler.bind(this, payarc));
            payarc.on('modalClosed', this.close_handler.bind(this, payarc));

            return payarc;
        }

        success_handler(payarc) {
            this.form.submit();
            payarc.closeModal();
        }

        failure_handler(payarc) {
            this.render_errors(['An error occurred, please try again or try an alternate form of payment.']);
            this.initialized = false;
            payarc.closeModal();
        }

        close_handler(payarc) {
            this.unblock_ui();
            this.initialized = false;
        }

        validate_required_fields() {
            let has_errors = false;
            $('.validate-required input:visible, .validate-required select:visible').trigger('validate');
            $('.woocommerce-invalid-required-field').each((index, element) => {
                has_errors = true;
            });

            return !has_errors;
        }
    };

    $(document.body).trigger('wc_payarc_hosted_payment_form_handler_loaded');
});
