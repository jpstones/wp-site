<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<?php
$current_user_id = get_current_user_id();
$calendar_id = get_user_meta($current_user_id, 'google_calendar_id', true);
$calendar_token = get_user_meta($current_user_id, 'google_calendar_token', true);

// If the user is missing a calendar, show the button
if (empty($calendar_id) || empty($calendar_token)) {
    $oauth_url = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query([
        'client_id'     => '1060911259617-c4g6mns3sola5nvm1j198vlc3ubu9qa1.apps.googleusercontent.com',
        'redirect_uri'  => 'https://chronicconditionsclinic.com/oauth-callback-test',
        'response_type' => 'code',
        'scope'         => 'https://www.googleapis.com/auth/calendar https://www.googleapis.com/auth/calendar.events https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
        'access_type'   => 'offline',
        'state'         => $current_user_id,
        'prompt'        => 'consent'
    ]);
    ?>
    <div class="connect-calendar-box">
        <p>⚠️ <strong>Your Google Calendar is not connected.</strong></p>
        <a href="<?php echo esc_url($oauth_url); ?>" class="connect-calendar-btn">📅 Connect Your Calendar</a>
    </div>
    <style>
        .connect-calendar-box {
            background: #fff3cd;
            padding: 15px;
            border: 1px solid #ffecb5;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 20px;
        }
        .connect-calendar-btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
        }
        .connect-calendar-btn:hover {
            background: #0056b3;
        }
    </style>
    <?php
}
?>

<div class="booking-wrapper">
    <!-- Coach selection -->
    <label for="coach-selection">Select a Coach:</label>
    <select id="coach-selection">
        <option value="">-- Select a Coach --</option>
        <?php 
        $member_id = get_current_user_id();
        $primary_coach = get_primary_clinician($member_id);
        
        // Display the coach options if a coach is assigned
        if ($primary_coach) {
            $calendar_id = get_user_meta($primary_coach->ID, 'google_calendar_id', true);
            
            // Debugging: Log the calendar ID
            error_log("🎯 Coach Calendar ID: " . $calendar_id);
            
            // Output the dropdown option
            echo '<option value="' . esc_attr($calendar_id) . '">' . esc_html($primary_coach->display_name) . '</option>';
        } else {
            echo '<option value="">No coach assigned yet</option>';
        }
        ?>
    </select>

    <!-- Date picker -->
    <label for="appointment-date">Select a Date:</label>
    <input type="text" id="appointment-date" placeholder="Pick a date" readonly>

    <!-- Available time slots -->
    <div id="available-slots">
        <p>Please select a date to view available slots.</p>
    </div>

    <!-- Book Now button -->
    <button id="book-now" disabled>Book Appointment</button>
</div>

<script>
    // Initialize the flatpickr date picker
    flatpickr('#appointment-date', {
        dateFormat: 'Y-m-d',
        minDate: 'today',  // Disable past dates
        onChange: function(selectedDates, dateStr) {
            fetchAvailableSlots(dateStr);
        }
    });

    function fetchAvailableSlots(date) {
        const selectedCoach = document.getElementById('coach-selection').value;
        const availableSlotsDiv = document.getElementById('available-slots');

        // Clear previous slots and show loading message
        availableSlotsDiv.innerHTML = '<p>Loading available slots...</p>';

        // Validate that a coach is selected
        if (!selectedCoach) {
            availableSlotsDiv.innerHTML = '<p>Please select a coach to see available slots.</p>';
            document.getElementById('book-now').disabled = true;
            return;
        }

        // Fetch available slots for the selected date and coach
        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=get_available_slots&date=' + date + '&calendar_id=' + selectedCoach)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    availableSlotsDiv.innerHTML = '<ul>';
                    data.slots.forEach(slot => {
                        availableSlotsDiv.innerHTML += `
                            <li>
                                <input type="radio" name="time_slot" value="${slot}" id="${slot}">
                                <label for="${slot}">${slot}</label>
                            </li>`;
                    });
                    availableSlotsDiv.innerHTML += '</ul>';
                    document.getElementById('book-now').disabled = false;  // Enable booking button
                } else {
                    availableSlotsDiv.innerHTML = '<p>No available slots for this date.</p>';
                    document.getElementById('book-now').disabled = true;
                }
            })
            .catch(error => {
                availableSlotsDiv.innerHTML = '<p>Error loading slots. Please try again.</p>';
            });
    }

    // Handle the booking request when the Book Now button is clicked
    document.getElementById('book-now').addEventListener('click', function () {
        const selectedSlot = document.querySelector('input[name="time_slot"]:checked');
        const selectedDate = document.getElementById('appointment-date').value;
        const selectedCoach = document.getElementById('coach-selection').value;

        if (!selectedSlot) {
            alert('Please select a time slot.');
            return;
        }

        // Send the booking request via AJAX
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: 'book_appointment',
                appointment_date: selectedDate,
                time_slot: selectedSlot.value,
                calendar_id: selectedCoach  // Use the coach's calendar ID for booking
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Appointment booked successfully! You can view the event in the coach\'s Google Calendar.');
            } else {
                alert('❌ Booking failed: ' + (data.message || 'Unknown error.'));
            }
        })
        .catch(error => {
            alert('❌ An error occurred while booking the appointment.');
        });
    });
</script>