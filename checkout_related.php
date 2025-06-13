<?php
/**
 * Plugin Name: Checkout Custom Functionality
 * Description: custom functionality related to checkout.
 * Author: Performance Driving Australia
 * Version: 1.0
 */

/* BAC method automatic status "complete" instead of onhold*/
add_action('woocommerce_thankyou', function($order_id) {
    if (!$order_id) return;

    $order = wc_get_order($order_id);
    
    if ($order->get_payment_method() === 'bacs' && $order->has_status('on-hold')) {
        $order->update_status('completed', 'Auto-completed because BACS was selected.');
    }
});
