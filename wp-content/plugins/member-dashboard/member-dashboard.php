<?php
/*
Plugin Name: CCC Member Dashboard
Description: Features on Free Member and Premium Member Dashbaords
Version: 1.1
Author: JP Stones
*/

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// START GREETING

function custom_member_dashboard_greeting() {
    // Get the current user info
    $current_user = wp_get_current_user();
    
    // Check if the user is logged in and has a first name set
    if (is_user_logged_in() && !empty($current_user->first_name)) {
        $user_first_name = $current_user->first_name;
    } else {
        $user_first_name = 'Guest'; // Fallback for users without a first name or not logged in
    }

    // Check the user's membership level
    $membership_level = 'Member'; // Default fallback
    if ( pmpro_hasMembershipLevel( 2, $current_user->ID ) ) {
        $membership_level = 'Free Member';
    } elseif ( pmpro_hasMembershipLevel( 1, $current_user->ID ) ) {
        $membership_level = 'Premium Member';
    }

    // Construct the greeting message
    $edit_url = 'https://chronicconditionsclinic.com/membership-account/your-profile/';
    $greeting = sprintf(
        "Hi %s (<a href=\"%s\">edit</a>). Welcome to your %s Dashboard.",
        esc_html($user_first_name),
        esc_url($edit_url),
        esc_html($membership_level)
    );

    // Return the greeting wrapped in a paragraph tag
    return '<p>' . $greeting . '</p>';
}

// Register a shortcode to display the greeting
add_shortcode('member_dashboard_greeting', 'custom_member_dashboard_greeting');

// END GREETING

// START GET COACHES

// Function to get the primary clinician for the logged-in user
function get_primary_clinician($member_id) {
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

// Shortcode to display the primary clinician assigned to the current member
function display_primary_clinician() {
    $member_id = get_current_user_id();

    // Debugging: Log the current user ID
    error_log('Current User ID: ' . $member_id);

    $primary_clinician = get_primary_clinician($member_id);

    if ($primary_clinician) {
        error_log('Primary Clinician Display Name: ' . $primary_clinician->display_name);

        // Generate the links
        $clinician_name = esc_html($primary_clinician->display_name);
        $clinician_url = esc_url('https://chronicconditionsclinic.com/team/' . sanitize_title($primary_clinician->display_name));

        // Output Full Name, View Profile (link), and Message (plain text)
        return $clinician_name . '<br>
                <a href="' . $clinician_url . '" target="_blank" rel="noopener">View Profile</a> | <a href="https://chronicconditionsclinic.com/private-messaging/">Message</a>';
    } else {
        error_log('No coach assigned for Member ID: ' . $member_id);
        return 'No coach assigned yet.';
    }
}
add_shortcode('primary_clinician', 'display_primary_clinician');

// END GET COACHES

// START ASSIGNED CONTENT


// find and show allocated content
function display_allocated_content_acf() {
    // Get the current user ID
    $user_id = get_current_user_id();

    // Check if user is logged in
    if (!$user_id) {
        return '<p>Please log in to view your allocated content.</p>';
    }

    // Fetch content slots from ACF user fields
    $content_slots = [];
    for ($i = 1; $i <= 3; $i++) {
        $content_slot = get_field("content_slot_$i", "user_$user_id");
        if ($content_slot) {
            $content_slots[] = $content_slot; // Store content slot if not empty
        }
    }

    // If no content is allocated
    if (empty($content_slots)) {
        return '<p>No content allocated to you.</p>';
    }

    // Generate the output for allocated content
    $output = '<div class="allocated-content-row" style="display: flex; gap: 20px; justify-content: space-between; align-items: flex-start; flex-wrap: wrap;">';

    foreach ($content_slots as $post_id) {
        // Ensure the post exists
        if (get_post_status($post_id)) {
            $title = get_the_title($post_id);
            $image = get_the_post_thumbnail($post_id, 'medium_large', ['class' => 'blog-image', 'style' => 'width: 100%; height: auto; max-width: 768px; display: block;']);
            $excerpt = get_the_excerpt($post_id);
            $permalink = get_permalink($post_id);

            // Add each post's details in a styled column
            $output .= '
            <div class="content-column" style="flex: 1; background: #fff; margin: 5px; padding: 15px; border: 1px solid #ccc; border-radius: 10px; box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1); text-align: left; max-width: calc(33.33% - 20px);">
                <div>' . $image . '</div>
                <h3 style="margin: 15px 0;">' . esc_html($title) . '</h3>
                <p style="margin: 15px 0; font-size: 14px; line-height: 1.6;">' . esc_html($excerpt) . '</p>
                <a class="elementor-button elementor-button-link elementor-size-sm" href="' . esc_url($permalink) . '" target="_blank" rel="noopener" style="background-color: #b32464; color: #fff; text-transform: uppercase; font-weight: bold;">
                    <span class="elementor-button-content-wrapper">
                        <span class="elementor-button-text">Read More</span>
                    </span>
                </a>
            </div>';
        }
    }

    $output .= '</div>';

    return $output;
}

// Register the shortcode
add_shortcode('allocated_content', 'display_allocated_content_acf');

// EBD ASSIGNED CONTENT
