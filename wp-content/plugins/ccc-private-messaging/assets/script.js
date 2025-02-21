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


// START MESSAGE LOADING
function formatMessageDate(dateString) {
    const messageDate = new Date(dateString);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    // Reset hours to compare just the dates
    const messageDateOnly = new Date(messageDate.getFullYear(), messageDate.getMonth(), messageDate.getDate());
    const todayOnly = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    const yesterdayOnly = new Date(yesterday.getFullYear(), yesterday.getMonth(), yesterday.getDate());

    if (messageDateOnly.getTime() === todayOnly.getTime()) {
        return 'Today';
    } else if (messageDateOnly.getTime() === yesterdayOnly.getTime()) {
        return 'Yesterday';
    } else {
        // For older dates, show full date
        return messageDate.toLocaleDateString('en-US', { 
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
}

function loadMessages(thread_id) {
    console.log('Loading messages for thread:', thread_id);
    
    $.ajax({
        url: pm_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'load_messages',
            thread_id: thread_id
        },
        success: function(response) {
            if (response.success && response.data.messages) {
                const messages = response.data.messages;
                let messageHTML = '';
                let currentDate = '';
                
                messages.forEach(function(message) {
                    // Check if we need to add a date separator
                    const messageDate = formatMessageDate(message.sent_at);
                    if (messageDate !== currentDate) {
                        messageHTML += `
                            <div class="pm-date-separator">
                                <span>${messageDate}</span>
                            </div>
                        `;
                        currentDate = messageDate;
                    }

                    const isCurrentUser = message.sender_id == pm_ajax.current_user_id;
                    const messageClass = isCurrentUser ? 'pm-message-sender' : 'pm-message-recipient';
                    
                    try {
                        const messageContent = JSON.parse(message.message);
                        
                        // Convert URL to HTTPS if needed
                        if (messageContent.file_url) {
                            messageContent.file_url = messageContent.file_url.replace('http://', 'https://');
                        }
                        
                        if (messageContent.type === 'audio') {
                            messageHTML += `
                                <div class="pm-message-bubble ${messageClass}">
                                    <div class="voice-message">
                                        <audio controls src="${messageContent.file_url}"></audio>
                                    </div>
                                </div>
                            `;
                        } else if (messageContent.type === 'image') {
                            messageHTML += `
                                <div class="pm-message-bubble ${messageClass}">
                                    <div class="message-file-info">
                                        <i class="fas fa-paperclip"></i>
                                        <span class="file-name">${messageContent.file_name}</span>
                                    </div>
                                    <div class="image-message">
                                        <img src="${messageContent.file_url}" alt="${messageContent.file_name}" />
                                    </div>
                                </div>
                            `;
                        } else if (messageContent.file_name && messageContent.file_name.toLowerCase().endsWith('.pdf')) {
                            messageHTML += `
                                <div class="pm-message-bubble ${messageClass}">
                                    <div class="message-file-info">
                                        <i class="fas fa-file-pdf"></i>
                                        <span class="file-name">${messageContent.file_name}</span>
                                    </div>
                                    <a href="${messageContent.file_url}" target="_blank" class="pdf-link">
                                        <div class="pdf-preview">
                                            <div class="pdf-icon">
                                                <i class="fas fa-file-pdf"></i>
                                                <div class="pdf-extension">.PDF</div>
                                            </div>
                                            <div class="pdf-action">Click to open PDF</div>
                                        </div>
                                    </a>
                                </div>
                            `;
                        } else {
                            messageHTML += `
                                <div class="pm-message-bubble ${messageClass}">
                                    <p>${message.message}</p>
                                </div>
                            `;
                        }
                    } catch (e) {
                        messageHTML += `
                            <div class="pm-message-bubble ${messageClass}">
                                <p>${message.message}</p>
                            </div>
                        `;
                    }
                });
                
                $('#pm-chat-thread').html(messageHTML);
                
                // Add chat input if it doesn't exist
                if ($('.pm-chat-input').length === 0) {
                    console.log('Creating chat input...'); // Debug log
                    $('#pm-chat-container').append(createChatInput());
                    // Initialize audio recording after creating input
                    initializeAudioRecording();
                }
                
                scrollToBottom();
            } else {
                console.error('Invalid response format:', response);
                $('#pm-chat-thread').html('<p>Error loading messages</p>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Ajax error:', error);
            $('#pm-chat-thread').html('<p>Error loading messages</p>');
        }
    });
}

function createChatInput() {
    console.log('Creating chat input HTML'); // Debug log
    return `
        <div class="pm-chat-input-container">
            <div class="pm-chat-input">
                <textarea id="pm-message-input" placeholder="Type your message..."></textarea>
                <div class="pm-chat-actions">
                    <button id="record-button" type="button" class="pm-action-button">
                        <i class="fas fa-microphone"></i>
                    </button>
                    <button id="send-button" type="button" class="pm-action-button">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
}

// START AUDIO RECORDING MANAGEMENT
let mediaRecorder = null;
let audioChunks = [];
let isRecording = false;
let timerInterval;
let startTime;

function createRecordingOverlay() {
    return `
        <div class="recording-overlay">
            <div class="recording-timer-container">
                <div class="recording-indicator"></div>
                <div class="recording-timer">00:00</div>
            </div>
            <button class="cancel-recording">Cancel</button>
        </div>
    `;
}

function initializeAudioRecording() {
    // Add overlay to DOM if it doesn't exist
    if (!document.querySelector('.recording-overlay')) {
        $('.pm-chat-input-container').append(createRecordingOverlay());
    }
    
    // Make sure we start with the text input view
    $('.recording-overlay').hide();
    $('#pm-message-content').show();
    isRecording = false;
    
    const recordButton = document.getElementById('pm-record-voice-note');
    if (!recordButton) {
        console.log('Record button not found');
        return;
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        console.log('Audio recording not supported');
        recordButton.style.display = 'none';
        return;
    }

    recordButton.addEventListener('click', async () => {
        try {
            if (!isRecording) {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];

                mediaRecorder.addEventListener('dataavailable', event => {
                    audioChunks.push(event.data);
                });

                mediaRecorder.addEventListener('stop', () => {
                    if (audioChunks.length > 0) {
                        const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                        const threadId = $('.pm-contact.pm-selected-contact').data('thread-id');
                        if (threadId) {
                            handleAudioUpload(audioBlob, threadId);
                        }
                    }
                    stream.getTracks().forEach(track => track.stop());
                });

                mediaRecorder.start();
                isRecording = true;
                recordButton.classList.add('recording');
                
                // Show overlay and start timer
                startRecording();

            } else {
                stopRecording();
            }
        } catch (error) {
            console.error('Error accessing microphone:', error);
            alert('Could not access microphone. Please ensure you have granted permission to use the microphone.');
        }
    });

    $('.cancel-recording').click(() => {
        stopRecording(true);
    });

    $('.stop-recording').click(() => {
        stopRecording();
    });
}

function startRecording() {
    $('.pm-chat-input-container').addClass('recording-active');
    $('#pm-record-voice-note').addClass('recording');
    $('.recording-overlay').show();
    startTime = Date.now();
    timerInterval = setInterval(updateTimer, 1000);
    updateTimer();
}

function updateTimer() {
    if (!startTime) return; // Guard against undefined startTime
    const elapsed = Math.floor((Date.now() - startTime) / 1000);
    const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
    const seconds = (elapsed % 60).toString().padStart(2, '0');
    $('.recording-timer').text(`${minutes}:${seconds}`);
}

function stopRecording(cancel = false) {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        if (cancel) {
            mediaRecorder.stop();
            audioChunks = [];
        } else {
            mediaRecorder.stop();
        }
    }
    
    clearInterval(timerInterval);
    startTime = null;
    $('.recording-overlay').hide();
    isRecording = false;
    const recordButton = document.getElementById('pm-record-voice-note');
    if (recordButton) {
        recordButton.classList.remove('recording');
    }
    $('.pm-chat-input-container').removeClass('recording-active');
}

// Make sure this runs when the page loads
$(document).ready(function() {
    initializeAudioRecording();
});
// END AUDIO RECORDING MANAGEMENT


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

    // START FILE UPLOAD MANAGEMENT
    $('#pm-attach-file').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log("Attach button clicked");
        
        // Remove any existing file input first
        $('#file-upload').remove();
        
        // Create new file input
        $('<input/>', {
            type: 'file',
            id: 'file-upload',
            style: 'display: none'
        }).appendTo('body').click();
    });

    // File change handler (attached to document to catch dynamically created input)
    $(document).on('change', '#file-upload', function() {
        let file = this.files[0];
        console.log("File selected:", file);

        if (!file) {
            console.log("No file selected");
            return;
        }

        // Validate file type and size
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        const maxSize = 5 * 1024 * 1024; // 5MB

        if (!allowedTypes.includes(file.type)) {
            alert('Invalid file type. Please upload an image (JPG, PNG, GIF) or PDF.');
            return;
        }

        if (file.size > maxSize) {
            alert('File is too large. Maximum size is 5MB.');
            return;
        }

        let formData = new FormData();
        formData.append('file', file);
        formData.append('action', 'pm_upload_file');
        formData.append('thread_id', $('.pm-contact.pm-selected-contact').data('thread-id'));
        formData.append('sender_id', pm_ajax.current_user_id);

        // Show loading state
        const loadingBubble = `
            <div class="pm-message-bubble pm-message-sender">
                <p>Uploading ${file.name}...</p>
            </div>
        `;
        $('#pm-chat-thread').append(loadingBubble);
        scrollToBottom();

        $.ajax({
            url: pm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log("Upload response:", response);
                
                if (response.success) {
                    // Remove loading bubble
                    $('#pm-chat-thread .pm-message-bubble:last-child').remove();
                    
                    // Create preview based on file type
                    let previewContent = '';
                    if (file.type.startsWith('image/')) {
                        previewContent = `
                            <div class="attachment-header">
                                <img src="${pm_ajax.plugins_url}/attach-icon.svg" class="attachment-icon" alt="Attachment">
                                <span>${file.name}</span>
                            </div>
                            <div class="attachment-preview">
                                <img src="${response.data.file_url}" class="pm-image-preview" alt="Uploaded image">
                            </div>
                        `;
                    } else {
                        previewContent = `
                            <div class="attachment-header">
                                <img src="${pm_ajax.plugins_url}/attach-icon.svg" class="attachment-icon" alt="Attachment">
                                <a href="${response.data.file_url}" target="_blank">${file.name}</a>
                            </div>
                        `;
                    }

                    // Add message bubble with preview
                    $('#pm-chat-thread').append(`
                        <div class="pm-message-bubble pm-message-sender">
                            ${previewContent}
                        </div>
                    `);
                    
                    // Clear the file input
                    $('#file-upload').val('');
                    scrollToBottom();
                } else {
                    // Remove loading bubble and show error
                    $('#pm-chat-thread .pm-message-bubble:last-child').remove();
                    alert('File upload failed: ' + response.data.error);
                }
            },
            error: function(xhr, status, error) {
                // Remove loading bubble and show error
                $('#pm-chat-thread .pm-message-bubble:last-child').remove();
                console.error("Upload error:", error);
                alert('An error occurred while uploading the file.');
            }
        });
    });
    // END FILE UPLOAD MANAGEMENT

    // Add periodic message checking
    function initializeMessagePolling() {
        setInterval(() => {
            if ($('.pm-selected-contact').length) {
                let thread_id = $('.pm-selected-contact').data('thread-id');
                loadMessages(thread_id);
            }
        }, 10000); // Check every 10 seconds
    }

    // START AUDIO RECORDING MANAGEMENT
    function handleAudioUpload(audioBlob, thread_id) {
        console.log('Handling audio upload for thread:', thread_id); // Debug log
        
        const formData = new FormData();
        formData.append('action', 'pm_upload_audio');
        formData.append('audio', audioBlob, 'voice-message.webm');
        formData.append('thread_id', thread_id);
        formData.append('sender_id', pm_ajax.current_user_id);

        $.ajax({
            url: pm_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Audio upload response:', response); // Debug log
                if (response.success) {
                    // Don't try to send another message - the PHP handler already saved it
                    loadMessages(thread_id); // Just reload the messages
                } else {
                    console.error('Upload failed:', response);
                    alert('Failed to upload audio message. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Upload failed:', error);
                alert('Failed to upload audio message. Please try again.');
            }
        });
    }

    // Function to send text messages (used by both text and audio messages)
    function sendTextMessage(message, thread_id) {
        $.ajax({
            url: pm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'pm_send_message',
                message: message,
                thread_id: thread_id,
                sender_id: pm_ajax.current_user_id
            },
            success: function(response) {
                if (response.success) {
                    loadMessages(thread_id);
                } else {
                    console.error('Message send failed:', response);
                    alert('Failed to send message. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Message send failed:', error);
                alert('Failed to send message. Please try again.');
            }
        });
    }

    // Initialize when document is ready AND when switching threads
    $(document).ready(function() {
        console.log('Document ready, initializing messaging...');
        console.log('AJAX URL:', pm_ajax.ajax_url);
        console.log('Current User ID:', pm_ajax.current_user_id);
        
        initializeAudioRecording();
        
        // Load initial messages if there's a selected contact
        const selectedContact = $('.pm-contact.pm-selected-contact');
        if (selectedContact.length) {
            const threadId = selectedContact.data('thread-id');
            console.log('Initial selected contact thread ID:', threadId);
            if (threadId) {
                loadMessages(threadId);
            }
        }
        
        // Handle contact selection
        $(document).on('click', '.pm-contact', function() {
            const threadId = $(this).data('thread-id');
            console.log('Contact clicked, loading thread ID:', threadId);
            if (threadId) {
                loadMessages(threadId);
            }
        });

        // Re-initialize when switching threads
        $(document).on('pm-thread-loaded', function() {
            console.log('Thread loaded, reinitializing audio...'); // Debug log
            initializeAudioRecording();
        });
    });
    // END AUDIO RECORDING MANAGEMENT
});