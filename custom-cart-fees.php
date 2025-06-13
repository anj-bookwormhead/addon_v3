<?php
/**
 * Plugin Name: Custom Cart Add-On Fee Breakdown
 * Description: Dynamically adds participant add-on fees based on WooCommerce Product Add-Ons selections.
 */


// remove add on in the cart
add_filter('woocommerce_get_item_data', 'remove_product_add_ons_from_display', 99, 2);
function remove_product_add_ons_from_display($item_data, $cart_item) {
    if (isset($cart_item['addons'])) {
        return []; // Don't show any addon info
    }
    return $item_data;
}

// remove the add-ons price and pull the base price only
add_action('woocommerce_before_calculate_totals', 'remove_addon_price_from_product_total', 90);
function remove_addon_price_from_product_total($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    foreach ($cart->get_cart() as $cart_item) {
        if (
            !isset($cart_item['data']) ||
            !$cart_item['data'] instanceof WC_Product
        ) continue;

        $product = $cart_item['data'];
        $qty     = $cart_item['quantity'];
        $price   = $product->get_price(); // This is the inflated price (with add-ons)

        $addon_total = 0;
        if (!empty($cart_item['addons'])) {
            foreach ($cart_item['addons'] as $addon) {
                if (isset($addon['price']) && is_numeric($addon['price'])) {
                    $addon_total += floatval($addon['price']);
                }
            }
        }

        $adjusted_price = $price - ($addon_total / max($qty, 1));

        // Set the adjusted base price per unit (removing add-ons)
        $product->set_price($adjusted_price);
    }
}


// remove the add ons price


add_action('woocommerce_cart_calculate_fees', 'always_show_optional_addons_fee', 20, 1);
function always_show_optional_addons_fee($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    $addons = WC()->session->get('custom_selected_addons');

    // 🧼 Normalize: ensure it's an array — otherwise reset it
    if (!is_array($addons)) {
        $addons = [];
        WC()->session->__unset('custom_selected_addons'); // Reset to avoid carrying stale data
        error_log('❌ Invalid addon session data. Resetting to empty array.');
    }

    $addon_total = 0;

    foreach ($addons as $addon) {
        if (!empty($addon['price']) && is_numeric($addon['price'])) {
            $addon_total += floatval($addon['price']);
        }
    }

    // ✅ Always apply fee, even if 0 — avoids stale display
    $label = sprintf(__('Additional: $%.2f', 'your-textdomain'), $addon_total);
    $cart->add_fee($label, $addon_total, false);

    error_log("💰 Fee Applied: $addon_total");
}




/* ----------------- THIS IS WORKING  ----
add_action('woocommerce_cart_calculate_fees', 'always_show_optional_addons_fee', 20, 1);
function always_show_optional_addons_fee($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    $addons = WC()->session->get('custom_selected_addons', []);

    // Debug log to verify data
    error_log('🧠 Session Addons: ' . print_r($addons, true));

    if (!is_array($addons) || empty($addons)) return;

    $addon_total = 0;

    foreach ($addons as $addon) {
        if (!empty($addon['price']) && is_numeric($addon['price'])) {
            $addon_total += floatval($addon['price']);
        }
    }

    if ($addon_total > 0) {
        $label = sprintf(
            __('Add-on fee(s) if you purchase extra certificate(s), online program(s): $%.2f', 'your-textdomain'),
            $addon_total
        );

        error_log("💰 Calculated Total Add-on Fee: $addon_total");

        $cart->add_fee($label, $addon_total, false);
    }
}
*/







/* working but don't update the add-on price 
add_action('woocommerce_before_calculate_totals', 'force_base_price_excluding_addons', 99);
function force_base_price_excluding_addons($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    foreach ($cart->get_cart() as $cart_item) {
        if (!isset($cart_item['data']) || !$cart_item['data'] instanceof WC_Product) continue;

        $product  = $cart_item['data'];
        $quantity = $cart_item['quantity'];

        // WooCommerce sets inflated price per unit (e.g. $227)
        $inflated_unit_price = $product->get_price();

        // Total add-ons for this line (e.g. $78 for 2 qty)
        $addon_total = 0;

        if (!empty($cart_item['addons'])) {
            foreach ($cart_item['addons'] as $addon) {
                if (isset($addon['price'])) {
                    $addon_total += floatval($addon['price']);
                }
            }
        }

        // 🧠 Since price is per unit, add-on price must be divided by quantity
        $addon_per_unit = $addon_total / max($quantity, 1);

        // Correct base price = unit price - per-unit add-ons
        $base_unit_price = $inflated_unit_price - $addon_per_unit;

        // ✅ Set corrected base price
        $product->set_price($base_unit_price);
    }
}
*/





// * remove the add ons price
// 🔧 Hook into WooCommerce before totals are calculated
// This allows us to modify the product prices in the cart
/*
add_action('woocommerce_before_calculate_totals', 'force_base_price_excluding_addons', 99);

function force_base_price_excluding_addons($cart) {
    // ✅ Safety check: Exit early if we're in the admin dashboard (except during AJAX requests)
    if (is_admin() && !defined('DOING_AJAX')) return;

    // 🔁 Loop through each item in the cart
    foreach ($cart->get_cart() as $cart_item) {

        // ❌ Skip this item if it's not a valid WooCommerce product
        if (!isset($cart_item['data']) || !$cart_item['data'] instanceof WC_Product) continue;

        // 💰 Get the current price of the product (this includes add-ons)
        $full_price = $cart_item['data']->get_price();

        // 🧮 Initialize a variable to store the total price of selected add-ons
        $addon_total = 0;

        // ✅ Check if the item has any add-ons applied
        if (!empty($cart_item['addons'])) {
            // 🔁 Loop through each add-on and sum their prices
            foreach ($cart_item['addons'] as $addon) {
                if (isset($addon['price'])) {
                    $addon_total += floatval($addon['price']);
                }
            }
        }

        // ➖ Subtract the total add-on price from the full product price
        $base_price = $full_price - $addon_total;

        // ✅ Override the product's price in the cart with the base price
        // This ensures only the original product price is used in totals
        $cart_item['data']->set_price($base_price);
    }
}
*/














//below the product name
/*
add_action('woocommerce_cart_calculate_fees', 'add_custom_booking_fee_with_qty_label', 20, 1);
function add_custom_booking_fee_with_qty_label($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    $total_qty = 0;

    // Calculate total quantity of all items in cart
    foreach ($cart->get_cart() as $cart_item) {
        $total_qty += $cart_item['quantity'];
    }

    // Define fee per item
    $fee_per_item = 10.00;

    // Total fee = fee per item × quantity
    $total_fee = $fee_per_item * $total_qty;

    if ($total_fee > 0) {
        // Custom label with quantity breakdown
      =label = sprintf(__('Put the add on here ($%.2f x %d)', 'your-textdomain'), $fee_per_item, $total_qty);
      // $label = sprintf(__('Optional Add-Ons ($%.2f x %d)', 'your-textdomain'), $total_addon_fee / $addon_count, $addon_count);

        // Add fee with dynamic label
        $cart->add_fee($label, $total_fee, false);
    }
}*/


/*
add_action('woocommerce_cart_calculate_fees', 'add_optional_addons_fee_summary', 20, 1);
function add_optional_addons_fee_summary($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    $total_addon_fee = 0;
    $addon_label_count = 0;
    $addon_unit_price_total = 0;

    foreach ($cart->get_cart() as $cart_item) {
        $qty = $cart_item['quantity'];

        if (!empty($cart_item['addons'])) {
            foreach ($cart_item['addons'] as $addon) {
                if (isset($addon['price'])) {
                    $addon_label_count += $qty; // once per quantity
                    $addon_unit_price_total += floatval($addon['price']);
                    $total_addon_fee += floatval($addon['price']) * $qty;
                }
            }
        }
    }

    if ($total_addon_fee > 0 && $addon_label_count > 0) {
        $avg_unit_price = $addon_unit_price_total / count($cart->get_cart()); // average per type
        $label = sprintf(__('Add-Ons - Certificate & Online Program ($%.2f x %d)', 'your-textdomain'), $avg_unit_price, $addon_label_count);

        $cart->add_fee($label, $total_addon_fee, false);
    }
}
*/

/*
add_action('woocommerce_cart_calculate_fees', 'always_show_optional_addons_fee', 20, 1);
function always_show_optional_addons_fee($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    $quantity_total = 0;
    $addon_fee_total = 0;
    $addon_unit_fee_total = 0;
    $addon_qty_count = 0;

    foreach ($cart->get_cart() as $item) {
        $qty = $item['quantity'];
        $quantity_total += $qty;

        if (!empty($item['addons'])) {
            foreach ($item['addons'] as $addon) {
                if (isset($addon['price'])) {
                    $addon_fee_total += floatval($addon['price']) * $qty;
                    $addon_unit_fee_total += floatval($addon['price']);
                    $addon_qty_count += $qty;
                }
            }
        }
    }

    // If no add-ons selected, show flat label with $0
    $fee_per = $addon_qty_count > 0 ? $addon_unit_fee_total / $addon_qty_count : 0;
 //   $label = sprintf(__('Add On Fee if you purchase extra certificate or online program ($%.2f x %d)', 'your-textdomain'), $addon_per_unit , $quantity_total);
 //$label = sprintf(__('Add-on fee(s) if you purchase extra certificate(s), online program(s) (x %d)', 'your-textdomain'), $quantity_total);
 $label = sprintf(__('Add-on fee(s) if you purchase extra certificate(s), online program(s): $%.2f', 'your-textdomain'), $addon_total);

 
    error_log("💰 Total add-on fee (from session): $addon_total");
    $cart->add_fee($label, $addon_fee_total, false);
} 

/*
add_action('woocommerce_cart_calculate_fees', 'always_show_optional_addons_fee', 20, 1);
function always_show_optional_addons_fee($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    // Pull selected add-ons from session
    $addons = WC()->session->get('custom_selected_addons', []);
    if (empty($addons) || !is_array($addons)) return;

    $addon_total = 0;
    foreach ($addons as $addon) {
        if (!empty($addon['price']) && is_numeric($addon['price'])) {
            $addon_total += floatval($addon['price']);
        }
    }

    if ($addon_total > 0) {
        $label = __('Add-on fee(s) if you purchase extra certificate(s), online program(s)', 'your-textdomain');
        $cart->add_fee($label, $addon_total, false);
    }
}
*/


/*--------------------- This cause the issue why the data is stored to old value
// Action Ajax
add_action('wp_ajax_update_custom_addons', 'handle_update_custom_addons');
add_action('wp_ajax_nopriv_update_custom_addons', 'handle_update_custom_addons');

function handle_update_custom_addons() {
    if (!isset($_POST['addons']) || !is_array($_POST['addons'])) {
        wp_send_json_error(['message' => 'Invalid data']);
    }

    WC()->session->set('custom_selected_addons', $_POST['addons']);
    wp_send_json_success(['stored' => $_POST['addons']]);
} */

/*--------------------- This cause the issue why the data is stored to old value--------------------- */



