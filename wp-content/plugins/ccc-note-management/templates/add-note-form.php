<?php
/**
 * CCC Add New Note Form
 *
 * Allows coaches to add notes and track client core issues.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add debugging information at the top
if (!is_user_logged_in()) {
    echo '<p style="color: red;">You must be logged in to add notes.</p>';
    return;
}

// Debug current user information
$current_user = wp_get_current_user();
echo '<!-- Debug Info:
User ID: ' . $current_user->ID . '
User Roles: ' . implode(', ', $current_user->roles) . '
-->';

// Get logged-in coach/clinician ID
$coach_id = get_current_user_id();

// Debug coach ID
echo '<!-- Coach ID: ' . $coach_id . ' -->';

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
    <h2>Session Notes & Core Issues</h2>

    <!-- Client Selection -->
    <div class="form-section">
        <label for="ccc_assigned_client" class="section-label"><strong>Client:</strong></label>
        <select name="ccc_assigned_client" id="ccc_assigned_client">
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
    </div>

    <!-- Notes Section -->
    <div class="form-section">
        <label for="ccc_note" class="section-label"><strong>Session Notes:</strong></label>
        <?php 
        $editor_settings = array(
            'textarea_name' => 'ccc_note',
            'textarea_rows' => 10,
            'media_buttons' => false,
            'teeny' => true,
            'quicktags' => true,
            'tinymce' => array(
                'toolbar1' => 'bold,italic,underline,bullist,numlist,link,unlink,undo,redo',
                'toolbar2' => '',
            ),
        );
        wp_editor('', 'ccc_note', $editor_settings); 
        ?>
    </div>

    <!-- Core Issues Sections -->
    <div class="form-section">
        <label class="section-label"><strong>Existing Core Issues:</strong></label>
        <div id="existing-core-issues-container">
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
                <p class="no-issues-message">No existing core issues found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Unsaved Core Issues Section -->
    <div class="form-section">
        <label class="section-label"><strong>New Core Issues:</strong></label>
        <div id="unsaved-core-issues-container">
            <!-- New issues will be added here dynamically -->
            <p class="no-unsaved-issues-message">No unsaved core issues.</p>
        </div>
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

            <!-- Frequency -->
            <div class="modal-section">
                <label for="core-issue-frequency">How frequently does this issue occur?</label>
                <select id="core-issue-frequency" required>
                    <option value="many_day">Many times a day</option>
                    <option value="once_day">Once or twice a day</option>
                    <option value="many_week">Many times a week</option>
                    <option value="once_week">Once or twice a week</option>
                    <option value="once_month">Once or twice a month</option>
                </select>
            </div>
    
            <!-- First Appearance Date -->
            <div class="modal-field">
                <label for="core-issue-first-appearance">Roughly, when did you first notice this issue?</label>
                <div class="month-year-picker">
                    <select id="core-issue-first-appearance-month" name="first-appearance-month">
                        <?php
                        $current_month = date('n'); // Get current month number (1-12)
                        $months = [
                            1 => 'January', 2 => 'February', 3 => 'March',
                            4 => 'April', 5 => 'May', 6 => 'June',
                            7 => 'July', 8 => 'August', 9 => 'September',
                            10 => 'October', 11 => 'November', 12 => 'December'
                        ];
                        foreach ($months as $num => $name) {
                            $selected = $num === $current_month ? 'selected' : '';
                            printf(
                                '<option value="%02d" %s>%s</option>',
                                $num,
                                $selected,
                                esc_html($name)
                            );
                        }
                        ?>
                    </select>
                    <select id="core-issue-first-appearance-year" name="first-appearance-year">
                        <?php
                        $current_year = intval(date('Y'));
                        for ($year = $current_year; $year >= $current_year - 10; $year--) {
                            printf(
                                '<option value="%d" %s>%d</option>',
                                $year,
                                $year === $current_year ? 'selected' : '',
                                $year
                            );
                        }
                        ?>
                    </select>
                </div>
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

<style>
    .month-year-picker {
        display: flex;
        gap: 10px;
        margin-top: 5px;
    }
    .month-year-picker select {
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        background-color: white;
    }
    #core-issue-first-appearance-month {
        flex: 2;
    }
    #core-issue-first-appearance-year {
        flex: 1;
    }
</style>