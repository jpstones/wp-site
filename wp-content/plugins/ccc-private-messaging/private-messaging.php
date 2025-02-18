<?php

/*
Plugin Name: CCC Private Messaging
Description: Private Messaging for CCC Platform
Version: 1.3 
Author: JP Stones
*/

// START BLOCK DIRECT ACCESS

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// START BLOCK DIRECT ACCESS


// START LOAD ONE TIME FILES & TABLES

require_once plugin_dir_path( __FILE__ ) . 'includes/db-functions.php';
register_activation_hook(__FILE__, 'pm_create_db_tables');

// END LOAD ONE TIME FILES & TABLES


// START GET ASSIGNED CONTACTS

function get_private_messaging_contacts() {
    $current_user = wp_get_current_user();
    $contacts = [];

    // Helper to get user profile avatar
    function get_user_profile_avatar($user_id) {
        $base_url = plugins_url('ccc-profile-management/assets/');
        $custom_avatar = $base_url . "user-id-{$user_id}.jpg";
        $default_avatar = $base_url . "default.png";

        if (file_exists(WP_PLUGIN_DIR . "/ccc-profile-management/assets/user-id-{$user_id}.jpg")) {
            return $custom_avatar;
        } else {
            return $default_avatar;
        }
    }

    // If the user is a clinician, get all assigned members
    if (in_array('clinician', $current_user->roles)) {
        $assigned_members = get_field('assigned_members', 'user_' . $current_user->ID);
        if (!empty($assigned_members)) {
            foreach ($assigned_members as $member_id) {
                $member = get_user_by('id', $member_id);
                $contacts[] = [
                    'user_id' => $member_id,
                    'name' => $member->display_name,
                    'avatar' => get_user_profile_avatar($member_id),  // Use local profile avatar
                    'thread_id' => get_thread_id($current_user->ID, $member_id)
                ];
            }
        }
    }
    // If the user is a premium member, show their assigned clinician
    elseif (in_array('premium_member', $current_user->roles)) {
        $assigned_clinician = get_field('assigned_clinician', 'user_' . $current_user->ID);
        if ($assigned_clinician) {
            $clinician = get_user_by('id', $assigned_clinician);
            $contacts[] = [
                'user_id' => $assigned_clinician,
                'name' => $clinician->display_name,
                'avatar' => get_user_profile_avatar($assigned_clinician),  // Use local profile avatar
                'thread_id' => get_thread_id($current_user->ID, $assigned_clinician)
            ];
        }
    }

    return $contacts;
}

// END GET ASSIGNED CONTACTS


// START CHECK IF THREAD EXISTS 

function get_thread_id($user1, $user2) {
    global $wpdb;

    // Check if a thread already exists between these users
    $thread = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}pm_message_threads 
         WHERE (user_1 = %d AND user_2 = %d) OR (user_1 = %d AND user_2 = %d)",
        $user1, $user2, $user2, $user1
    ));

    // Log whether a thread was found
    if ($thread) {
        error_log("Thread found: " . $thread->id . " between User $user1 and User $user2");
        return $thread->id;
    } else {
        error_log("No thread found between User $user1 and User $user2. Creating a new one...");
    }

    // Create a new thread if no existing one is found
    $wpdb->insert(
        "{$wpdb->prefix}pm_message_threads",
        [
            'user_1' => $user1,
            'user_2' => $user2,
            'last_updated' => current_time('mysql')
        ]
    );

    $new_thread_id = $wpdb->insert_id;
    error_log("New thread created with ID: $new_thread_id");
    return $new_thread_id;
}

// END CHECK IF THREAD EXISTS 


// START SHOW CONTACTS & MESSAGE CONTAINER

function render_private_messaging_page() {
    ob_start(); ?>
    <div class="pm-container">
        <!-- Left Column: Contacts -->
        <div class="pm-contacts">
            <ul id="pm-contacts-list">
                <?php 
                $contacts = get_private_messaging_contacts(); // Fetch assigned members/clinicians dynamically
                if (!empty($contacts)): ?>
                    <ul class="pm-contact-list">
                        <?php foreach ($contacts as $contact): ?>
                        <?php  error_log("Thread ID: " . $contact['thread_id'] . " | User ID: " . $contact['user_id']); ?>
                            <li class="pm-contact <?php echo ($contact === reset($contacts)) ? 'pm-selected-contact' : ''; ?>" 
                                data-thread-id="<?php echo esc_attr($contact['thread_id']); ?>" 
                                data-user-id="<?php echo esc_attr($contact['user_id']); ?>">
                                <div class="pm-pill">
                                    <img src="<?php echo esc_url($contact['avatar']); ?>" alt="<?php echo esc_attr($contact['name']); ?>" class="pm-avatar">
                                    <span><?php echo esc_html($contact['name']); ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No contacts available.</p>
                <?php endif; ?>
            </ul>
        </div>
        <!-- Right Column: Chat Thread -->
        <div class="pm-chat-area">
            <div id="pm-chat-thread">
                <p>Select a contact to start chatting.</p>
            </div>
            <div class="pm-chat-input">
                <textarea id="pm-message-content" placeholder="Type your message..."></textarea>
                <button id="pm-send-message">Send</button>
                
                <!-- Chat action buttons stacked vertically -->
                <div class="pm-action-buttons">
                    <!-- Voice Note Button -->
                    <button id="pm-record-voice-note" type="button" class="action-button">
                        <img src="<?php echo plugins_url('assets/mic-icon.svg', __FILE__); ?>" alt="Record Voice Note" class="action-icon">
                    </button>
                
                    <!-- Attach File Button -->
                    <button id="pm-attach-file" type="button" class="action-button">
                        <img src="<?php echo plugins_url('assets/attach-icon.svg', __FILE__); ?>" alt="Attach File" class="action-icon">
                    </button>
                    
                    <!-- Hidden file input -->
                    <input id="file-upload" type="file" style="display: none;" />
                </div>
        
                <!-- Audio player to preview the recording -->
                <audio id="audio-preview" controls style="display: none;"></audio>
            </div>
        </div>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode('private_messaging', 'render_private_messaging_page');

// END SHOW CONTACTS & MESSAGE CONTAINER


// START LOAD MESSAGES

function load_messages_ajax_handler() {
    global $wpdb;

    $thread_id = intval($_POST['thread_id']);
    $current_user_id = get_current_user_id();

    function get_user_profile_avatar($user_id) {
        $base_url = plugins_url('ccc-profile-management/assets/');
        $custom_avatar = $base_url . "user-id-{$user_id}.jpg";
        $default_avatar = $base_url . "default.png";

        if (file_exists(WP_PLUGIN_DIR . "/ccc-profile-management/assets/user-id-{$user_id}.jpg")) {
            return $custom_avatar;
        } else {
            return $default_avatar;
        }
    }

    // Fetch messages and file attachments
    $messages = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT sender_id, message, file_url, file_name, sent_at 
             FROM {$wpdb->prefix}pm_messages 
             WHERE thread_id = %d 
             ORDER BY sent_at ASC",
            $thread_id
        )
    );
    
    // Directly log the fetched messages
    error_log("Messages Retrieved for Thread ID $thread_id: " . print_r($messages, true));
    
    if (!$messages) {
        wp_send_json_error(['error' => 'No messages found']);
        return;
    }

    // Format the messages for the response
    $formatted_messages = array_map(function($message) use ($current_user_id) {
        $avatar_url = get_user_profile_avatar($message->sender_id);
        return [
            'sender_id' => $message->sender_id,
            'message' => $message->message,
            'file_url' => $message->file_url,
            'file_name' => $message->file_name,
            'sent_at' => $message->sent_at,
            'is_sender' => $message->sender_id == $current_user_id,
            'avatar' => $avatar_url
        ];
    }, $messages);

    wp_send_json_success(['messages' => $formatted_messages]);
}

add_action('wp_ajax_load_messages', 'load_messages_ajax_handler');
add_action('wp_ajax_nopriv_load_messages', 'load_messages_ajax_handler');  // Optional for non-logged-in users

// END LOAD MESSAGES


// START NEW MESSAGE

function handle_new_message_submission() {
    if (isset($_POST['recipient']) && isset($_POST['message_body'])) {
        $sender_id = get_current_user_id();
        if (!$sender_id) {
            wp_die('You must be logged in to send messages.');
        }

        // Get recipient (by username)
        $recipient_user = get_user_by('login', sanitize_text_field($_POST['recipient']));
        if (!$recipient_user) {
            wp_die('Recipient not found or invalid.');
        }
        $recipient_id = $recipient_user->ID;

        global $wpdb;

        // Check for existing thread
        $existing_thread = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}pm_message_threads 
                 WHERE (user_1 = %d AND user_2 = %d) OR (user_1 = %d AND user_2 = %d) LIMIT 1",
                $sender_id, $recipient_id, $recipient_id, $sender_id
            )
        );

        if ($existing_thread) {
            $thread_id = $existing_thread;
        } else {
            // Create a new thread if none exists
            $wpdb->insert(
                $wpdb->prefix . 'pm_message_threads',
                [
                    'user_1' => $sender_id,
                    'user_2' => $recipient_id,
                    'last_updated' => current_time('mysql')
                ]
            );
            $thread_id = $wpdb->insert_id;
        }

        // Insert the message
        $wpdb->insert(
            $wpdb->prefix . 'pm_messages',
            [
                'thread_id' => $thread_id,
                'sender_id' => $sender_id,
                'recipient_id' => $recipient_id,
                'message' => sanitize_textarea_field($_POST['message_body']),
                'sent_at' => current_time('mysql')
            ]
        );

        // Redirect to the message thread (optional)
        wp_redirect(get_permalink($thread_id));
        exit;
    }
}
add_action('admin_post_send_new_message', 'handle_new_message_submission');
add_action('admin_post_nopriv_send_new_message', 'handle_new_message_submission');

// END NEW MESSAGE


// START DETERMINE RECIPIENT OF MESSAGE

function get_recipient_by_thread($thread_id, $sender_id) {
    global $wpdb;

    $thread = $wpdb->get_row($wpdb->prepare(
        "SELECT user_1, user_2 FROM {$wpdb->prefix}pm_message_threads WHERE id = %d",
        $thread_id
    ));

    // Determine recipient based on who the sender is
    return ($thread->user_1 == $sender_id) ? $thread->user_2 : $thread->user_1;
}

// END DETERMINE RECIPIENT OF MESSAGE


// DO WE USE THIS FUNCTION AND SHOULD WE?
// Shortcodes for message list
function pm_message_list_shortcode() {
    ob_start();
    include plugin_dir_path( __FILE__ ) . 'templates/message-list.php';
    return ob_get_clean();
}
add_shortcode( 'pm_message_list', 'pm_message_list_shortcode' );

// DO WE USE THIS FUNCTION AND SHOULD WE?
// Shortcodes for thread view

function pm_message_thread_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'thread_id' => 0,
    ), $atts );

    ob_start();
    include plugin_dir_path( __FILE__ ) . 'templates/message-thread.php';
    return ob_get_clean();
}
add_shortcode( 'pm_message_thread', 'pm_message_thread_shortcode' );


// THIS IS A DUPLICATE FUNCTION WE WILL NEED TO CENTRALISE AT SOME POINT
// Function to get the assigned clinician using the ACF field 'assigned_clinician'
if ( ! function_exists( 'get_pm_primary_clinician' ) ) {
    // Function to get the assigned clinician using the ACF field 'assigned_clinician'
    function get_pm_primary_clinician($member_id) {
        if (function_exists('get_field')) {
            // Fetch the assigned clinician using the ACF field 'assigned_clinician'
            $clinician = get_field('assigned_clinician', 'user_' . $member_id);

            // Debugging: Log the clinician field output
            error_log('ACF Field "assigned_clinician" for User ' . $member_id . ': ' . print_r($clinician, true));

            if ($clinician) {
                $user_object = get_user_by('id', $clinician);
                error_log('Fetched User Object for Clinician: ' . print_r($user_object, true));
                return $user_object;
            }
        }

        error_log('No clinician assigned for User ID: ' . $member_id);
        return false;
    }
}

// START SEND REPLY

function send_reply_ajax() {
    // Validate the message, thread ID, and recipient ID
    if (!isset($_POST['message']) || !isset($_POST['thread_id']) || !isset($_POST['recipient_id'])) {
        wp_send_json_error(['error' => 'Missing message, thread ID, or recipient ID.']);
    }

    $message = sanitize_text_field($_POST['message']);
    $thread_id = intval($_POST['thread_id']);
    $recipient_id = intval($_POST['recipient_id']);
    $sender_id = get_current_user_id();

    global $wpdb;
    $inserted = $wpdb->insert(
        $wpdb->prefix . 'pm_messages',
        [
            'thread_id' => $thread_id,
            'sender_id' => $sender_id,
            'recipient_id' => $recipient_id,  // Explicitly store the recipient ID
            'message' => $message,
            'sent_at' => current_time('mysql')
        ]
    );

    if ($inserted) {
        // Trigger the centralized notifications system
        do_action('ccc_trigger_notification', $recipient_id, 'new_message', '', [
            'dynamic_data' => ['sender' => get_userdata($sender_id)->display_name],
            'related_item_id' => $wpdb->insert_id,  // Pass the newly inserted message ID
            'link' => site_url("/private-messaging?client_id=" . $recipient_id),
            'priority' => 'normal'
        ]);

        $new_message_html = '<div class="pm-message-bubble pm-message-sender">
                                <p>' . esc_html($message) . '</p>
                             </div>';

        wp_send_json_success(['new_message' => $new_message_html]);
    } else {
        wp_send_json_error(['error' => 'Failed to insert the message into the database.']);
    }
}

// END SEND REPLY



// Hook the function to the AJAX action
add_action('wp_ajax_send_reply', 'send_reply_ajax');
add_action('wp_ajax_nopriv_send_reply', 'send_reply_ajax'); // Allow non-logged-in users if necessary


// START UPLOAD FILE MANAGEMENT

add_action('wp_ajax_pm_upload_file', 'pm_handle_file_upload');
add_action('wp_ajax_nopriv_pm_upload_file', 'pm_handle_file_upload');

function pm_handle_file_upload() {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(['error' => 'No file uploaded or an upload error occurred.']);
        return;
    }

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
    if (!in_array($_FILES['file']['type'], $allowed_types)) {
        wp_send_json_error(['error' => 'Invalid file type.']);
        return;
    }

    // Handle file upload
    $uploaded = wp_handle_upload($_FILES['file'], ['test_form' => false]);
    if (isset($uploaded['error'])) {
        wp_send_json_error(['error' => $uploaded['error']]);
        return;
    }

    $file_url = $uploaded['url'];
    $file_name = basename($uploaded['file']);
    $thread_id = intval($_POST['thread_id']);
    $sender_id = intval($_POST['sender_id']);

    // Database insert with error handling
    global $wpdb;
    $result = $wpdb->insert(
        "{$wpdb->prefix}pm_messages",
        [
            'thread_id' => $thread_id,
            'sender_id' => $sender_id,
            'recipient_id' => get_recipient_by_thread($thread_id, $sender_id),
            'message' => '',
            'file_url' => $file_url,
            'file_name' => $file_name,
            'sent_at' => current_time('mysql')
        ]
    );

    if ($result === false) {
        wp_send_json_error(['error' => 'Database insert failed: ' . $wpdb->last_error]);
        return;
    }

    wp_send_json_success(['file_url' => $file_url, 'file_name' => $file_name]);
}

// END UPLOAD FILE MANAGEMENT

// START ENQUEUE CSS & JS

function pm_enqueue_scripts() {
    wp_enqueue_style( 'pm-style', plugin_dir_url( __FILE__ ) . 'assets/style.css' );
    
    // Explicitly enqueue jQuery first
    wp_enqueue_script( 'jquery' ); 
    
    wp_enqueue_script( 'pm-script', plugin_dir_url( __FILE__ ) . 'assets/script.js', array( 'jquery' ), null, true );

    // Pass both the AJAX URL and the current user ID to the script
    wp_localize_script( 'pm-script', 'pm_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'current_user_id' => get_current_user_id()  // Pass user ID to JS
    ));
}

add_action( 'wp_enqueue_scripts', 'pm_enqueue_scripts' );

// END ENQUEUE CSS & JS





