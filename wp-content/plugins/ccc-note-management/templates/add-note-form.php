<?php
/**
 * CCC Add New Note Form
 *
 * Allows coaches to add notes and track client core issues.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get logged-in coach/clinician ID
$coach_id = get_current_user_id();

// Fetch assigned clients using ACF
$assigned_clients = get_field('assigned_members', 'user_' . $coach_id);
$assigned_clients = !empty($assigned_clients) ? (array) $assigned_clients : [];

// Get selected client (from dropdown or URL parameter)
$selected_client = isset($_GET['client_id']) ? intval($_GET['client_id']) : (isset($assigned_clients[0]) ? intval($assigned_clients[0]) : 0);
if (!$selected_client) {
    echo '<p style="color: red;">No assigned clients available.</p>';
    return;
}

// Fetch all active core issues for the selected client from Core Issues Post Type
$args = array(
    'post_type' => 'core_issues',
    'post_status' => 'publish', // We can leave this as 'publish' to only fetch published posts
    'meta_query' => array(
        array(
            'key'     => 'client', // ACF 'client' field that links the issue to the client
            'value'   => $selected_client,
            'compare' => '='
        ),
        array(
            'key'     => 'status', // ACF 'status' field that marks core issues as active or archived
            'value'   => 'active', // Only show active core issues
            'compare' => '='
        ),
    ),
    'posts_per_page' => -1, // Get all core issues for this client
);

$core_issues_query = new WP_Query($args);

// Check if core issues exist
$core_issues = [];
if ($core_issues_query->have_posts()) {
    while ($core_issues_query->have_posts()) {
        $core_issues_query->the_post();
        $core_issues[] = array(
            'id' => get_the_ID(),
            'name' => get_the_title(),
            'severity' => get_field('severity'),
            'first_appearance' => get_field('first_appearance'),
            'curiosity' => get_field('curiosity'),
            'compassion' => get_field('compassion'),
            'status' => get_field('status') // Include status for debugging
        );
    }
} else {
    $core_issues = [];
}

wp_reset_postdata();
?>

<div class="ccc-note-container">
    <h2>Add New Note</h2>

    <!-- Client Selection -->
    <label for="ccc_assigned_client"><strong>Client:</strong></label>
    <select name="ccc_assigned_client" id="ccc_assigned_client" style="width: 100%; margin-bottom: 10px;">
        <?php
        if (!empty($assigned_clients)) {
            foreach ($assigned_clients as $client_id) {
                $client = get_user_by('id', $client_id);
                $selected = ($client_id == $selected_client) ? 'selected' : '';
                if ($client) {
                    echo '<option value="' . esc_attr($client_id) . '" ' . $selected . '>' . esc_html($client->display_name) . '</option>';
                }
            }
        } else {
            echo '<option value="">No assigned members available</option>';
        }
        ?>
    </select>

    <!-- Notes Section -->
    <label for="ccc_note"><strong>Session Notes:</strong></label>
    <textarea id="ccc_note" name="ccc_note" rows="5" style="width: 100%;" required></textarea>

    <!-- Core Issues Section -->
    <label for="ccc_issues"><strong>Core Issues:</strong></label>
    <div id="core-issues-container">
        <?php if (!empty($core_issues)) : ?>
            <?php foreach ($core_issues as $issue) : ?>
                <div class="core-issue-row" data-issue-id="<?php echo esc_attr($issue['id']); ?>" style="display: flex; width: 100%; align-items: center; justify-content: space-between; padding: 10px 0;">
                    <!-- Issue Name (disabled) -->
                    <input type="text" value="<?php echo esc_attr($issue['name']); ?>" disabled style="flex: 2; padding: 8px; border: 1px solid #ccc; background: #fff; font-size: 16px;">

                    <!-- Severity Dropdown -->
                    <select name="core_issue_severity[]" style="flex: 1; padding: 8px; font-size: 16px;">
                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                            <option value="<?php echo $i; ?>" <?php selected($issue['severity'], $i); ?>>
                                <?php echo $i . ($i == 1 ? " - Mild" : ($i == 5 ? " - Severe" : "")); ?>
                            </option>
                        <?php endfor; ?>
                    </select>

                    <!-- Archive Icon -->
                    <img src="<?php echo esc_url(CCC_NOTE_MANAGEMENT_URL . 'assets/images/archive-icon.svg'); ?>" 
                         alt="Archive" 
                         class="archive-core-issue" 
                         data-issue-id="<?php echo esc_attr($issue['id']); ?>"
                         style="width: 24px; height: 24px; cursor: pointer; margin-left: 10px;">
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p>No core issues found.</p>
        <?php endif; ?>
    </div>

    <!-- Modal for adding a new core issue -->
    <div id="core-issue-modal" class="modal">
        <div class="modal-content">
            <span id="modal-close" class="close">&times;</span>
            <h2>Add New Core Issue</h2>
    
            <!-- Issue Name -->
            <div class="modal-section">
                <label for="core-issue-name">Issue Name:</label>
                <input type="text" id="core-issue-name" placeholder="Enter Core Issue Name" required>
            </div>
    
            <!-- Severity -->
            <div class="modal-section">
                <label for="core-issue-severity">How severe is this issue on a typical day?</label>
                <select id="core-issue-severity" required>
                    <option value="1">1 - Mild</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5 - Severe</option>
                </select>
            </div>
    
            <!-- First Appearance Date -->
            <div class="modal-section">
                <label for="core-issue-first-appearance">Roughly, when did you first notice this issue?</label>
                <input type="date" id="core-issue-first-appearance" required>
            </div>
    
            <!-- Curiosity -->
            <div class="modal-section">
                <label for="core-issue-curiosity">How curious are you about this issue?</label>
                <select id="core-issue-curiosity" required>
                    <option value="1">1 - Not Curious</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5 - Very Curious</option>
                </select>
            </div>
    
            <!-- Compassion -->
            <div class="modal-section">
                <label for="core-issue-compassion">How compassionate do you feel toward yourself regarding this issue?</label>
                <select id="core-issue-compassion" required>
                    <option value="1">1 - No Compassion</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5 - Very Compassionate</option>
                </select>
            </div>
    
            <button id="save-core-issue">Save Core Issue</button>
        </div>
    </div>
    <!-- Add New Core Issue -->

    <button id="add-core-issue">Add New Core Issue</button>

    <!-- Submit Button -->
    <button type="submit" id="submit-note">Save Note</button>
</div>


<script>
document.getElementById("add-core-issue").addEventListener("click", function(event) {
    event.preventDefault();
    // Open the modal with fields for the new core issue
    document.getElementById("core-issue-modal").style.display = "block";
});

// Close the modal when the "X" button is clicked
document.getElementById("close-modal").addEventListener("click", function() {
    document.getElementById("core-issue-modal").style.display = "none";
});

// Close the modal if the user clicks outside of it
window.onclick = function(event) {
    if (event.target == document.getElementById("core-issue-modal")) {
        document.getElementById("core-issue-modal").style.display = "none";
    }
};

// Initialize datepicker
jQuery(document).ready(function($){
    $(".datepicker").datepicker();
});
</script>
