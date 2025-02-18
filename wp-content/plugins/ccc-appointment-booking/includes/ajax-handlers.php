<?php

error_log('📥 Booking Request Received: ' . print_r($_POST, true));

// START TEST GCAL CONNECTION

add_action('wp_ajax_check_calendar_api_status', 'ccc_check_calendar_api_status');
add_action('wp_ajax_nopriv_check_calendar_api_status', 'ccc_check_calendar_api_status');

function ccc_check_calendar_api_status() {
    $user_id = get_current_user_id();
    $calendar_id = get_user_meta($user_id, 'google_calendar_id', true);
    $token = get_user_meta($user_id, 'google_calendar_token', true);

    if (!$calendar_id || !$token) {
        wp_send_json_error('❌ Google Calendar is not connected. <a href="https://chronicconditionsclinic.com/book-an-appointment/">Connect Now</a>');
        return;
    }

    try {
        $calendar = new GoogleCalendarClient();
        $calendars = $calendar->getUserCalendars();  // Test fetching calendars

        if (!empty($calendars)) {
            wp_send_json_success('✅ Google Calendar is connected to <strong>' . esc_html($calendar_id) . '</strong>.');
        } else {
            wp_send_json_error('❌ No calendars found. Please reconnect.');
        }
    } catch (Exception $e) {
        wp_send_json_error('❌ Failed to connect to Google Calendar API: ' . esc_html($e->getMessage()));
    }
}

// END TEST GCAL CONNECTION


// START CHECK AVAILABLE SLOTS

add_action('wp_ajax_get_available_slots', 'ccc_get_available_slots');
add_action('wp_ajax_nopriv_get_available_slots', 'ccc_get_available_slots');

function ccc_get_available_slots() {
    // Step 1: Check if the date is passed from the front-end
    if (!isset($_POST['date']) || empty($_POST['date'])) {
        error_log('❌ No date provided for available slots.');
        wp_send_json_error('❌ No date provided.');
        return;
    }

    $selected_date = sanitize_text_field($_POST['date']);
    error_log('📅 Date received from AJAX: ' . $selected_date);  // Log the selected date for debugging

    // Step 2: Fetch the current user's selected calendar ID
    $currentUserId = get_current_user_id();
    $calendarId = get_user_meta($currentUserId, 'google_calendar_id', true);
    error_log('📆 Using calendar ID: ' . $calendarId);  // Log the calendar ID

    if (empty($calendarId)) {
        error_log('❌ No calendar ID found for user ID: ' . $currentUserId);
        wp_send_json_error('❌ No calendar selected.');
        return;
    }

    try {
        // Step 3: Get available slots from the selected calendar
        $calendar = new GoogleCalendarClient();
        $slots = $calendar->getAvailableTimeSlots($calendarId, $selected_date);

        if (!empty($slots)) {
            error_log('✅ Available slots found: ' . json_encode($slots));  // Log the slots
            wp_send_json_success($slots);
        } else {
            error_log('❌ No available slots found for the date: ' . $selected_date);
            wp_send_json_error('❌ No available slots found.');
        }
    } catch (Exception $e) {
        // Step 4: Handle and log any exceptions
        error_log('❌ Error fetching available slots: ' . $e->getMessage());
        wp_send_json_error('❌ Error fetching slots: ' . $e->getMessage());
    }

    wp_die();  // End the AJAX request properly
}

// END CHECK AVAILABLE SLOTS


// START BOOK APPOINTMENT

add_action('wp_ajax_book_appointment', 'ccc_book_appointment');
add_action('wp_ajax_nopriv_book_appointment', 'ccc_book_appointment');

function ccc_book_appointment() {
    // Log the incoming POST data for debugging
    error_log('📥 Received POST data: ' . json_encode($_POST));

    // Validate required fields
    if (empty($_POST['time_slot']) || empty($_POST['date']) || empty($_POST['coach_id'])) {
        error_log('❌ Missing POST data: time_slot=' . ($_POST['time_slot'] ?? 'EMPTY') . 
                  ', date=' . ($_POST['date'] ?? 'EMPTY') . 
                  ', coach_id=' . ($_POST['coach_id'] ?? 'EMPTY'));
        wp_send_json_error(['message' => '❌ Missing time slot, date, or coach.']);
        return;
    }

    $time_slot = sanitize_text_field($_POST['time_slot']);
    $selectedDate = sanitize_text_field($_POST['date']);
    $coachCalendarId = sanitize_text_field($_POST['coach_id']); // This is actually the Google Calendar ID
    error_log('🧐 Coach Calendar ID received: ' . $coachCalendarId);

    // Fetch the WordPress user ID based on Google Calendar ID
    global $wpdb;
    $selectedCoachId = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'google_calendar_id' AND meta_value = %s",
        $coachCalendarId
    ));

    if (!$selectedCoachId) {
        error_log('❌ No WordPress user found for Google Calendar ID: ' . $coachCalendarId);
        wp_send_json_error(['message' => '❌ No coach found for this calendar.']);
        return;
    }

    error_log('✅ Coach Found: WordPress User ID = ' . $selectedCoachId);

    // Retrieve the stored access token for this coach
    $accessToken = get_user_meta($selectedCoachId, 'google_calendar_token', true);
    if (!$accessToken) {
        error_log('❌ No access token found for Coach ID: ' . $selectedCoachId);
        wp_send_json_error(['message' => '❌ No access token found for this coach.']);
        return;
    }
    
    // Decode the stored access token
    $accessTokenData = json_decode($accessToken, true);
    if (empty($accessTokenData['access_token'])) {
        error_log('❌ Invalid access token structure: ' . json_encode($accessTokenData));
        wp_send_json_error(['message' => '❌ Invalid access token structure.']);
        return;
    }
    
    error_log('🔍 Token Owner (WordPress User ID): ' . $selectedCoachId . ' | Calendar ID: ' . $coachCalendarId);
    error_log('🔑 Using Access Token: ' . $accessTokenData['access_token']);

    // Get the member’s saved time zone (or default to site time zone)
    $userId = get_current_user_id();
    $userTimezone = get_user_meta($userId, 'user_timezone', true) ?: wp_timezone_string();

    // Split the time slot into start and end times (e.g., "09:00 - 11:00")
    $times = explode(' - ', $time_slot);
    if (count($times) !== 2) {
        error_log('❌ Invalid time slot format: ' . $time_slot);
        wp_send_json_error(['message' => '❌ Invalid time slot format.']);
        return;
    }

    $startTime = $times[0];
    $endTime = $times[1];

    // Convert to RFC 3339 format using the selected date and user’s time zone
    $timezone = new DateTimeZone($userTimezone);
    $startTimeRFC3339 = (new DateTime("$selectedDate $startTime", $timezone))->format(DATE_RFC3339);
    $endTimeRFC3339 = (new DateTime("$selectedDate $endTime", $timezone))->format(DATE_RFC3339);

    // Log the details before attempting to book
    error_log("📅 Booking details: Coach=$selectedCoachId, Calendar=$coachCalendarId, Start=$startTimeRFC3339, End=$endTimeRFC3339");
    // Log final calendar ID before booking
    error_log('📤 Final calendar ID for booking: ' . $coachCalendarId);

    try {
        $calendar = new GoogleCalendarClient();

        // Ensure the Google client has the correct token before making a request
        $client = $calendar->getClient();
        $client->setAccessToken($accessTokenData);
        
        if ($client->isAccessTokenExpired()) {
            error_log('⚠️ Access token expired, attempting to refresh...');
            $newAccessToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            if (!isset($newAccessToken['access_token'])) {
                error_log('❌ Failed to refresh access token.');
                wp_send_json_error(['message' => '❌ Failed to refresh access token.']);
                return;
            }
            update_user_meta($selectedCoachId, 'google_calendar_token', json_encode($newAccessToken));
            $client->setAccessToken($newAccessToken);
            error_log('✅ Access token refreshed successfully.');
        }

        error_log("📤 Attempting to create event on Calendar: $coachCalendarId");

        $event = $calendar->bookAppointment(
            $coachCalendarId,  // Use the selected coach’s calendar ID
            'Appointment with ' . wp_get_current_user()->display_name,
            'Health session',
            $startTimeRFC3339,  // Correct start time
            $endTimeRFC3339     // Correct end time
        );

        error_log('✅ Appointment successfully booked: ' . json_encode($event));
        wp_send_json_success(['message' => '✅ Appointment successfully booked!']);
    } catch (Exception $e) {
        // Log the error and send an error response
        error_log('❌ Booking error: ' . $e->getMessage());
        wp_send_json_error(['message' => '❌ Failed to book the appointment: ' . $e->getMessage()]);
    }
}

// END BOOK APPOINTMENT


function calculate_end_time($start_time) {
    return date('Y-m-d\TH:i:s', strtotime($start_time) + 3600); // Add 1 hour
}





// DEBUG ACTION DELETE EVENTUALLY

add_action('wp_ajax_debug_list_calendars', 'ccc_debug_list_calendars');
add_action('wp_ajax_nopriv_debug_list_calendars', 'ccc_debug_list_calendars');

function ccc_debug_list_calendars() {
    $calendarClient = new GoogleCalendarClient();
    $result = $calendarClient->debugListCalendars();
    
    wp_send_json_success(['message' => $result]);
}

// DEBUG ACTION DELETE EVENTUALLY