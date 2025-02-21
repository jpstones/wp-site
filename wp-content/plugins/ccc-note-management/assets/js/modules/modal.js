import { CoreIssues } from './note-core-issues.js';  // Updated import path

export const Modal = {
    elements: {
        modal: null,
        closeBtn: null,
        name: null,
        severity: null,
        frequency: null,
        firstAppearanceMonth: null,
        firstAppearanceYear: null,
        curiosity: null,
        compassion: null,
        saveBtn: null
    },
    coreIssues: null,

    init(modalId, closeBtnId) {
        console.log("Initializing modal...");
        this.elements.modal = document.getElementById(modalId);
        this.elements.closeBtn = document.getElementById(closeBtnId);
        this.elements.name = document.getElementById('core-issue-name');
        this.elements.severity = document.getElementById('core-issue-severity');
        this.elements.frequency = document.getElementById('core-issue-frequency');
        this.elements.firstAppearanceMonth = document.getElementById('core-issue-first-appearance-month');
        this.elements.firstAppearanceYear = document.getElementById('core-issue-first-appearance-year');
        this.elements.curiosity = document.getElementById('core-issue-curiosity');
        this.elements.compassion = document.getElementById('core-issue-compassion');
        this.elements.saveBtn = document.getElementById('save-core-issue');

        console.log("Modal elements:", this.elements);
        return this;
    },

    setupListeners() {
        console.log("Setting up modal listeners");
        if (this.elements.closeBtn) {
            this.elements.closeBtn.addEventListener('click', () => this.hide());
        }

        if (this.elements.saveBtn) {
            console.log("Adding save button listener");
            this.elements.saveBtn.addEventListener('click', () => {
                console.log("Save button clicked");
                if (this.validate()) {
                    this.saveIssue();
                }
            });
        } else {
            console.error("Save button not found");
        }
    },

    validate() {
        console.log("Validating modal inputs");
        const isValid = (
            this.elements.name.value.trim() !== '' &&
            this.elements.severity.value !== '' &&
            this.elements.frequency.value !== '' &&
            this.elements.firstAppearanceMonth.value !== '' &&
            this.elements.firstAppearanceYear.value !== ''
        );
        console.log("Form validation result:", isValid);
        return isValid;
    },

    getFirstAppearance() {
        console.log('Getting first appearance date...');
        const month = this.elements.firstAppearanceMonth.value;
        const year = this.elements.firstAppearanceYear.value;
        
        // Convert month number to month name
        const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        const monthName = monthNames[parseInt(month) - 1];
        
        const date = `${monthName}, ${year}`;
        console.log('First appearance date:', date);
        return date;
    },

    saveIssue() {
        console.log("Saving core issue");
        const issueData = {
            id: 'new_' + Date.now(),
            name: this.elements.name.value.trim(),
            severity: this.elements.severity.value,
            frequency: this.elements.frequency.value,
            first_appearance: this.getFirstAppearance(),
            curiosity: this.elements.curiosity?.value || '',
            compassion: this.elements.compassion?.value || ''
        };

        console.log("Issue data:", issueData);
        this.coreIssues.addNewIssue(issueData);
        this.hide();
    },

    show() {
        console.log("Showing modal");
        this.elements.modal.style.display = 'flex';
    },

    hide() {
        console.log("Hiding modal");
        this.elements.modal.style.display = 'none';
        // Clear form fields
        this.elements.name.value = '';
        this.elements.severity.value = '1';
        this.elements.frequency.value = '';
        this.elements.firstAppearanceMonth.value = '';
        this.elements.firstAppearanceYear.value = '';
        this.elements.curiosity.value = '';
        this.elements.compassion.value = '';
    }
}; 