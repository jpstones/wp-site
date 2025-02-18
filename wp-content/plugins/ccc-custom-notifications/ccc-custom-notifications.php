<?php
/*
Plugin Name: CCC Custom Notifications
Description: A centralized notifications system for the Chronic Conditions Clinic website.
Version: 1.0
Author: JP Stones
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// START INCLUDE CORE FILES

require_once plugin_dir_path(__FILE__) . 'includes/functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/database.php';
require_once plugin_dir_path(__FILE__) . 'includes/display.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings.php';

// On plugin activation, create database tables
register_activation_hook(__FILE__, 'ccc_create_notifications_table');

// END INCLUDE CORE FILES

// START REGISTER INITIAL NOTIFICATION TYPES

function ccc_register_initial_notification_types() {
    $registered_types = get_option('ccc_registered_notification_types', []);

    // Log if we're skipping the registration
    if (!empty($registered_types)) {
        error_log('Skipping registration because types already exist.');
        return;
    }

    // Register initial notification types
    ccc_register_notification_type('new_message', 'New Private Message', 'You have a new message from {{sender}}.');
    ccc_register_notification_type('new_content_assigned', 'New Content Assigned', 'New content has been assigned to you.');
    ccc_register_notification_type('payment_reminder', 'Payment Reminder', 'Your payment is due soon.');

    // Log the full array of types before saving
    $final_registered_types = get_option('ccc_registered_notification_types', []);
    error_log('Final registered types: ' . print_r($final_registered_types, true));
}

// Hook into plugin activation to set default notification types
register_activation_hook(__FILE__, 'ccc_register_initial_notification_types');

// END REGISTER INITIAL NOTIFICATION TYPES

// START REGISTER SETTINGS PAGE

add_action('admin_menu', 'ccc_register_notifications_settings_page');

function ccc_register_notifications_settings_page() {
    add_options_page(
        'CCC Notifications Settings',  // Page title
        'CCC Notifications',           // Menu title
        'manage_options',              // Capability required
        'ccc-notifications-settings',  // Menu slug
        'ccc_render_notifications_settings_page'  // Function to render the page
    );
}

// END REGISTER SETTINGS PAGE


// START ENQUEUE JS & CSS

function ccc_enqueue_notification_assets() {
    // Enqueue JS
    wp_enqueue_script('ccc-notifications-js', plugins_url('/assets/notifications.js', __FILE__), ['jquery'], '1.0', true);
    wp_localize_script('ccc-notifications-js', 'ajaxurl', admin_url('admin-ajax.php'));

    // Enqueue CSS
    wp_enqueue_style('ccc-notifications-css', plugins_url('/assets/styles.css', __FILE__), [], '1.0');
}
add_action('wp_enqueue_scripts', 'ccc_enqueue_notification_assets');

// END ENQUEUE JS & CSS
