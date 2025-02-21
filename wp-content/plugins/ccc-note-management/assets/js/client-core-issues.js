import { Modal } from './modules/modal.js';
import { CoreIssues } from './modules/note-core-issues.js';
import { AjaxHandlers } from './modules/ajax-handlers.js';

console.log("✅ CCC Note Management JS is loaded.");

window.onload = function() {
    console.log("✅ Window Loaded");

    // Check if we're on add-new-note page or client profile page
    const isAddNotePage = window.location.href.includes('add-new-note');
    const isProfilePage = document.querySelector('.ccc-client-profile');

    if (!isAddNotePage && !isProfilePage) {
        console.log("📝 Not a core issues related page - skipping initialization");
        return;
    }

    // Initialize elements for add-new-note page
    if (isAddNotePage) {
        const submitButton = document.getElementById("submit-note");
        const noteInput = document.getElementById("ccc_note");
        const addCoreIssueButton = document.getElementById("add-core-issue");

        if (!submitButton || !noteInput || !addCoreIssueButton) {
            console.error("❌ Required elements not found on add-note page!");
            return;
        }

        // Initialize modules for add-note page
        const modal = Modal.init("core-issue-modal", "modal-close");
        const coreIssues = new CoreIssues();
        
        // Setup initial state
        coreIssues.init();
        modal.setupListeners();

        // Add Core Issue button handler
        addCoreIssueButton.addEventListener("click", (event) => {
            event.preventDefault();
            modal.show();
        });

        // Modal save handler
        modal.elements.saveButton.addEventListener("click", () => {
            // Validate required fields exist
            if (!modal.elements.name || !modal.elements.severity || 
                !modal.elements.firstAppearanceMonth || !modal.elements.firstAppearanceYear) {
                console.error("❌ Required modal elements not found!");
                return;
            }

            const issueData = {
                id: `new_${Date.now()}`,
                name: modal.elements.name.value.trim(),
                severity: modal.elements.severity.value,
                first_appearance: modal.getFirstAppearance(),
                curiosity: modal.elements.curiosity?.value || '',
                compassion: modal.elements.compassion?.value || ''
            };

            console.log("Creating new issue:", issueData);

            if (!issueData.name) {
                alert("Please enter an issue name.");
                return;
            }

            coreIssues.addNewIssue(issueData);
            modal.hide();
        });

        // Save Note handler
        submitButton.addEventListener("click", async (event) => {
            event.preventDefault();
            
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
                noteInput.value.trim(),
                coreIssues.getAllIssues()
            );
        });
    }

    // Initialize charts for profile page
    if (isProfilePage && document.querySelector('.severity-chart')) {
        initializeCharts();
    }
};

document.addEventListener('DOMContentLoaded', () => {
    console.log("✅ Client Core Issues JS loaded");
    initializeCharts();
});

function initializeCharts() {
    const charts = document.querySelectorAll('.severity-chart');
    
    charts.forEach((canvas, index) => {
        canvas.id = `severity-chart-${index}`;
        
        const existingChart = Chart.getChart(canvas);
        if (existingChart) {
            existingChart.destroy();
        }

        try {
            const logs = JSON.parse(canvas.dataset.logs);
            const ctx = canvas.getContext('2d');
            
            if (logs.length > 0) {
                // Create chart with the data
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: logs.map(log => {
                            const date = new Date(log.date);
                            return date.toLocaleDateString();
                        }),
                        datasets: [{
                            label: 'Severity',
                            data: logs.map(log => log.severity),
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        aspectRatio: 2,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 5,
                                min: 0,
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    display: false
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            } else {
                ctx.font = '14px Arial';
                ctx.fillStyle = '#7f8c8d';
                ctx.textAlign = 'center';
                ctx.fillText('No severity data available', canvas.width / 2, canvas.height / 2);
            }
        } catch (error) {
            console.error("Error creating chart:", error);
        }
    });
}