<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once('class-seven-eleven-form.php');

if ( ! class_exists( 'WC_Seven_Eleven_Shipping_Method' ) ) {
	/*
	 * Custom shipping method for 7-11.
	 */
	class WC_Seven_Eleven_Shipping_Method extends WC_Shipping_Method {
		/*
		 * Constructor.
		 */
		public function __construct($instance_id = 0) {
			$this->instance_id = absint( $instance_id );
			$this->id = 'seven_eleven_shipping_method';
			$this->method_title = __( '7-11 Shipping Method', 'woocommerce' );

			$this->supports  = array(
            	'shipping-zones',
            	'instance-settings',
                'instance-settings-modal',
             );

			// Load the settings
			$this->init_form_fields();

			add_action('woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ));

			// Define user set variables
			$this->enabled = $this->get_option( 'enabled' );
			$this->title = $this->get_option( 'title' );
			$this->eshop_uid = $this->get_option( 'eshop_uid' );
			$this->eshop_id = $this->get_option( 'eshop_id' );
			$this->eshop_servicetype = $this->get_option( 'eshop_servicetype' );
			$this->eshop_hasoutside = $this->get_option( 'eshop_hasoutside' );
			$this->flatrate_fee = $this->get_option( 'flatrate_fee' );
			$this->freeshipping_threshold = $this->get_option( 'freeshipping_threshold' );
		}

		/*
		 * Initializes the admin setting fields.
		 */
		public function init_form_fields() {
			$this->instance_form_fields = array(
				'enabled' => array(
					'title' 	=> __( 'Enable/Disable', 'woocommerce' ),
					'type' 		=> 'checkbox',
					'label' 	=> __( 'Enable 7-11 Shipping', 'woocommerce' ),
					'default' => 'no'
				),
				'title' => array(
					'title' 		  => __( 'Method Title', 'woocommerce' ),
					'type' 			  => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
					'default'		  => __( '7-11 Shipping', 'woocommerce' ),
				),
				'eshop_uid' => array(
					'title' 		  => 'uid',
					'type' 			  => 'text',
					'default'		  => '829',
				),
				'eshop_id' => array(
					'title' 		  => 'eshopid',
					'type' 			  => 'text',
					'default'		  => '208',
				),
				'eshop_servicetype' => array(
					'title' 		  => 'Service Type',
					'type' 			  => 'select',
					'options'		  => array('1' => '取貨付款', '3' => '取貨不付款'),
				),
				'eshop_hasoutside' => array(
					'title' 		  => 'Has Outside',
					'type' 			  => 'select',
					'options'		  => array('1' => '顯示本島 + 離島全部門市',
					'2' => '顯示本島 + 澎湖 + 綠島門市 ( 不含連江、金門門市 )',
					'3' => '顯示本島門市'),
				),
				'flatrate_fee' => array(
					'title' 		  => 'Flat rate fee (TWD)',
					'type' 			  => 'number',
					'description' 	  => 'This sets the flat rate fee to charge for this shipping method.',
					'default'		  => '50',
				),
				'freeshipping_threshold' => array(
					'title' 		  => 'Free shipping threshold amount (TWD)',
					'type' 			  => 'number',
					'description' 	  => 'This sets the cart total amount threshold for free shipping.',
					'default'		  => '10000',
				),
			);
		}

        /**
         * Calculates shipping cost.
         */
        public function calculate_shipping($package = array()) {
			$cart_total = WC()->cart->cart_contents_total;
			if ($cart_total >= $this->freeshipping_threshold) {
				$cost = 0;
			} else {
				$cost = $this->flatrate_fee;
			}
            $rate = array(
                'id' => $this->id,
                'label' => $this->title,
                'cost' => $cost
            );
            $this->add_rate( $rate );
        }

		/*
		 * Constructs the map form html and returns it.
		 */
		function get_map_form_html() {
			if (!defined('Plugin_URL')) {
				define('Plugin_URL', plugins_url());
			}

			$serverReplyUrl = Plugin_URL."/woo-seven-eleven-shipping-method/getResponse.php";
			$formObj = new SevenElevenForm();
			$formObj->PostParams = array(
				'uid' => $this->eshop_uid,
				'eshopid' => $this->eshop_id,
				'servicetype' => $this->eshop_servicetype,
				'url' => $serverReplyUrl,
				'tempvar' => '',
				'storeid' => '',
				'display' => (wp_is_mobile() ? 'touch' : 'page'),
				'charset' => 'utf-8',
				'hasoutside' => $this->eshop_hasoutside
			);

			// Return form html
			return $formObj->SevenElevenMap('Select 7-11 store', 'mapForm');
		}
	}
}

?>
