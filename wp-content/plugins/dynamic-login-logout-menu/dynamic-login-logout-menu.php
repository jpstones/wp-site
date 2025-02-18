<?php
/*
Plugin Name: CCC Dynamic Login/Logout Menu
Description: Adds dynamic login/logout links to your WordPress menu.
Version: 1.4
Author: JP Stones
*/

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Create a shortcode for the dynamic login/logout menu
function dynamic_login_logout_shortcode() {
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();

        // Get the user's first name
        $first_name = $current_user->user_firstname;

        // Determine the correct dashboard based on user roles
        $dashboard_url = admin_url();
        if (in_array('clinician', $current_user->roles)) {
            $dashboard_url = home_url('/c-dash');
        } elseif (in_array('free_member', $current_user->roles)) {
            $dashboard_url = home_url('/fm-dash');
        } elseif (in_array('premium_member', $current_user->roles)) {
            $dashboard_url = home_url('/pm-dash');
        } elseif (in_array('administrator', $current_user->roles)) {
            $dashboard_url = home_url('/a-dash');
        }

        // Combine Greeting (as a link), Dashboard, and Logout links
        $greeting = '<a href="https://chronicconditionsclinic.com/membership-account/" class="dynamic-login-menu-link">Hi ' . esc_html($first_name) . '</a>';
        $dashboard_link = '<a href="' . esc_url($dashboard_url) . '" class="dynamic-login-menu-link">Dashboard</a>';
        $logout_link = '<a href="' . esc_url(wp_logout_url(home_url())) . '" class="dynamic-login-menu-link">Logout</a>';

        // Return the combined menu
        $output  = '<div class="dynamic-login-menu">';
        $output .= $greeting . '|' . $dashboard_link . '|' . $logout_link;
        $output .= '</div>';

        return $output;
    } else {
        return '<div class="dynamic-login-menu"><a href="' . esc_url(wp_login_url(home_url())) . '" class="dynamic-login-menu-link">Member & Coach Login</a></div>';
    }
}
add_shortcode('dynamic_login_logout', 'dynamic_login_logout_shortcode');

// Enqueue the CSS for the dynamic menu
function enqueue_dynamic_login_menu_styles() {
    wp_enqueue_style('dynamic-login-menu-styles', plugin_dir_url(__FILE__) . 'css/dynamic-login-logout-menu.css');
}
add_action('wp_enqueue_scripts', 'enqueue_dynamic_login_menu_styles');