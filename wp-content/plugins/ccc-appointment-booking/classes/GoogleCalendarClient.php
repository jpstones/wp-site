<?php

// START GOOGLE CAL API CONNECTION AND SET SCOPES

class GoogleCalendarClient {
    private $client;

    public function __construct() {
        // Initialize the Google client and set the required scopes
        $this->client = new Google_Client();
        $this->client->setAuthConfig(plugin_dir_path(__FILE__) . '../config/oauth-credentials.json');
        $this->client->addScope(Google_Service_Calendar::CALENDAR);
        $this->client->setAccessType('offline');  // Request refresh tokens
    }

    /**
     * Get the authenticated Google client.
     *
     * @return Google_Client The authenticated client.
     */
    public function getClient() {
        $current_user_id = get_current_user_id();
        $accessToken = get_user_meta($current_user_id, 'google_calendar_token', true);
    
        error_log('🔍 Retrieved Token for User ' . $current_user_id . ': ' . print_r($accessToken, true)); // <-- ADD THIS LINE
    
        if ($accessToken) {
            $accessToken = json_decode($accessToken, true);  // Decode JSON
            $this->client->setAccessToken($accessToken);
    
            // Refresh token if needed
            if ($this->client->isAccessTokenExpired()) {
                $newAccessToken = $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                update_user_meta($current_user_id, 'google_calendar_token', json_encode($newAccessToken));
                error_log('🔄 Refreshed Token for User ' . $current_user_id . ': ' . print_r($newAccessToken, true)); // <-- ADD THIS LINE
            }
        }
    
        return $this->client;
    }

    /**
     * Fetch and return the list of available calendars for the authenticated user.
     *
     * @return array List of calendars with their IDs and names.
     * @throws Exception If an error occurs while fetching the calendars.
     */
    public function getUserCalendars() {
        // Initialize the Google Calendar service
        $service = new Google_Service_Calendar($this->getClient());

        try {
            // Fetch the list of calendars available to the authenticated user
            $calendarList = $service->calendarList->listCalendarList();
            $calendars = [];

            // Loop through each calendar and store its ID and summary (name)
            foreach ($calendarList->getItems() as $calendar) {
                $calendars[] = [
                    'id' => $calendar->getId(),         // Calendar ID (used for API calls)
                    'summary' => $calendar->getSummary() // Calendar name (for display)
                ];
            }

            return $calendars;  // Return the list of calendars
        } catch (Exception $e) {
            // Log the error if the calendar fetch fails
            error_log('Error fetching calendars: ' . $e->getMessage());
            throw new Exception('Failed to fetch calendars.');
        }
    }

    /**
     * Fetch available time slots for the given date by checking gaps between events.
     *
     * @param string $calendarId The Google Calendar ID.
     * @param string $date The date to check for availability (Y-m-d format).
     * @return array List of events on the given date.
     * @throws Exception If an error occurs while fetching events.
     */
    public function getAvailableTimeSlots($calendarId, $date) {
        $service = new Google_Service_Calendar($this->getClient());
        error_log('🔑 Access Token: ' . $this->getClient()->getAccessToken());
    
        // Define the booking window
        $timeMin = date('c', strtotime($date . ' 09:00:00'));  // Start time: 9 AM
        $timeMax = date('c', strtotime($date . ' 17:00:00'));  // End time: 5 PM
    
        try {
            // Fetch events from Google Calendar
            $events = $service->events->listEvents($calendarId, [
                'timeMin' => $timeMin,
                'timeMax' => $timeMax,
                'singleEvents' => true,
                'orderBy' => 'startTime',
            ]);
    
            $eventItems = $events->getItems();
            error_log('📋 Events fetched: ' . json_encode($eventItems));  // Log events for debugging
    
            // If no events, split the entire day into 2-hour slots
            if (empty($eventItems)) {
                return $this->generateTimeSlots('09:00', '17:00', 2);  // 2-hour slots
            }
    
            // Otherwise, calculate available slots based on existing events
            $availableSlots = [];
            $lastEndTime = $timeMin;
    
            foreach ($eventItems as $event) {
                $eventStart = $event->start->dateTime ?? $event->start->date;
                $eventEnd = $event->end->dateTime ?? $event->end->date;
    
                // Check for gaps between events and generate 2-hour slots within those gaps
                if ($lastEndTime < $eventStart) {
                    $availableSlots = array_merge(
                        $availableSlots,
                        $this->generateTimeSlots($lastEndTime, $eventStart, 2)
                    );
                }
    
                // Update the end time to the current event's end
                $lastEndTime = $eventEnd;
            }
    
            // Check for available slots after the last event
            if ($lastEndTime < $timeMax) {
                $availableSlots = array_merge(
                    $availableSlots,
                    $this->generateTimeSlots($lastEndTime, $timeMax, 2)
                );
            }
    
            return $availableSlots;
        } catch (Exception $e) {
            error_log('❌ Error fetching events: ' . $e->getMessage());
            throw new Exception('Unable to fetch slots: ' . $e->getMessage());
        }
    }
    
    private function generateTimeSlots($startTime, $endTime, $durationInHours) {
        $slots = [];
        $currentTime = strtotime($startTime);
        $endTime = strtotime($endTime);
    
        while ($currentTime + ($durationInHours * 3600) <= $endTime) {
            $slotStart = date('H:i', $currentTime);
            $slotEnd = date('H:i', $currentTime + ($durationInHours * 3600));
            $slots[] = "$slotStart - $slotEnd";
    
            // Move to the next slot
            $currentTime += $durationInHours * 3600;
        }
    
        return $slots;
    }
    
    
    public function bookAppointment($calendarId, $summary, $description, $startTime, $endTime) {
        $service = new Google_Service_Calendar($this->getClient());
    
        error_log('📤 Booking Event - Calendar ID: ' . $calendarId);
        error_log('📌 Event Summary: ' . $summary);
        error_log('📝 Event Description: ' . $description);
        error_log('⏳ Start Time: ' . $startTime);
        error_log('⏳ End Time: ' . $endTime);
    
        // Create the event
        $timeZone = date_default_timezone_get();  // Automatically get server timezone
        
        $event = new Google_Service_Calendar_Event([
            'summary' => $summary,
            'description' => $description,
            'start' => ['dateTime' => $startTime, 'timeZone' => $timeZone],
            'end' => ['dateTime' => $endTime, 'timeZone' => $timeZone],
        ]);
    
        try {
            error_log('📤 Attempting to insert event into Google Calendar...');
            $insertedEvent = $service->events->insert($calendarId, $event);
            
            error_log('✅ Event successfully created! Event ID: ' . $insertedEvent->getId());
            return $insertedEvent;
        } catch (Exception $e) {
            error_log('❌ Google Calendar API Error: ' . $e->getMessage());
            throw new Exception('Failed to create event: ' . $e->getMessage());
        }
    }
    
    
    
    
    // DEBUG ACTION DELETE EVENTUALLY

    public function debugListCalendars() {
        try {
            $service = new Google_Service_Calendar($this->getClient());
            $calendarList = $service->calendarList->listCalendarList();
    
            foreach ($calendarList->getItems() as $calendar) {
                error_log("📆 Found Calendar: " . $calendar->getSummary() . " (" . $calendar->getId() . ")");
            }
    
            return '✅ Calendars listed in logs.';
        } catch (Exception $e) {
            error_log('❌ Failed to fetch calendar list: ' . $e->getMessage());
            return '❌ Failed to fetch calendar list. Check error logs.';
        }
    }
    // DEBUG ACTION DELETE EVENTUALLY

}