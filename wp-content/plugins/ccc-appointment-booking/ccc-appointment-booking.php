<?php
/*
Plugin Name: CCC Appointment Booking
Description: Custom plugin to handle Google Calendar appointment booking.
Version: 1.1
Author: JP Stones
*/

if (!defined('ABSPATH')) exit; // Prevent direct access

// Load classes and files
require_once plugin_dir_path(__FILE__) . 'classes/GoogleCalendarClient.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handlers.php';
require_once plugin_dir_path(__FILE__) . 'classes/GoogleCalendarHelper.php';

// START OAUTH CALLBACK

add_shortcode('oauth_callback_test', 'ccc_oauth_callback');

function ccc_oauth_callback() {
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return '';  // Do nothing during page save
    }

    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

    $client = new Google_Client();
    $client->setAuthConfig(plugin_dir_path(__FILE__) . 'config/oauth-credentials.json');
    $client->setRedirectUri('https://chronicconditionsclinic.com/oauth-callback-test');
    $client->setScopes([
        Google_Service_Calendar::CALENDAR,
        Google_Service_Calendar::CALENDAR_EVENTS,
        "https://www.googleapis.com/auth/userinfo.email"
    ]);    
    $client->setAccessType('offline');
    $client->setPrompt('consent');

    if (isset($_GET['code'])) {
        $authCode = $_GET['code'];

        try {
            // Exchange the authorization code for an access token
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

            // Debugging: Log full token response from Google
            error_log('🛑 OAuth Callback Triggered');
            error_log('🔑 Full Token Data: ' . print_r($accessToken, true));

            if (isset($accessToken['access_token'])) {
                if (is_user_logged_in()) {
                    $user_id = get_current_user_id(); // ✅ Get the current user ID

                    // Save the token to user_meta
                    update_user_meta($user_id, 'google_calendar_token', json_encode($accessToken));
                    error_log('✅ Saved token for user ID ' . $user_id);

                    // Extract email from the ID token
                    if (isset($accessToken['id_token'])) {
                        $decodedToken = explode(".", $accessToken['id_token'])[1]; // Extract JWT payload
                        $decodedToken = json_decode(base64_decode($decodedToken), true);

                        if (!empty($decodedToken['email'])) {
                            update_user_meta($user_id, 'google_calendar_email', sanitize_email($decodedToken['email']));
                            error_log('📧 Stored Google Calendar Email: ' . $decodedToken['email']);
                        } else {
                            error_log('❌ No email found in id_token.');
                        }
                    }

                } else {
                    error_log('❌ No user logged in during OAuth callback.');
                    return '❌ No user logged in.';
                }

                // Fetch available calendars
                $service = new Google_Service_Calendar($client);
                $calendarList = $service->calendarList->listCalendarList();

                $output = '<h2>Select a Calendar for Bookings:</h2>';
                $output .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
                $output .= '<select name="selected_calendar">';

                foreach ($calendarList->getItems() as $calendar) {
                    $output .= '<option value="' . esc_attr($calendar->getId()) . '">' . esc_html($calendar->getSummary()) . '</option>';
                }

                $output .= '</select>';
                $output .= '<input type="hidden" name="action" value="save_calendar_selection">';
                $output .= '<input type="submit" value="Save Calendar">';
                $output .= '</form>';

                return $output;
            } else {
                return '❌ Failed to retrieve access token: ' . json_encode($accessToken);
            }
        } catch (Exception $e) {
            return '❌ OAuth exception: ' . $e->getMessage();
        }
    } else {
        return '❌ No authorization code provided.';
    }
}

// END OAUTH CALLBACK


add_action('admin_post_save_calendar_selection', 'ccc_save_calendar_selection');

function ccc_save_calendar_selection() {
    if (isset($_POST['selected_calendar'])) {
        $selectedCalendar = sanitize_text_field($_POST['selected_calendar']);
        update_user_meta(get_current_user_id(), 'google_calendar_id', $selectedCalendar);

        // Redirect back to a confirmation page or the dashboard
        wp_redirect(home_url('/calendar-selection-success'));
        exit;
    }
}


// Shortcode to display booking form
function ccc_booking_form() {
    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/booking-form.php';
    return ob_get_clean();
}
add_shortcode('ccc_booking_form', 'ccc_booking_form');

// START UPCOMING EVENTS CAL SHORTOCDE

// Registers a shortcode to display upcoming events on any WordPress page.
// Usage: [display_calendar_events]
add_shortcode('display_calendar_events', function() {
    getGoogleCalendarEvents();  // Fetch events for the next week by default.
});

// END UPCOMING EVENTS CAL SHORTOCDE

// START FETCH AVAILABLE SLOTS

add_action('wp_ajax_get_available_slots', 'get_available_slots');
add_action('wp_ajax_nopriv_get_available_slots', 'get_available_slots');

function get_available_slots() {
    if (!isset($_GET['date'])) {
        wp_send_json_error(['message' => 'Missing date parameter.']);
        return;
    }

    $date = sanitize_text_field($_GET['date']);

    require_once plugin_dir_path(__FILE__) . 'classes/GoogleCalendarClient.php';

    try {
        $calendarClient = new GoogleCalendarClient();
        $availableSlots = $calendarClient->getAvailableTimeSlots('primary', $date);

        if (!empty($availableSlots)) {
            wp_send_json_success(['slots' => $availableSlots]);
        } else {
            wp_send_json_error(['message' => 'No available slots for the selected date.']);
        }
    } catch (Exception $e) {
        error_log('Error in get_available_slots: ' . $e->getMessage());
        wp_send_json_error(['message' => 'An unexpected error occurred.']);
    }
}
// END FETCH AVAILABLE SLOTS

// Register the shortcode to display the booking form
add_shortcode('booking_form', function() {
    ob_start();  // Start output buffering
    require_once plugin_dir_path(__FILE__) . 'templates/booking-form.php';  // Load the form template
    return ob_get_clean();  // Return the form output
});


// START BOOK APPOINTMENTS HANDLER

add_action('wp_ajax_handle_booking', 'handle_booking_request');
add_action('wp_ajax_nopriv_handle_booking', 'handle_booking_request');

function handle_booking_request() {
    $date = sanitize_text_field($_POST['appointment_date']);
    $timeSlot = sanitize_text_field($_POST['time_slot']);

    // Combine the date and time to create the full start and end times
    $startDateTime = date('Y-m-d\TH:i:s', strtotime("$date $timeSlot"));
    $endDateTime = date('Y-m-d\TH:i:s', strtotime("$startDateTime +1 hour"));  // 1-hour session

    require_once plugin_dir_path(__FILE__) . 'classes/BookingHandler.php';

    $bookingHandler = new BookingHandler();
    $calendarId = 'primary';
    $userId = get_current_user_id();  // Assume the user is logged in

    // Attempt to book the appointment
    $event = $bookingHandler->handleBooking($calendarId, $userId, $startDateTime);

    if ($event) {
        wp_send_json_success(['eventLink' => $event->htmlLink]);
    } else {
        wp_send_json_error(['error' => 'The selected time slot is already booked or unavailable.']);
    }
}

// END BOOK APPOINTMENTS HANDLER


// DEBUG DELETE EVENTUALLY

function ccc_test_list_calendars() {
    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

    $client = new Google_Client();
    $accessToken = get_user_meta(16, 'google_calendar_token', true);
    $client->setAccessToken(json_decode($accessToken, true));

    if ($client->isAccessTokenExpired()) {
        error_log('⚠️ Brei’s access token is expired, refreshing...');
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        update_user_meta(16, 'google_calendar_token', json_encode($client->getAccessToken()));
    }

    $service = new Google_Service_Calendar($client);
    $calendarList = $service->calendarList->listCalendarList();

    foreach ($calendarList->getItems() as $calendar) {
        error_log('📆 Found Calendar: ' . $calendar->getSummary() . ' (' . $calendar->getId() . ')');
    }

    return '✅ Calendar list logged!';
}

add_shortcode('test_calendars', 'ccc_test_list_calendars');

// DEBUG DELETE EVENTUALLY



// START ENQUEUE

function ccc_booking_enqueue_scripts() {
    wp_enqueue_style('ccc-booking-style', plugin_dir_url(__FILE__) . 'assets/css/booking-style.css');
    wp_enqueue_script('ccc-booking-script', plugin_dir_url(__FILE__) . 'assets/js/booking.js', array('jquery'), null, true);
    
    // Localize AJAX URL
    wp_localize_script('ccc-booking-script', 'ajaxData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
    ));
}
add_action('wp_enqueue_scripts', 'ccc_booking_enqueue_scripts');

// END ENQUEUE