<?php
/*
Plugin Name: PMPro Role Sync
Description: Sync user roles with Paid Memberships Pro membership levels.
Version: 1.0
Author: JP Stones
*/

// Hook to update roles when membership level changes
add_action('pmpro_after_change_membership_level', 'sync_user_role_with_pmpro_level', 10, 2);

function sync_user_role_with_pmpro_level($level_id, $user_id) {
    $user = new WP_User($user_id);

    // Define the role for the clinician membership level
    $clinician_level_id = 5; // Replace with your clinician level ID
    $clinician_role = 'clinician'; // Ensure this role exists in WordPress

    if ($level_id == $clinician_level_id) {
        // Set the role to 'clinician'
        $user->set_role($clinician_role);
    } elseif ($level_id == 0) {
        // Handle when membership is cancelled (optional)
        $user->set_role('subscriber');
    } else {
        // Default role for other membership levels
        $user->set_role('subscriber'); // Replace with another role if needed
    }
}