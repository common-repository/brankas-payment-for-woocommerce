<?php
/**
 * Plugin Name: Brankas Payment For WooCommerce
 * Plugin URI:
 * Author: Brankas
 * Author URI: https://brank.as
 * Description: Make secure bank transfer payments using Brankas plugin for WooCommerce
 * Version: 1.3.1
 * License: GPL2
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: brankas-payment-for-woocommerce
 * 
 * Class WC_Gateway_Brankas file.
 *
 * @package WooCommerce Brankas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action( 'plugins_loaded', 'brankas_payment_init', 11 );
add_action( 'wp_enqueue_scripts', 'brankas_load_plugin_assets' );
add_filter( 'woocommerce_currencies', 'brankas_add_currencies' );
add_filter( 'woocommerce_currency_symbol', 'brankas_add_currencies_symbol', 10, 2 );
add_filter( 'woocommerce_payment_gateways', 'add_to_woo_brankas_payment_gateway');
add_filter( 'woocommerce_locate_template', 'brankas_intercept_wc_template', 10, 3 );

/**
 * Filter the payment checkout template path to use payment-method.php in this plugin instead of the one in WooCommerce.
 *
 * @param string $template      Default template file path.
 * @param string $template_name Template file slug.
 * @param string $template_path Template file name.
 *
 * @return string The new Template file path.
 */
function brankas_intercept_wc_template( $template, $template_name, $template_path ) {
    $template_directory = trailingslashit( plugin_dir_path( __FILE__ ) ) . 'woocommerce/';
    $path = $template_directory . $template_name;
    return file_exists( $path ) ? $path : $template;

}
function brankas_payment_init() {
    if( class_exists( 'WC_Payment_Gateway' ) ) {
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-wc-payment-gateway-brankas.php';
		require_once plugin_dir_path( __FILE__ ) . '/includes/brankas-checkout-validation.php';
		require_once plugin_dir_path( __FILE__ ) . '/includes/brankas-checkout-select-source-field.php';
	}
}

function brankas_load_plugin_assets() {
    $plugin_url = plugin_dir_url( __FILE__ );
    wp_enqueue_style( 'BrankasStyleHandler', $plugin_url . 'assets/css/brankas_style.css' );
    wp_enqueue_script('BrankasScriptHandler', $plugin_url . 'assets/js/brankas_scripts.js', array('jquery'));
}

function add_to_woo_brankas_payment_gateway( $gateways ) {
    $gateways[] = 'WC_Gateway_Brankas';
    return $gateways;
}

function brankas_add_currencies( $currencies ) {
	// $currencies['IDR'] = __( 'Indonesian Rupiah', 'brankas-payment-for-woocommerce' );
	$currencies['PHP'] = __( 'Philippine Peso', 'brankas-payment-for-woocommerce' );
	return $currencies;
}

function brankas_add_currencies_symbol( $currency_symbol, $currency ) {
	switch ( $currency ) {
		// case 'IDR': 
		// 	$currency_symbol = 'IDR'; 
		case 'PHP': 
			$currency_symbol = 'PHP'; 
		break;
	}
	return $currency_symbol;
}