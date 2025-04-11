/**
 * Runs after the DOM has fully loaded.
 * Sets up collapsible sections, fetches tasks and assignable users,
 * and attaches event listeners for form actions.
 */
document.addEventListener("DOMContentLoaded", () => {
    console.log("📂 tasks_management.js loaded");
    //initialize collapsible UI sections
    setupCollapsibles();
    //load all tasks from the server
    fetchAllTasks();
    //load assignable users for task creation
    fetchAssignableUsers();

    //update display when user checkboxes are toggled
    document.getElementById("additional-user-container").addEventListener("change", () => {
        updateSelectedUsersDisplay();
    });

    //setup live search filtering for tasks
    const searchInput = document.getElementById("search-tasks");
    if (searchInput) {
        searchInput.addEventListener("input", filterTasks);
    }

    //live search for assignable users
    document.getElementById("user-search").addEventListener("input", filterAssignableUsers);

    //handle task creation
    const createTaskBtn = document.getElementById("create-task-btn");
    if (createTaskBtn) {
        createTaskBtn.addEventListener("click", createTask);
    }
});

/**
 * Converts a PostgreSQL datetime to HTML datetime-local format (yyyy-MM-ddThh:mm)
 * @param {string} pgDateTime - PostgreSQL datetime string
 * @returns {string} - Formatted datetime string
 */
function formatDateTime(pgDateTime) {
    const date = new Date(pgDateTime);
    return date.toISOString().slice(0, 16);
}

/**
 * Adds toggle functionality to all collapsible sections
 */
function setupCollapsibles() {
    const buttons = document.querySelectorAll(".collapsible");
    buttons.forEach(btn => {
        btn.addEventListener("click", () => {
            btn.classList.toggle("active");
            const content = btn.nextElementSibling;
            content.style.display = content.style.display === "block" ? "none" : "block";
        });
    });
}

/**
 * Fetches and displays all tasks in the task list
 */
async function fetchAllTasks() {
    try {
        const res = await fetch("../php/fetch_tasks_admin.php");
        const data = await res.json();

        if (!data.success) return showToast(data.message, "error");

        const container = document.getElementById("task-list");
        container.innerHTML = "";

        //create task list items
        data.tasks.forEach(task => {
            const li = document.createElement("li");
            li.classList.add("task-item");
            li.dataset.taskId = task.id;

            li.innerHTML = `
                <strong>${task.title}</strong> - ${task.status}
                <button class="view-btn" onclick="viewTask(${task.id})">👁 View and Edit</button>
                <button class="delete-btn" onclick="deleteTask(${task.id})">🗑 Delete</button>
            `;

            container.appendChild(li);
        });
    }
    catch (err) {
        console.error("❌ Fetch error:", err);
        showToast("Failed to load tasks", "error");
    }
}

/**
 * Filters tasks in the UI based on search input
 */
function filterTasks() {
    const query = document.getElementById("search-tasks").value.toLowerCase();
    const tasks = document.querySelectorAll(".task-item");

    tasks.forEach(task => {
        const text = task.textContent.toLowerCase();
        task.style.display = text.includes(query) ? "block" : "none";
    });
}

/**
 * Submits a new task to the server
 */
async function createTask() {
    const title = document.getElementById("task-title").value.trim();
    const description = document.getElementById("task-description").value.trim();
    const deadline = document.getElementById("task-deadline").value;
    const priority = document.getElementById("task-priority").value;
    const team = document.getElementById("task-team").value.trim();

    //collect task actions
    const actionInputs = document.querySelectorAll(".task-action-input");
    const taskActions = Array.from(actionInputs)
        .map(input => input.value.trim())
        .filter(action => action !== "");

    //get selected user IDs
    const assignedCheckboxes = document.querySelectorAll(".assign-user-checkbox:checked");
    const additionalUserIds = Array.from(assignedCheckboxes).map(cb => cb.value);

    //validation
    if (!title || !description || !deadline || !priority || !team) {
        return showToast("⚠️ Please fill in all task fields.", "error");
    }

    if (taskActions.length === 0) {
        return showToast("⚠️ Please add at least one task action.", "error");
    }

    try {
        const res = await fetch("../php/create_task.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                title,
                description,
                deadline,
                priority,
                team,
                actions: taskActions,
                additional_users: additionalUserIds
            })
        });

        const data = await res.json();
        showToast(data.message, data.success ? "success" : "error");

        if (data.success) {
            //reset form
            document.getElementById("task-title").value = "";
            document.getElementById("task-description").value = "";
            document.getElementById("task-deadline").value = "";
            document.getElementById("task-priority").value = "Low";
            document.getElementById("task-team").value = "";
            document.getElementById("task-actions-list").innerHTML = "";
            document.querySelectorAll(".assign-user-checkbox").forEach(cb => cb.checked = false);
            document.getElementById("selected-users-display").innerHTML = "";

            fetchAllTasks();
        }
    }
    catch (err) {
        console.error("❌ Create task error:", err);
        showToast("❌ Failed to create task", "error");
    }
}
