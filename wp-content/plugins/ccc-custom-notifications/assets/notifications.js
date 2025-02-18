jQuery(document).ready(function($) {
    function refreshNotifications() {
        $.get(ajaxurl, { action: 'ccc_fetch_notifications' }, function(response) {
            $('#ccc-notifications-widget ul').html(response);
        });
    }

    setInterval(refreshNotifications, 10000); // Refresh every 10 seconds
});


// START IGNORE LINK ON CLICK

jQuery(document).ready(function ($) {

    $('.ccc-notifications-table').on('click', '.ccc-ignore-link', function (e) {
        e.preventDefault();  // Prevent default link behavior

        let notificationId = $(this).data('notification-id');  // Get the notification ID

        // Send AJAX request to mark the notification as ignored
        $.ajax({
            url: ajaxurl,  // WordPress AJAX URL
            type: 'POST',
            data: {
                action: 'ccc_ignore_notification',  // Hook to PHP function
                notification_id: notificationId
            },
            success: function (response) {
                if (response.success) {
                    // Fade out and remove the ignored notification
                    const $row = $(`.ccc-ignore-link[data-notification-id="${notificationId}"]`).closest('tr');
                    $row.fadeOut(300, function () {
                        $(this).remove();  // Remove the row after fade-out

                        // Check if any notifications are left
                        if ($('.ccc-notifications-table tbody tr').length === 0) {
                            $('.ccc-notifications-table').after('<p>No new notifications.</p>');
                            $('.ccc-notifications-table').hide();  // Hide the table
                        }
                    });
                } else {
                    alert('An error occurred while ignoring the notification.');
                }
            },
            error: function () {
                alert('Failed to process the request.');
            }
        });
    });
});

// END IGNORE LINK ON CLICK
