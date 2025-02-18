<?php

// START CREATE REQUIRED DB TABLES

function pm_create_db_tables() {
    global $wpdb;

    // Define the table names using the correct prefix
    $table_messages = $wpdb->prefix . 'pm_messages';
    $table_threads = $wpdb->prefix . 'pm_message_threads';

    // SQL query to create the tables
    $sql = "CREATE TABLE IF NOT EXISTS $table_threads (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_1 BIGINT(20) UNSIGNED NOT NULL,
        user_2 BIGINT(20) UNSIGNED NOT NULL,
        last_updated DATETIME NOT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB;";  // Added IF NOT EXISTS and kept primary key outside columns

    $sql .= "CREATE TABLE IF NOT EXISTS $table_messages (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        thread_id BIGINT(20) UNSIGNED NOT NULL,
        sender_id BIGINT(20) UNSIGNED NOT NULL,
        recipient_id BIGINT(20) UNSIGNED NOT NULL,
        message TEXT NOT NULL,
        sent_at DATETIME NOT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB;";  // Added IF NOT EXISTS and kept primary key outside columns

    // Debugging: Log the SQL query
    error_log("Creating Tables: $sql");

    // This ensures that the tables are created or updated
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);  // Call dbDelta to execute the query

    // Log the result of dbDelta
    error_log("dbDelta Result: " . $wpdb->last_query);  // Log the last query executed
}

// END CREATE REQUIRED DB TABLES
