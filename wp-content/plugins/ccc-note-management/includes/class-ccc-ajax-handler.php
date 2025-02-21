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
    
        // Check client permissions
        if (!current_user_can('edit_user', $client_id)) {
            error_log("❌ User does not have permission to edit the client ID $client_id.");
            wp_send_json_error(['message' => 'Unauthorized to edit client data.']);
        }
    
        // Decode core issues
        $core_issues = json_decode(stripslashes($core_issues_raw), true);
        if (!is_array($core_issues)) {
            $core_issues = [];
        }
        
        error_log("📋 Processing Core Issues: " . print_r($core_issues, true));

        // Process each core issue
        foreach ($core_issues as $issue) {
            // Skip if no ID
            if (empty($issue['id'])) {
                continue;
            }

            // Check if this is a numeric ID (existing issue)
            if (is_numeric($issue['id'])) {
                $core_issue_post = get_post($issue['id']);
                
                // Verify post exists and is a core issue
                if ($core_issue_post && $core_issue_post->post_type === 'core_issues') {
                    // Get current severity
                    $current_severity = get_field('severity', $issue['id']);
                    
                    // Only update if severity has changed
                    if ($current_severity != $issue['severity']) {
                        error_log("📝 Updating existing core issue {$issue['id']} severity from {$current_severity} to {$issue['severity']}");
                        
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
                    }
                }
            } 
            // Only create new issues if ID starts with 'new_'
            else if (strpos($issue['id'], 'new_') === 0) {
                error_log("📝 Creating new core issue: " . $issue['name']);
                
                // Create new core issue
                $core_issue_post_id = wp_insert_post([
                    'post_title' => sanitize_text_field($issue['name']),
                    'post_type' => 'core_issues',
                    'post_status' => 'publish',
                    'meta_input' => [
                        'client' => $client_id
                    ]
                ]);

                if ($core_issue_post_id) {
                    // Set initial fields
                    update_field('severity', $issue['severity'], $core_issue_post_id);
                    update_field('status', 'active', $core_issue_post_id);
                    update_field('frequency', $issue['frequency'], $core_issue_post_id);
                    update_field('first_appearance', $issue['first_appearance'], $core_issue_post_id);
                    
                    // Initialize history with first severity using proper date format
                    $history = [[
                        'date' => date('d/m/Y'),  // Changed to d/m/Y format
                        'severity' => $issue['severity']
                    ]];
                    update_field('history', $history, $core_issue_post_id);
                    
                    error_log("✅ Created new core issue: ID {$core_issue_post_id}");
                    error_log("Frequency: " . $issue['frequency']);
                }
            }
        }

        // Get client and clinician names
        $client = get_user_by('id', $client_id);
        $clinician = wp_get_current_user();
        $client_name = $client ? $client->display_name : "Client {$client_id}";
        $clinician_name = $clinician->display_name;

        // Create the note
        $note_id = wp_insert_post([
            'post_title'  => "Session Notes for {$client_name} by {$clinician_name}",
            'post_content' => $note_content,
            'post_status' => 'publish',
            'post_type'   => 'note',
            'post_author' => get_current_user_id(),
            'meta_input'  => ['client_id' => $client_id]
        ]);

        if ($note_id) {
            update_field('client', $client_id, $note_id);
            update_field('note', $note_content, $note_id);
            
            wp_send_json_success([
                'message' => 'Note and core issues saved successfully!',
                'note_id' => $note_id
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to save note.']);
        }
    }

}

// Initialize the class
new CCC_AJAX_Handler();