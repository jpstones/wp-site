import { Modal } from './modules/modal.js';
import { CoreIssues } from './modules/note-core-issues.js';
import { AjaxHandlers } from './modules/ajax-handlers.js';

document.addEventListener('DOMContentLoaded', () => {
    console.log("✅ Add Note JS initialized");
    
    // Debug element existence
    console.log("Add Core Issue button:", document.getElementById("add-core-issue"));
    console.log("Modal element:", document.getElementById("core-issue-modal"));
    console.log("Submit button:", document.getElementById("submit-note"));
    
    const coreIssues = new CoreIssues();
    const modal = Modal.init("core-issue-modal", "modal-close");
    
    // Set the coreIssues instance in the modal
    modal.coreIssues = coreIssues;
    
    console.log("CoreIssues initialized:", coreIssues);
    console.log("Modal initialized:", modal);
    
    // Initialize core issues
    coreIssues.init();
    
    // Setup modal listeners
    if (modal) {
        modal.setupListeners();
        
        // Add Core Issue button handler
        const addCoreIssueButton = document.getElementById("add-core-issue");
        if (addCoreIssueButton) {
            console.log("Setting up Add Core Issue button listener");
            addCoreIssueButton.addEventListener("click", (event) => {
                console.log("Add Core Issue button clicked");
                event.preventDefault();
                modal.show();
            });
        } else {
            console.error("❌ Add Core Issue button not found");
        }
    } else {
        console.error("❌ Modal initialization failed");
    }

    // Setup submit button listener
    const submitButton = document.getElementById("submit-note");
    if (submitButton) {
        console.log("Setting up Submit button listener");
        submitButton.addEventListener("click", async (event) => {
            console.log("Submit button clicked");
            event.preventDefault();
            
            // Get TinyMCE content
            const noteContent = tinyMCE.get('ccc_note') ? tinyMCE.get('ccc_note').getContent() : '';
            console.log("Note content:", noteContent);

            let clientId = new URLSearchParams(window.location.search).get("client_id");
            if (!clientId) {
                const clientDropdown = document.getElementById("ccc_assigned_client");
                clientId = clientDropdown?.value;
            }

            if (!clientId) {
                alert("Invalid client ID. Please select a client from the dropdown.");
                return;
            }

            await AjaxHandlers.saveNote(
                clientId,
                noteContent,  // Use the TinyMCE content
                coreIssues.getAllIssues()
            );
        });
    } else {
        console.error("❌ Submit button not found");
    }
}); 