export const AjaxHandlers = {
    async saveNote(clientId, noteContent, coreIssues) {
        const formData = new FormData();
        formData.append("action", "ccc_save_note");
        formData.append("security", ccc_ajax.security);
        formData.append("client_id", clientId);
        formData.append("note", noteContent);
        formData.append("core_issues", JSON.stringify(coreIssues));

        try {
            const response = await fetch(ccc_ajax.ajax_url, {
                method: "POST",
                body: formData
            });
            const data = await response.json();
            
            console.log("✅ AJAX Response:", data);
            if (data.success) {
                console.log("✅ Note saved successfully!");
                location.reload();
            } else {
                console.error("❌ Error saving note:", data);
            }
        } catch (error) {
            console.error("🚨 AJAX Error:", error);
        }
    },

    async archiveCoreIssue(issueId) {
        console.log(`🔥 Archiving Core Issue ID: ${issueId}`);
    
        const formData = new FormData();
        formData.append("action", "ccc_archive_core_issue");
        formData.append("security", ccc_ajax.security);
        formData.append("issue_id", issueId);
    
        try {
            const response = await fetch(ccc_ajax.ajax_url, {
                method: "POST",
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                console.log(data.message || "Core issue archived successfully!");
                location.reload();
            } else {
                console.error(data.message || "Failed to archive core issue.");
            }
        } catch (error) {
            console.error("❌ Error archiving core issue:", error);
        }
    }
}; 