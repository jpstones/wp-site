<?php
/*
Plugin Name: Disable Toolbar for New Users
Description: Automatically disables the WordPress admin toolbar for newly registered users and optionally for existing non-admin users.
Version: 1.0
Author: Your Name
*/

// Disable toolbar for new users
function disable_toolbar_for_new_users($user_id) {
    update_user_meta($user_id, 'show_admin_bar_front', 'false');
}
add_action('user_register', 'disable_toolbar_for_new_users');

// Optional: Disable the toolbar for all users (except admins)
function disable_toolbar_for_existing_users() {
    if (!current_user_can('administrator')) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'disable_toolbar_for_existing_users');