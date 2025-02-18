console.log("✅ CCC Note Management JS is loaded.");

window.onload = function() {
    console.log("✅ Window Loaded");

    // Grab elements
    const submitButton = document.getElementById("submit-note");
    const noteInput = document.getElementById("ccc_note");
    const addCoreIssueButton = document.getElementById("add-core-issue");
    const coreIssuesContainer = document.getElementById("core-issues-container");

    // Check if elements exist before proceeding
    if (!submitButton) {
        console.error("❌ Save Note button not found!");
        return;
    }

    if (!noteInput) {
        console.error("❌ Note input not found!");
        return;
    }

    if (!addCoreIssueButton) {
        console.error("❌ Add Core Issue button not found!");
        return;
    }

    if (!coreIssuesContainer) {
        console.error("❌ Core Issues container not found!");
        return;
    }

    // Modal Elements
    const modal = document.getElementById("core-issue-modal");
    const modalCloseButton = document.getElementById("modal-close");
    const modalSaveButton = document.getElementById("save-core-issue");
    const modalName = document.getElementById("core-issue-name");
    const modalSeverity = document.getElementById("core-issue-severity");
    const modalFirstAppearance = document.getElementById("core-issue-first-appearance");
    const modalCuriosity = document.getElementById("core-issue-curiosity");
    const modalCompassion = document.getElementById("core-issue-compassion");

    // Check modal elements
    if (!modal || !modalCloseButton || !modalSaveButton || !modalName || 
        !modalSeverity || !modalFirstAppearance || !modalCuriosity || !modalCompassion) {
        console.error("❌ One or more modal elements not found!");
        return;
    }

    let coreIssuesData = [];
    let activeCoreIssues = {};

    // ✅ Handle "Add Core Issue" Button Click
    addCoreIssueButton.addEventListener("click", function (event) {
        event.preventDefault();
        console.log("🔥 Add Core Issue button clicked!");

        // Open Modal for adding new core issue
        modal.style.display = "flex"; // Show modal overlay
    });

    // ✅ Close the modal when clicking "X" button
    modalCloseButton.addEventListener("click", function () {
        modal.style.display = "none"; // Hide modal
    });

    // ✅ Save the new core issue (severity check)
    modalSaveButton.addEventListener("click", function () {
        const issueName = modalName.value.trim();
        const severity = modalSeverity.value;  // Get the selected severity value
        const firstAppearance = modalFirstAppearance.value;
        const curiosity = modalCuriosity.value;
        const compassion = modalCompassion.value;
    
        if (!issueName) {
            alert("Please enter an issue name.");
            return;
        }
    
        const issueId = `new_${Date.now()}`; // Temporary ID for new issues
        const issueData = {
            id: issueId,
            name: issueName,
            severity: severity,
            first_appearance: firstAppearance,
            curiosity: curiosity,
            compassion: compassion
        };
    
        coreIssuesData.push(issueData);
        activeCoreIssues[issueId] = issueData;
    
        // Debugging: Log coreIssuesData to ensure it's populated correctly
        console.log("Core Issues Data after saving:", coreIssuesData);
    
        // Close modal after saving
        modal.style.display = "none";
    
        // Add new row with issue name (disabled) and severity dropdown
        const newRow = document.createElement("div");
        newRow.classList.add("core-issue-row");
        newRow.style.display = "flex";
        newRow.style.width = "100%";
        newRow.style.alignItems = "center";
        newRow.style.justifyContent = "space-between";
        newRow.style.padding = "10px 0";
    
        const inputField = document.createElement("input");
        inputField.type = "text";
        inputField.value = issueName;
        inputField.disabled = true;
        inputField.style = "flex: 2; padding: 8px; border: 1px solid #ccc; background: none; font-size: 16px;";
    
        // Create the severity dropdown and select the correct severity
        const selectDropdown = document.createElement("select");
        selectDropdown.name = "core_issue_severity[]";
        selectDropdown.style = "flex: 1; padding: 8px; font-size: 16px;";
    
        // Loop through severity levels to add them to the dropdown
        for (let i = 1; i <= 5; i++) {
            const option = document.createElement("option");
            option.value = i;
            option.textContent = i + (i === 1 ? " - Mild" : i === 5 ? " - Severe" : "");
            if (i == severity) {
                option.selected = true; // Select the correct severity based on what was chosen in the modal
            }
            selectDropdown.appendChild(option);
        }
    
        newRow.appendChild(inputField);
        newRow.appendChild(selectDropdown);
    
        // Delete icon (for removal)
        const deleteIcon = document.createElement("img");
        deleteIcon.src = cccData.trashIcon;
        deleteIcon.alt = "Delete";
        deleteIcon.classList.add("delete-core-issue");
        deleteIcon.style = "width: 24px; height: 24px; cursor: pointer; margin-left: 10px;";
        newRow.appendChild(deleteIcon);
    
        // Append the new core issue row to the container
        coreIssuesContainer.appendChild(newRow);
        console.log("✅ New core issue added!");
    });

    // ✅ Event delegation for dynamically added delete buttons
    coreIssuesContainer.addEventListener("click", function (event) {
        if (event.target.classList.contains("delete-core-issue")) {
            event.target.closest(".core-issue-row").remove();
            console.log("🗑️ Core issue deleted.");
        }
    });

    // ✅ Enable "Save Note" Button by default (No longer required to fill `ccc_note` to enable submit)
    submitButton.disabled = false;  // Directly enable the submit button without checking note input

    // ✅ Handle "Save Note" Button Click
    if (submitButton) {
        submitButton.addEventListener("click", function (event) {
            event.preventDefault();
            console.log("🔥 Save Note button clicked!");

            // Try to get client ID from URL first
            let clientId = new URLSearchParams(window.location.search).get("client_id");
            
            // If not in URL, try to get from dropdown
            if (!clientId) {
                const clientDropdown = document.getElementById("ccc_assigned_client");
                if (clientDropdown) {
                    clientId = clientDropdown.value;
                }
            }

            // Log the client ID for debugging
            console.log("📌 Client ID:", clientId);

            if (!clientId) {
                alert("Invalid client ID. Please select a client from the dropdown.");
                return;
            }

            // Combine new and existing core issues with updated severities
            const coreIssues = Object.values(activeCoreIssues).map(issue => ({
                id: issue.id,
                name: issue.name,
                severity: issue.severity,
                is_existing: issue.id.toString().indexOf('new_') === -1 // Flag to identify existing issues
            }));
            
            console.log("📡 Core Issues being sent:", coreIssues);

            const formData = new FormData();
            formData.append("action", "ccc_save_note");
            formData.append("security", ccc_ajax.security);
            formData.append("client_id", clientId);
            formData.append("note", noteInput.value.trim());
            formData.append("core_issues", JSON.stringify(coreIssues));

            fetch(ccc_ajax.ajax_url, {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log("✅ AJAX Response:", data);
                if (data.success) {
                    alert("✅ Note saved successfully!");
                    location.reload();
                } else {
                    console.error("❌ Error saving note:", data);
                }
            })
            .catch(error => {
                console.error("🚨 AJAX Error:", error);
            });
        });
    } else {
        console.error("❌ Save Note button not found!");
    }

    // Handle "Archive" button click
    coreIssuesContainer.addEventListener("click", function (event) {
        if (event.target.classList.contains("archive-core-issue")) {
            const issueId = event.target.getAttribute("data-issue-id");

            // Make an AJAX request to archive the core issue
            archiveCoreIssue(issueId);
        }
    });

    // Function to handle archiving core issue
    function archiveCoreIssue(issueId) {
        console.log(`🔥 Archiving Core Issue ID: ${issueId}`);
    
        // Send the issue ID to the server for archiving
        const formData = new FormData();
        formData.append("action", "ccc_archive_core_issue");
        formData.append("security", ccc_ajax.security);
        formData.append("issue_id", issueId);
    
        fetch(ccc_ajax.ajax_url, {
            method: "POST",
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(data.message || "Core issue archived successfully!"); // Log success message to console
                location.reload(); // Reload the page to reflect changes
            } else {
                console.error(data.message || "Failed to archive core issue."); // Log error message to console
            }
        })
        .catch(error => {
            console.error("❌ Error archiving core issue:", error);
        });
    }

    // Add event listener for severity changes on line items
    coreIssuesContainer.addEventListener("change", function(event) {
        if (event.target.matches('select[name="core_issue_severity[]"]')) {
            const row = event.target.closest('.core-issue-row');
            const issueId = row.dataset.issueId;
            const newSeverity = event.target.value;
            
            console.log(`Severity changed for issue ${issueId} to ${newSeverity}`);
            
            if (issueId) {
                activeCoreIssues[issueId] = {
                    id: issueId,
                    name: row.querySelector('input[type="text"]').value,
                    severity: newSeverity,
                    is_existing: true // Mark as existing issue
                };
            }
        }
    });

    // Initialize existing core issues on page load
    document.addEventListener("DOMContentLoaded", function() {
        const existingRows = coreIssuesContainer.querySelectorAll('.core-issue-row');
        existingRows.forEach(row => {
            const issueId = row.dataset.issueId;
            const name = row.querySelector('input[type="text"]').value;
            const severity = row.querySelector('select').value;
            
            activeCoreIssues[issueId] = {
                id: issueId,
                name: name,
                severity: severity
            };
        });
        
        console.log("Initialized existing core issues:", activeCoreIssues);
    });
};