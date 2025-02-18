<?php
if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';  // Load Google API library

// START RETRIEVE GOOGLE CAL API TOKEN

function getGoogleClient() {
    $client = new Google_Client();
    $client->setAuthConfig(plugin_dir_path(__FILE__) . '../config/oauth-credentials.json');

    // Retrieve the token from the database
    $accessToken = get_option('google_calendar_access_token');
    $client->setAccessToken($accessToken);

    // Check if token is expired and refresh it
    if ($client->isAccessTokenExpired()) {
        $refreshToken = $client->getRefreshToken();
        if ($refreshToken) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
            update_option('google_calendar_access_token', $newToken);
        } else {
            echo '❌ No refresh token available. You may need to reauthorize.';
        }
    }

    return $client;
}

// END RETRIEVE GOOGLE MAPS API TOKEN

// START FETCH EVENTS FROM GOOGLE CAL

// Fetches events from the specified Google Calendar within a given time range.
function getGoogleCalendarEvents($calendarId = 'primary', $timeMin = 'now', $timeMax = '+1 week') {
    // Get an authenticated Google client using the token stored in WordPress.
    $client = getGoogleClient();  
    $service = new Google_Service_Calendar($client);

    // Convert the provided time range into RFC3339 format (required by the Google Calendar API).
    $timeMinFormatted = date(DATE_RFC3339, strtotime($timeMin));
    $timeMaxFormatted = date(DATE_RFC3339, strtotime($timeMax));

    // Fetch events between $timeMin and $timeMax from the specified calendar.
    $events = $service->events->listEvents($calendarId, [
        'timeMin' => $timeMinFormatted,
        'timeMax' => $timeMaxFormatted,
        'singleEvents' => true,
        'orderBy' => 'startTime',
    ]);

    // If no events are found, display a message. Otherwise, loop through and display the events.
    if (count($events->getItems()) == 0) {
        echo '✅ No upcoming events found.';
    } else {
        echo '📅 <strong>Upcoming Events:</strong><br>';
        
        // Loop through each event and display its summary and start time.
        foreach ($events->getItems() as $event) {
            // Handle both all-day events (date) and events with specific times (dateTime).
            $start = $event->start->dateTime ?? $event->start->date;
            echo '📌 <strong>' . $event->getSummary() . '</strong> - Start: ' . $start . '<br>';
        }
    }
}

// END FETCH EVENTS FROM GOOGLE CAL
