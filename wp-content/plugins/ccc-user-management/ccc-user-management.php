<?php
/*
Plugin Name: CCC User Management
Description: User management plugin for CCC Platform
Version: 1.0
Author: JP Stones
*/

// Ensure no direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add the form shortcode
add_shortcode('ccc_timezone_form', 'ccc_display_timezone_form');

/**
 * Display the time zone selection form.
 */
    /**
 * Display the time zone selection form.
 */
function ccc_display_timezone_form() {
    // Get current user's time zone from user_meta
    $currentUserId = get_current_user_id();
    $currentTimezone = get_user_meta($currentUserId, 'user_timezone', true);

    // If no time zone is set yet, default to UTC (or leave empty)
    if (!$currentTimezone) {
        $currentTimezone = 'UTC';
    }

    // List of time zones
    $timezones = timezone_identifiers_list();

    // Generate the time zone dropdown options
    $options = '';
    foreach ($timezones as $timezone) {
        $selected = ($timezone === $currentTimezone) ? 'selected' : '';
        $options .= "<option value='$timezone' $selected>$timezone</option>";
    }

    // Form HTML with correct selected option displayed
    $form = "
    <div style='background-color: white; padding: 20px; border-radius: 10px; max-width: 500px; margin: auto;'>
        <h2>Choose Your Time Zone</h2>
        <form id='timezone-form'>
            <label for='user-timezone'>Time Zone:</label>
            <select id='user-timezone' name='user-timezone' required>
                $options
            </select>
            <br><br>
            <button type='submit'>Save Time Zone</button>
        </form>
        <p id='timezone-message' style='margin-top: 10px;'></p>
    </div>
    ";

    return $form;
}

// Handle the AJAX request to save the time zone
add_action('wp_ajax_save_user_timezone', 'ccc_save_timezone');

function ccc_save_timezone() {
    if (isset($_POST['user_timezone'])) {
        error_log('🛰️ Time zone sent via POST: ' . $_POST['user_timezone']);
        $userId = get_current_user_id();
        $timezone = sanitize_text_field($_POST['user_timezone']);

        // Log the data being saved
        error_log("🌐 Saving time zone for user $userId: $timezone");

        // Save the selected time zone to user_meta
        update_user_meta($userId, 'user_timezone', $timezone);

        // Log what is actually saved in user_meta (just for debugging)
        $savedTimezone = get_user_meta($userId, 'user_timezone', true);
        error_log("✅ Time zone successfully saved: $savedTimezone");

        // Send success response back to the client
        wp_send_json_success(['message' => '✅ Time zone successfully saved!']);
    } else {
        wp_send_json_error(['message' => '❌ No time zone data provided.']);
    }
}

// Enqueue the JavaScript to handle form submission via AJAX

function ccc_enqueue_timezone_scripts() {
    // Check if we are on a relevant page (membership-account or your-profile)
    if (is_page(array('membership-account', 'your-profile'))) {
        wp_enqueue_script(
            'ccc-timezone-script', 
            plugins_url('/assets/js/timezone.js', __FILE__), 
            ['jquery'], 
            '1.0', 
            true
        );

        // Inject the AJAX URL using wp_add_inline_script
        $inline_script = "
            const timezoneAjax = {
                ajaxurl: '" . admin_url('admin-ajax.php') . "'
            };
        ";
        wp_add_inline_script('ccc-timezone-script', $inline_script);
    }
}
add_action('wp_enqueue_scripts', 'ccc_enqueue_timezone_scripts');