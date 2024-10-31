=== PAYARC Payment Gateway ===
Author: payarc
Contributors: payarc
Tags: woocommerce, ecommerce, e-commerce, payarc
Requires PHP: 7.4
Requires at least: 5.3
Tested up to: 6.5.2
WC requires at least: 4.3
Stable tag: 1.1.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.txt

Adds the PAYARC Payment Gateway to your WooCommerce site.

== Description ==
PAYARC is a payment processing provider offering credit processing merchant accounts and tools to enable smart and safe transactions.
With their client-centered and technology-driven payment platform, they empower merchants and customers to make smarter decisions, optimize their organization’s processes, and scale their businesses – one payment at a time.

Integrated directly into the PAYARC gateway and leveraging the latest PCI Scope reducing technology, merchants can now accept their payments easily and safely.

The platform is equipped to handle different types of transactions with ease, ensuring a seamless process and a positive experience for your customers.

The payarc.js JavaScript is used for the PAYARC Payment Gateway's Hosted Checkout page. This page provides a modal that enables credit card details to be captured without going through the WordPress installation.
This increases the PCI Compliance level of the WordPress/WooCommerce installation and reduces the liability of the merchant. This is an optional checkout method that the merchant can enable in the plugin settings.

Learn more about PAYARC:
https://payarc.com/

Terms and conditions:
https://payarc.com/terms-and-conditions/

Privacy Policy:
https://payarc.com/privacy-policy-2/

== Installation ==

Installing via uploaded ZIP file
1. Please log in to your dashboard and go to **Plugins > Add New**.
2. Click the **Upload Plugin** button.
3. Choose `woocommerce-gateway-payarc.zip` from your local directory.
4. Click the **Install Now** button.
5. After installation is complete click on the **Activate Plugin** button.


Installing manually
1. Upload the `woocommerce-gateway-payarc.zip` files to the `/wp-content/plugins`.
2. Unzip `woocommerce-gateway-payarc.zip`.
3. Activate the plugin through the **Plugins** menu in WordPress.

== Screenshots ==

1. Installed Plugin
2. Credit Card Configuration
3. Hosted Checkout Configuration
4. Hosted Checkout Payment
5. Hosted Checkout Page

== Changelog ==

= 1.0.0 =
- Implementation of payment methods: authorization, capture, authorize+capture
- Implementation of void and refund
- Implementation of saved cards and vault
- Implementation of Hosted Checkout

= 1.1.0 =
- Add support for PHP 8