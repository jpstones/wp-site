<?php
require_once __DIR__ . '/vendor/autoload.php';

// Check if the access token is available
$accessToken = get_option('google_calendar_access_token');
if (!$accessToken || !isset($accessToken['access_token'])) {
    echo '<p>❌ No valid access token found. Please reauthorize the app.</p>';
    exit;
}

try {
    // Initialize the Google Calendar Client
    require_once __DIR__ . '/classes/GoogleCalendarClient.php';
    $calendarClient = new GoogleCalendarClient();
    $calendars = $calendarClient->getUserCalendars();

    // Display the calendar selection form
    echo '<h2>Select a Calendar for Booking Appointments</h2>';
    echo '<form method="POST" action="">';
    echo '<select name="selected_calendar" required>';

    // Populate dropdown with calendars
    foreach ($calendars as $calendar) {
        echo '<option value="' . esc_attr($calendar['id']) . '">' . esc_html($calendar['summary']) . '</option>';
    }

    echo '</select>';
    echo '<br><br>';
    echo '<input type="submit" name="save_calendar" value="Save Calendar">';
    echo '</form>';
} catch (Exception $e) {
    error_log('❌ Error fetching calendars: ' . $e->getMessage());
    echo '<p>❌ Failed to fetch calendars. Check the error log.</p>';
}

// Handle form submission for calendar selection
if (isset($_POST['save_calendar'])) {
    // Get the selected calendar ID from the form
    $selectedCalendar = sanitize_text_field($_POST['selected_calendar']);

    // Store the selected calendar ID in the current user's metadata
    $currentUserId = get_current_user_id();
    update_user_meta($currentUserId, 'google_calendar_id', $selectedCalendar);

    echo '<p>✅ Calendar saved successfully! You can now use this calendar for bookings.</p>';
}