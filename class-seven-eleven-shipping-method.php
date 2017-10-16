<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once('class-seven-eleven-form.php');

function wc_seven_eleven_init() {
	if ( ! class_exists( 'WC_Seven_Eleven_Shipping_Method' ) ) {
		/*
		 * Custom shipping method for 7-11.
		 */
		class WC_Seven_Eleven_Shipping_Method extends WC_Shipping_Method {

			/*
			 * Constructor.
			 */
			public function __construct() {
				$this->id = 'seven_eleven_shipping_method';
				$this->method_title = __( '7-11 Shipping Method', 'woocommerce' );

				// Availability
				$this->availability = 'including';
				$this->countries = array('TW');

				// Load the settings
				$this->init_form_fields();
				$this->init_settings();

				add_action('woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ));

				// Define user set variables
				$this->enabled = $this->get_option( 'enabled' );
				$this->title = $this->get_option( 'title' );
				$this->eshop_uid = $this->get_option( 'eshop_uid' );
				$this->eshop_id = $this->get_option( 'eshop_id' );
				$this->eshop_servicetype = $this->get_option( 'eshop_servicetype' );
				$this->eshop_hasoutside = $this->get_option( 'eshop_hasoutside' );

				add_filter('woocommerce_checkout_fields', array(&$this, 'custom_override_checkout_fields'));
				// add_action('woocommerce_cart_totals_after_shipping', array(&$this, 'action_woocommerce_cart_totals_after_shipping'));
				add_action('woocommerce_review_order_after_shipping', array(&$this, 'action_woocommerce_add_map_after_shipping'));
			}

			/*
			 * Initializes the admin setting fields.
			 */
			public function init_form_fields() {
				$this->form_fields = array(
					'enabled' => array(
						'title' 	=> __( 'Enable/Disable', 'woocommerce' ),
						'type' 		=> 'checkbox',
						'label' 	=> __( 'Enable 7-11 Shipping', 'woocommerce' ),
						'default' => 'yes'
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
					)
				);
			}

            /**
             * Calculates shipping cost. This is needed but not used.
             */
            public function calculate_shipping($package = array()) {
                $rate = array(
                    'id' => $this->id,
                    'label' => $this->title,
                    'cost' => 0
                );

                $this->add_rate( $rate );
            }

			/*
			 * Adds the 7-11 map button to the checkout page.
			 */
			function action_woocommerce_add_map_after_shipping()
			{
				global $woocommerce;
				try {
					if (!defined('Plugin_URL')) {
						define('Plugin_URL', plugins_url());
					}

					// Hide fields initially
					?>
					<script type="text/javascript">

					document.getElementById('store_id').setAttribute("readonly", true);
					document.getElementById('store_id_field').style.display = 'none';
					document.getElementById('store_name').setAttribute("readonly", true);
					document.getElementById('store_name_field').style.display = 'none';
					document.getElementById('store_address').setAttribute("readonly", true);
					document.getElementById('store_address_field').style.display = 'none';

					</script>
					<?php

					// Add map if 7-11 shipping method is selected
					$chosen_method = $woocommerce->session->get('chosen_shipping_methods');
					if (is_array($chosen_method) && in_array($this->id, $chosen_method) && !empty($this->enabled) && $this->enabled === 'yes')
					{
						$serverReplyUrl = Plugin_URL."/woo-seven-eleven-shipping-method/getResponse.php";

						$formObj = new SevenElevenForm();
						$formObj->PostParams = array(
							'uid' => $this->eshop_uid,
							'eshopid' => $this->eshop_id,
							'servicetype' => $this->eshop_servicetype,
							'url' => $serverReplyUrl,
							'tempvar' => '',
							'storeid' => (!empty($selectedStoreId) ? $selectedStoreId : ''),
							'display' => (wp_is_mobile() ? 'touch' : 'page'),
							'charset' => 'utf-8',
							'hasoutside' => $this->eshop_hasoutside
						);

						// Get SevenElevenMap form
						$html = $formObj->SevenElevenMap();

						// Displays the map button
						echo '
						<tr class="shipping_option">
						<th>' . $this->method_title . '</th>
						<td>
						'.$html.'
						<p style="color: #ff0000;">!!custom text!! 使用綠界科技超商取貨，連絡電話請填寫手機號碼。</p>
						</td>
						</tr>
						';

						?>
						<script type="text/javascript">
						if (
							document.getElementById("__paymentButton") !== null &&
							typeof document.getElementById("__paymentButton") !== "undefined"
						) {
							document.getElementById("__paymentButton").onclick = function() {
								map = window.open("", "sevenElevenForm", "width=1000,height=600,toolbar=0");
								if (map) {
									document.getElementById("SevenElevenForm").submit();
								}
							};
						}

						</script>
						<?php
					}
				}
				catch(Exception $e)
				{
					echo $e->getMessage();
				}
			}

			/*
			 * Adds custom checkout fields to store 7-11 shipping specific info.
			 */
			function custom_override_checkout_fields($fields)
			{
				$fields['billing']['store_id'] = array(
					'default'       => '',
					'required'      => true,
					'class'         => array('hidden')
				);
				$fields['billing']['store_name'] = array(
					'default'       => '',
					'label'         => 'Store Name',
					'required'      => true,
					'clear'         => true
				);
				$fields['billing']['store_address'] = array(
					'default'       => '',
					'label'         => 'Store Address',
					'required'      => true,
					'clear'         => true
				);

				return $fields;
			}

		}
	}
}
?>
