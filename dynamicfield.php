<?php
/**
 * Plugin Name: Dynamic Participant Fields for WooCommerce Checkout
 * Description: Adds dynamic participant fields to WooCommerce checkout, admin, and emails based on product quantity.
 * Author: Performance Driving Australia
 * Version: 1.0
 */

defined('ABSPATH') || exit;

// 1. Show the field container in Checkout
add_action('woocommerce_before_order_notes', 'custom_dynamic_participant_fields');
function custom_dynamic_participant_fields($checkout) {
    echo '<div id="participant-fields-wrapper"><h3>Participant Details</h3></div>';
}

// 2. Prepare global add-ons and selected values
add_action('wp_enqueue_scripts', function () {
    if (!is_checkout()) return;

    $global_addons = [];
    // erase later
    error_log("🧪 Global Add-On Label: " . $option['label']);
    error_log("🧪 Global Add-On Key: " . sanitize_title($option['label']));

    $selected_addons = [];
    // erase later
    error_log("🔍 Cart Add-On Name: " . $addon['name']);
    error_log("🔑 Sanitized Key: " . sanitize_title($addon['name']));


   foreach (WC()->cart->get_cart() as $item) {
    if (!empty($item['addons'])) {
        foreach ($item['addons'] as $addon) {
            // This uses the same logic you use to build 'field_name' in the front-end
            $label = isset($addon['value']) ? $addon['value'] : $addon['name']; // fallback
            $field_name = sanitize_title($label);
            $selected_addons[$field_name] = true;
        }
    }
}

    // Fetch global add-on groups
    $addon_groups = get_posts([
        'post_type' => 'global_product_addon',
        'numberposts' => -1,
    ]);

    foreach ($addon_groups as $addon_group) {
        $fields = get_post_meta($addon_group->ID, '_product_addons', true);
        if (!$fields || !is_array($fields)) continue;

        foreach ($fields as $field) {
            if (
                isset($field['type'], $field['options']) &&
                $field['type'] === 'checkbox' &&
                is_array($field['options'])
            ) {
                foreach ($field['options'] as $option) {
                    if (!isset($option['label'])) continue;

                    $option_label = $option['label'];
                    $field_name = sanitize_title($option_label);
                    $price = isset($option['price']) ? floatval($option['price']) : 0;

                    $global_addons[] = [
                        'label' => $option_label,
                        'field_name' => $field_name,
                        'price' => $price,
                        'selected' => isset($selected_addons[$field_name]),
                    ];
                }
            }
        }
    }

    wp_register_script('participant-addons', false);
    wp_enqueue_script('participant-addons');

/*
    wp_localize_script('participant-addons', 'participant_addons_data', [
        'addons' => $global_addons,
        'selected_addons' => array_keys($selected_addons)
    ]);
*/

    $selected_addon_details = array_map(function($addon) {
        return [
            'label' => $addon['label'],
            'field_name' => $addon['field_name'],
            'price' => $addon['price'],
            'selected' => $addon['selected'],
        ];
    }, array_filter($global_addons, fn($addon) => !empty($addon['selected'])));

    wp_localize_script('participant-addons', 'participant_addons_data', [
        'addons' => $global_addons,
      //  'selected_addons' => $selected_addon_details
      'selected_addons' => array_values(array_filter($global_addons, fn($addon) => !empty($addon['selected'])))
    ]);

});

// 3. Inject JS to render participant fields
add_action('wp_footer', function () {
    if (!is_checkout()) return;

    $qty = array_sum(array_map(fn($item) => $item['quantity'], WC()->cart->get_cart()));
    $qty = max($qty, 1);
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function generateFields(qty) {
                const wrapper = document.getElementById('participant-fields-wrapper');
                if (!wrapper) return;

                wrapper.innerHTML = '<h3>Participant Details</h3>';
                let selectedAddons = participant_addons_data?.selected_addons || [];

                for (let i = 1; i <= qty; i++) {
                    const block = document.createElement('div');
                    block.className = 'participant-block';
                    block.style.marginBottom = '30px';
                    block.style.borderBottom = '1px solid #ddd';
                    block.style.paddingBottom = '20px';

                    let addonHTML = '';
                    if (participant_addons_data.addons.length > 0) {
                        addonHTML += '<div class="participant-addons"><b>OPTIONAL ADD-ONS</b>';

                        participant_addons_data.addons.forEach(function (addon) {
                            const fieldName = `participant_${i}_${addon.field_name}`;
                            const price_text = addon.price > 0 ? ` (+ $${addon.price.toFixed(2)})` : '';
                          //  const isChecked = selectedAddons.includes(addon.field_name);
                          const isChecked = selectedAddons.some(sel => sel.field_name === addon.field_name);
                            // erase later
                            console.log("📦 Loaded Add-ons:", participant_addons_data);
                            console.log(`🧪 Match test: ${addon.field_name} in [${selectedAddons.join(', ')}] = ${isChecked}`);
                            if (isChecked) console.log(`✅ isSelected TRUE: ${addon.field_name}`);

                            const html = `
                                <p>
                                    <label>
                                        <input type="checkbox"
                                            name="${fieldName}"
                                            value="on"
                                            ${isChecked ? 'checked="checked"' : ''}
                                            data-addon-key="${addon.field_name}" />
                                        ${addon.label}${price_text}
                                    </label>
                                </p>`;

                            addonHTML += html;
                        });

                        addonHTML += '</div>';
                    }

                    block.innerHTML = `
                        <h4>Participant ${i}</h4>
                        <p class="form-row form-row-wide">
                            <label>Full Name</label>
                            <input type="text" class="input-text" name="participant_${i}_full_name" required />
                        </p>
                        <p class="form-row form-row-wide">
                            <label>Phone Number</label>
                            <input type="text" class="input-text" name="participant_${i}_phone" required />
                        </p>
                        <p class="form-row form-row-wide">
                            <label>Email</label>
                            <input type="email" class="input-text" name="participant_${i}_email" required />
                        </p>
                        ${addonHTML}
                        <div class="participant-addons-summary" id="participant_${i}_summary" style="margin-top: 10px; font-style: italic;"></div>
                    `;

                    wrapper.appendChild(block);
                }
            }

            generateFields(<?php echo intval($qty); ?>);
        });
    </script>
    <?php
});
