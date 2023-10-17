<?php
/*
 * Plugin Name: WooCommerce Invoice Payment Gateway
 * Plugin URI: https://stonedigital.com.au/
 * Description: Allow your customers to select invoice payment method during checkout on LifeLong Literacy eCommerce website.
 * Author: Stone Digital
 * Author URI: https://stonedigital.com.au/
 * Version: 1.0.0
 */

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'll_add_invoice_payment_gateway_class' );
function ll_add_invoice_payment_gateway_class( $gateways ) {
	$gateways[] = 'LL_Invoice_Payment_Gateway'; // your class name is here
	return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'll_init_invoice_payment_gateway_class' );
function ll_init_invoice_payment_gateway_class() {

	class LL_Invoice_Payment_Gateway extends WC_Payment_Gateway {

 		/**
 		 * Class constructor
 		 */
 		public function __construct() {

			$this->id = 'll-invoice-payment'; // payment gateway plugin ID
			$this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
			$this->method_title = 'Invoice Payment Gateway';
			$this->method_description = 'Allow your customers to select invoice payment method during checkout.'; // will be displayed on the options page
		
			// gateways can support subscriptions, refunds, saved payment methods,
			// but in this tutorial we begin with simple payments
			$this->supports = array(
				'products'
			);
		
			// Method with all the options fields
			$this->init_form_fields();
		
			// Load the settings.
			$this->init_settings();
			$this->title = $this->get_option( 'title' );
			$this->description = $this->get_option( 'description' );
			$this->instructions = $this->get_option( 'instructions' );
		
			// This action hook saves the settings
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

			// Add instructions to the thank you page.
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );		

 		}

		/**
 		 * Plugin options
 		 */
 		public function init_form_fields(){


			$this->form_fields = array(
				'enabled' => array(
					'title'       => 'Enable/Disable',
					'label'       => 'Enable Invoice Payment Gateway',
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				),
				'title' => array(
					'title'       => 'Title',
					'type'        => 'text',
					'description' => 'This controls the title which the user sees during checkout.',
					'default'     => 'Invoice Payment',
				),
				'description' => array(
					'title'       => 'Description',
					'type'        => 'textarea',
					'description' => 'This controls the description which the user sees during checkout.',
				),
				'instructions' => array(
					'title'       => 'Instructions',
					'description' => 'Instructions that will be added to the thank you page and emails.',
					'type'        => 'textarea',
				),
			);
	
	 	}

		/**
		 * You will need it if you want your custom credit card form
		 */
		public function payment_fields() {

			// ok, let's display some description before the payment form
			if ( $this->description ) {

				// display the description with <p> tags etc.
				echo wpautop( wp_kses_post( $this->description ) );
			}
	
		}

		/*
		 * We're processing the payments here
		 */
		public function process_payment( $order_id ) {


			global $woocommerce;
 
			// we need it to get any order detailes
			$order = wc_get_order( $order_id );

			// Mark as on-hold (we're awaiting the payment)
			$order->update_status('on-hold', __( 'Awaiting invoice payment Order status changed from Pending payment to On hold.', 'woocommerce' ));

			// Remove cart.
			$woocommerce->cart->empty_cart();

			// Return thankyou redirect.
			return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
			);
					
	 	}

    /**
     * Output for the order received page.
     */
    public function thankyou_page() {

			if ( $this->instructions ) {
					echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
			}
		
		}


 	}
}