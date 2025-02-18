<?php
/**
 * Plugin Name: CCC Admin Dashboard
 * Description: Manage Coaches and Members.
 * Version: 1.4
 * Author: JP Stones
 */

// START USER GREETING
add_shortcode('admin_greeting', 'render_admin_greeting');

function render_admin_greeting() {
    // Get current user info
    $current_user = wp_get_current_user();

    // Greeting message
    ob_start();
    ?>
    <div class="admin-greeting">
        <p>Hello, <?php echo esc_html($current_user->display_name); ?>. Welcome to your Admin Dashboard.</p>
    </div>
    <?php
    return ob_get_clean();
}
// END USER GREETING

// START UNASSIGNED CLIENTS

// Register shortcode to display admin dashboard
add_shortcode('admin_dashboard', 'render_admin_dashboard');

function render_admin_dashboard() {
    // Fetch unassigned clients
    $unassigned_clients = get_unassigned_clients();

    // Fetch clinicians ordered by least assigned clients
    $clinicians = get_clinicians_ordered_by_load();

    // Get total clients (dynamic count)
    $total_clients = get_total_clients_count();

    ob_start();
    ?>
    <div class="admin-dashboard">

        <h3 class="unassigned-header">Unassigned Premium Members</h3>
        <?php if (empty($unassigned_clients)) : ?>
            <p>No unassigned premium members found.</p>
        <?php else : ?>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Name</th>
                        <th style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unassigned_clients as $client) : ?>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                                <?php echo esc_html($client['name']); ?>
                            </td>
                            <td style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">
                                <button class="select-client" data-client-id="<?php echo esc_attr($client['id']); ?>">
                                    Select Coach
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="clinician-list" style="margin-top: 20px; display: none;">
            <h3>Clinicians</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Name</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Clients Assigned</th>
                        <th style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clinicians as $clinician) : ?>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                                <?php echo esc_html($clinician['name']); ?>
                            </td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                                <?php echo esc_html($clinician['assigned_count']); ?>
                            </td>
                            <td style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">
                                <button class="assign-clinician" 
                                        data-clinician-id="<?php echo esc_attr($clinician['id']); ?>">
                                    Assign
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// AJAX handler to assign a client
add_action('wp_ajax_admin_dashboard_assign_client', 'admin_dashboard_assign_client');

function admin_dashboard_assign_client() {
    // Output the raw POST data for debugging
    error_log("🛠 Received POST Data: " . print_r($_POST, true));

    if (!isset($_POST['client_id']) || !isset($_POST['clinician_id'])) {
        wp_send_json_error(['message' => 'Missing required parameters.']);
    }

    $client_id = intval($_POST['client_id']);
    $clinician_id = intval($_POST['clinician_id']);

    if ($client_id && $clinician_id) {
        update_user_meta($client_id, 'assigned_clinician', $clinician_id);

        wp_send_json_success([
            'message' => 'Success! Member was successfully assigned to Clinician.',
            'undoAction' => [
                'clientId' => $client_id,
                'clinicianId' => $clinician_id
            ]
        ]);
    } else {
        wp_send_json_error(['message' => 'Invalid client or clinician ID.']);
    }
}

add_action('wp_ajax_undo_assignment', 'undo_assignment');
function undo_assignment() {
    if (empty($_POST['client_id']) || empty($_POST['clinician_id'])) {
        wp_send_json_error(['message' => 'Invalid client or clinician ID.']);
        return;
    }

    $client_id = intval($_POST['client_id']);
    $clinician_id = intval($_POST['clinician_id']);

    // Remove clinician from client profile
    update_user_meta($client_id, 'assigned_clinician', '');

    // Remove client from clinician profile
    $assigned_members = get_field('assigned_members', 'user_' . $clinician_id) ?: [];
    $updated_members = array_diff($assigned_members, [$client_id]);
    update_field('assigned_members', array_values($updated_members), 'user_' . $clinician_id);

    // Send success response with proper formatting
    wp_send_json_success([
        'data' => [
            'message' => "Member was successfully unassigned from Clinician.",
            'client_id' => $client_id,
            'clinician_id' => $clinician_id
        ]
    ]);
}

// Fetch unassigned premium members
function get_unassigned_clients() {
    global $wpdb;

    $clients = $wpdb->get_results(
        "
        SELECT u.ID as id, u.display_name as name
        FROM GJN_users u
        INNER JOIN GJN_usermeta um ON u.ID = um.user_id
        LEFT JOIN GJN_usermeta ac ON u.ID = ac.user_id AND ac.meta_key = 'assigned_clinician'
        WHERE um.meta_key = 'GJN_capabilities' 
          AND um.meta_value LIKE '%premium_member%' 
          AND (ac.meta_value IS NULL OR ac.meta_value = '' OR ac.meta_value = '0' OR ac.meta_value = 'a:0:{}')
        GROUP BY u.ID
        ",
        ARRAY_A
    );

    return $clients;
}

// Fetch clinicians ordered by least assigned clients
function get_clinicians_ordered_by_load() {
    global $wpdb;

    $clinicians = $wpdb->get_results("
        SELECT u.ID as id, u.display_name as name
        FROM {$wpdb->users} u
        INNER JOIN {$wpdb->usermeta} um
        ON u.ID = um.user_id
        WHERE um.meta_key = '{$wpdb->prefix}capabilities'
        AND um.meta_value LIKE '%clinician%'
    ", ARRAY_A);

    foreach ($clinicians as &$clinician) {
        // Fetch assigned members for each clinician
        $assigned_members = get_field('assigned_members', 'user_' . $clinician['id']) ?: [];
        $clinician['assigned_count'] = count($assigned_members);
    }

    // Sort clinicians by the number of assigned clients (least to most)
    usort($clinicians, function ($a, $b) {
        return $a['assigned_count'] <=> $b['assigned_count'];
    });

    return $clinicians;
}

// END UNASSIGNED CLIENTS


// START COACH WORKLOAD

// define total clients

function get_total_clients_count() {
    global $wpdb;

    return $wpdb->get_var("
        SELECT COUNT(*)
        FROM {$wpdb->users} u
        INNER JOIN {$wpdb->usermeta} um
        ON u.ID = um.user_id
        WHERE um.meta_key = '{$wpdb->prefix}capabilities'
        AND um.meta_value LIKE '%premium_member%'
    ");
}

// Add Clinician Workload Shortcode
add_shortcode('clinician_workload_dashboard', function() {
    ob_start();

    // Fetch all premium members
    $premium_members = get_users(['role' => 'premium_member']); // Adjust role name if necessary
    $clinicians = get_users(['role' => 'clinician']);

    ?>
    <div class="...">
        <h3>Manage Coach Workload</h3>

        <!-- Info Box -->
        <div class="workload">
            <div class="card">
                <h4>Total Clients</h4>
                <hr />
                <p class="stat-value"><?php echo get_total_clients_count(); ?></p>
            </div>
            <div class="card">
                <h4>Active Clients</h4>
                <hr />
                <p class="stat-value">N/A</p>
            </div>
            <div class="card">
                <h4>Demanding Clients</h4>
                <hr />
                <p class="stat-value">N/A</p>
            </div>
            <div class="card">
                <h4>Thriving Clients</h4>
                <hr />
                <p class="stat-value">N/A</p>
            </div>
        </div>

        <!-- Filter Dropdown -->
        <label for="clinician-filter">Filter by Clinician:</label>
        <select id="clinician-filter">
            <option value="0">No Clinician Selected</option>
            <?php foreach ($clinicians as $clinician): ?>
                <option value="<?php echo esc_attr($clinician->ID); ?>">
                    <?php echo esc_html($clinician->display_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>
        <!-- Table -->
        <table id="premium-members-table" class="widefat fixed striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Activity Level</th>
                    <th>Demand Level</th>
                    <th>Thriving Level</th>
                    <th>Member Since</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($premium_members as $member): ?>
                    <tr>
                        <td><?php echo esc_html($member->display_name); ?></td>
                        <td>N/A</td>
                        <td>N/A</td>
                        <td>N/A</td>
                        <td>
                            <?php 
                            // Example for Member Since
                              echo date('F j, Y', strtotime($member->user_registered));
                            ?>
                        </td>
                        <td><button>View Profile</button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
});

// Handle AJAX for Info Box Data
add_action('wp_ajax_get_info_box_data', function() {
    $clinician_id = isset($_POST['clinician_id']) ? intval($_POST['clinician_id']) : 0;

    // Fetch premium members
    $premium_members = get_users(['role' => 'premium_member']);
    $clients = $premium_members;

    // Filter by clinician if selected
    if ($clinician_id) {
        $clients = array_filter($premium_members, function($member) use ($clinician_id) {
            // Example: Check if assigned clinician meta matches
            $assigned_clinician = get_user_meta($member->ID, 'assigned_clinician', true);
            return intval($assigned_clinician) === $clinician_id;
        });
    }

    // Calculate stats
    $total_clients = count($clients);
    $high_effort = $total_clients > 0 ? round((count($clients) * 0.4)) . '%' : '0%'; // Placeholder logic
    $clients_better = $total_clients > 0 ? round((count($clients) * 0.6)) . '%' : '0%'; // Placeholder logic

    wp_send_json_success([
        'total_clients' => $total_clients,
        'high_effort' => $high_effort . '*',
        'clients_better' => $clients_better . '*'
    ]);
});

// Handle AJAX for Premium Members Table
add_action('wp_ajax_get_premium_members', function() {
    $clinician_id = isset($_POST['clinician_id']) ? intval($_POST['clinician_id']) : 0;

    error_log("🔍 Debug: Received Clinician ID: $clinician_id");

    // Fetch premium members
    $args = [
        'role' => 'premium_member',
        'meta_query' => []
    ];
    
    if ($clinician_id) {
        $args['meta_query'][] = [
            'key' => 'assigned_clinician',
            'value' => sprintf(':"%d";', $clinician_id), // Match serialized value
            'compare' => 'LIKE'
        ];
    }

    $premium_members = get_users($args);
    error_log("🔍 Debug: Found " . count($premium_members) . " premium members.");

    // Prepare data
    $data = [];
    foreach ($premium_members as $member) {
        $data[] = [
            'id' => $member->ID,
            'name' => $member->display_name,
            'activity' => 'N/A',
            'effort' => 'N/A',
            'health_trend' => 'N/A',
            'member_since' => date('F j, Y', strtotime($member->user_registered))
        ];
    }

    wp_send_json_success($data);
});

// END COACH WORKLOAD

// START ENQUEUE FOR JS & CSS

// Enqueue JavaScript
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script(
        'admin-dashboard-script',
        plugins_url('/js/admin-dashboard.js', __FILE__),
        [],
        null,
        true
    );

    // Localize AJAX URL
    wp_localize_script('admin-dashboard-script', 'adminDashboard', [
        'ajaxurl' => admin_url('admin-ajax.php'),
    ]);

    // Enqueue the CSS file
    wp_enqueue_style(
        'admin-dashboard-style',
        plugins_url('/css/admin-dashboard.css', __FILE__)
    );
});

// END ENQUEUE FOR JS & CSS