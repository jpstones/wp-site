<?php

// INCLUDES/SETTINGS.PHP 
// CCC NOTIFICATIONS PLUGIN SETTINGS PAGE

// Render the CCC Notifications Settings page
function ccc_render_notifications_settings_page() {
    // Get the registered notification types
    $notification_types = get_option('ccc_registered_notification_types', []);

    // Check if the form has been submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update the default messages with the submitted values
        foreach ($_POST['ccc_notification_templates'] as $type => $new_message) {
            $notification_types[$type]['default_message'] = sanitize_text_field($new_message);
        }

        // Save the updated notifications to the database
        update_option('ccc_registered_notification_types', $notification_types);

        echo '<div class="updated"><p>Notification templates updated successfully.</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>CCC Notifications Settings</h1>
        <form method="POST">
            <table class="form-table" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <th style="width: 30%; padding: 10px; background-color: #f4f4f4;">Notification Type</th>
                    <th style="width: 70%; padding: 10px; background-color: #f4f4f4;">Default Message</th>
                </tr>
                <?php foreach ($notification_types as $type => $details): ?>
                    <tr>
                        <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                            <strong><?php echo esc_html($details['label']); ?></strong>
                        </td>
                        <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                            <input type="text" name="ccc_notification_templates[<?php echo esc_attr($type); ?>]" 
                                   value="<?php echo esc_attr($details['default_message']); ?>" 
                                   style="width: 100%;">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <p class="submit">
                <input type="submit" class="button-primary" value="Save Changes">
            </p>
        </form>
    </div>
    <?php
}