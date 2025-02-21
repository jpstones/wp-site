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
    // Add debugging
    add_action('wp_footer', function() {
        if (is_user_logged_in()) {
            echo '<!-- CCC Note Management Plugin: User is logged in -->';
            echo '<!-- User ID: ' . get_current_user_id() . ' -->';
        } else {
            echo '<!-- CCC Note Management Plugin: User is NOT logged in -->';
        }
    });
}

// Make sure the plugin loads early enough
add_action('init', 'ccc_note_management_init', 1);

// END INCLUDE FILES DB ENTRIES


// START ENQUEUE ASSETS

function ccc_enqueue_assets() {
    // Styles - load everywhere
    wp_enqueue_style(
        'ccc-styles',
        CCC_NOTE_MANAGEMENT_URL . 'assets/css/styles.css',
        [],
        CCC_NOTE_MANAGEMENT_VERSION
    );

    // Scripts - only load on add-note page
    if (isset($_GET['page']) && $_GET['page'] === 'add-new-note') {
        wp_enqueue_script(
            'ccc-add-note',
            CCC_NOTE_MANAGEMENT_URL . 'assets/js/add-note.js',
            ['jquery'],
            time(),
            true
        );

        // Add type="module" to the script
        add_filter('script_loader_tag', function($tag, $handle, $src) {
            if ('ccc-add-note' === $handle) {
                return '<script type="module" src="' . esc_url($src) . '"></script>';
            }
            return $tag;
        }, 10, 3);

        wp_localize_script('ccc-add-note', 'ccc_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('ccc_save_note_nonce'),
        ]);

        wp_localize_script('ccc-add-note', 'cccData', [
            'trashIcon' => CCC_NOTE_MANAGEMENT_URL . 'assets/images/trash-icon.svg',
            'archiveIcon' => CCC_NOTE_MANAGEMENT_URL . 'assets/images/archive-icon.svg',
        ]);
    }

    // Member Profile page scripts
    if (is_page('c-dash/member-profile')) {
        // Enqueue Chart.js first
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            null,
            true
        );

        // Then enqueue our client core issues script
        wp_enqueue_script(
            'ccc-client-core-issues',
            CCC_NOTE_MANAGEMENT_URL . 'assets/js/client-core-issues.js',
            ['chartjs'],
            time(),
            true
        );

        // Add type="module" to the script
        add_filter('script_loader_tag', function($tag, $handle, $src) {
            if ('ccc-client-core-issues' === $handle) {
                return '<script type="module" src="' . esc_url($src) . '"></script>';
            }
            return $tag;
        }, 10, 3);
    }
}

add_action('wp_enqueue_scripts', 'ccc_enqueue_assets');
add_action('admin_enqueue_scripts', 'ccc_enqueue_assets');

// END ENQUEUE ASSETS

