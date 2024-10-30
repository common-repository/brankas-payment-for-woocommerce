<?php

add_action( 'woocommerce_checkout_process', 'wc_minimum_order_amount' );
add_action( 'woocommerce_before_cart' , 'wc_minimum_order_amount' );

/**
 * Set a minimum order amount for checkout
 */

function wc_minimum_order_amount() {
    if( isset( $_POST['payment_method'] ) && 'brankas' === $_POST['payment_method'] ) {
        // Set this variable to specify a minimum order value
        // TODO: add config fields to configure the minimum order amount for checkout.
        $minimum = 100.00;

        if ( WC()->cart->total < $minimum ) {

            if( is_cart() ) {

                wc_print_notice( 
                    sprintf( 'Your current order total is %s — you must have an order with a minimum of %s to place your order ' , 
                        wc_price( WC()->cart->total ), 
                        wc_price( $minimum )
                    ), 'error' 
                );

            } else {

                wc_add_notice( 
                    sprintf( 'Your current order total is %s — you must have an order with a minimum of %s to place your order' , 
                        wc_price( WC()->cart->total ), 
                        wc_price( $minimum )
                    ), 'error' 
                );

            }
        }
    }
}