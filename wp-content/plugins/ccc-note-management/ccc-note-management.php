<?php
/**
 * Plugin Name: CCC Note Management
 * Plugin URI:  https://chronicconditionsclinic.com
 * Description: A plugin to manage client notes and track core issues.
 * Version:     1.3
 * Author:      JP Stones
 */

// START INCLUDE FILES DB ENTRIES

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('CCC_NOTE_MANAGEMENT_VERSION', '1.0.0');
define('CCC_NOTE_MANAGEMENT_PATH', plugin_dir_path(__FILE__));
define('CCC_NOTE_MANAGEMENT_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once CCC_NOTE_MANAGEMENT_PATH . 'includes/class-ccc-ajax-handler.php';
require_once CCC_NOTE_MANAGEMENT_PATH . 'includes/class-ccc-shortcodes.php';
require_once CCC_NOTE_MANAGEMENT_PATH . 'includes/install.php';

// Initialize plugin
function ccc_note_management_init() {
    // Register hooks here if needed later
}
add_action('plugins_loaded', 'ccc_note_management_init');

// END INCLUDE FILES DB ENTRIES


// START ENQUEUE ASSETS

function ccc_enqueue_assets() {
    // Styles
    wp_enqueue_style(
        'ccc-styles',
        CCC_NOTE_MANAGEMENT_URL . 'assets/css/styles.css',
        [],
        CCC_NOTE_MANAGEMENT_VERSION
    );

    // Scripts
    wp_enqueue_script(
        'ccc-core-issues',
        CCC_NOTE_MANAGEMENT_URL . 'assets/js/core-issues.js',
        ['jquery'],
        time(), // Force latest version
        true
    );

    // ✅ Pass AJAX data to JavaScript using `ccc_ajax` (Fix for Undefined Error)
    wp_localize_script('ccc-core-issues', 'ccc_ajax', [
        'ajax_url'     => admin_url('admin-ajax.php'),
        'security'     => wp_create_nonce('ccc_save_note_nonce'), // Nonce for security
    ]);

    // ✅ Pass icons separately (Fix path issue)
    wp_localize_script('ccc-core-issues', 'cccData', [
        'trashIcon'    => CCC_NOTE_MANAGEMENT_URL . 'assets/images/trash-icon.svg',
        'archiveIcon'  => CCC_NOTE_MANAGEMENT_URL . 'assets/images/archive-icon.svg',
    ]);
}

add_action('wp_enqueue_scripts', 'ccc_enqueue_assets');
add_action('admin_enqueue_scripts', 'ccc_enqueue_assets');

// END ENQUEUE ASSETS

