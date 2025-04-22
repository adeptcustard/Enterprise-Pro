/**
 * Wait until the DOM is fully loaded before executing any code.
 */
document.addEventListener("DOMContentLoaded", function () {
    console.log("✅ tasks_supervisor.js loaded!");

    //DOM elements
    const taskList = document.getElementById("task-list");
    const taskDetails = document.getElementById("task-details");
    const filterContainer = document.getElementById("filter-container");
    const userHeader = document.getElementById("user-header");
    const filterPanel = document.getElementById("filter-panel");
    const searchBar = document.getElementById("search-bar");
    const taskActionsList = document.getElementById("task-actions-list");
    const toggleButtons = document.querySelectorAll(".task-toggle button");

    //store user's tasks and all tasks
    let myTasks = [];
    let allTasks = [];

    //track which view is active ("my-tasks" or "all-tasks")
    let currentView = "my-tasks";

    /**
     * Format a datetime string into DD/MM/YYYY HH:MM format
     * @param {string} dateString - ISO datetime string
     * @returns {string} - Formatted date string
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
     * Show or hide the task status info table
     */
    window.toggleStatusTable = function () {
        const table = document.getElementById("status-info");
        table.classList.toggle("hidden");
    };

    /**
     * Fetch tasks from the server for both views
     */
    async function fetchTasks() {
        try {
            const response = await fetch("../php/fetch_supervisor_tasks.php");
            const data = await response.json();

            console.log("✅ Server Response:", data);

            if (data.success) {
                myTasks = data.my_tasks;
                allTasks = data.all_tasks;
                displayTasks(currentView === "my-tasks" ? myTasks : allTasks);
            } else {
                taskList.innerHTML = `<p class="error">${data.message}</p>`;
            }
        } catch (error) {
            console.error("❌ Error fetching tasks:", error);
            taskList.innerHTML = `<p class="error">Failed to load tasks. Please try again.</p>`;
        }
    }

    /**
     * Render a list of tasks into the task list
     * @param {Array} tasks - Array of task objects
     */
    function displayTasks(tasks) {
        taskList.innerHTML = "";
        taskDetails.style.display = "none";

        if (tasks.length === 0) {
            taskList.innerHTML = "<p>No tasks found.</p>";
            return;
        }

        tasks.forEach(task => {
            const li = document.createElement("li");
            li.classList.add("task-card");

            const primaryUser = task.primary_user
                ? `${task.primary_user.first_name} ${task.primary_user.last_name} (${task.primary_user.email})`
                : "None";

            const additionalUsers = task.assigned_users?.length
                ? task.assigned_users.map(user => `${user.first_name} ${user.last_name} (${user.email})`).join(", ")
                : "None";

            li.innerHTML = `
                <strong>${task.title}</strong>
                <p>Status: ${task.status}</p>
                <p>Created: ${formatDateTime(task.created_at)} | Deadline: ${formatDateTime(task.deadline)}</p>
                <p><strong>Created By:</strong> ${primaryUser}</p>
                <p><strong>Additionally Assigned Users:</strong> ${additionalUsers}</p>
                <p>Actions: ${task.completed_actions}/${task.total_actions} completed</p>
                <button onclick="openTask(${task.id}, '${currentView}')">View Details</button>
            `;
            taskList.appendChild(li);
        });
    }

    /**
     * Search through tasks by title based on current view
     */
    window.searchTasks = function () {
        const searchText = searchBar.value.toLowerCase();
        const source = currentView === "my-tasks" ? myTasks : allTasks;
        const filtered = source.filter(task => task.title.toLowerCase().includes(searchText));
        displayTasks(filtered);
    };

    /**
     * Filter tasks by status, creation date, and deadline
     */
    window.filterTasks = function () {
        const source = currentView === "my-tasks" ? [...myTasks] : [...allTasks];

        const status = document.getElementById("filter-status").value;
        const createdSort = document.getElementById("sort-created").value;
        const deadlineSort = document.getElementById("sort-deadline").value;

        let filtered = source;

        if (status) {
            filtered = filtered.filter(task => task.status === status);
        }

        if (createdSort === "newest") {
            filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        } else if (createdSort === "oldest") {
            filtered.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        }

        if (deadlineSort === "soonest") {
            filtered.sort((a, b) => new Date(a.deadline) - new Date(b.deadline));
        } else if (deadlineSort === "latest") {
            filtered.sort((a, b) => new Date(b.deadline) - new Date(a.deadline));
        }

        displayTasks(filtered);
    };

    /**
     * Clear all filters and reset task list
     */
    window.clearFilters = function () {
        document.getElementById("filter-status").value = "";
        document.getElementById("sort-created").value = "newest";
        document.getElementById("sort-deadline").value = "soonest";
        searchBar.value = "";

        displayTasks(currentView === "my-tasks" ? myTasks : allTasks);
    };

    /**
     * Opens a task and displays detailed info
     * @param {number} taskId
     * @param {string} viewType - View context ("my-tasks" or "all-tasks")
     */
    window.openTask = function (taskId, viewType) {
        taskId = parseInt(taskId, 10);
        const task = (viewType === "my-tasks" ? myTasks : allTasks).find(t => parseInt(t.id, 10) === taskId);
        if (!task) return showToast("❌ Task not found", "error");

        document.getElementById("task-id").value = task.id;
        document.getElementById("task-title").innerText = task.title;
        document.getElementById("task-description").innerText = task.description;
        document.getElementById("task-status").innerText = task.status;
        document.getElementById("task-created").innerText = formatDateTime(task.created_at);
        document.getElementById("task-deadline").innerText = formatDateTime(task.deadline);
        document.getElementById("task-actions").innerText = `${task.completed_actions ?? 0}/${task.total_actions ?? 0} completed`;

        const progress = task.total_actions > 0 ? (task.completed_actions / task.total_actions) * 100 : 0;
        document.getElementById("task-progress").style.width = `${progress}%`;

        fetchTaskLog(task.id);
        loadFiles(task.id);

        document.getElementById("primary-assigned-user").innerText = task.primary_user
            ? `${task.primary_user.first_name} ${task.primary_user.last_name} (${task.primary_user.email})`
            : "None";

        const additionalUsersList = document.getElementById("additional-users-list");
        additionalUsersList.innerHTML = task.assigned_users?.length
            ? task.assigned_users.map(u => `<li>${u.first_name} ${u.last_name} (${u.email})</li>`).join("")
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

        //hide list and show details view
        document.getElementById("current-view-label").style.display = "none";
        document.getElementById("task-toggle").style.display = "none";
        taskList.style.display = "none";
        filterContainer.style.display = "none";
        userHeader.style.display = "block";
        taskDetails.style.display = "block";
    };

    /**
     * Fetch comments and log history for a task
     * @param {number} taskId
     */
    async function fetchTaskLog(taskId) {
        try {
            const response = await fetch(`../php/fetch_task_log.php?task_id=${taskId}`);
            const data = await response.json();

            if (data.success) {
                displayComments(data.comments);
                displayRunningLog(data.log_entries);
            } else {
                document.getElementById("task-log").innerHTML = `<p>${data.message}</p>`;
            }
        } catch (error) {
            document.getElementById("task-log").innerHTML = "<p>Failed to load task log.</p>";
        }
    }

    /**
     * Display comments
     * @param {Array} comments - Comment entries
     */
    function displayComments(comments) {
        const list = document.getElementById("comment-list");
        list.innerHTML = comments.length
            ? comments.map(c => `<li><strong>${c.first_name} ${c.last_name}:</strong> ${c.comment} <small>(${formatDateTime(c.created_at)})</small></li>`).join("")
            : "<p>No comments yet.</p>";
    }

    /**
     * Display running log entries
     * @param {Array} logEntries
     */
    function displayRunningLog(logEntries) {
        const list = document.getElementById("task-log");
        list.innerHTML = logEntries.length
            ? logEntries.map(l => `
                <li class="log-entry">
                    <strong>${l.first_name} ${l.last_name}:</strong> ${l.action}
                    <small>(${formatDateTime(l.created_at)})</small>
                </li>`).join("")
            : "<p>No log entries yet.</p>";
    }

    /**
     * Add a new comment to the task
     */
    window.addComment = async function () {
        const comment = document.getElementById("new-comment").value.trim();
        const taskId = document.getElementById("task-id").value;

        if (!comment) return showToast("⚠️ Comment cannot be empty!", "error");

        try {
            const response = await fetch("../php/add_comment.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `task_id=${taskId}&comment=${encodeURIComponent(comment)}`
            });

            const data = await response.json();
            if (data.success) {
                document.getElementById("new-comment").value = "";
                fetchTaskLog(taskId);
            }
            else {
                showToast(data.message, "error");
            }
        }
        catch {
            showToast("Failed to add comment.", "error");
        }
    };

    /**
     * Return from task details to task list
     */
    window.goBack = function () {
        taskDetails.style.display = "none";
        taskList.style.display = "block";
        filterContainer.style.display = "block";
        userHeader.style.display = "block";
        document.getElementById("task-toggle").style.display = "flex";
        document.getElementById("current-view-label").style.display = "block";
    };

    /**
     * Toggle visibility of the filter panel
     */
    window.toggleFilters = function () {
        filterPanel.classList.toggle("hidden");
    };

    /**
     * Switch between "my tasks" and "all tasks"
     * @param {string} viewType
     */
    window.toggleTaskView = function (viewType) {
        console.log("📌 Switching to:", viewType);
        currentView = viewType;
        document.getElementById("current-view-label").innerText =
            viewType === "my-tasks" ? "📋 Viewing: My Tasks" : "📋 Viewing: All Tasks";
        displayTasks(viewType === "my-tasks" ? myTasks : allTasks);
    };

    //apply click listeners to toggle buttons
    toggleButtons.forEach(button => {
        button.addEventListener("click", function () {
            toggleTaskView(this.getAttribute("data-view"));
        });
    });

    /**
     * Upload a file for a task
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
            } else {
                showToast(data.message, "error");
            }
        } catch {
            showToast("❌ Upload failed.", "error");
        }
    });

    /**
     * Load files for a task
     * @param {number} taskId
     */
    async function loadFiles(taskId) {
        try {
            const res = await fetch(`../php/fetch_task_files.php?task_id=${taskId}`);
            const data = await res.json();

            const fileList = document.getElementById("file-list");
            fileList.innerHTML = data.success && data.files.length
                ? data.files.map(f => `<li><a href="${f.file_path}" target="_blank">${f.file_name}</a></li>`).join("")
                : "<p>No files uploaded yet.</p>";
        } catch {
            document.getElementById("file-list").innerHTML = "<p>Error loading files.</p>";
        }
    }

    /**
     * Toggle an action's completion status
     * @param {number} actionId
     */
    window.toggleActionCompletion = async function (actionId) {
        const task = [...myTasks, ...allTasks].find(t => t.actions.some(a => a.id === actionId));
        const action = task?.actions.find(a => a.id === actionId);

        if (!action) return showToast("❌ Action not found.", "error");

        const confirmed = confirm(
            action.completed
                ? "Are you sure you want to mark this action as INCOMPLETE?"
                : "Are you sure you want to mark this action as COMPLETE?"
        );

        if (!confirmed) return;

        try {
            const res = await fetch(`../php/update_task_action.php?action_id=${actionId}`, { method: "POST" });
            const data = await res.json();

            if (data.success) {
                action.completed = !action.completed;
                showToast(`✅ Action ${action.completed ? "marked as complete" : "marked as incomplete"}`, "success");
                openTask(task.id, currentView);
                fetchTaskLog(task.id);
            } else {
                showToast("❌ Failed to update action status.", "error");
            }
        } catch {
            showToast("❌ Something went wrong.", "error");
        }
    };

    /**
     * Update the status of a task
     * @param {number} taskId
     * @param {string} newStatus
     */
    window.updateTaskStatus = async function (taskId, newStatus) {
        try {
            const response = await fetch("../php/update_task_status.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `task_id=${taskId}&new_status=${encodeURIComponent(newStatus)}`
            });

            const data = await response.json();
            if (data.success) {
                showToast("✅ Status updated successfully!", "success");
                fetchTaskLog(taskId);
                await fetchTasks();
                openTask(parseInt(taskId), currentView);
            } else {
                showToast(data.message || "❌ Failed to update status.", "error");
            }
        } catch {
            showToast("❌ Error changing status.", "error");
        }
    };

    /**
     * Show a toast-style alert popup
     * @param {string} message - Message to display
     * @param {string} type - Toast type (success/error)
     */
    function showToast(message, type = "success") {
        const toast = document.getElementById("toast");
        if (!toast) return;

        toast.className = `toast ${type}`;
        toast.innerText = message;
        toast.classList.remove("hidden");

        setTimeout(() => toast.classList.add("show"), 10);
        setTimeout(() => {
            toast.classList.remove("show");
            setTimeout(() => toast.classList.add("hidden"), 300);
        }, 3000);
    }

    //load tasks initially
    fetchTasks();
});
