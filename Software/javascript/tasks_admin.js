/**
 * Wait for the full DOM to be ready before executing any script.
 */
document.addEventListener("DOMContentLoaded", function () {
    console.log("✅ tasks_admin.js loaded!");

    //DOM Elements
    const taskList = document.getElementById("task-list");
    const taskDetails = document.getElementById("task-details");
    const filterContainer = document.getElementById("filter-container");
    const userHeader = document.getElementById("user-header");
    const filterPanel = document.getElementById("filter-panel");
    const searchBar = document.getElementById("search-bar");
    const taskActionsList = document.getElementById("task-actions-list");

    /** @type {Array} All fetched tasks */
    let tasks = [];

    /**
     * Formats a datetime string to `DD/MM/YYYY HH:MM`
     * @param {string} dateString - Raw datetime string
     * @returns {string} - Formatted date
     */
    function formatDateTime(dateString) {
        if (!dateString || dateString === "null") return "N/A";

        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, "0");
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, "0");
        const minutes = String(date.getMinutes()).padStart(2, "0");

        return `${day}/${month}/${year} ${hours}:${minutes}`;
    }

    /**
     * Toggles visibility of the status info table
     */
    window.toggleStatusTable = function () {
        const table = document.getElementById("status-info");
        table.classList.toggle("hidden");
    };

    /**
     * Fetches all tasks from the server
     */
    async function fetchTasks() {
        try {
            const response = await fetch("../php/fetch_tasks_admin.php");
            const data = await response.json();

            if (data.success) {
                tasks = data.tasks;
                displayTasks(tasks);
            }
            else {
                taskList.innerHTML = `<p class="error">${data.message}</p>`;
            }
        }
        catch (error) {
            console.error("❌ Error fetching tasks:", error);
            taskList.innerHTML = `<p class="error">Failed to load tasks. Please try again.</p>`;
        }
    }

    /**
     * Display a list of tasks
     * @param {Array} filteredTasks - Tasks to display
     */
    function displayTasks(filteredTasks) {
        taskList.innerHTML = "";
        taskDetails.style.display = "none";
        filterContainer.style.display = "block";
        userHeader.style.display = "block";

        if (filteredTasks.length === 0) {
            taskList.innerHTML = "<p>No tasks found.</p>";
            return;
        }

        filteredTasks.forEach(task => {
            const li = document.createElement("li");
            li.classList.add("task-card");

            const primaryUser = task.primary_user
                ? `${task.primary_user.first_name} ${task.primary_user.last_name} (${task.primary_user.email})`
                : "None";

            const additionalUsers = task.additional_users?.length
                ? task.additional_users.map(user => `${user.first_name} ${user.last_name} (${user.email})`).join(", ")
                : "None";

            li.innerHTML = `
                <strong>${task.title}</strong>
                <p>Status: ${task.status}</p>
                <p>Created: ${formatDateTime(task.created_at)} | Deadline: ${formatDateTime(task.deadline)}</p>
                <p><strong>Created By:</strong> ${primaryUser}</p>
                <p><strong>Additionally Assigned Users:</strong> ${additionalUsers}</p>
                <p>Actions: ${task.completed_actions}/${task.total_actions} completed</p>
                <button onclick="openTask(${task.id})">View Details</button>
            `;
            taskList.appendChild(li);
        });
    }

    /**
     * Search tasks based on title
     */
    window.searchTasks = function () {
        const searchText = searchBar.value.toLowerCase();
        const filteredTasks = tasks.filter(task => task.title.toLowerCase().includes(searchText));
        displayTasks(filteredTasks);
    };

    /**
     * Filter tasks based on status and sort by date
     */
    window.filterTasks = function () {
        let filteredTasks = [...tasks];

        const statusFilter = document.getElementById("filter-status").value;
        if (statusFilter) {
            filteredTasks = filteredTasks.filter(task => task.status === statusFilter);
        }

        const sortCreated = document.getElementById("sort-created").value;
        if (sortCreated === "newest") {
            filteredTasks.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        }
        else if (sortCreated === "oldest") {
            filteredTasks.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        }

        const sortDeadline = document.getElementById("sort-deadline").value;
        if (sortDeadline === "soonest") {
            filteredTasks.sort((a, b) => new Date(a.deadline) - new Date(b.deadline));
        }
        else if (sortDeadline === "latest") {
            filteredTasks.sort((a, b) => new Date(b.deadline) - new Date(a.deadline));
        }

        displayTasks(filteredTasks);
    };

    /**
     * Clears all filter fields and resets the view
     */
    window.clearFilters = function () {
        document.getElementById("filter-status").value = "";
        document.getElementById("sort-created").value = "newest";
        document.getElementById("sort-deadline").value = "soonest";
        searchBar.value = "";
        displayTasks(tasks);
    };

    /**
     * Opens and displays full task details
     * @param {number} taskId - Task ID
     */
    window.openTask = function (taskId) {
        taskId = parseInt(taskId, 10);
        const task = tasks.find(t => parseInt(t.id, 10) === taskId);
        if (!task) {
            console.error("❌ Task not found:", taskId);
            return;
        }

        document.getElementById("task-id").value = task.id;
        document.getElementById("task-title").innerText = task.title;
        document.getElementById("task-description").innerText = task.description;
        document.getElementById("task-status").innerText = task.status;
        document.getElementById("task-created").innerText = formatDateTime(task.created_at);
        document.getElementById("task-deadline").innerText = formatDateTime(task.deadline);
        document.getElementById("task-actions").innerText = `${task.completed_actions ?? 0}/${task.total_actions ?? 0} completed`;

        const progress = (task.total_actions > 0) ? (task.completed_actions / task.total_actions) * 100 : 0;
        document.getElementById("task-progress").style.width = `${progress}%`;

        fetchTaskLog(task.id);

        const primaryUserEl = document.getElementById("primary-assigned-user");
        primaryUserEl.innerHTML = task.primary_user
            ? `${task.primary_user.first_name} ${task.primary_user.last_name} (${task.primary_user.email})`
            : "None";

        const additionalUsersList = document.getElementById("additional-users-list");
        additionalUsersList.innerHTML = task.additional_users?.length
            ? task.additional_users.map(user => `<li>${user.first_name} ${user.last_name} (${user.email})</li>`).join("")
            : "<p>No additional users assigned to this task.</p>";

        taskActionsList.innerHTML = task.actions?.length
            ? task.actions.map(action => `
                <li class="task-action-item">
                    ${action.action_description} -
                    <button onclick="toggleActionCompletion(${action.id})">
                        ${action.completed ? "✅ Completed" : "❌ Not Completed"}
                    </button>
                </li>`).join("")
            : "<p>No actions available for this task.</p>";

        taskList.style.display = "none";
        filterContainer.style.display = "none";
        userHeader.style.display = "none";
        taskDetails.style.display = "block";
    };

    /** Hides task details and returns to the list */
    window.goBack = function () {
        taskList.style.display = "block";
        filterContainer.style.display = "block";
        userHeader.style.display = "block";
        taskDetails.style.display = "none";
    };

    /** Show/hide the filter panel */
    window.toggleFilters = function () {
        filterPanel.classList.toggle("hidden");
    };

    /**
     * Add a comment to a task
     */
    window.addComment = async function () {
        const commentInput = document.getElementById("new-comment");
        const taskId = document.getElementById("task-id").value;

        const commentText = commentInput.value.trim();
        if (!commentText) return alert("Comment cannot be empty!");

        try {
            const response = await fetch("../php/add_comment.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `task_id=${taskId}&comment=${encodeURIComponent(commentText)}`
            });

            const data = await response.json();
            if (data.success) {
                commentInput.value = "";
                fetchTaskLog(taskId);
            }
            else {
                alert(data.message);
            }
        }
        catch (error) {
            alert("Failed to add comment.");
        }
    };

    /**
     * Renders comment list
     * @param {Array} comments - Comment objects
     */
    function displayComments(comments) {
        const commentsList = document.getElementById("comment-list");
        commentsList.innerHTML = comments.length
            ? comments.map(c => `<li><strong>${c.first_name} ${c.last_name}:</strong> ${c.comment} <small>(${formatDateTime(c.created_at)})</small></li>`).join("")
            : "<p>No comments yet.</p>";
    }

    /**
     * Renders task log entries
     * @param {Array} logEntries - Log objects
     */
    function displayRunningLog(logEntries) {
        const logList = document.getElementById("task-log");
        logList.innerHTML = logEntries.length
            ? logEntries.map(entry => `
                <li class="log-entry">
                    <strong>${entry.first_name} ${entry.last_name}:</strong>
                    ${entry.action}
                    <small>(${formatDateTime(entry.created_at)})</small>
                </li>`).join("")
            : "<p>No log entries yet.</p>";
    }

    /**
     * Fetch log for a task
     * @param {number} taskId
     */
    async function fetchTaskLog(taskId) {
        try {
            const response = await fetch(`../php/fetch_task_log.php?task_id=${taskId}`);
            const data = await response.json();

            if (data.success) {
                displayComments(data.comments);
                displayRunningLog(data.log_entries);
            }
            else {
                document.getElementById("task-log").innerHTML = `<p>${data.message}</p>`;
            }
        }
        catch (error) {
            document.getElementById("task-log").innerHTML = "<p>Failed to load task log.</p>";
        }
    }

    /**
     * Upload a file associated with a task
     */
    document.getElementById("upload-form").addEventListener("submit", async function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.set("task_id", document.getElementById("task-id").value);

        try {
            const response = await fetch("../php/upload_task_file.php", {
                method: "POST",
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                showToast("File uploaded successfully!", "success");
                loadFiles(formData.get("task_id"));
                this.reset();
            }
            else {
                showToast(data.message, "error");
            }
        }
        catch {
            showToast("Upload failed.", "error");
        }
    });

    /**
     * Load files attached to a task
     * @param {number} taskId
     */
    async function loadFiles(taskId) {
        try {
            const response = await fetch(`../php/fetch_task_files.php?task_id=${taskId}`);
            const data = await response.json();

            const fileList = document.getElementById("file-list");
            fileList.innerHTML = data.success && data.files.length
                ? data.files.map(file => `<li><a href="${file.file_path}" target="_blank">${file.file_name}</a></li>`).join("")
                : "<p>No files uploaded yet.</p>";
        } catch {
            document.getElementById("file-list").innerHTML = "<p>Error loading files.</p>";
        }
    }

    /**
     * Toggle an action's completed status
     * @param {number} actionId
     */
    window.toggleActionCompletion = async function (actionId) {
        const task = tasks.find(t => t.actions.some(a => a.id === actionId));
        const action = task?.actions.find(a => a.id === actionId);
        if (!action) return showToast("❌ Action not found.", "error");

        const confirmMsg = action.completed
            ? "Mark this action as INCOMPLETE?"
            : "Mark this action as COMPLETE?";

        if (!confirm(confirmMsg)) return;

        try {
            const response = await fetch(`../php/update_task_action.php?action_id=${actionId}`, { method: "POST" });
            const data = await response.json();

            if (data.success) {
                action.completed = !action.completed;
                showToast(`✅ Action marked as ${action.completed ? "complete" : "incomplete"}`, "success");
                openTask(task.id);
                fetchTaskLog(task.id);
            }
            else {
                showToast("❌ Failed to update action.", "error");
            }
        }
        catch {
            showToast("❌ Something went wrong.", "error");
        }
    };

    /**
     * Update a task’s status
     * @param {number} taskId
     * @param {string} newStatus
     */
    window.updateTaskStatus = async function (taskId, newStatus) {
        try {
            const response = await fetch(`../php/update_task_status.php`, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `task_id=${taskId}&new_status=${encodeURIComponent(newStatus)}`
            });

            const data = await response.json();
            if (data.success) {
                showToast("✅ Status updated successfully!", "success");
                fetchTaskLog(taskId);
                await fetchTasks();
                openTask(parseInt(taskId));
            }
            else {
                showToast(data.message, "error");
            }
        }
        catch {
            showToast("❌ Error changing status.", "error");
        }
    };

    /**
     * Show toast notifications
     * @param {string} message - Message to display
     * @param {string} type - Type of toast (success/error)
     */
    function showToast(message, type = "success") {
        const toast = document.getElementById("toast");
        if (!toast) return;

        toast.className = `toast ${type}`;
        toast.innerText = message;
        toast.classList.remove("hidden");

        setTimeout(() => {
            toast.classList.add("show");
        }, 10);

        setTimeout(() => {
            toast.classList.remove("show");
            setTimeout(() => {
                toast.classList.add("hidden");
            }, 300);
        }, 3000);
    }

    //initial fetch
    fetchTasks();
});
