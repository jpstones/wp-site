<?php
/*
Plugin Name: Estimated Reading Time
Plugin URI: https://example.com
Description: A simple plugin to display the estimated reading time for posts.
Version: 1.0
Author: Your Name
Author URI: https://example.com
License: GPL2
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Function to calculate reading time
function ert_calculate_reading_time($content) {
    $word_count = str_word_count(strip_tags($content));
    $reading_time = ceil($word_count / 200); // Adjust the reading speed (words per minute) as needed
    return $reading_time . ' minute' . ($reading_time > 1 ? 's' : '') . ' read';
}

// Function to display reading time before the content
function ert_add_reading_time_to_post($content) {
    if (is_single() && in_the_loop() && is_main_query()) {
        $reading_time = ert_calculate_reading_time($content);
        $reading_time_html = '<p><strong>Estimated Reading Time: ' . esc_html($reading_time) . '</strong></p>';
        $content = $reading_time_html . $content;
    }
    return $content;
}
add_filter('the_content', 'ert_add_reading_time_to_post');

// Add a settings page (optional)
function ert_settings_page() {
    add_options_page(
        'Estimated Reading Time',
        'Reading Time',
        'manage_options',
        'estimated-reading-time',
        'ert_settings_page_html'
    );
}
add_action('admin_menu', 'ert_settings_page');

// Render settings page (optional)
function ert_settings_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    echo '<div class="wrap">';
    echo '<h1>Estimated Reading Time Settings</h1>';
    echo '<p>This plugin displays the estimated reading time for your posts. No additional settings are required.</p>';
    echo '</div>';
}