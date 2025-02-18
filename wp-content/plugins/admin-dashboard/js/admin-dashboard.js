document.addEventListener('DOMContentLoaded', function () {
    console.log("Admin Dashboard JS loaded.");

    // Display notification if one exists from sessionStorage
    const storedNotification = sessionStorage.getItem('notificationData');
    if (storedNotification) {
        const { message, type, undoAction } = JSON.parse(storedNotification);
        displayNotification(message, type, undoAction);
        sessionStorage.removeItem('notificationData');
    }

    let selectedClientId = null;

    document.querySelectorAll('.select-client').forEach(button => {
        button.addEventListener('click', function () {
            selectedClientId = this.getAttribute('data-client-id');
            console.log(`🔍 Selected Client ID: ${selectedClientId}`);
            document.querySelector('.clinician-list').style.display = 'block';

            document.querySelectorAll('.assign-clinician').forEach(coachButton => {
                coachButton.setAttribute('data-client-id', selectedClientId);
            });
        });
    });

    document.querySelectorAll('.assign-clinician').forEach(button => {
        button.addEventListener('click', function () {
            const clientId = this.getAttribute('data-client-id');
            const clinicianId = this.getAttribute('data-clinician-id');

            if (clientId && clinicianId) {
                console.log(`🔄 Assigning Client ID ${clientId} to Clinician ID ${clinicianId}...`);

                const formData = new FormData();
                formData.append('action', 'admin_dashboard_assign_client');
                formData.append('client_id', clientId);
                formData.append('clinician_id', clinicianId);

                console.log("📡 Sending Data: ", [...formData.entries()]);

                fetch(adminDashboard.ajaxurl, {
                    method: 'POST',
                    body: formData
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP status ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.data) {
                            console.log("✅ Success! Received Data:", data.data);

                            // Store the notification data in sessionStorage
                            sessionStorage.setItem('notificationData', JSON.stringify({
                                message: data.data.message,
                                type: 'success',
                                undoAction: data.data.undoAction
                            }));

                            // Refresh the page
                            location.reload();
                        } else {
                            console.error("❌ Error: ", data);
                            displayNotification("Error! An unknown error occurred.", 'error');
                        }
                    })
                    .catch(error => {
                        console.error("❌ AJAX Error: ", error);
                        displayNotification("Error! An unknown error occurred.", 'error');
                    });
            }
        });
    });

    function displayNotification(message, type = "success", undoAction = null) {
        console.log("🔍 Showing Notification:", { message, undoAction });
    
        // Clear previous notifications
        document.querySelectorAll('.notification-box').forEach(box => box.remove());
    
        // Create new notification
        const notificationBox = document.createElement("div");
        notificationBox.className = `notification-box ${type}`;
        notificationBox.innerHTML = `<p>${message}</p>`;
    
        if (undoAction) {
            const undoButton = document.createElement("button");
            undoButton.className = "undo-button";
            undoButton.innerText = "Undo";
            undoButton.onclick = function () {
                undoAssignment(undoAction.clientId, undoAction.clinicianId);
            };
            notificationBox.appendChild(undoButton);
        }
    
        document.body.appendChild(notificationBox);
        setTimeout(() => notificationBox.remove(), 5000);
    }
    
    function undoAssignment(clientId, clinicianId) {
        console.log("🔄 Sending undo request...");
    
        const formData = new FormData();
        formData.append('action', 'undo_assignment');
        formData.append('client_id', clientId);
        formData.append('clinician_id', clinicianId);
    
        fetch(adminDashboard.ajaxurl, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log("✅ Undo action completed successfully.");
    
                    // Store the success message in sessionStorage
                    sessionStorage.setItem('notificationData', JSON.stringify({
                        message: "Success! Assignment successfully undone.",
                        type: 'success'
                    }));
    
                    // Refresh the page to reflect changes
                    location.reload();
                } else {
                    console.error("❌ Undo failed: ", data);
                    displayNotification("Error! Failed to undo assignment.", 'error');
                }
            })
            .catch(error => {
                console.error("❌ AJAX Error: ", error);
                displayNotification("Error! Undo request failed.", 'error');
            });
    }
    let clinicianFilter = document.getElementById('clinician-filter');
    if (clinicianFilter) {
        clinicianFilter.addEventListener('change', function () {
            const clinicianId = this.value;
            console.log(`🔍 Filter selected: Clinician ID ${clinicianId}`);
    
            const formData = new FormData();
            formData.append('action', 'get_premium_members');
            formData.append('clinician_id', clinicianId);
    
            console.log("📡 Sending AJAX Request for Filtered Members...");
    
            fetch(adminDashboard.ajaxurl, {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    console.log("✅ AJAX Response Received:", response);
                    if (!response.ok) {
                        throw new Error(`HTTP status ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log("✅ Filtered Members Data:", data);
                    if (data.success) {
                        updateMembersTable(data.data);
                    } else {
                        console.error("❌ Failed to fetch filtered members:", data);
                    }
                })
                .catch(error => console.error("❌ AJAX Error: ", error));
        });
    } else {
        console.log("ℹ️ #clinician-filter element not found on the page.");
    }
    
    function updateMembersTable(members) {
        const tbody = document.querySelector('#premium-members-table tbody');
        tbody.innerHTML = ''; // Clear existing rows
    
        if (members.length === 0) {
            const noDataRow = document.createElement('tr');
            noDataRow.innerHTML = `<td colspan="6">No members found for this clinician.</td>`;
            tbody.appendChild(noDataRow);
            console.log("ℹ️ No members found, displaying message.");
            return;
        }
    
        // Populate table with filtered members
        members.forEach(member => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${member.name}</td>
                <td>${member.activity}</td>
                <td>${member.effort}</td>
                <td>${member.health_trend}</td>
                <td>${member.member_since}</td>
                <td><button>View Profile</button></td>
            `;
            tbody.appendChild(row);
        });
    
        console.log("✅ Members table updated.");
    }
});