<?php
/**
 * CCC Plugin Uninstallation
 *
 * Cleans up plugin data when uninstalled.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit; // Prevent direct access
}

// Get all users
$users = get_users();

// Remove stored meta fields for all users
foreach ($users as $user) {
    delete_user_meta($user->ID, '_core_issues');
    delete_user_meta($user->ID, '_core_issues_archive');
}

// Remove 'core_issue_log' post type entries
$logs = get_posts([
    'post_type'   => 'core_issue_log',
    'numberposts' => -1,
]);

foreach ($logs as $log) {
    wp_delete_post($log->ID, true);
}

// Flush rewrite rules
flush_rewrite_rules();