<?php
/**
 * CCC Shortcodes
 *
 * Registers shortcodes for displaying client profile data.
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

class CCC_Shortcodes {

    public function __construct() {
        add_shortcode('ccc_client_profile', [$this, 'render_client_profile']);
        add_shortcode('ccc_add_note', [$this, 'render_add_note_form']); // Moved inside constructor
    }

    /**
     * Renders the client profile page using a shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML content for the client profile.
     */
    public function render_client_profile($atts) {
        ob_start();

        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<p style="color: red;">You must be logged in to view this page.</p>';
        }

        // Get logged-in user ID
        $client_id = get_current_user_id();

        // Load the profile template
        include CCC_NOTE_MANAGEMENT_PATH . 'templates/client-profile.php';

        return ob_get_clean();
    }
    
    /**
     * Renders the add note form using a shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML content for the add note form.
     */
    public function render_add_note_form($atts) {
        ob_start();
    
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<p style="color: red;">You must be logged in to add notes.</p>';
        }
    
        // Get client ID (coach selects client or gets from query param)
        $client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : get_current_user_id();
    
        // Load the add-note-form template
        include CCC_NOTE_MANAGEMENT_PATH . 'templates/add-note-form.php';
    
        return ob_get_clean();
    }
}

// Initialize the class
new CCC_Shortcodes();