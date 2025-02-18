<?php
/**
 * CCC Plugin Activation
 *
 * Ensures proper setup on activation.
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

/**
 * Runs on plugin activation.
 */
function ccc_activate_plugin() {
    // Ensure 'core_issue_log' post type exists
    $args = [
        'label'         => __('Core Issue Log', 'ccc-note-management'),
        'public'        => false,
        'show_ui'       => true,
        'supports'      => ['title', 'custom-fields'],
        'capability_type' => 'post',
    ];
    register_post_type('core_issue_log', $args);

    // Flush rewrite rules to ensure CPT works
    flush_rewrite_rules();
}

// Hook into plugin activation
register_activation_hook(__FILE__, 'ccc_activate_plugin');