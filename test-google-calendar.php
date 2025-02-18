<?php
require_once 'wp-content/plugins/ccc-appointment-booking/vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig('wp-content/plugins/ccc-appointment-booking/config/oauth-credentials.json');

$accessToken = get_option('google_calendar_access_token');
if ($accessToken) {
    $client->setAccessToken($accessToken);

    if ($client->isAccessTokenExpired()) {
        $refreshToken = $client->getRefreshToken();
        $newAccessToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
        update_option('google_calendar_access_token', $newAccessToken);
    }

    $service = new Google_Service_Calendar($client);

    try {
        $events = $service->events->listEvents('primary', [
            'timeMin' => date('c', strtotime('2025-02-04 09:00:00')),
            'timeMax' => date('c', strtotime('2025-02-04 17:00:00')),
            'singleEvents' => true,
            'orderBy' => 'startTime',
        ]);

        echo '<h3>Events on 2025-02-04:</h3>';
        foreach ($events->getItems() as $event) {
            echo $event->getSummary() . ' - ' . $event->getStart()->getDateTime() . '<br>';
        }
    } catch (Exception $e) {
        echo 'Google Calendar API Error: ' . $e->getMessage();
    }
} else {
    echo 'No access token found. Please reauthorize the app.';
}