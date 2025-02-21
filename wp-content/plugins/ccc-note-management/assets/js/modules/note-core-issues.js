import { AjaxHandlers } from './ajax-handlers.js';

export class CoreIssues {
    constructor() {
        this.existingContainer = document.getElementById("existing-core-issues-container");
        this.unsavedContainer = document.getElementById("unsaved-core-issues-container");
        this.activeCoreIssues = {};
        
        if (!this.existingContainer || !this.unsavedContainer) {
            console.error("❌ Core Issues containers not found!");
            return;
        }
    }

    init() {
        this.initializeExistingIssues();
        this.setupSeverityListeners();
        this.setupDeleteListeners();
        this.setupArchiveListeners();
    }

    initializeExistingIssues() {
        const existingRows = this.existingContainer.querySelectorAll('.core-issue-row');
        existingRows.forEach(row => {
            const issueId = row.dataset.issueId;
            const name = row.querySelector('input[type="text"]').value;
            const severity = row.querySelector('select').value;
            
            this.activeCoreIssues[issueId] = {
                id: issueId,
                name: name,
                severity: severity,
                is_existing: true
            };
        });
        
        console.log("Initialized existing core issues:", this.activeCoreIssues);
    }

    setupSeverityListeners() {
        // Existing issues severity changes
        this.existingContainer.addEventListener("change", (event) => {
            if (event.target.matches('select[name="core_issue_severity[]"]')) {
                this.handleSeverityChange(event, true);
            }
        });

        // Unsaved issues severity changes
        this.unsavedContainer.addEventListener("change", (event) => {
            if (event.target.matches('select[name="core_issue_severity[]"]')) {
                this.handleSeverityChange(event, false);
            }
        });
    }

    setupDeleteListeners() {
        this.unsavedContainer.addEventListener("click", (event) => {
            if (event.target.classList.contains("delete-core-issue")) {
                const row = event.target.closest(".core-issue-row");
                const issueId = row.dataset.issueId;
                delete this.activeCoreIssues[issueId];
                row.remove();
                console.log("🗑️ Core issue deleted.");
            }
        });
    }

    setupArchiveListeners() {
        this.existingContainer.addEventListener("click", async (event) => {
            if (event.target.classList.contains("archive-core-issue")) {
                const issueId = event.target.getAttribute("data-issue-id");
                await AjaxHandlers.archiveCoreIssue(issueId);
            }
        });
    }

    handleSeverityChange(event, isExisting) {
        const row = event.target.closest('.core-issue-row');
        const issueId = row.dataset.issueId;
        const newSeverity = event.target.value;
        
        console.log(`Severity changed for ${isExisting ? 'existing' : 'unsaved'} issue ${issueId} to ${newSeverity}`);
        
        if (issueId) {
            this.activeCoreIssues[issueId] = {
                id: issueId,
                name: row.querySelector('input[type="text"]').value,
                severity: newSeverity,
                is_existing: isExisting
            };
        }
    }

    addNewIssue(issueData) {
        const noUnsavedMessage = this.unsavedContainer.querySelector('.no-unsaved-issues-message');
        if (noUnsavedMessage) {
            noUnsavedMessage.style.display = 'none';
        }

        this.activeCoreIssues[issueData.id] = {
            ...issueData,
            first_appearance: issueData.first_appearance,
            frequency: issueData.frequency
        };

        const newRow = this.createIssueRow(issueData);
        this.unsavedContainer.appendChild(newRow);
    }

    createIssueRow(issueData) {
        const newRow = document.createElement("div");
        newRow.classList.add("core-issue-row");
        newRow.dataset.issueId = issueData.id;
        newRow.style.display = "flex";
        newRow.style.width = "100%";
        newRow.style.alignItems = "center";
        newRow.style.justifyContent = "space-between";
        newRow.style.padding = "10px 0";

        // Create input field
        const inputField = document.createElement("input");
        inputField.type = "text";
        inputField.value = issueData.name;
        inputField.disabled = true;
        inputField.style = "flex: 2; padding: 8px; border: 1px solid #ccc; background: none; font-size: 16px;";

        // Create severity dropdown
        const selectDropdown = document.createElement("select");
        selectDropdown.name = "core_issue_severity[]";
        selectDropdown.style = "flex: 1; padding: 8px; font-size: 16px;";

        for (let i = 1; i <= 5; i++) {
            const option = document.createElement("option");
            option.value = i;
            option.textContent = i + (i === 1 ? " - Mild" : i === 5 ? " - Severe" : "");
            if (i == issueData.severity) {
                option.selected = true;
            }
            selectDropdown.appendChild(option);
        }

        // Create delete icon
        const deleteIcon = document.createElement("img");
        deleteIcon.src = cccData.trashIcon;
        deleteIcon.alt = "Delete";
        deleteIcon.classList.add("delete-core-issue");
        deleteIcon.style = "width: 24px; height: 24px; cursor: pointer; margin-left: 10px;";

        // Append elements
        newRow.appendChild(inputField);
        newRow.appendChild(selectDropdown);
        newRow.appendChild(deleteIcon);

        return newRow;
    }

    getAllIssues() {
        return Object.values(this.activeCoreIssues);
    }
} 