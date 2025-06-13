
/*
document.addEventListener('change', function (e) {
    if (e.target.matches('input[type="checkbox"][data-addon-key]')) {
        const key = e.target.dataset.addonKey;
        const isChecked = e.target.checked;

        const addonData = participant_addons_data.addons.find(a => a.field_name === key);
        if (!addonData) return;

        let selected = participant_addons_data.selected_addons;
        const index = selected.findIndex(a => a.field_name === key);

        if (isChecked && index === -1) {
            selected.push({
                label: addonData.label,
                field_name: addonData.field_name,
                price: addonData.price,
                selected: true
            });
        } else if (!isChecked && index > -1) {
            selected.splice(index, 1);
        }

        console.log("✅ this is checkbox_dynamics - Updated selected_addons:", selected);
        console.log("📦 participant_addons_data:", participant_addons_data);
        
        const total = addons.reduce((sum, a) => sum + (parseFloat(a.price) || 0), 0);
        console.log(`💰 Total selected add-on price: $${total.toFixed(2)}`);

    }
});


// ✅ Send updated selected addons to server via AJAX
jQuery.post(wc_ajax_data.ajax_url, {
    action: 'update_custom_addons',
    addons: selected
}, function(response) {
    console.log('✅ Session Updated:', response);

    // 🔄 Refresh WooCommerce totals after session is synced
    jQuery('body').trigger('update_checkout');
});
*/





/*------ THIS IS WORKING ----------------------
document.addEventListener('change', function (e) {
    const input = e.target;

    if (input.matches('input[type="checkbox"][data-addon-key]')) {
        const key = input.dataset.addonKey;
        const isChecked = input.checked;

        const addonData = participant_addons_data.addons.find(a => a.field_name === key);
        if (!addonData) return;

        let selected = participant_addons_data.selected_addons;
        const index = selected.findIndex(a => a.field_name === key);

        // ✅ Update array
        if (isChecked && index === -1) {
            selected.push({
                label: addonData.label,
                field_name: addonData.field_name,
                price: addonData.price,
                selected: true
            });
        } else if (!isChecked && index !== -1) {
            selected.splice(index, 1);
        }

        // ✅ Sync attribute state
        input.checked = isChecked;

        // ✅ AJAX to update session
        jQuery.post(wc_ajax_data.ajax_url, {
            action: 'update_custom_addons',
            addons: selected
        }, function (response) {
            console.log('✅ Session Updated:', response);

            // Optional: Trigger WooCommerce cart update
            jQuery('body').trigger('update_checkout');
        });
    }
});

*/



document.addEventListener('change', function (e) {
    const input = e.target;

    if (input.matches('input[type="checkbox"][data-addon-key]')) {
        // 💡 Always rebuild fresh selected array
        const selected = [];

        const allChecked = document.querySelectorAll('input[type="checkbox"][data-addon-key]:checked');

        allChecked.forEach((checkbox) => {
            const key = checkbox.dataset.addonKey;
            const addon = participant_addons_data.addons.find(a => a.field_name === key);
            if (addon) {
                selected.push({
                    label: addon.label,
                    field_name: addon.field_name,
                    price: addon.price,
                    selected: true
                });
            }
        });

        // ✅ AJAX to update session
        jQuery.post(wc_ajax_data.ajax_url, {
            action: 'update_custom_addons',
            addons: selected
        }, function (response) {
            console.log('✅ Session Updated:', response);
            jQuery('body').trigger('update_checkout');
        });
    }
});














/*
document.addEventListener('change', function (e) {
    const input = e.target;

    if (input.matches('input[type="checkbox"][data-addon-key]')) {
        const key = input.dataset.addonKey;
        const isChecked = input.checked;

        const addonData = participant_addons_data.addons.find(a => a.field_name === key);
        if (!addonData) return;

        let selected = participant_addons_data.selected_addons;
        const index = selected.findIndex(a => a.field_name === key);

        // ✅ Update the selected_addons array
        if (isChecked && index === -1) {
            selected.push({
                label: addonData.label,
                field_name: addonData.field_name,
                price: addonData.price,
                selected: true
            });
        } else if (!isChecked && index !== -1) {
            selected.splice(index, 1);
        }

        // ✅ Update actual checkbox state (not just attribute)
        input.checked = isChecked;

        // 🪵 Debug
        console.log(`[Toggle] ${key} is now ${isChecked ? '✅ checked' : '❌ unchecked'}`);
        console.log("🧩 Updated selected_addons array:", selected);

        // 💰 Calculate and log total price of all selected addons
        let totalPrice = 0;
        selected.forEach((addon, i) => {
            const parsedPrice = parseFloat(addon.price);
            console.log(`🧾 Addon #${i + 1} - Label: ${addon.label}, Raw Price: ${addon.price}, Parsed: ${parsedPrice}`);
            totalPrice += isNaN(parsedPrice) ? 0 : parsedPrice;
        });

        console.log(`💵 Total selected add-ons price: $${totalPrice.toFixed(2)}`);
        console.log("🔍 participant_addons_data on load:", participant_addons_data);

/*
        // 🔄 Trigger WooCommerce cart update
        if (typeof jQuery !== 'undefined' && typeof jQuery('body').trigger === 'function') {
            jQuery('body').trigger('update_checkout');
        }
    }
});

function syncAddonsToSession(selectedAddons) {
    jQuery.post(wc_ajax_url.toString().replace('%%endpoint%%', 'update_custom_addons'), {
        addons: selectedAddons
    }, function(response) {
        console.log("🗂️ Session synced:", response);
    });
}

// After updating the array:
syncAddonsToSession(participant_addons_data.selected_addons);



   // 🔁 Send selected_addons to server via AJAX
        jQuery.post(wc_ajax_data.ajax_url, {
            action: 'update_custom_addons',
            addons: selected
        }, function (response) {
            console.log('✅ Session Updated', response);

            // 🔄 Recalculate cart
            jQuery('body').trigger('update_checkout');
        });
    }
});
*/
