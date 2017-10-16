<?php
/*
Plugin Name: WooCommerce 7-11 Shipping Method
Plugin URI: https://modnat.com.tw
Description: 7-11 Shipping Method for WooCommerce
Version: 1.0.0
Author: Modern Nature
Author URI: https://modnat.com.tw
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	/*
	 * Initializes the 7-11 shipping method.
	 */
	add_action('woocommerce_shipping_init', 'seven_eleven_shipping_method');
	function seven_eleven_shipping_method() {
		require_once("class-seven-eleven-shipping-method.php");
		wc_seven_eleven_init();
	}

	/*
	 * Adds the 7-11 shipping method to the list of available shipping methods.
	 */
	add_filter('woocommerce_shipping_methods', 'custom_add_seven_eleven_shipping_method');
	function custom_add_seven_eleven_shipping_method($methods) {
        $methods[] = 'WC_Seven_Eleven_Shipping_Method';
        return $methods;
    }

	/*
	 * Performs custom validation of 7-11 shipping specific fields during checkout.
	 */
	add_action('woocommerce_checkout_process', 'action_woocommerce_checkout_validate_fields');
	function action_woocommerce_checkout_validate_fields() {
		$shipping_method = WC()->session->get('chosen_shipping_methods');
		if ($shipping_method[0] == 'seven_eleven_shipping_method' && empty($_POST['store_id'])) {
			wc_add_notice( 'Please first select a 7-11 store to proceed.', 'error' );
		}
	}

	/*
	 * Saves the populated 7-11 shipping specific fields during checkout.
	 */
	add_action('woocommerce_checkout_update_order_meta', 'action_woocommerce_checkout_save_fields');
	function action_woocommerce_checkout_save_fields($order_id) {
		if(!empty($_POST['store_id']) && !empty($_POST['store_address'])) {
			update_post_meta($order_id, '_shipping_storeId', wc_clean($_POST['store_id']));
			update_post_meta($order_id, '_shipping_storeName', wc_clean($_POST['store_name']));
			update_post_meta($order_id, '_shipping_storeAddress', wc_clean($_POST['store_address']));
		}
	}

	/*
	 * Overrides the formatted shipping address displayed in the order.
	 */
	add_filter('woocommerce_order_formatted_shipping_address', 'custom_order_formatted_shipping_address', 10, 2);
	function custom_order_formatted_shipping_address($address, $order) {
		if (!empty(get_post_meta($order->get_id(), '_shipping_storeName', true))) {
			$address = array(
				'store_id' => get_post_meta($order->get_id(), '_shipping_storeId', true),
				'store_name' => get_post_meta($order->get_id(), '_shipping_storeName', true),
				'store_address' => get_post_meta($order->get_id(), '_shipping_storeAddress', true)
	    	);
		}

	    return $address;
	}

	/*
	 * Modifies the formatting of the address.
	 */
	add_filter('woocommerce_localisation_address_formats', 'custom_address_format');
	function custom_address_format($formats) {
		if (is_admin()) {
			$formats['default'] = "{store_id}\n{store_name}\n{store_address}\n{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}";
		} else {
			$formats['default'] = "{store_name}\n{store_address}\n{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}";
		}

		return $formats;
	}

	/*
	 * Replaces replacement string with actual data.
	 */
	add_filter('woocommerce_formatted_address_replacements', 'custom_formatted_address_replacements', 10, 2);
	function custom_formatted_address_replacements($replacements, $args) {
		if (isset($args['store_name'])) {
			$replacements['{store_id}'] = $args['store_id'];
			$replacements['{store_name}'] = $args['store_name'];
			$replacements['{store_address}'] = $args['store_address'];
		}

	    return $replacements;
	}

}
?>
