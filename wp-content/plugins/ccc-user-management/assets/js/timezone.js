jQuery(document).ready(function ($) {
    $('#timezone-form').on('submit', function (e) {
        e.preventDefault();  // Prevent the form from reloading the page

        const selectedTimezone = $('#user-timezone').val();
        $('#timezone-message').text('Saving your time zone...').css('color', 'blue');

        // Send the selected time zone to the server
        $.ajax({
            url: timezoneAjax.ajaxurl,
            method: 'POST',
            dataType: 'json',  // Ensure JSON data format is specified correctly
            data: {
                action: 'save_user_timezone',
                user_timezone: selectedTimezone
            },
            success: function (response) {
                console.log('🛰️ Full Server Response:', response);  // Log the full response
                
                if (response.success) {
                    $('#timezone-message').text(response.data.message).css('color', 'green');
                } else {
                    $('#timezone-message').text(response.data?.message || '❌ Unknown error').css('color', 'red');
                }
            },  // Fix: Add a comma here before the error block
            error: function () {
                $('#timezone-message').text('❌ Error saving time zone. Please try again.').css('color', 'red');
            }
        });
    });
});