<?php
/**
 * Plugin Name: Continue Reading
 * Description: Adds a "Continue Reading" section to display the last post a user viewed.
 * Version: 1.2
 * Author:  JP Stones
 * License: GPL2+
 */


// Prevent direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
define( 'CR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include functions file.
require_once CR_PLUGIN_DIR . 'includes/functions.php';

/**
 * Enqueue JavaScript to track scroll percentage.
 */
function cr_enqueue_scripts() {
    if ( is_single() && is_user_logged_in() ) {
        wp_enqueue_script( 'cr-scroll-tracker', CR_PLUGIN_URL . 'js/scroll-tracker.js', array( 'jquery' ), '1.0', true );
        wp_localize_script( 'cr-scroll-tracker', 'crData', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'postId'  => get_the_ID(),
        ));
    }
}
add_action( 'wp_enqueue_scripts', 'cr_enqueue_scripts' );