<?php

// Track the last post a user viewed.
function cr_track_last_post_read() {
    if ( is_single() && is_user_logged_in() ) {
        $user_id = get_current_user_id();
        $post_id = get_the_ID();
        update_user_meta( $user_id, 'last_post_read', $post_id );
    }
}
add_action( 'wp', 'cr_track_last_post_read' );

// Handle AJAX request to save scroll percentage.
function cr_save_scroll_percentage() {
    if ( is_user_logged_in() && isset( $_POST['post_id'] ) && isset( $_POST['percentage'] ) ) {
        $user_id = get_current_user_id();
        $post_id = intval( $_POST['post_id'] );
        $percentage = intval( $_POST['percentage'] );

        // Save percentage as user meta.
        update_user_meta( $user_id, "post_{$post_id}_percentage", $percentage );

        wp_send_json_success();
    }

    wp_send_json_error();
}
add_action( 'wp_ajax_cr_save_scroll_percentage', 'cr_save_scroll_percentage' );

// Display the "Continue Reading" link with percentage read.
function cr_display_continue_reading_section() {
    if ( is_user_logged_in() ) {
        $user_id = get_current_user_id();
        $last_post_id = get_user_meta( $user_id, 'last_post_read', true );

        if ( $last_post_id ) {
            $last_post_title = get_the_title( $last_post_id );
            $last_post_url = get_permalink( $last_post_id );
            $percentage = get_user_meta( $user_id, "post_{$last_post_id}_percentage", true ) ?: 0;

            echo '<a href="' . esc_url( $last_post_url ) . '">' . esc_html( $last_post_title ) . '</a>';
            echo ' (' . intval( $percentage ) . '% read)';
        }
    }
}
add_shortcode( 'continue_reading', 'cr_display_continue_reading_section' );