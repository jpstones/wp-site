<?php

// INCLUDES/DISPLAY.PHP 
// FUNCTIONS FOR DISPLAYING NOTIFICATIONS IN DASHBOARDS


/**
 * Retrieve user notifications from the database.
 *
 * @param int $user_id The user whose notifications should be retrieved.
 * @param string $status Filter notifications by status (default: 'unread').
 * @return array List of notifications for the user.
 */
function ccc_get_user_notifications($user_id, $status = 'unread') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ccc_custom_notifications';

    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d AND status = %s ORDER BY created_at DESC",
        $user_id,
        $status
    ));
}

// START PLACEHOLDER REPLACEMENT LOGIC FOR NAME

function ccc_replace_placeholders($message, $notification) {
    $placeholders = [
        '{{sender}}' => ccc_get_sender_name($notification->related_item_id)
    ];

    // Replace placeholders with actual values
    foreach ($placeholders as $placeholder => $value) {
        $message = str_replace($placeholder, $value, $message);
    }

    return $message;
}

function ccc_get_sender_name($message_id) {
    global $wpdb;

    // Get the sender ID from the message
    $sender_id = $wpdb->get_var($wpdb->prepare(
        "SELECT sender_id FROM {$wpdb->prefix}pm_messages WHERE id = %d",
        $message_id
    ));

    // Get the sender's display name
    $sender = get_userdata($sender_id);
    return $sender ? $sender->display_name : 'Unknown Sender';
}

// END PLACEHOLDER REPLACEMENT LOGIC FOR NAME


// START NOTIFICATIONS WIDGET

// @return string HTML output of the notification widget.
function ccc_display_notifications_widget() {
    $user_id = get_current_user_id();
    $notifications = ccc_get_user_notifications($user_id);

    ob_start();
    ?>
    <div id="ccc-notifications-widget">
        <h3>Your Notifications</h3>

        <?php if (!empty($notifications)) : ?>
            <table class="ccc-notifications-table">
                <thead>
                    <tr>
                        <th>Notification</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notifications as $notification) : 
                        // Determine the client ID if available in the related item metadata
                        $client_id = $notification->related_item_id; 
                        $view_link = site_url("/private-messaging?client_id=$client_id");
                    ?>
                        <tr>
                            <td><?php echo esc_html(ccc_replace_placeholders($notification->message, $notification)); ?></td>
                            <td>
                                <a href="<?php echo esc_url($notification->link); ?>" class="ccc-view-link">View</a> |
                                <a href="#" class="ccc-ignore-link" data-notification-id="<?php echo $notification->id; ?>">Ignore</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>No new notifications.</p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ccc_notifications_widget', 'ccc_display_notifications_widget');

// END NOTIFICATIONS WIDGET


// AJAX handler for refreshing notifications
add_action('wp_ajax_ccc_fetch_notifications', 'ccc_fetch_notifications_ajax');
function ccc_fetch_notifications_ajax() {
    $user_id = get_current_user_id();
    $notifications = ccc_get_user_notifications($user_id);

    foreach ($notifications as $notification) {
        echo '<li class="ccc-notification ' . esc_attr($notification->priority) . '">';
        echo esc_html($notification->message);
        if ($notification->link) {
            echo ' <a href="' . esc_url($notification->link) . '">View</a>';
        }
        echo '</li>';
    }

    wp_die(); // Required to terminate AJAX requests properly
}