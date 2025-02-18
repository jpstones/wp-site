<?php
/**
 * Handles database setup and queries for notifications.
 */

/**
 * Create the notifications table on plugin activation.
 */
function ccc_create_notifications_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ccc_custom_notifications';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(20) NOT NULL,
        notification_type VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        related_item_id BIGINT(20) DEFAULT NULL,
        link TEXT DEFAULT NULL,
        status ENUM('unread', 'read') DEFAULT 'unread',
        priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}