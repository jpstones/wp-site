document.addEventListener('DOMContentLoaded', function () {
    console.log('Clinician Dashboard JS loaded.');

    // Check for the update_success query parameter
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('update_success') === '1') {
        const successNotification = document.createElement('div');
        successNotification.id = 'success-notification';
        successNotification.style.backgroundColor = '#d4edda';
        successNotification.style.color = '#155724';
        successNotification.style.padding = '10px';
        successNotification.style.marginBottom = '15px';
        successNotification.style.border = '1px solid #c3e6cb';
        successNotification.textContent = 'Content updated successfully!';

        // Insert the notification above the table
        const table = document.querySelector('.assigned-content-table');
        table.parentNode.insertBefore(successNotification, table);

        // Automatically hide the notification after 5 seconds
        setTimeout(function () {
            successNotification.style.display = 'none';
        }, 5000);

        // Remove the update_success=1 query parameter to prevent showing it on page load again
        urlParams.delete('update_success');
        window.history.replaceState({}, '', `${window.location.pathname}?${urlParams}`);
    }

    const modal = document.getElementById('content-selection-modal');
    const selector = document.getElementById('content-selector');
    const confirmButton = document.getElementById('confirm-selection');

    document.querySelectorAll('.update-content-button').forEach(button => {
        button.addEventListener('click', function () {
            const slot = this.getAttribute('data-slot');
            const memberId = this.getAttribute('data-member-id');

            // Show the modal
            modal.style.display = 'block';

            // Handle content selection
            confirmButton.onclick = function () {
                const selectedPostId = selector.value;
                if (!selectedPostId) {
                    alert('Please select content.');
                    return;
                }

                // Send AJAX request to update content
                fetch(ajax_object.ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'update_client_content',
                        member_id: memberId,
                        slot_number: slot,
                        post_id: selectedPostId,
                        security: ajax_object.security
                    })
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.success) {
                            // Redirect with client_id and success flag
                            const currentUrl = new URL(window.location.href);
                            currentUrl.searchParams.set('update_success', '1');
                            currentUrl.searchParams.set('client_id', memberId);
                            window.location.href = currentUrl.toString();
                        } else {
                            alert('Error: ' + data.data.message);
                        }
                    })
                    .catch((error) => {
                        console.error('AJAX request failed:', error);
                    });
            };
        });
    });
});