<?php
/**
 * Class WC_Gateway_Brankas_Request file.
 *
 * @package WooCommerce\Brankas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates requests to send to Brankas.
 */
class WC_Gateway_Brankas_Request {

	/**
	 * Pointer to gateway making the request.
	 *
	 * @var WC_Gateway_Brankas
	 */
	protected $gateway;

	/**
	 * Endpoint for requests from Brankas.
	 *
	 * @var string
	 */
	protected $notify_url;


	/**
	 * Constructor.
	 *
	 * @param WC_Gateway_Brankas $gateway Brankas gateway object.
	 */
	public function __construct( $gateway ) {
		$this->gateway    = $gateway;
		$this->notify_url = WC()->api_request_url( 'WC_Gateway_Brankas' );
	}

	/**
	 * Post request to get the Brankas PIDP URL for an order.
	 *
	 * @param  WC_Order $order Order object.
	 * @param  string   $endpoint init endpoint to perform POST.
	 * @param  string   $api_key to get the org details.
	 * @param  bool     $sandbox_mode Whether to use sandbox mode or not.
	 * @return string
	 */
	public function post_request_init_url( $order, $endpoint, $api_key = false, $sandbox_mode = false, $payment_source ) {

        $webhook_url = site_url( '/?wc-api=WC_Gateway_Brankas/', 'https' );
        $order_id = $order->get_id();
        $order_date_created = $order->get_date_created();
        $webhook_data = sha1($api_key.$order_date_created);
        $total_amount = intval($order->get_total() * ( 100 ));
        $brankas_args = array (
            "sandbox" => $sandbox_mode,
            "api_key" => $api_key,
            "amount" => array (
                "cur" => $order->get_currency(),
                "amt" => (string)$total_amount
			),
            "expiry" => 24,
            "redirect_url" => esc_url_raw( add_query_arg( 'utm_nooverride', '1', $this->gateway->get_return_url( $order ) ) ),
            "webhook" => $webhook_url,
            "webhook_data" => $webhook_data,
            "order_id" => (string)$order_id,
            "customer" => array (
                "email" => $order->get_billing_email(),
                "fname" => $order->get_billing_first_name(),
                "lname" => $order->get_billing_last_name()
            )
		);
		
		if (! empty ( $payment_source )) {
			$brankas_args["bank_code"] = $payment_source;
		}

        $response = wp_remote_post(
			esc_url_raw( $endpoint ),
			array(
				'body'        => json_encode( $brankas_args ),
				'headers'     => array( 'Content-Type: application/json' ),
			)
		);

        if( !is_wp_error( $response ) ) {
            $body = json_decode( $response['body'], true );
		} else {
			wc_add_notice(  'Connection error.', 'error' );
			return;
		}

		return $body['url'];
	}


    /**
     * Get the list of available Brankas PIDP payment sources.
     *
     * @param  string   $endpoint init endpoint to perform POST.
     * @param  string   $api_key to get the org details.
     * @param  bool     $sandbox_mode Whether to use sandbox mode or not.
     * @param  string   $country is shipping country
     * @return array
     */
    public function post_request_payment_sources( $endpoint, $api_key = false, $sandbox_mode = false, $country) {
        $brankas_args = array (
            "sandbox" => $sandbox_mode,
            "api_key" => $api_key,
            "country" => $country
        );
		
		$full_url = esc_url_raw( $endpoint );

		$response = wp_remote_post(
			$full_url,
			array(
				'body'        => json_encode( $brankas_args ),
				'headers'     => array( 'Content-Type: application/json' ),
			)
		);

		if( is_wp_error( $response ) ) {
    		error_log(print_r($response, true));
			wc_add_notice( 'Connection error getting payment sources', 'error' );
			return false;
		}
		
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if( empty( $data ) ) {
    		error_log(print_r($response, true));
			wc_add_notice( 'No available payment sources', 'error' );
			return false;
		}

		return $data['sources'];
	}

}
