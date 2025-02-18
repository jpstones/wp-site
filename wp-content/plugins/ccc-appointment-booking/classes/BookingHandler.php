<?php

class BookingHandler {
    private $googleCalendarClient;

    public function __construct() {
        $this->googleCalendarClient = new GoogleCalendarClient();  // Assumes this handles authentication
    }

    /**
     * Checks if the given time slot is available by querying the calendar for conflicts.
     *
     * @param string $calendarId The Google Calendar ID.
     * @param string $startDateTime The start of the time slot (RFC3339 format).
     * @param string $endDateTime The end of the time slot (RFC3339 format).
     * @return bool True if available, false if a conflict is found.
     */
    public function isTimeSlotAvailable($calendarId, $startDateTime, $endDateTime) {
        $events = $this->googleCalendarClient->getEvents($calendarId, $startDateTime, $endDateTime);

        // If any events are found within the time range, return false
        return count($events) === 0;
    }

    /**
     * Handles booking an appointment in the Google Calendar.
     *
     * @param string $calendarId The Google Calendar ID.
     * @param int $userId The user ID of the person booking.
     * @param string $timeSlot The start time of the appointment (user-selected).
     * @return object|null The created event or null if booking failed.
     */
    public function handleBooking($calendarId, $userId, $timeSlot) {
        $user = get_userdata($userId);
        $summary = 'Appointment with ' . $user->display_name;
        $description = 'Session booked through CCC platform';

        // Convert selected time slot to event start and end times
        $startTime = $timeSlot;
        $endTime = date('Y-m-d\TH:i:s', strtotime($timeSlot) + 3600);  // 1-hour session

        // Check if the time slot is available
        if (!$this->isTimeSlotAvailable($calendarId, $startTime, $endTime)) {
            return null;  // Return null if the slot is already taken
        }

        // Book the appointment
        return $this->googleCalendarClient->bookAppointment(
            $calendarId,
            $summary,
            $description,
            $startTime,
            $endTime
        );
    }
}