// START MATCH ID TO MESSAGE THREAD
console.log("PM Script loaded successfully!");
jQuery(document).ready(function($) {
    // Helper to get query parameters
    function getQueryParam(param) {
        let urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(param);
    }
    
    // Trigger file input when attach icon is clicked
    $('#pm-attach-file').on('click', function () {
        console.log("Attach icon clicked!");  // Debug to ensure the event fires
        $('#file-upload').click();  // Open the file dialog
    });

    // Check for client_id and map it to a thread_id
    let clientId = getQueryParam('client_id');
    let targetThread = null;

    // Clear any previously selected contacts first
    $('.pm-contact').removeClass('pm-selected-contact');
    
    // Find the corresponding contact and highlight it
    if (clientId) {
        $('.pm-contact').each(function() {
            if ($(this).data('user-id').toString() === clientId) {
                targetThread = $(this).data('thread-id');
                $(this).addClass('pm-selected-contact');
    
                // Debug log to check if correct thread is selected
                console.log("Target thread selected: ", targetThread);
    
                return false;  // Exit the loop once we find the right contact
            }
        });
    }
    
    // If a thread is found, load messages; otherwise, load the first contact by default
    if (targetThread) {
        loadMessages(targetThread);
    } else {
        let firstContact = $('.pm-contact').first();
        if (firstContact.length) {
            firstContact.addClass('pm-selected-contact');
            loadMessages(firstContact.data('thread-id'));
        }
    }
    
// END MATCH ID TO MESSAGE THREAD


    // Event handler for contact clicks
    $('.pm-contact').on('click', function() {
        $('.pm-contact').removeClass('pm-selected-contact'); // Clear previous selection
        $(this).addClass('pm-selected-contact');
        
        let thread_id = $(this).data('thread-id');
        let user_id = $(this).data('user-id'); // Get the user ID for the clicked contact

        updateURL(user_id);  // Update the URL dynamically
        loadMessages(thread_id);
    });


// START LOAD MESSAGES

function loadMessages(thread_id) {
    console.log("Thread ID being sent to load messages: ", thread_id);  // Debug log
    $('#pm-chat-thread').html('<p>Loading messages...</p>');  // Show loading indicator

    $.ajax({
        url: pm_ajax.ajax_url,
        method: 'POST',
        data: {
            action: 'load_messages',
            thread_id: thread_id
        },
        success: function (response) {
            console.log("AJAX Response: ", response);
            if (response.success) {
                $('#pm-chat-thread').empty(); // Clear old messages

                let lastDate = null; // Track last message date

                response.data.messages.forEach(function (message) {
                    let messageDate = new Date(message.sent_at).toLocaleDateString();
                    if (messageDate !== lastDate) {
                        $('#pm-chat-thread').append(`<div class="pm-date-separator">${messageDate}</div>`);
                        lastDate = messageDate;
                    }

                    // Correctly determine if the message is from the current user or the recipient
                    let isSender = message.sender_id == pm_ajax.current_user_id;
                    let messageClass = isSender ? 'pm-message-sender' : 'pm-message-recipient';

                    // Render messages or attachments dynamically
                    let content = message.file_url 
                        ? `<p>ðŸ“Ž <a href="${message.file_url}" target="_blank">${message.file_name}</a></p>` 
                        : `<p>${message.message}</p>`;

                    $('#pm-chat-thread').append(
                        `<div class="pm-message-bubble ${messageClass}">
                            ${!isSender ? `<img src="${message.avatar}" class="pm-recipient-avatar" alt="Avatar">` : ''}
                            ${content}
                        </div>`
                    );
                });

                scrollToBottom(); // Scroll to the latest message after loading
            } else {
                $('#pm-chat-thread').html('<p>No messages found.</p>');  // Handle case with no messages
            }
        },
        error: function () {
            alert('Failed to load messages. Please try again.');
        }
    });
}

// Dynamically update the URL when switching contacts

function updateURL(user_id) {
    let newURL = new URL(window.location.href);
    newURL.searchParams.set('client_id', user_id);  // Update the client_id parameter
    history.pushState(null, '', newURL);  // Change the URL without refreshing the page
}

// END LOAD MESSAGES


// START SEND MESSAGES

jQuery(document).ready(function ($) {
    // Disable the send button initially
    $('#pm-send-message').prop('disabled', true).addClass('disabled-button');

    // Monitor input field changes to enable/disable the button
    $('#pm-message-content').on('input', function () {
        if ($(this).val().trim().length > 0) {
            $('#pm-send-message').prop('disabled', false).removeClass('disabled-button');
        } else {
            $('#pm-send-message').prop('disabled', true).addClass('disabled-button');
        }
    });

    // Send message logic
    $('#pm-send-message').on('click', function () {
        let message = $('#pm-message-content').val().trim();
        if (message.length === 0) {
            return;  // Prevent sending empty messages
        }

        let thread_id = $('.pm-contact.pm-selected-contact').data('thread-id');
        let recipient_id = $('.pm-contact.pm-selected-contact').data('user-id');  // Get recipient's user ID
        console.log("Recipient ID being sent:", recipient_id);

        $('#pm-send-message').text('Sending...');  // Feedback during sending

        $.ajax({
            url: pm_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'send_reply',
                thread_id: thread_id,
                recipient_id: recipient_id,  // Send the recipient ID here
                message: message
            },
            success: function (response) {
                if (response.success) {
                    $('#pm-chat-thread').append(response.data.new_message);  // Display the new message
                    $('#pm-message-content').val('');  // Clear input
                    $('#pm-send-message').prop('disabled', true).addClass('disabled-button');  // Disable button again
                    scrollToBottom();  // Scroll to the bottom
                } else {
                    alert('Message failed: ' + response.data.error);
                }
                $('#pm-send-message').text('Send');  // Reset button text
            },
            error: function () {
                alert('An error occurred while sending the message.');
                $('#pm-send-message').text('Send');
            }
        });
    });
});

// END SEND MESSAGES


        // Scroll to the bottom of the chat
    function scrollToBottom() {
        $('#pm-chat-thread').scrollTop($('#pm-chat-thread')[0].scrollHeight);
    }
});



// START FILE UPLOAD MANAGEMENT

$('#file-upload').on('change', function () {
    let file = this.files[0];
    console.log("File input triggered. File detected:", file);  // Debugging
    
    if (!file) {
        console.log("No file selected.");
        return;
    }

    console.log("File selected:", file.name, "Type:", file.type, "Size:", file.size);

    let formData = new FormData();
    formData.append('file', file);
    formData.append('action', 'pm_upload_file');
    formData.append('thread_id', $('.pm-contact.pm-selected-contact').data('thread-id'));
    formData.append('sender_id', pm_ajax.current_user_id);

    // Basic AJAX without preview for now
    $.ajax({
        url: pm_ajax.ajax_url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            console.log("AJAX Response:", response);
            if (response.success) {
                $('#pm-chat-thread').append(
                    `<div class="pm-message-bubble pm-message-sender">
                        📎 <a href="${response.data.file_url}" target="_blank">${file.name}</a>
                    </div>`
                );
                $('#file-upload').val('');  // Clear the input
                scrollToBottom();
            } else {
                console.log("Upload failed:", response.data.error);
                alert('File upload failed: ' + response.data.error);
            }
        },
        error: function (xhr, status, error) {
            console.log("AJAX error:", error);
            alert('An error occurred while uploading the file.');
        }
    });
});

// END FILE UPLOAD MANAGEMENT