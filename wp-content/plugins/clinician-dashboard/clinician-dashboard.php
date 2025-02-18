<?php
/*
Plugin Name: CCC Clinician Dashboard
Description: Dashboard to help clinicians manage their members.
Version: 2.5
Author: JP Stones
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// START GREETING 

function custom_clinician_dashboard_greeting() {
    // Get the current user info
    $current_user = wp_get_current_user();
    
    // Check if the user is logged in and has a first name set
    if (is_user_logged_in() && !empty($current_user->first_name)) {
        $user_first_name = $current_user->first_name;
    } else {
        $user_first_name = 'Guest'; // Fallback for users without a first name or not logged in
    }

    // Construct the greeting message
    $edit_url = 'https://chronicconditionsclinic.com/membership-account/';
    $greeting = sprintf(
        "Hi %s (<a href=\"%s\">edit</a>). Welcome to your Clinician Dashboard.",
        esc_html($user_first_name),
        esc_url($edit_url)
    );

    // Return the greeting wrapped in a paragraph tag
    return '<p>' . $greeting . '</p>';
}

// Register a shortcode to display the greeting
add_shortcode('clinician_dashboard_greeting', 'custom_clinician_dashboard_greeting');

// END GREETING 

// START NOTIFICATIONS 

// empty for now

// START NOTIFICATIONS 


// START ACTIVE CLIENT ROSTER
function clinician_dashboard_shortcode() {
    if (!is_user_logged_in() || !current_user_can('clinician')) {
        return 'You do not have permission to view this dashboard.';
    }

    $clinician_id = get_current_user_id();
    $assigned_members = get_field('assigned_members', 'user_' . $clinician_id);

    if (empty($assigned_members)) {
        return '<div id="no-clients-error">
                    <p>There are currently no clients assigned to you. Please contact the <a href="mailto:hi@chronicconditionsclinic.com">Administrator</a> if you believe this is an error.</p>
                </div>';
    }

    ob_start();
    ?>
    <p>These are the clients currently assigned to you. To interact with a specific client, please select their name from the list below.</p>
        <table class="clinician-dashboard-table">
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assigned_members as $member_id): 
                    $member = get_user_by('id', $member_id);
                    $profile_url = add_query_arg(['client_id' => $member_id], site_url('/c-dash/member-profile'));
                    $content_url = add_query_arg(['client_id' => $member_id], site_url('/c-dash/assigned-content'));
                    $message_url = add_query_arg(['client_id' => $member_id], site_url('/private-messaging')); ?>
                    <tr>
                        <td><?php echo esc_html($member->display_name); ?></td>
                    <td>
                        <a href="<?php echo esc_url($profile_url); ?>" class="button">Profile</a>
                        <a href="<?php echo esc_url($content_url); ?>" class="button">Content</a>
                        <a href="<?php echo esc_url($message_url); ?>" class="button">Message</a>
                        <a href="<?php echo esc_url(add_query_arg('client_id', $member_id, site_url('/notes/add-new-note/'))); ?>" class="button">Create Note</a>
                    </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php
    return ob_get_clean();
}
add_shortcode('clinician_dashboard', 'clinician_dashboard_shortcode');

// END ACTIVE CLIENT ROSTER

// START DISPLAY NOTES IN PROFILE PAGE

// Shortcode to display all notes for a client
function ccc_display_client_notes() {
    // Get client_id from the URL
    if (!isset($_GET['client_id']) || empty($_GET['client_id'])) {
        return '<p>No client specified.</p>';
    }
    $client_id = intval($_GET['client_id']);

    // Query notes for this client
    $query = new WP_Query(array(
        'post_type' => 'note',
        'meta_key' => '_ccc_assigned_client',
        'meta_value' => $client_id,
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'post_status' => 'publish'
    ));

    // Check if any notes were found
    if (!$query->have_posts()) {
        return '<p>No notes found for this client.</p>';
    }

    ob_start(); // Start output buffering

    echo '<div style="margin-top: 20px;">'; // Container for all notes

    // Loop through notes
    while ($query->have_posts()) : $query->the_post();
        $note_date = get_the_date();
        $note_content = get_the_content();
        $clinician_id = get_the_author_meta('ID');
        $clinician_name = get_the_author_meta('display_name', $clinician_id);

        // Display each note in its own styled container
        ?>
        <div style="background-color: #ffffff; border: 1px solid #d3d3d3; border-radius: 5px; padding: 15px; margin-bottom: 15px;">
            <p><strong>Date:</strong> <?php echo esc_html($note_date); ?></p>
            <p><strong>Note:</strong><br/> <?php echo wpautop(esc_html($note_content)); ?></p>
            <p><strong>Clinician:</strong> <?php echo esc_html($clinician_name); ?></p>
        </div>
        <?php
    endwhile;

    echo '</div>';

    wp_reset_postdata(); // Reset post data

    return ob_get_clean(); // Return the output
}
add_shortcode('ccc_client_notes', 'ccc_display_client_notes');

// END DISPLAY NOTES IN PROFILE PAGE


// START MEMBER PROFILE

function member_profile_shortcode($atts) {
    // Check if client_id is provided in the URL
    if (!isset($_GET['client_id']) || empty($_GET['client_id'])) {
        return '<p>No client selected. Please go back and select a client.</p>';
    }

    $member_id = intval($_GET['client_id']);
    $member = get_user_by('id', $member_id);

    if (!$member) {
        return '<p>Client not found. Please go back and select a valid client.</p>';
    }

    ob_start(); ?>
    <table border="1" style="width:100%; text-align:left;">
        <tr>
            <td><strong>Full Name:</strong></td>
            <td><?php echo esc_html($member->display_name); ?></td>
        </tr>
        <tr>
            <td><strong>Email:</strong></td>
            <td><?php echo esc_html($member->user_email); ?></td>
        </tr>
        <tr>
            <td><strong>Member Since:</strong></td>
            <td><?php echo esc_html(date('F j, Y', strtotime($member->user_registered))); ?></td>
        </tr>
        <tr>
            <td><strong>Role:</strong></td>
            <td><?php echo implode(', ', $member->roles); ?></td>
        </tr>
    </table>
    <?php

    return ob_get_clean();
}

add_shortcode('member_profile', 'member_profile_shortcode');

// END MEMBER PROFILE

// START ASSIGNED CONTENT 

function assigned_content_shortcode($atts) {
    if (!isset($_GET['client_id']) || empty($_GET['client_id'])) {
        return '<p>No client selected. Please go back and select a client.</p>';
    }

    $member_id = intval($_GET['client_id']);
    $member = get_user_by('id', $member_id);

    if (!$member) {
        return '<p>Client not found. Please go back and select a valid client.</p>';
    }

    $acf_context = 'user_' . $member_id;
    $content_slots = [
        get_field('content_slot_1', $acf_context),
        get_field('content_slot_2', $acf_context),
        get_field('content_slot_3', $acf_context)
    ];

    ob_start(); ?>
    


    <p>This is the content currently assigned to <b><?php echo esc_html($member->display_name); ?></b>. Click update to assign new content to this user. </p>

    <table class="assigned-content-table" border="1" style="width:100%; text-align:left;">
        <thead>
            <tr>
                <th>Slot</th>
                <th>Content</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($content_slots as $index => $slot) : 
                $slot_number = $index + 1;
                $post_title = $slot ? get_the_title($slot) : 'No Content Assigned';
                $post_link = $slot ? get_permalink($slot) : '#'; ?>
                <tr data-slot-number="<?php echo $slot_number; ?>" data-member-id="<?php echo $member_id; ?>">
                    <td><?php echo $slot_number; ?></td>
                    <td>
                        <?php if ($slot) : ?>
                            <a href="<?php echo esc_url($post_link); ?>" target="_blank"><?php echo esc_html($post_title); ?></a>
                        <?php else : ?>
                            No Content Assigned
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="update-content-button" data-slot="<?php echo $slot_number; ?>" data-member-id="<?php echo $member_id; ?>">Update</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Modal for selecting content -->
    <div id="content-selection-modal" style="display:none;">
        <h3>Select Content</h3>
         <select id="content-selector">
            <option value="">-- Select Content --</option>
            <?php
            // Fetch posts from Lesson and Practice post types
            $posts = get_posts([
                'post_type' => ['Lesson', 'Practice'],
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            ]);
            foreach ($posts as $post) {
                // Get the post type and format it
                $post_type = get_post_type_object(get_post_type($post));
                $post_type_label = $post_type ? $post_type->labels->singular_name : 'Unknown';
        
                echo '<option value="' . $post->ID . '">' . esc_html($post->post_title) . ' (' . esc_html($post_type_label) . ')</option>';
            }
            ?>
        </select>
        <button id="confirm-selection">Confirm</button>
    </div>


    <?php
    return ob_get_clean();
}

add_shortcode('assigned_content', 'assigned_content_shortcode');

// AJAX handler to update client content
function ajax_update_client_content() {
    check_ajax_referer('update_content_nonce', 'security');

    $member_id = intval($_POST['member_id']);
    $slot_number = intval($_POST['slot_number']);
    $post_id = intval($_POST['post_id']);

    if (!$member_id || !$slot_number || !$post_id) {
        wp_send_json_error(['message' => 'Invalid data provided.']);
    }

    $acf_field = 'content_slot_' . $slot_number;
    $updated = update_field($acf_field, $post_id, 'user_' . $member_id);

    if ($updated) {
        wp_send_json_success(['message' => 'Content updated successfully.']);
    } else {
        wp_send_json_error(['message' => 'Failed to update content.']);
    }
}

add_action('wp_ajax_update_client_content', 'ajax_update_client_content');

// END ASSIGNED CONTENT 

// START ENQUEUE JS & CSS

// Enqueue JS 
function enqueue_clinician_dashboard_scripts() {
    wp_enqueue_script(
        'clinician-dashboard-js',
        plugin_dir_url(__FILE__) . 'js/clinician-dashboard.js',
        ['jquery'],
        '1.0',
        true
    );

    // Pass AJAX URL and security nonce to the script
    wp_localize_script('clinician-dashboard-js', 'ajax_object', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('update_content_nonce'),
    ]);
}
add_action('wp_enqueue_scripts', 'enqueue_clinician_dashboard_scripts');

// Enqueue CSS 
function enqueue_clinician_dashboard_styles() {
    wp_enqueue_style(
        'clinician-dashboard-style',
        plugin_dir_url(__FILE__) . 'css/style.css',
        [],
        '1.0'
    );
}
add_action('wp_enqueue_scripts', 'enqueue_clinician_dashboard_styles');

// END ENQUEUE JS & CSS