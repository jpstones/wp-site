<?php
    
// INCLUDES/FUNCTIONS.PHP 
// CORE NOTIFICATION FUNCTIONS

// START REGISTER NOTIFICATION

function ccc_register_notification_type($type, $label, $default_message) {
    $registered_types = get_option('ccc_registered_notification_types', []);

    if (!isset($registered_types[$type])) {
        $registered_types[$type] = [
            'label' => $label,
            'default_message' => $default_message,
        ];

        // Try saving the option and log the result
        $result = update_option('ccc_registered_notification_types', $registered_types);
        if ($result) {
            error_log('Notification type ' . $type . ' saved successfully.');
        } else {
            error_log('Failed to save notification type ' . $type);
        }
    }
}

// END REGISTER NOTIFICATION

    
// START COLLECT AND SAVE NEW NOTIFICATION

function ccc_trigger_notification($user_id, $notification_type, $default_message, $options = []) {
    global $wpdb;

    // Step 1: Get the registered notification types
    $registered_types = get_option('ccc_registered_notification_types', []);

    // Step 2: Check if the notification type exists and get its default message
    if (isset($registered_types[$notification_type])) {
        $default_message = $registered_types[$notification_type]['default_message'];
    }

    // Step 3: Replace dynamic placeholders (if any)
    if (!empty($options['dynamic_data'])) {
        foreach ($options['dynamic_data'] as $placeholder => $value) {
            $default_message = str_replace("{{" . $placeholder . "}}", $value, $default_message);
        }
    }

    // Prepare the notification data
    $data = [
        'user_id' => $user_id,
        'notification_type' => $notification_type,
        'message' => $default_message,
        'related_item_id' => $options['related_item_id'] ?? null,
        'link' => $options['link'] ?? null,
        'status' => 'unread',
        'priority' => $options['priority'] ?? 'normal',
    ];

    // Insert the notification into the database
    $wpdb->insert($wpdb->prefix . 'ccc_custom_notifications', $data);

    // Trigger an action for additional output handling (email, WhatsApp, etc.)
    do_action('ccc_notification_triggered', $user_id, $notification_type, $default_message, $options);
}
    
// END COLLECT AND SAVE NEW NOTIFICATION


// START

function ccc_handle_new_message_notification($thread_id, $sender_id, $message) {
    // Find the recipient of the message based on the thread
    $recipient_id = get_recipient_by_thread($thread_id, $sender_id);
    $sender = get_user_by('id', $sender_id);
    
    if ($recipient_id) {
        // Generate link with the correct client_id
        $view_link = site_url("/private-messaging?client_id=$sender_id");

        // Create the notification
        ccc_trigger_notification(
            $recipient_id,
            'new_message',
            "You have a new message from {$sender->display_name}",
            [
                'related_item_id' => $thread_id, // Store the thread in case you need it later
                'link' => $view_link,
                'priority' => 'normal',
            ]
        );
    }
}
    
// END

// START IGNORE NOTIFICATION

add_action('wp_ajax_ccc_ignore_notification', 'ccc_ignore_notification');

function ccc_ignore_notification() {
    // Check if the notification ID was sent
    if (!isset($_POST['notification_id'])) {
        wp_send_json_error(['error' => 'No notification ID provided.']);
    }

    global $wpdb;
    $notification_id = intval($_POST['notification_id']);
    $table_name = $wpdb->prefix . 'ccc_custom_notifications';

    // Update the notification status to 'ignored'
    $updated = $wpdb->update(
        $table_name,
        ['status' => 'ignored'],
        ['id' => $notification_id],
        ['%s'],
        ['%d']
    );

    if ($updated) {
        // Fetch remaining notifications
        $user_id = get_current_user_id();
        $notifications = ccc_get_user_notifications($user_id);

        if (!empty($notifications)) {
            ob_start();
            foreach ($notifications as $notification) {
                $client_id = $notification->related_item_id;
                $view_link = site_url("/private-messaging?client_id=$client_id");

                echo '<tr>';
                echo '<td>' . esc_html($notification->message) . '</td>';
                echo '<td>';
                echo '<a href="' . esc_url($view_link) . '">View</a> | ';
                echo '<a href="#" class="ccc-ignore-link" data-notification-id="' . $notification->id . '">Ignore</a>';
                echo '</td>';
                echo '</tr>';
            }
            $updated_html = ob_get_clean();
            wp_send_json_success(['html' => $updated_html]);
        } else {
            // No notifications left, return a message
            wp_send_json_success(['html' => '<p>No new notifications.</p>']);
        }
    } else {
        wp_send_json_error(['error' => 'Failed to ignore the notification.']);
    }
}

// END IGNORE NOTIFICATION

// START

add_action('ccc_trigger_notification', 'ccc_trigger_notification_handler', 10, 4);

function ccc_trigger_notification_handler($user_id, $notification_type, $default_message, $options = []) {
    global $wpdb;

    // Step 1: Get the registered notification types
    $registered_types = get_option('ccc_registered_notification_types', []);

    // Step 2: Get the default message for the notification type
    if (isset($registered_types[$notification_type])) {
        $default_message = $registered_types[$notification_type]['default_message'];
    }

    // Step 3: Replace dynamic placeholders (e.g., {{sender}})
    if (!empty($options['dynamic_data'])) {
        foreach ($options['dynamic_data'] as $placeholder => $value) {
            $default_message = str_replace("{{" . $placeholder . "}}", $value, $default_message);
        }
    }

    // Step 4: Insert the notification into the database
    $wpdb->insert($wpdb->prefix . 'ccc_custom_notifications', [
        'user_id' => $user_id,
        'notification_type' => $notification_type,
        'message' => $default_message,
        'related_item_id' => $options['related_item_id'] ?? null,
        'link' => $options['link'] ?? null,
        'status' => 'unread',
        'priority' => $options['priority'] ?? 'normal',
    ]);

    // Log success for debugging purposes
    error_log("Notification triggered successfully for user $user_id: $default_message");
}

// END

