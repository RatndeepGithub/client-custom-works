<?php require 'checksum.php'; ?>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/*
Plugin Name: Woocommerce CedPay Payment Gateway
Plugin URI: http://thegreyparrots.com/
Description: CedPay is an indian payment which will accept the payment from any types of credit and dabit card .
Version: 1.0.0
Author: The Grey Parrots
Author URI: http://thegreyparrots.com/
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

	if (!defined('ABSPATH')) {
		exit;
	}
add_action( 'plugins_loaded', 'woocommerce_cedpay_init', 0 );
function add_cedpay_gateway_class($methods)
{
	$methods[] = 'WC_Gateway_Cedpay';
	return $methods;
}
add_filter('woocommerce_payment_gateways', 'add_cedpay_gateway_class');


function woocommerce_cedpay_init() {


	class WC_Gateway_Cedpay extends WC_Payment_Gateway
	{
         public function __construct()
    {
        $this->id                 = 'cedpay';
        $this->icon               = ''; // URL of the icon that will be displayed on checkout page near your gateway name
        $this->has_fields         = true;
        $this->method_title       = __('Cedpay', 'woocommerce');
        $this->method_description = __('Allows payments using Cedpay.', 'woocommerce');

        $this->init_form_fields();
        $this->init_settings();

        $this->title        = $this->get_option('title');
        $this->description  = $this->get_option('description');
        $this->merchant_id  = $this->get_option('merchant_id');
        $this->secret_key   = $this->get_option('secret_key');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __('Enable/Disable', 'woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable Cedpay Payment', 'woocommerce'),
                'default' => 'yes'
            ),
            'title' => array(
                'title'       => __('Title', 'woocommerce'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                'default'     => __('Cedpay', 'woocommerce'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Description', 'woocommerce'),
                'type'        => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
                'default'     => __('Pay securely using your credit card with Cedpay.', 'woocommerce'),
            ),
            'merchant_id' => array(
                'title'       => __('Merchant ID', 'woocommerce'),
                'type'        => 'text',
                'description' => __('This is the Merchant ID provided by Cedpay.', 'woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'secret_key' => array(
                'title'       => __('Secret Key', 'woocommerce'),
                'type'        => 'password',
                'description' => __('This is the Secret Key provided by Cedpay.', 'woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
        );
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        // Mark as pending payment (you can change this)
        $order->update_status('pending', __('Awaiting Cedpay payment', 'woocommerce'));

        // Return thank you page redirect to the receipt page
        return array(
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );
    }

    public function receipt_page($order_id)
    {
        $order = wc_get_order($order_id);
        echo '<p>' . __('Thank you for your order, please complete the payment below.', 'woocommerce') . '</p>';
        echo $this->generate_cedpay_iframe($order);
    }

    public function generate_cedpay_iframe($order)
    {
        $order_id = $order->get_id();
        $amount = $order->get_total();
        $currency = get_woocommerce_currency();
        $redirect_url = $this->get_return_url($order);

        // Cedpay API parameters
        $params = array(
            'merchantIdentifier' => $this->merchant_id,
            'orderId' => $order_id,
            'returnUrl' => $redirect_url,
            'buyerEmail' => $order->get_billing_email(),
            'buyerPhoneNumber' => $order->get_billing_phone(),
            'currency' => $currency,
            'amount' => $amount * 100, // Amount in paise
            'productDescription' => 'Order ' . $order_id,
            'txnType' => 1,
            'zpPayOption' => 1,
        );

        // Generate checksum
        $checksum = $this->generate_checksum($params, $this->secret_key);
        $params['checksum'] = $checksum;

        $iframe_url = 'https://api.zaakpay.com/api/paymentTransact/V10';
        $iframe_url = 'https://wordpress-957220-4062555.cloudwaysapps.com';

        // Create the form with hidden fields
        $form = '<form id="cedpay_payment_form" method="post" action="' . $iframe_url . '" target="cedpay_iframe">';
        foreach ($params as $key => $value) {
            $form .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
        }
        $form .= '<iframe name="cedpay_iframe" width="100%" height="600px" frameborder="0"></iframe>';
        $form .= '<input type="submit" class="button alt" id="submit_cedpay_payment_form" value="' . __('Pay via Cedpay', 'woocommerce') . '" onclick="document.getElementById(\'cedpay_payment_form\').submit(); return false;" />';
        $form .= '</form>';

        return $form;
    }

    private function generate_checksum($params, $secret_key)
    {
        // Sort the parameters alphabetically
        ksort($params);

        // Create a string with the sorted parameters
        $str = '';
        foreach ($params as $key => $value) {
            $str .= $key . '=' . $value . '&';
        }
        $str = rtrim($str, '&');

        // Generate the checksum using the secret key
        return hash_hmac('sha256', $str, $secret_key);
    }
    }


}
define( 'cedpay_imgdir', WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) . '/images/' );
define( 'WC_CEDPAY_PAYMENT_PLUGIN_TOKEN', 'wc-cedpay-payment' );
define( 'WC_CEDPAY_PAYMENT_TEXT_DOMAIN', 'cedpay_payment' );
define( 'WC_CEDPAY_PAYMENT_PLUGIN_VERSION', '1.0.0' );
// function woocommerce_cedpay_fallback_notice() {
// 	 echo '<div class="error"><p>' . sprintf( __( 'WooCommerce CedPay Payment Gateways depends on the last version of %s to work!', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a>' ) . '</p></div>';
// }

// function woocommerce_cedpay_init() {

// 	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
// 		add_action( 'admin_notices', 'woocommerce_cedpay_fallback_notice' );
// 		return;
// 	}

// 	if ( isset( $_GET['msg'] ) && ! empty( $_GET['msg'] ) ) {
// 		add_action( 'the_content', 'cedpay_showMessage' );
// 	}
// 	function cedpay_showMessage( $content ) {

// 		 return '<div class="' . htmlentities( $_GET['type'] ) . '">' . htmlentities( urldecode( $_GET['msg'] ) ) . '</div>' . $content;
// 	}

// 	/**
// 	 * Payment Gateway class
// 	 */
// 	class Ced_Payment extends WC_Payment_Gateway {

// 		public function __construct() {

// 			$this->id                 = 'zpay';
// 			$this->method_title       = 'CedPay Payment Gateway';
// 			$this->method_description = __( 'This plugin no need any ssl it will redirect to the cedpay secure hosted page and will return after payment It is only accept the INR ', WC_CEDPAY_PAYMENT_TEXT_DOMAIN );
// 			$this->has_fields         = false;
// 			$this->init_form_fields();
// 			$this->init_settings();
// 			// if ( $this->settings['showlogo'] == 'yes' ) {
// 					// $this->icon = cedpay_imgdir . 'logo.png';
// 			// }
// 			foreach ( $this->settings as $setting_key => $value ) {
// 				$this->$setting_key = $value;
// 			}
// 			if ( $this->mode != 1 ) {
// 				$this->liveurl = 'https://zaakstaging.cedpay.com/api/paymentTransact/V8';
// 			} else {
// 				$this->liveurl = 'https://api.cedpay.com/api/paymentTransact/V8';

// 			}
// 			add_action( 'init', array( &$this, 'check_zpay_response' ) );
// 			// update for woocommerce >2.0
// 			add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_zpay_response' ) );
// 			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
// 						/* 2.0.0 */
// 				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
// 			} else {
// 					/* 1.6.6 */
// 				add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
// 			}

// 			add_action( 'woocommerce_receipt_zpay', array( &$this, 'receipt_page' ) );
// 		}

// 		function init_form_fields() {

// 			global $woocommerce;
// 			$this->form_fields = array(
// 				'enabled'            => array(
// 					'title'       => __( 'Enable/Disable', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'type'        => 'checkbox',
// 					'label'       => __( 'Enable CedPay Payment Gateway.', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'default'     => 'no',
// 					'description' => __( 'Show in the Payment List as a payment option', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 				),
// 				'max_amount'         => array(
// 					'title'       => __( 'Max Amount by this gateway:', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'type'        => 'text',
// 					'default'     => __( '20000', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'description' => __( 'Please set the maximum amount can be proceed with this gateway', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'desc_tip'    => true,
// 				),
// 				'title'              => array(
// 					'title'       => __( 'Title:', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'type'        => 'text',
// 					'default'     => __( 'CedPay Payments', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'description' => __( 'This controls the title which the user sees during checkout.', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'desc_tip'    => true,
// 				),
// 				'availability'       => array(
// 					'title'   => __( 'Countries (Method Availability)', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'type'    => 'select',
// 					'default' => 'all',
// 					'class'   => 'availability',
// 					'options' => array(
// 						'all'      => __( 'All Countries', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 						'specific' => __( 'Specific Countries', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					),
// 				),
// 				'countries'          => array(
// 					'title'   => __( 'Choose Countries', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'type'    => 'multiselect',
// 					'class'   => 'chosen_select',
// 					'css'     => 'width: 450px;',
// 					'default' => '',
// 					'options' => $woocommerce->countries->get_allowed_countries(),
// 				),
// 				'description'        => array(
// 					'title'       => __( 'Description:', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'type'        => 'textarea',
// 					// 'default'     => __( 'Pay securely by Credit or Debit Card through CedPay.', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'description' => __( 'This controls the description which the user sees during checkout.', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'desc_tip'    => true,
// 				),
// 				'merchantIdentifier' => array(
// 					'title'       => __( 'Merchant Identifier', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'type'        => 'text',
// 					'description' => __( 'Given to Merchant by CedPay', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'desc_tip'    => true,
// 				),
// 				'secret'             => array(
// 					'title'       => __( 'Secret Key', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'type'        => 'text',
// 					'description' => __( 'Given to Merchant by CedPay', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'desc_tip'    => true,
// 				),
// 				'showlogo'           => array(
// 					'title'       => __( 'Show Logo', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'type'        => 'checkbox',
// 					'label'       => __( 'Show the CedPay logo in the Payment Method section for the user', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'default'     => 'yes',
// 					'description' => __( 'Tick to show CedPay logo', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'desc_tip'    => true,
// 				),

// 				'mode'               => array(
// 					'title'    => __( 'Choose the Payment Mode', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'type'     => 'select',
// 					'class'    => 'select required',
// 					'css'      => 'width:250px;',
// 					'desc_tip' => __( 'Choose transaction type live or sandbox.', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'default'  => '',
// 					'options'  => array(
// 						''  => __( 'Please Select', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 						'0' => __( 'Sandbox', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 						'1' => __( 'Live', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					),
// 				),
// 				'purpose'            => array(
// 					'title'    => __( 'Purpose', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'type'     => 'select',
// 					'class'    => 'select required',
// 					'css'      => 'width:250px;',
// 					'desc_tip' => __( 'Choose the purpose to using this gateway.', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'default'  => '',
// 					'options'  => array(
// 						''  => __( 'Please Select', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 						'0' => __( 'Service', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 						'1' => __( 'Goods', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 						'2' => __( 'Auction', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 						'3' => __( 'Others', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					),
// 				),
// 				'redirect_page_id'   => array(
// 					'title'       => __( 'Please Select Return Page', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'type'        => 'select',
// 					'options'     => $this->cedpay_get_pages( 'http://localhost/index.php/cedpayresponse' ),
// 					'description' => __( 'URL of success page', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'desc_tip'    => true,
// 				),
// 				'log'                => array(
// 					'title'    => __( 'Choose log mode', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'type'     => 'select',
// 					'class'    => 'select required',
// 					'css'      => 'width:250px;',
// 					'desc_tip' => __( 'Choose log to be written or not.', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					'default'  => '',
// 					'options'  => array(
// 						''    => __( 'Please Select', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 						'no'  => __( 'disable', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 						'yes' => __( 'enable', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ),
// 					),
// 				),
// 			);
// 		}

// 		/**
// 		 * Admin Panel Options
// 		 * - Options for bits like 'title' and availability on a country-by-country basis
// 		 **/
// 		public function admin_options() {

// 			echo '<h3>' . __( 'CedPay Payment Gateway', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ) . '</h3>';
// 			echo '<p>' . __( 'CedPay Payment gateway is very simple to use with secure transaction', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ) . '</p>';
// 			echo '<table class="form-table">';
// 			// Generate the HTML For the settings form.
// 			$this->generate_settings_html();
// 			echo '</table>';
// 		}
// 		/**
// 		 *  There are no payment fields for cedpay, but we want to show the description if set.
// 		 **/
// 		function payment_fields() {

// 			if ( $this->description ) {
// 				echo wpautop( wptexturize( $this->description ) );
// 			}
// 			if ( $this->mode != 1 ) {
// 				// echo wpautop( wptexturize( '<p>CedPay is in sandbox mode so please use the test card details given below.<ul style="list-style:none;"><li>Test Card Number : 401288888881881</li><li>Test Expiry Month/Year : 11/2000</li><li>Test CVV : 123</li></ul></p>' ) );
// 			}
// 		}
// 		/**
// 		 * Receipt Page
// 		 **/
// 		function receipt_page( $order ) {

// 			echo '<p>' . __( 'Thank you for your order, please click the button below to pay with CedPay.' . $order, WC_CEDPAY_PAYMENT_TEXT_DOMAIN ) . '</p>';
// 			echo $this->generate_cedpay_form( $order );
// 		}

// 		// get all pages
// 		function cedpay_get_pages( $title = false, $indent = true ) {

// 			$wp_pages  = get_pages( 'sort_column=menu_order' );
// 			$page_list = array();
// 			if ( $title ) {
// 				$page_list[] = $title;
// 			}
// 			foreach ( $wp_pages as $page ) {
// 				$prefix = '';
// 				// show indented child pages?
// 				if ( $indent ) {
// 					$has_parent = $page->post_parent;
// 					while ( $has_parent ) {
// 									 $prefix    .= ' - ';
// 									 $next_page  = get_post( $has_parent );
// 									 $has_parent = $next_page->post_parent;
// 					}
// 				}
// 				// add to page list array array
// 				$page_list[ $page->ID ] = $prefix . $page->post_title;
// 			}
// 			return $page_list;
// 		}
// 		/**
// 		 * web redirect redirected to the chhosen return page by javascript function.
// 		 */
// 		public function web_redirect( $url ) {

// 			 echo "<html><head><script language=\"javascript\">
// 				<!--
// 				window.location=\"{$url}\";
// 				//-->
// 				</script>
// 				</head><body><noscript><meta http-equiv=\"refresh\" content=\"0;url={$url}\"></noscript></body></html>";
// 		}

// 		/**
// 		 * Check if the gateway is available for use
// 		 *
// 		 * @return bool
// 		 */
// 		public function is_available() {

// 			 $is_available = ( 'yes' === $this->enabled ) ? true : false;
// 			if ( WC()->cart && 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total() ) {
// 				   $is_available = false;
// 			}
// 			$store_currency = get_woocommerce_currency();
// 			if ( $store_currency != 'INR' ) {
// 				wc_print_notice( 'Only INR Supported by cedpay payment gateway, please change the store currency to INR ', 'notice' );
// 				$is_available = false;
// 			}
// 			if ( 'specific' === $this->availability ) {
// 				if ( is_array( $this->countries ) && ! in_array( $package['destination']['country'], $this->countries ) ) {
// 					$is_available = false;
// 				}
// 			}
// 			return $is_available;
// 		}

// 		/**
// 		 * Generate payment button link
// 		 **/
// 		function generate_cedpay_form( $order_id ) {

// 			global $woocommerce;
// 			$order                       = new WC_Order( $order_id );
// 			$redirect_url                = $order->get_checkout_order_received_url();
// 			$redirect_url                = ( $this->redirect_page_id == '' || $this->redirect_page_id == 0 ) ? get_site_url() . '/' : get_permalink( $this->redirect_page_id );
// 									  $a = strstr( $redirect_url, '?' );
// 			if ( $a ) {
// 				$redirect_url .= '&wc-api=' . get_class( $this );
// 			} else {
// 				$redirect_url .= '?wc-api=' . get_class( $this );
// 			}
// 									  error_log( "redirect url = this {$redirect_url}" );
// 			// For wooCoomerce 2.0
// 			// if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
// 			// $redirect_url = esc_url(add_query_arg( 'wc-api', get_class( $this ), $redirect_url ));
// 			// }

// 			$txnDate                                  = date( 'Y-m-d' );
// 									  $amt            = (int) ( 100 * ( $order->order_total ) );
// 			$currency                                 = 'INR';
// 									  $post_data_args = array(
// 										  'merchantIdentifier' => $this->merchantIdentifier,
// 										  'orderId'        => $order_id,
// 										  'returnUrl'      => $redirect_url,
// 										  'buyerEmail'     => $order->billing_email,
// 										  'buyerFirstName' => $order->billing_first_name,
// 										  'buyerLastName'  => $order->billing_last_name,
// 										  'buyerAddress'   => $order->billing_address_1,
// 										  'buyerCity'      => $order->billing_city,
// 										  'buyerState'     => $order->billing_state,
// 										  'buyerCountry'   => $order->billing_country,
// 										  'buyerPincode'   => $order->billing_postcode,
// 										  'buyerPhoneNumber' => $order->billing_phone,
// 										  'txnType'        => 1,
// 										  'zpPayOption'    => 1,
// 										  'mode'           => $this->mode,
// 										  'currency'       => $currency,
// 										  'amount'         => $amt,
// 										  'merchantIpAddress' => $_SERVER['REMOTE_ADDR'],
// 										  'purpose'        => $this->purpose,
// 										  'productDescription' => 'this is test product description',
// 										  'txnDate'        => $txnDate,
// 									  );

// 									  $checksumsequence = array(
// 										  'amount',
// 										  'bankid',
// 										  'buyerAddress',
// 										  'buyerCity',
// 										  'buyerCountry',
// 										  'buyerEmail',
// 										  'buyerFirstName',
// 										  'buyerLastName',
// 										  'buyerPhoneNumber',
// 										  'buyerPincode',
// 										  'buyerState',
// 										  'currency',
// 										  'debitorcredit',
// 										  'merchantIdentifier',
// 										  'merchantIpAddress',
// 										  'mode',
// 										  'orderId',
// 										  'product1Description',
// 										  'product2Description',
// 										  'product3Description',
// 										  'product4Description',
// 										  'productDescription',
// 										  'productInfo',
// 										  'purpose',
// 										  'returnUrl',
// 										  'shipToAddress',
// 										  'shipToCity',
// 										  'shipToCountry',
// 										  'shipToFirstname',
// 										  'shipToLastname',
// 										  'shipToPhoneNumber',
// 										  'shipToPincode',
// 										  'shipToState',
// 										  'showMobile',
// 										  'txnDate',
// 										  'txnType',
// 										  'zpPayOption',
// 									  );
// 									  $all              = '';
// 									  foreach ( $checksumsequence as $seqvalue ) {
// 										  if ( array_key_exists( $seqvalue, $post_data_args ) ) {
// 											  if ( $seqvalue != 'checksum' ) {
// 																				$all .= $seqvalue;
// 																				$all  = $all . '=';
// 																				$all .= $post_data_args[ $seqvalue ];
// 																				$all .= '&';
// 											  }
// 										  }
// 									  }
// 									  echo 'this is it' . $all;
// 									  if ( $this->log == 'yes' ) {
// 													   error_log( 'AllParams : ' . $all );
// 										  error_log( 'Secret Key : ' . $this->secret_key );
// 									  }
// 									  $checksum = Checksum::calculateChecksum( $this->secret, $all );

// 									  $data_args       = array(
// 										  'merchantIdentifier' => $this->merchantIdentifier,
// 										  'orderId'        => $order_id,
// 										  'returnUrl'      => $redirect_url,
// 										  'buyerEmail'     => $order->billing_email,
// 										  'buyerFirstName' => $order->billing_first_name,
// 										  'buyerLastName'  => $order->billing_last_name,
// 										  'buyerAddress'   => $order->billing_address_1,
// 										  'buyerCity'      => $order->billing_city,
// 										  'buyerState'     => $order->billing_state,
// 										  'buyerCountry'   => $order->billing_country,
// 										  'buyerPincode'   => $order->billing_postcode,
// 										  'buyerPhoneNumber' => $order->billing_phone,
// 										  'txnType'        => 1,
// 										  'zpPayOption'    => 1,
// 										  'mode'           => $this->mode,
// 										  'currency'       => $currency,
// 										  'amount'         => $amt,
// 										  'merchantIpAddress' => $_SERVER['REMOTE_ADDR'],
// 										  'purpose'        => $this->purpose,
// 										  'productDescription' => 'this is test product description',
// 										  'txnDate'        => $txnDate,
// 										  'checksum'       => $checksum,
// 										  'allstring'      => $all,
// 									  );
// 									  $data_args_array = array();
// 									  foreach ( $data_args as $key => $value ) {
// 										  if ( $key != 'checksum' ) {
// 											  if ( $key == 'returnUrl' ) {
// 																				$data_args_array[] = "<input type='hidden' name='$key' value='" . $value . "'/>";
// 											  } elseif ( $key == 'allstring' ) {
// 																		$data_args_array[] = "<input type='hidden' name='$key' value='" . ( $value ) . "'/>";
// 											  } else {
// 																$data_args_array[] = "<input type='hidden' name='$key' value='" . $value . "'/>";
// 											  }
// 										  } else {
// 												  $data_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
// 										  }
// 									  }

// 									  return '	<form action="' . $this->liveurl . '" method="post" id="cedpay_payment_form">
//   				' . implode( '', $data_args_array ) . '
// 				<input type="submit" class="button-alt" id="submit_cedpay_payment_form" value="' . __( 'Pay via CedPay', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ) . '" /> <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __( 'Cancel order &amp; restore cart', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ) . '</a>
// 					<script type="text/javascript">
// 					jQuery(function(){
// 					jQuery("body").block({
// 						message: "' . __( 'Thank you for your order. We are now redirecting you to Payment Gateway to make payment.', WC_CEDPAY_PAYMENT_TEXT_DOMAIN ) . '",
// 						overlayCSS: {
// 							background		: "#fff",
// 							opacity			: 0.6
// 						},
// 						css: {
// 							padding			: 20,
// 							textAlign		: "center",
// 							color			: "#555",
// 							border			: "3px solid #aaa",
// 							backgroundColor	: "#fff",
// 							cursor			: "wait",
// 							lineHeight		: "32px"
// 						}
// 					});
// 					jQuery("#submit_cedpay_payment_form").click();});
// 					</script>
// 				</form>';
// 		}

// 		private function verifyChecksum( $checksum, $all, $secret ) {

// 			$cal_checksum = $this->calculateChecksum( $secret, $all );
// 			$bool         = 0;
// 			if ( $checksum == $cal_checksum ) {
// 				$bool = 1;
// 			}
// 							   return $bool;
// 		}

// 		private function calculateChecksum( $secret_key, $all ) {

// 			$hash     = hash_hmac( 'sha256', $all, $secret_key );
// 			$checksum = $hash;
// 			return $checksum;
// 		}

// 		/**
// 		 * Process the payment and return the result
// 		 **/
// 		function process_payment( $order_id ) {

// 			global $woocommerce;
// 			$order = new WC_Order( $order_id );
// 			if ( version_compare( WOOCOMMERCE_VERSION, '2.1.0', '>=' ) ) {
// 				/* 2.1.0 */
// 				$checkout_payment_url = $order->get_checkout_payment_url( true );
// 			} else {
// 				/* 2.0.0 */
// 				$checkout_payment_url = get_permalink( get_option( 'woocommerce_pay_page_id' ) );
// 			}



// 			return array(
// 				'result'   => 'success',
// 				'redirect' => 'https://zaakstaging.cedpay.com/api/paymentTransact/V8?amount=10000&buyerEmail=abc@gmail.com&currency=INR&merchantIdentifier=b19e8f103bce406cbd3476431b6b7973&orderId=ZPLive1721120655376&returnUrl=http://localhost/Cedpay_PHP_Integration_Kit/src/com/cedpay/api/Response.php&checksum=fe71813e7defab733a449da06514e015f3f39c48b1ff597da4d356668dff9ce0',
// 			);
// 		}
// 		/**
// 		 * Check for valid payu server callback
// 		 **/
// 		function check_zpay_response() {

// 			  global $woocommerce;
// 			if ( isset( $_REQUEST['orderId'] ) && isset( $_REQUEST['responseCode'] ) ) {

// 				$order_sent          = $_REQUEST['orderId'];
// 				$responseDescription = $_REQUEST['responseDescription'];
// 				if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
// 					$order = new WC_Order( $_REQUEST['orderId'] );
// 				} else {
// 					$order = new woocommerce_order( $_REQUEST['orderId'] );
// 				}
// 				if ( $this->log == 'yes' ) {
// 					error_log( 'Response Code = ' . $_REQUEST['responseCode'] );
// 				}
// 				$redirect_url         = ( $this->redirect_page_id == '' || $this->redirect_page_id == 0 ) ? get_site_url() . '/' : get_permalink( $this->redirect_page_id );
// 				$this->msg['class']   = 'error';
// 				$this->msg['message'] = $order_sent . 'and ' . $responseDescription . 'Thank you for shopping with us. However, the transaction has been Failed For Reason  : ' . $responseDescription;
// 				if ( $_REQUEST['responseCode'] == 100 ) {
// 					// success
// 					if ( $this->log == 'yes' ) {
// 						error_log( 'Order Id ' . $_REQUEST['orderId'] . 'Completed successfully' );
// 					}
// 					if ( $order->status !== 'completed' ) {
// 						error_log( 'SUCCESS' );
// 						$all              = '';
// 						$checksumsequence = array(
// 							'amount',
// 							'bank',
// 							'bankid',
// 							'cardId',
// 							'cardScheme',
// 							'cardToken',
// 							'cardhashid',
// 							'doRedirect',
// 							'orderId',
// 							'paymentMethod',
// 							'paymentMode',
// 							'responseCode',
// 							'responseDescription',
// 							'productDescription',
// 							'product1Description',
// 							'product2Description',
// 							'product3Description',
// 							'product4Description',
// 							'pgTransId',
// 							'pgTransTime',
// 						);
// 						foreach ( $checksumsequence as $seqvalue ) {
// 							if ( array_key_exists( $seqvalue, $_POST ) ) {
// 											 $all .= $seqvalue;
// 											 $all .= '=';
// 								if ( $seqvalue == 'returnUrl' ) {
// 									$all .= $_REQUEST[ $seqvalue ];
// 								} else {
// 									$all .= $_REQUEST[ $seqvalue ];
// 								}
// 												  $all .= '&';
// 							}
// 						}
// 						if ( $this->verifychecksum( $_REQUEST['checksum'], $all, $this->secret ) == 1 ) {
// 							$this->msg['message'] = 'Thank you for shopping with us. Your account has 									been charged and your transaction is successful.';
// 						} else {
// 							$this->msg['message'] = "Thank you for shopping with us. Your account has 									been charged and your transaction is successful.BUT HOWEVER CHECKSUM DOESN'T 									MATCH. THERE MIGHT BE TAMPERING";
// 						}

// 						 $this->msg['class'] = 'success';
// 						if ( $order->status == 'processing' ) {
// 							 $order->add_order_note( 'Please check the payment status before the ' );
// 							$woocommerce->cart->empty_cart();
// 						} else {
// 							$order->payment_complete();
// 							$order->add_order_note( 'CedPay payment successful' );
// 							$order->add_order_note( $this->msg['message'] );
// 							$woocommerce->cart->empty_cart();

// 						}
// 					} else {
// 						// server to server failed while call//
// 						// error_log("api process failed");
// 						$this->msg['class']   = 'error';
// 						$this->msg['message'] = 'Severe Error Occur.';
// 						$order->update_status( 'failed' );
// 						$order->add_order_note( 'Failed' );
// 						$order->add_order_note( $this->msg['message'] );
// 					}
// 				} else {
// 					$order->update_status( 'failed' );
// 					$order->add_order_note( 'Failed' );
// 					$order->add_order_note( $responseDescription );
// 					$order->add_order_note( $this->msg['message'] );
// 				}
// 							 add_action( 'the_content', array( &$this, 'showMessage' ) );
// 							 $redirect_url = ( $this->redirect_page_id == '' || $this->redirect_page_id == 0 ) ? get_site_url() . '/' : get_permalink( $this->redirect_page_id );
// 							 // For wooCoomerce 2.0
// 							$redirect_url = add_query_arg(
// 								array(
// 									'msg'  => urlencode( $this->msg['message'] ),
// 									'type' => $this->msg['class'],
// 								),
// 								$redirect_url
// 							);
// 				wp_redirect( $redirect_url );
// 				exit;
// 			}
// 		}

// 	}

// 	/**
// 	 * Add the Gateway to WooCommerce
// 	 **/
// 	function woocommerce_add_zpay_gateway( $methods ) {

// 		$methods[] = 'Ced_Payment';
// 		return $methods;
// 	}

// 	add_filter( 'woocommerce_payment_gateways', 'woocommerce_add_zpay_gateway' );
// }

