<?php
/**
 * Output a single payment method
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/payment-method.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme toh
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<li class="wc_payment_method payment_method_<?php echo esc_attr( $gateway->id ); ?>">
	<input id="payment_method_<?php echo esc_attr( $gateway->id) . "_" . esc_attr( $gateway->method_title) ; ?>" type="radio" class="input-radio" name="payment_method"
           onchange="setBrankasPaymentSource(this)"
	value="<?php echo esc_attr($gateway->method_title); ?>"
	data-payment-source="<?php echo esc_attr( $gateway->payment_source ); ?>" <?php checked( $gateway->chosen, true ); ?>
	data-order_button_text="<?php echo esc_attr( $gateway->order_button_text ); ?>" />

	<label for="payment_method_<?php echo esc_attr( $gateway->id) . "_" . esc_attr( $gateway->method_title); ?>">
		<?php echo $gateway->title; /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?>
        <img src="<?php echo esc_attr($gateway->icon) ?>" alt="<?php echo esc_attr($gateway->title) ?>">
	</label>
</li>
