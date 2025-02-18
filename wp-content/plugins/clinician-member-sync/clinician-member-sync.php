<?php
/**
 * Plugin Name: CCC Clinician-Member Sync
 * Description: Synchronize clinician and member relationships bidirectionally.
 * Version: 1.0.0
 * Author: Your Name
 */

// Ensure this file is not accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// START BILATERAL CHECK

add_action('acf/save_post', 'sync_acf_clinician_member_relationship', 20);
add_action('updated_user_meta', 'sync_user_meta_clinician_member', 20, 4);

// Handle ACF updates using ACF hooks
function sync_acf_clinician_member_relationship($post_id) {
    if (strpos($post_id, 'user_') !== 0) {
        return; // Only target user updates
    }

    $user_id = str_replace('user_', '', $post_id);
    $assigned_clinician = get_field('assigned_clinician', "user_{$user_id}");

    // Sync the relationship
    sync_bilateral_relationship($user_id, $assigned_clinician);
}

// Handle direct updates via user meta (for compatibility)
function sync_user_meta_clinician_member($meta_id, $object_id, $meta_key, $meta_value) {
    if ($meta_key === 'assigned_clinician') {
        sync_bilateral_relationship($object_id, $meta_value);
    }
}

// Sync both sides of the relationship
function sync_bilateral_relationship($member_id, $clinician_id) {
    if (!$member_id || !$clinician_id) {
        return;
    }

    // Update the member’s assigned clinician
    update_field('assigned_clinician', $clinician_id, "user_{$member_id}");

    // Update the clinician’s assigned members
    $assigned_members = get_field('assigned_members', "user_{$clinician_id}") ?: [];
    if (!in_array($member_id, $assigned_members)) {
        $assigned_members[] = $member_id;
        update_field('assigned_members', $assigned_members, "user_{$clinician_id}");
    }
}