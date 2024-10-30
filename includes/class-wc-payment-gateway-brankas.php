<?php

/**
 * Brankas Direct Payments Gateway.
 *
 * Provides a Brankas Direct Payment Gateway.
 *
 * @class       WC_Gateway_Brankas
 * @extends     WC_Payment_Gateway
 * @version     1.0.9
 * @package     WooCommerce/Classes/Payment
 */
class WC_Gateway_Brankas extends WC_Payment_Gateway {

    /**
     * the selected payment bank
     * @var int
     */
    public $payment_source;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		// Setup general properties.
		$this->setup_properties();

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Get settings.
		$this->title                = $this->get_option( 'title' );
		$this->description          = $this->get_option( 'description' );
		$this->api_key              = $this->get_option( 'api_key' );
		$this->instructions         = $this->get_option( 'instructions' );
		$this->sandbox_mode         = 'yes' === $this->get_option( 'sandbox_mode', 'no' );
		$this->sandbox_api_endpoint = 'https://plugins-services.sandbox.bnk.to';
		$this->live_api_endpoint    = 'https://plugins-services.bnk.to';
		$this->init_endpoint    	= '/v1/init';
		$this->sources_endpoint    	= '/v1/payment_sources';
		$this->select_placeholder   = $this->get_option( 'select_placeholder' );
		$this->select_field_label   = $this->get_option( 'select_field_label' );
		$this->select_invalid_msg   = $this->get_option( 'select_invalid_msg' );
		$this->payment_field_label   = $this->get_option( 'payment_field_label' );


		if ( $this->sandbox_mode ) {
			/* translators: %s: Link to Brankas Direct sandbox testing guide page */
			$this->description .= ' ' . sprintf( __( '<br /><br />WARNING: SANDBOX ENABLED<br /><br />You can use sandbox testing accounts only. See the <a href="%s">Brankas Direct Payment Sandbox Testing Guide</a> for more details.', 'brankas-payment-for-woocommerce' ), 'https://brank.as/direct' );
			$this->description  = trim( $this->description );
		}

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'change_payment_complete_order_status' ), 10, 3 );

		// Customer Emails.
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );

		add_action( 'woocommerce_api_wc_gateway_brankas', array( $this, 'check_response' ) );
		add_action( 'valid-brankas-direct-request', array( $this, 'valid_response' ) );
		
		add_filter('brankas_get_payment_sources', array( $this, 'get_payment_sources'));
		add_filter('brankas_get_config_settings', array( $this, 'get_config_settings' ));
	}


	/**
	 * Setup general properties for the gateway.
	 */
	protected function setup_properties() {
		$this->id                 = 'brankas';
		$this->icon               = apply_filters( 'woocommerce_brankas_icon', plugins_url('../assets/brankas-logo-mark-circle-dark.svg', __FILE__ ) );
		$this->method_title       = __( 'Brankas Direct Payments', 'brankas-payment-for-woocommerce' );
		$this->api_key            = __( 'Add API Key', 'brankas-payment-for-woocommerce' );
		$this->method_description = __( 'Let your customers pay with Brankas Direct Payments.', 'brankas-payment-for-woocommerce' );
		$this->has_fields         = false;
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'            => array(
				'title'       => __( 'Enable/Disable', 'brankas-payment-for-woocommerce' ),
				'label'       => __( 'Enable Brankas Direct Payments', 'brankas-payment-for-woocommerce' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'sandbox_mode'              => array(
				'title'       => __( 'Enable/Disable Sandbox', 'brankas-payment-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Brankas Direct Payments Sandbox environment', 'brankas-payment-for-woocommerce' ),
				'default'     => 'no',
				'description' => __( 'Brankas Direct Payment Sandbox can be used to test payments with a dummy bank account' , 'brankas-payment-for-woocommerce' ),
				'desc_tip'    => true,
			),			
			'api_key'             => array(
				'title'       => __( 'API Key', 'brankas-payment-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Add your API key that has been given to you by Brankas', 'brankas-payment-for-woocommerce' ),
				'desc_tip'    => true,
			),
			'title'              => array(
				'title'       => __( 'Checkout Title', 'brankas-payment-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Brankas Direct Payment method description that the customer will see on your checkout.', 'brankas-payment-for-woocommerce' ),
				'default'     => __( 'Brankas Direct Payment', 'brankas-payment-for-woocommerce' ),
				'desc_tip'    => true,
			),
			'description'        => array(
				'title'       => __( 'Checkout Description', 'brankas-payment-for-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Brankas Direct Payment method description that the customer will see on checkout.', 'brankas-payment-for-woocommerce' ),
				'default'     => __( 'Pay using Brankas Direct - secure, simplified bank transfers with no fees.', 'brankas-payment-for-woocommerce' ),
				'desc_tip'    => true,
			),
			'instructions'       => array(
				'title'       => __( 'Order Instructions', 'brankas-payment-for-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the Order Received thank you page.', 'brankas-payment-for-woocommerce' ),
				'default'     => __( 'Thank you for using Brankas Direct Payments', 'brankas-payment-for-woocommerce' ),
				'desc_tip'    => true,
			),
			'select_placeholder'	  => array(
				'title'       => __( 'Dropdown Placeholder', 'brankas-payment-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'The placeholder text on the bank selection dropdown', 'brankas-payment-for-woocommerce' ),
				'default'     => __( 'Choose Bank', 'brankas-payment-for-woocommerce' ),
				'desc_tip'    => true,
			),
			'select_field_label'	  => array(
				'title'       => __( 'Dropdown Field Label', 'brankas-payment-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'The label that appears above the bank selection dropdown', 'brankas-payment-for-woocommerce' ),
				'default'     => __( 'Select a bank to transfer from', 'brankas-payment-for-woocommerce' ),
				'desc_tip'    => true,
			),
			'select_invalid_msg'	  => array(
				'title'       => __( 'Dropdown Validation Error Text', 'brankas-payment-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'The error notice that appears when a bank selection has not been made', 'brankas-payment-for-woocommerce' ),
				'default'     => __( 'Please select a Bank to start your Brankas Direct Payment', 'brankas-payment-for-woocommerce' ),
				'desc_tip'    => true,
			),
			'payment_field_label'	  => array(
				'title'       => __( 'Payment Field Label', 'brankas-payment-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'The label that on the billing and order details', 'brankas-payment-for-woocommerce' ),
				'default'     => __( 'Selected Payment Bank', 'brankas-payment-for-woocommerce' ),
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Return a set of plugin config settings
	 *
	 * @return array
	 */
	public function get_config_settings() {
		return [
			'select_placeholder' => $this->select_placeholder,
			'select_field_label' => $this->select_field_label,
			'select_invalid_msg' => $this->select_invalid_msg,
			'payment_field_label' => $this->payment_field_label
		];
	}

	/**
	 * Check for Brankas Direct Response.
	 */
	public function check_response() {
        if ( ! empty( $_POST ) ) { // WPCS: CSRF ok.
            $posted = wp_unslash($_POST); // WPCS: CSRF ok, input var ok.

            // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
			do_action( 'valid-brankas-direct-request', $posted );
			
			exit;
		}

		wp_die( 'Brankas Direct Request Failure', 'Brankas Direct', array( 'response' => 500 ) );
	}

	/**
	 * There was a valid response.
	 *
	 * @param  array $posted Post data after wp_unslash.
	 */
	public function valid_response( $posted ) {
		$order = ! empty( $posted['order_id'] ) ? wc_get_order( $posted['order_id'] ) : false;

		if ( $order ) {
			$posted['payment_status'] = strtolower( $posted['payment_status'] );

			if ( method_exists( $this, 'payment_status_' . $posted['payment_status'] ) ) {
				call_user_func( array( $this, 'payment_status_' . $posted['payment_status'] ), $order, $posted );
			}
		}
	}
	/**
	 * Handle a pending payment.
	 *
	 * @param WC_Order $order  Order object.
	 * @param array    $posted Posted data.
	 */
	protected function payment_status_pending( $order, $posted ) {
		$this->payment_status_completed( $order, $posted );
	}

	/**
	 * Handle a failed payment.
	 *
	 * @param WC_Order $order  Order object.
	 * @param array    $posted Posted data.
	 */
	protected function payment_status_failed( $order, $posted ) {
		/* translators: %s: payment status. */
		$order->update_status( 'failed', sprintf( __( 'Payment %s via Brankas Direct.', 'brankas-payment-for-woocommerce' ), wc_clean( $posted['payment_status'] ) ) );
	}

	/**
	 * Handle a denied payment.
	 *
	 * @param WC_Order $order  Order object.
	 * @param array    $posted Posted data.
	 */
	protected function payment_status_denied( $order, $posted ) {
		$this->payment_status_failed( $order, $posted );
	}

	/**
	 * Handle an expired payment.
	 *
	 * @param WC_Order $order  Order object.
	 * @param array    $posted Posted data.
	 */
	protected function payment_status_expired( $order, $posted ) {
		$this->payment_status_failed( $order, $posted );
	}

	/**
	 * Handle an cancelled payment.
	 *
	 * @param WC_Order $order  Order object.
	 * @param array    $posted Posted data.
	 */
	protected function payment_status_cancelled( $order, $posted ) {
		$this->payment_status_pending( $order, $posted );
	}

	/**
	 * Handle a completed payment.
	 *
	 * @param WC_Order $order  Order object.
	 * @param array    $posted Posted data.
	 */
	protected function payment_status_completed( $order, $posted ) {
		if ( $order->has_status( wc_get_is_paid_statuses() ) ) {
			exit;
		}

		$this->validate_currency( $order, $posted['currency'] );
		$this->validate_amount( $order, $posted['amount'] );
		$this->save_brankas_direct_meta_data( $order, $posted );

        $order_date_created = $order->get_date_created();
        $webhookToken = sha1($this->api_key.$order_date_created);
        $webhook_data = $posted['additional_data'];
        if($webhookToken != $webhook_data) {
            error_log("Token posted data is " .$webhook_data. " is invalid for order " .$order->get_id());
            wc_add_notice( 'Webhook token data was invalid', 'error' );
            return;
        }
		if ( 'completed' === $posted['payment_status'] ) {
			$this->payment_complete( $order, ( ! empty( $posted['txn_id'] ) ? wc_clean( $posted['txn_id'] ) : '' ), __( 'Brankas Direct payment completed', 'brankas-payment-for-woocommerce' ) );
		}
        else {
            /* translators: %s: pending reason. */
            $this->payment_on_hold( $order, sprintf( __( 'Payment pending (%s).', 'brankas-payment-for-woocommerce' ), $posted['payment_status'] ) );
		}
	}

	/**
	 * Check currency from Brankas Direct matches the order.
	 *
	 * @param WC_Order $order    Order object.
	 * @param string   $currency Currency code.
	 */
	protected function validate_currency( $order, $currency ) {
		if ( $order->get_currency() !== $currency ) {
			
			/* translators: %s: currency code. */
			$order->update_status( 'on-hold', sprintf( __( 'Validation error: Brankas Direct currencies do not match (code %s).', 'brankas-payment-for-woocommerce' ), $currency ) );
			exit;
		}
	}

	/**
	 * Check payment amount from Brankas Direct matches the order.
	 *
	 * @param WC_Order $order  Order object.
	 * @param int      $amount Amount to validate.
	 */
	protected function validate_amount( $order, $amount ) {
		if ( number_format( $order->get_total(), 2, '.', '' ) !== number_format( ($amount / 100), 2, '.', '' ) ) {

			/* translators: %s: Amount. */
			$order->update_status( 'on-hold', sprintf( __( 'Validation error: Brankas Direct amounts do not match (gross %s).', 'brankas-payment-for-woocommerce' ), $amount ) );
			exit;
		}
	}

	/**
	 * Save important data from the Brankas Direct to the order.
	 *
	 * @param WC_Order $order  Order object.
	 * @param array    $posted Posted data.
	 */
	protected function save_brankas_direct_meta_data( $order, $posted ) {
		if ( ! empty( $posted['txn_id'] ) ) {
			update_post_meta( $order->get_id(), '_transaction_id', wc_clean( $posted['txn_id'] ) );
		}
		if ( ! empty( $posted['payment_status'] ) ) {
			update_post_meta( $order->get_id(), '_brankas_direct_status', wc_clean( $posted['payment_status'] ) );
		}
	}


	/**
	 * Complete order, add transaction ID and note.
	 *
	 * @param  WC_Order $order Order object.
	 * @param  string   $txn_id Transaction ID.
	 * @param  string   $note Payment note.
	 */
	protected function payment_complete( $order, $txn_id = '', $note = '' ) {
		if ( ! $order->has_status( array( 'processing', 'completed' ) ) ) {
			$order->add_order_note( $note );
			$order->payment_complete( $txn_id );
			WC()->cart->empty_cart();
		}
	}

	/**
	 * Hold order and add note.
	 *
	 * @param  WC_Order $order Order object.
	 * @param  string   $reason Reason why the payment is on hold.
	 */
	protected function payment_on_hold( $order, $reason = '' ) {
		$order->update_status( 'on-hold', $reason );
		WC()->cart->empty_cart();
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		include_once dirname( __FILE__ ) . '/class-wc-gateway-brankas-direct-request.php';

		$order          = wc_get_order( $order_id );
		$brankas_request = new WC_Gateway_Brankas_Request( $this );
		$endpoint    = $this->sandbox_mode ? $this->sandbox_api_endpoint : $this->live_api_endpoint;
        $payment_source = sanitize_text_field($_POST['payment_source']);

        return array(
			'result'   => 'success',
			'redirect' => $brankas_request->post_request_init_url( $order, $endpoint . $this->init_endpoint, $this->api_key, $this->sandbox_mode, $payment_source ),
		);
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function get_payment_sources() {
        include_once dirname( __FILE__ ) . '/class-wc-gateway-brankas-direct-request.php';
        $brankas_request = new WC_Gateway_Brankas_Request( $this );
        $endpoint    = $this->sandbox_mode ? $this->sandbox_api_endpoint : $this->live_api_endpoint;
        $shipping_country = WC()->customer->get_shipping_country();
        $data = $brankas_request->post_request_payment_sources( $endpoint . $this->sources_endpoint, $this->api_key, $this->sandbox_mode, $shipping_country );

        if($this->sandbox_mode) {
            return $data;
        }
        return array_filter($data, function($bank) {
            return $bank['status'] != 'Offline';
        });
	}

    /**
     * This is using to display list of gateways in woocommerce/checkout/payment.php template
     */
    public function brankas_convert_payment_source_to_gateway(): array
    {
        $payment_sources = $this->get_payment_sources();
        $arr_gateways = array();
        foreach ($payment_sources as $payment_source) {
            $brankas_payment_gateway = new WC_Gateway_Brankas();
            $brankas_payment_gateway->id = $payment_source['code'];
            $brankas_payment_gateway->title = $payment_source['name'];
            $brankas_payment_gateway->method_title = 'brankas';
            $brankas_payment_gateway->icon = $payment_source['logo_url'] ?? "";
            $brankas_payment_gateway->payment_source = $payment_source['code'];
            $arr_gateways[] = $brankas_payment_gateway;
        }
        return $arr_gateways;
    }

	/**
	 * Output for the order received page.
	 */
	public function thankyou_page() {
		if ( $this->instructions ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
		}
	}

	/**
	 * Change payment complete order status to completed for brankas orders.
	 *
	 * @since  3.1.0
	 * @param  string         $status Current order status.
	 * @param  int            $order_id Order ID.
	 * @param  WC_Order|false $order Order object.
	 * @return string
	 */
	public function change_payment_complete_order_status( $status, $order_id = 0, $order = false ) {
		if ( $order && 'brankas' === $order->get_payment_method() ) {
			$status = 'completed';
		}
		return $status;
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin  Sent to admin.
	 * @param bool     $plain_text Email format: plain text or HTML.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
		}
	}
}