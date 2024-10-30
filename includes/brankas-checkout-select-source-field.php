<?php

add_action( 'woocommerce_checkout_process', 'brankas_source_field_validation' );
function brankas_source_field_validation() {
    if('brankas' != $_POST['payment_method']) {
        return;
    }

    if(empty( $_POST['payment_source'] )) {
        $settings = apply_filters('brankas_get_config_settings', null);
        wc_add_notice( $settings['select_invalid_msg'], 'error' );
    }
}

add_action( 'woocommerce_checkout_update_order_meta', 'brankas_checkout_update_order_meta', 10, 1 );
function brankas_checkout_update_order_meta( $order_id ) {
    if( isset( $_POST['payment_source'] ) || ! empty( $_POST['payment_source'] ) ) {
       $payment_source = sanitize_text_field( wp_unslash( $_POST['payment_source'] ) );
       update_post_meta( $order_id, 'payment_source', $payment_source );
    }
}

// Used on Order Details page
add_action( 'woocommerce_admin_order_data_after_billing_address', 'brankas_order_data_after_billing_address', 10, 1 );
function brankas_order_data_after_billing_address( $order ) {
    $settings = apply_filters('brankas_get_config_settings', null);
    echo '<p><strong>' . __( 'Selected Payment Bank', 'brankas-payment-for-woocommerce' ) . '</strong><br>' . esc_html(get_post_meta( (int)$order->get_id(), 'payment_source', true )) . '</p>';
}

// TODO: I'm not sure what these are needed for yet?
add_action( 'woocommerce_order_item_meta_end', 'brankas_order_item_meta_end', 10, 3 );
function brankas_order_item_meta_end( $item_id, $item, $order ) {
    $settings = apply_filters('brankas_get_config_settings', null);
    echo '<p><strong>' . __( 'Selected Payment Bank', 'brankas-payment-for-woocommerce' ) . '</strong><br>' . esc_html(get_post_meta( (int)$order->get_id(), 'payment_source', true )) . '</p>';
}

