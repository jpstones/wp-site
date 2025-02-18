// START DATE AND TIME PICKER

jQuery(document).ready(function ($) {
    // Initialize date picker if necessary (uncomment if you need jQuery UI Datepicker)
    // $('#appointment-date').datepicker({
    //     dateFormat: 'yy-mm-dd',
    //     onSelect: function (dateText) {
    //         $(this).trigger('change');  // Trigger the change event after a date is selected
    //     }
    // });

    // Listen for changes to the date input
    $('#appointment-date').on('change', function () {
        const selectedDate = $(this).val();  // Get the selected date
        console.log('📅 Selected Date:', selectedDate);  // Log the selected date

        if (!selectedDate) {
            alert('Please select a date.');
            return;
        }

        // Fetch available slots for the selected date
        $.post(ajaxData.ajaxurl, {
            action: 'get_available_slots',
            date: selectedDate,
        }, function (response) {
            console.log('📋 Server Response:', response);  // Log the server response
            $('#available-slots').empty();  // Clear previous slots

            if (response.success) {
                response.data.forEach(slot => {
                    $('#available-slots').append(`<div class="time-slot">${slot}</div>`);
                });
            } else {
                $('#available-slots').append(`<p>${response.data}</p>`);
            }
        }).fail(function () {
            alert('❌ Error fetching slots.');
        });
    });

    // Handle time slot selection and booking
    $('#available-slots').on('click', '.time-slot', function () {
        const selectedSlot = $(this).text();
        const selectedDate = $('#appointment-date').val();
        const selectedCoach = $('#coach-selection').val();  // Get the selected coach from dropdown
    
        if (!selectedSlot) {
            alert('❌ No time slot selected. Please select a time slot.');
            return;
        }
    
        if (!selectedDate) {
            alert('❌ No date selected. Please select a date.');
            return;
        }
    
        if (!selectedCoach) {
            alert('❌ No coach selected. Please select a coach.');
            return;
        }
    
        // Log the data being sent for debugging
        console.log('📤 POST Data:', {
            action: 'book_appointment',
            time_slot: selectedSlot,
            date: selectedDate,
            coach_id: selectedCoach
        });
        
        console.log('🛠 Sending Booking Request:', {
            action: 'book_appointment',
            time_slot: selectedSlot,
            date: selectedDate,
            coach_id: selectedCoach
        });

        // Send the booking request via AJAX
        $.post(ajaxData.ajaxurl, {
            action: 'book_appointment',
            time_slot: selectedSlot,
            date: selectedDate,
            coach_id: selectedCoach  // Send coach_id with the request
        }, function (response) {
            console.log('📋 Booking Response:', response);  // Log the server response
    
            if (response.success) {
                alert('✅ Appointment booked successfully!');
            } else {
                alert('❌ Booking failed: ' + response.data.message);
            }
        }).fail(function (error) {
            console.error('❌ Booking Request Failed:', error);  // Log any AJAX errors
            alert('❌ An error occurred while booking the appointment.');
        });
    });
});

// END DATE AND TIME PICKER


// START TEST GCAL CONNECTION

document.addEventListener('DOMContentLoaded', function () {
    fetch(ajaxData.ajaxurl + '?action=check_calendar_api_status')
        .then(response => response.json())
        .then(data => {
            const statusDiv = document.createElement('div');
            statusDiv.style.marginTop = '20px';
            statusDiv.style.fontWeight = 'bold';

            if (data.success) {
                statusDiv.innerHTML = data.data;  // Use innerHTML to render links
                statusDiv.style.color = 'green';
            } else {
                statusDiv.innerHTML = data.data || '❌ Unknown error connecting to the API.';
                statusDiv.style.color = 'red';
            }

            document.body.prepend(statusDiv);
        })
        .catch(err => {
            console.error('AJAX error:', err);
        });
});

// END TEST GCAL CONNECTION