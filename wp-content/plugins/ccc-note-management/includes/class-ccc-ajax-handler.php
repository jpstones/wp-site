<?php
/**
 * CCC AJAX Handler
 *
 * Handles saving notes and core issues via AJAX.
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// Handle archiving core issue
function ccc_archive_core_issue() {
    // Security check
    check_ajax_referer('ccc_save_note_nonce', 'security');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.']);
    }

    // Validate input
    $issue_id = isset($_POST['issue_id']) ? sanitize_text_field($_POST['issue_id']) : '';
    
    if (empty($issue_id)) {
        wp_send_json_error(['message' => 'Invalid issue ID.']);
    }

    // Get the core issue post by ID
    $core_issue_post = get_post($issue_id);

    if (!$core_issue_post || $core_issue_post->post_type !== 'core_issues') {
        wp_send_json_error(['message' => 'Core issue not found.']);
    }

    // Update the status of the core issue to archived
    update_field('status', 'archived', $issue_id); // Assuming "status" is the ACF field for core issue status

    wp_send_json_success(['message' => 'Core issue archived successfully!']);
}

// Register the action for archiving core issues
add_action('wp_ajax_ccc_archive_core_issue', 'ccc_archive_core_issue');

class CCC_AJAX_Handler {

    public function __construct() {
        add_action('wp_ajax_ccc_save_note', [$this, 'save_note']); // Admin users only
        add_action('wp_ajax_nopriv_ccc_save_note', '__return_false'); // Block non-logged users
    }

    // Saves Note and Core Issues to their respective POST TYPES
    public function save_note() {
        // Security check
        check_ajax_referer('ccc_save_note_nonce', 'security');
    
        // Validate user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }
    
        // Get POST data
        $client_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
        $note_content = isset($_POST['note']) ? sanitize_textarea_field($_POST['note']) : '';
        $core_issues_raw = isset($_POST['core_issues']) ? $_POST['core_issues'] : '[]';
    
        // Check if the current user has permission to edit the specified client
        if (!current_user_can('edit_user', $client_id)) {
            error_log("❌ User does not have permission to edit the client ID $client_id.");
            wp_send_json_error(['message' => 'Unauthorized to edit client data.']);
        }
    
        // Decode core issues properly
        $core_issues = json_decode(stripslashes($core_issues_raw), true);
        if (!is_array($core_issues)) {
            $core_issues = [];
        }
        
        error_log("📋 Core Issues Data Received: " . print_r($core_issues, true));
    
        // Process each core issue
        foreach ($core_issues as $issue) {
            // Skip if no ID
            if (empty($issue['id'])) {
                continue;
            }

            // Handle existing core issues (those with numeric IDs)
            if (is_numeric($issue['id']) && isset($issue['is_existing']) && $issue['is_existing']) {
                $core_issue_post = get_post($issue['id']);
                
                // Verify post exists and is a core issue
                if ($core_issue_post && $core_issue_post->post_type === 'core_issues') {
                    // Get current severity
                    $current_severity = get_field('severity', $issue['id']);
                    
                    // Only update if severity has changed
                    if ($current_severity != $issue['severity']) {
                        // Update the main severity field
                        update_field('severity', $issue['severity'], $issue['id']);
                        
                        // Get existing history
                        $history = get_field('history', $issue['id']) ?: [];
                        
                        // Add new severity change to history
                        $history[] = [
                            'date' => date('Y-m-d'),
                            'severity' => $issue['severity']
                        ];
                        
                        // Update history field
                        update_field('history', $history, $issue['id']);
                        
                        error_log("✅ Updated severity for core issue {$issue['id']} to {$issue['severity']}");
                    }
                }
            } else if (strpos($issue['id'], 'new_') === 0) {
                // Handle new core issues
                // ... existing new core issue creation code ...
            }
        }
    
        // Debugging logs
        error_log("📌 Saving Note - Client ID: $client_id, Content: $note_content");
        error_log("📋 Core Issues Data Received: " . print_r($core_issues, true));
    
        // Log client_id to check if it's correctly passed
        error_log("📋 Client ID: " . $client_id);
    
        // Get the client details for the note title
        $client = get_user_by('id', $client_id);
        $client_name = $client ? $client->display_name : 'Unknown Client';
    
        // Get the coach (current logged-in user) details for the note title
        $coach_name = wp_get_current_user()->display_name;
    
        // Generate a more meaningful note title
        $note_title = "Session Notes by $coach_name (Coach) for $client_name (Client)";
    
        // Save the note as a post under the 'note' post type with a dynamic title
        $note_id = wp_insert_post([
            'post_title'  => $note_title,  // Use the dynamic title here
            'post_content' => $note_content,
            'post_status' => 'publish',
            'post_type'   => 'note',
            'post_author' => get_current_user_id(),
            'meta_input'  => ['client_id' => $client_id] // Store client_id in post meta
        ]);
    
        if ($note_id) {
            error_log("✅ Note Created - Post ID: $note_id");
    
            // Save the client relationship to the Note (ACF)
            update_field('client', $client_id, $note_id);  // Assuming 'client' is the ACF relationship field on Notes
            
            // Save the actual note content to ACF (saving it in 'note' field)
            update_field('note', $note_content, $note_id);  // 'note' is the ACF field for note content
    
            // Debugging: Make sure core issues are correctly fetched
            error_log("📋 Core Issues to Save: " . print_r($core_issues, true));
    
            // Save core issues to ACF fields (not user meta now)
            if (is_array($core_issues) && !empty($core_issues)) {
                foreach ($core_issues as $issue) {
                    // Get the Core Issue post ID
                    $core_issue_post_id = wp_insert_post([
                        'post_title' => sanitize_text_field($issue['name']),
                        'post_type' => 'core_issues',  // Core Issues post type
                        'post_status' => 'publish',
                        'meta_input' => [
                            'client' => $client_id,  // Link the core issue to the client (user field)
                            'severity' => $issue['severity'],  // Save severity in ACF field
                            'curiosity' => isset($issue['curiosity']) ? $issue['curiosity'] : '',
                            'compassion' => isset($issue['compassion']) ? $issue['compassion'] : '',
                            'first_appearance' => isset($issue['first_appearance']) ? $issue['first_appearance'] : '',
                            'status' => 'active', // Set status to active programmatically
                            'history' => []  // Initialize empty history array
                        ]
                    ]);
    
                    if ($core_issue_post_id) {
                        // Programmatically set the status to 'active'
                        update_field('status', 'active', $core_issue_post_id);
                        error_log("✅ Core Issue Saved - Post ID: $core_issue_post_id");
    
                        // Now add the first severity change to the history
                        $history = [
                            [
                                'date' => date('Y-m-d'),  // Store current date
                                'severity' => $issue['severity'],  // First severity
                                ] 
                            ];
    
                        update_field('history', $history, $core_issue_post_id); // Save history
                        error_log("✅ History for Core Issue Updated");
                    } else {
                        error_log("❌ Failed to Save Core Issue");
                    }
                }
            }
    
            // Send the success message with both note and core issues saved
            wp_send_json_success([
                'message' => 'Note and core issue history saved successfully!',
                'note_id' => $note_id,
                'core_issues_message' => '✅ Core Issues Saved Successfully!'  // Add success message for core issues
            ]);
        } else {
            error_log("❌ Failed to create note.");
            wp_send_json_error(['message' => 'Failed to save note.']);
        }
    }

}

// Initialize the class
new CCC_AJAX_Handler();