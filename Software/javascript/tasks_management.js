/**
 * Runs when the DOM is fully loaded.
 * Sets up collapsibles, fetches tasks and users, and attaches event listeners.
 */
document.addEventListener("DOMContentLoaded", () => {
    console.log("📂 tasks_management.js loaded");

    setupCollapsibles(); // enable toggle sections
    fetchAllTasks(); // load all existing tasks
    fetchAssignableUsers(); // fetch users who can be assigned to tasks

    // update assigned user display whenever checkboxes change
    document.getElementById("additional-user-container").addEventListener("change", () => {
        updateSelectedUsersDisplay();
    });

    // set up search bar for filtering tasks
    const searchInput = document.getElementById("search-tasks");
    if (searchInput) {
        searchInput.addEventListener("input", filterTasks);
    }

    // search input for assignable user list
    document.getElementById("user-search").addEventListener("input", filterAssignableUsers);

    // trigger task creation
    const createTaskBtn = document.getElementById("create-task-btn");
    if (createTaskBtn) {
        createTaskBtn.addEventListener("click", createTask);
    }
});

/**
 * Formats a PostgreSQL datetime string to HTML datetime-local input format
 * @param {string} pgDateTime - PostgreSQL datetime string
 * @returns {string} - Formatted datetime string (yyyy-MM-ddThh:mm)
 */
function formatDateTime(pgDateTime) {
    const date = new Date(pgDateTime);
    return date.toISOString().slice(0, 16);
}

/**
 * Enables collapsible section toggle behaviour for .collapsible elements
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
 * Fetches all tasks and renders them in a list
 */
async function fetchAllTasks() {
    try {
        const res = await fetch("../php/fetch_tasks_admin.php");
        const data = await res.json();

        if (!data.success) return showToast(data.message, "error");

        const container = document.getElementById("task-list");
        container.innerHTML = "";

        // build each task item with edit and delete buttons
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
    } catch (err) {
        console.error("❌ Fetch error:", err);
        showToast("Failed to load tasks", "error");
    }
}

/**
 * Filters task list items based on search query
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
 * Submits a new task to the server with entered form data
 */
async function createTask() {
    const title = document.getElementById("task-title").value.trim();
    const description = document.getElementById("task-description").value.trim();
    const deadline = document.getElementById("task-deadline").value;
    const priority = document.getElementById("task-priority").value;
    const team = document.getElementById("task-team").value.trim();

    const actionInputs = document.querySelectorAll(".task-action-input");
    const taskActions = Array.from(actionInputs).map(input => input.value.trim()).filter(action => action !== "");

    const assignedCheckboxes = document.querySelectorAll(".assign-user-checkbox:checked");
    const additionalUserIds = Array.from(assignedCheckboxes).map(cb => cb.value);

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
        showToast(data.message, data.success === true || data.success === "true" ? "success" : "error");

        if (data.success) {
            // reset form after task creation
            document.getElementById("task-title").value = "";
            document.getElementById("task-description").value = "";
            document.getElementById("task-deadline").value = "";
            document.getElementById("task-priority").value = "Low";
            document.getElementById("task-team").value = "";
            document.getElementById("task-actions-list").innerHTML = "";
            document.querySelectorAll(".assign-user-checkbox").forEach(cb => cb.checked = false);
            document.getElementById("selected-users-display").innerHTML = "";

            fetchAllTasks(); // refresh list
        }
    } catch (err) {
        console.error("❌ Create task error:", err);
        showToast("❌ Failed to create task", "error");
    }
}

/**
 * Displays the selected task in the "Edit Task" view with populated fields
 * @param {number} taskId - ID of the task to view/edit
 */
async function viewTask(taskId) {
    try {
        const res = await fetch(`../php/get_task_details.php?id=${taskId}`);
        const data = await res.json();

        if (!data.success) return showToast(data.message, "error");

        const task = data.task;
        const assignedUserIds = data.assigned_users || [];
        const allUsers = data.all_users || [];

        // Show detail view and hide the task list
        const detailSection = document.getElementById("task-details");
        const taskListSection = document.getElementById("task-list");
        taskListSection.style.display = "none";
        detailSection.style.display = "block";

        // Populate form with task data
        detailSection.innerHTML = `
            <button onclick="goBackToTaskList()" style="margin-bottom: 15px;">⬅ Back to Tasks</button>
            <h3>Edit Task – ${task.title}</h3>

            <label for="edit-title"><strong>Title:</strong></label>
            <input type="text" id="edit-title" value="${task.title}">

            <label for="edit-description"><strong>Description:</strong></label>
            <textarea id="edit-description">${task.description}</textarea>

            <label for="edit-deadline"><strong>Deadline:</strong></label>
            <input type="datetime-local" id="edit-deadline" value="${formatDateTime(task.deadline)}">

            <label for="edit-priority"><strong>Change Priority:</strong></label>
            <select id="edit-priority">
                <option value="Low" ${task.priority === "Low" ? "selected" : ""}>Low</option>
                <option value="Medium" ${task.priority === "Medium" ? "selected" : ""}>Medium</option>
                <option value="High" ${task.priority === "High" ? "selected" : ""}>High</option>
            </select>

            <button class="collapsible">👥 Assign Additional Users</button>
            <div class="collapsible-content" id="edit-user-container">
                <input type="text" id="edit-user-search" placeholder="Search users...">
                <div id="edit-user-list" class="assignable-user-list"></div>

                <div id="current-assigned" class="selected-users-box">
                    <p><strong>Currently Assigned Users:</strong></p>
                    <ul id="current-assigned-list"></ul>
                </div>

                <div id="updated-assigned" class="selected-users-box">
                    <p><strong>Updated Assigned Users:</strong></p>
                    <ul id="updated-assigned-list"></ul>
                </div>
            </div>

            <button onclick="updateTask(${taskId})" class="save-btn">💾 Save Changes</button>
        `;

        setupCollapsibles(); // re-apply collapsible logic to new section
        renderUserCheckboxes(allUsers, assignedUserIds); // build user checkboxes
        document.getElementById("edit-user-search").addEventListener("input", filterEditUserList);
        updateEditAssignedLists(); // set initial lists

    } catch (err) {
        console.error("❌ View task error:", err);
        showToast("Could not load task details", "error");
    }
}

/**
 * Submits updated task data to the backend
 * @param {number} taskId - ID of the task to update
 */
async function updateTask(taskId) {
    const updatedTask = {
        id: taskId,
        title: document.getElementById("edit-title").value,
        description: document.getElementById("edit-description").value,
        deadline: document.getElementById("edit-deadline").value,
        priority: document.getElementById("edit-priority").value,
        additional_users: Array.from(document.querySelectorAll(".edit-user-checkbox:checked")).map(cb => cb.value)
    };

    try {
        const res = await fetch("../php/update_task.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(updatedTask)
        });

        const data = await res.json();
        showToast(data.message, data.success ? "success" : "error");

        if (data.success) {
            fetchAllTasks(); // reload task list
            document.getElementById("task-details").innerHTML = ""; // clear details section
            goBackToTaskList(); // return to task list view
        }
    } catch (err) {
        console.error("❌ Update task error:", err);
        showToast("Could not update task", "error");
    }
}

/**
 * Deletes a task after user confirmation
 * @param {number} taskId - ID of the task to delete
 */
async function deleteTask(taskId) {
    if (!confirm("Are you sure you want to delete this task?")) return;

    try {
        const res = await fetch("../php/delete_task.php", {
            method: "DELETE",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `id=${taskId}`
        });

        const data = await res.json();
        showToast(data.message, data.success ? "success" : "error");

        if (data.success) fetchAllTasks(); // refresh task list after deletion
    } catch (err) {
        console.error("❌ Delete task error:", err);
        showToast("Failed to delete task", "error");
    }
}

/**
 * Updates the visible list of users selected for assignment
 */
function updateSelectedUsersDisplay() {
    const selected = Array.from(document.querySelectorAll(".assign-user-checkbox:checked"))
        .map(cb => cb.dataset.name);

    const display = document.getElementById("selected-users-display");
    display.innerHTML = selected.length > 0
        ? `<p><strong>Assigned Users:</strong></p><ul>${selected.map(name => `<li>${name}</li>`).join("")}</ul>`
        : "<p>No additional users selected.</p>";
}

/**
 * Fetches assignable users and renders them as checkboxes in the UI
 */
async function fetchAssignableUsers() {
    try {
        const res = await fetch("../php/fetch_users.php");
        const data = await res.json();

        const listContainer = document.getElementById("assignable-users-list");
        listContainer.innerHTML = "";

        if (!data.success || !Array.isArray(data.users)) {
            return showToast("⚠️ Failed to load assignable users.", "error");
        }

        // Build UI for each user
        data.users.forEach(user => {
            const wrapper = document.createElement("div");
            wrapper.classList.add("user-checkbox-item");

            wrapper.innerHTML = `
                <input type="checkbox" class="assign-user-checkbox" value="${user.id}" data-name="${user.first_name} ${user.last_name} (${user.email})">
                <div class="user-info">
                    <span class="name">${user.first_name} ${user.last_name}</span>
                    <span class="email">${user.email}</span>
                </div>
            `;

            // Update user display on change
            wrapper.querySelector("input").addEventListener("change", updateSelectedUsersDisplay);
            listContainer.appendChild(wrapper);
        });
    } catch (err) {
        console.error("❌ Error loading users:", err);
        showToast("❌ Unable to fetch assignable users.", "error");
    }
}

/**
 * Filters assignable users in the creation form based on live search input
 */
function filterAssignableUsers() {
    const query = document.getElementById("user-search").value.toLowerCase(); // get lowercase input
    const userItems = document.querySelectorAll("#assignable-users-list .user-checkbox-item");

    // show/hide user items depending on name/email match
    userItems.forEach(item => {
        const name = item.querySelector(".name")?.textContent.toLowerCase() || "";
        const email = item.querySelector(".email")?.textContent.toLowerCase() || "";
        const matches = name.includes(query) || email.includes(query);
        item.style.display = matches ? "flex" : "none";
    });
}

/**
 * Adds a new task action to the list in the create task form
 */
function addTaskAction() {
    const input = document.getElementById("task-action-input");
    const actionText = input.value.trim();
    if (actionText === "") return showToast("Please enter a task action.", "error");

    const container = document.getElementById("task-actions-list");

    const wrapper = document.createElement("div");
    wrapper.classList.add("task-action-wrapper");

    // create hidden input for form submission
    const hiddenInput = document.createElement("input");
    hiddenInput.type = "hidden";
    hiddenInput.classList.add("task-action-input");
    hiddenInput.value = actionText;

    // visible action text
    const displayText = document.createElement("span");
    displayText.textContent = actionText;
    displayText.classList.add("task-action-text");

    // delete button for the action
    const deleteBtn = document.createElement("button");
    deleteBtn.textContent = "✖";
    deleteBtn.classList.add("delete-action-btn");
    deleteBtn.addEventListener("click", () => {
        wrapper.remove(); // remove the entire action row
    });

    // assemble and append
    wrapper.appendChild(displayText);
    wrapper.appendChild(hiddenInput);
    wrapper.appendChild(deleteBtn);
    container.appendChild(wrapper);

    input.value = ""; // clear static input
}

/**
 * Returns to the task list view and hides the task details section
 */
function goBackToTaskList() {
    document.getElementById("task-details").style.display = "none";
    document.getElementById("task-list").style.display = "block";
}

/**
 * Loads all users for editing view and updates checkboxes/lists
 * @param {Object} task - The task object with assigned_users
 */
async function loadEditAssignableUsers(task) {
    try {
        const res = await fetch("../php/fetch_users.php");
        const data = await res.json();
        if (!data.success || !Array.isArray(data.users)) return;

        const listContainer = document.getElementById("edit-user-list");
        const currentList = document.getElementById("current-assigned-list");
        const updatedList = document.getElementById("updated-assigned-list");
        const searchInput = document.getElementById("edit-user-search");

        listContainer.innerHTML = "";
        currentList.innerHTML = "";
        updatedList.innerHTML = "";

        const assignedIds = task.assigned_users.map(u => u.id); // array of assigned user IDs

        data.users.forEach(user => {
            const isAssigned = assignedIds.includes(user.id);

            // add to "currently assigned" list
            if (isAssigned) {
                const li = document.createElement("li");
                li.textContent = `${user.first_name} ${user.last_name} (${user.email})`;
                currentList.appendChild(li);
            }

            // create editable checkbox
            const wrapper = document.createElement("div");
            wrapper.classList.add("user-checkbox-item");

            const checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.value = user.id;
            checkbox.checked = isAssigned;
            checkbox.classList.add("edit-user-checkbox");
            checkbox.dataset.name = `${user.first_name} ${user.last_name} (${user.email})`;

            const info = document.createElement("div");
            info.classList.add("user-info");
            info.innerHTML = `<span class="name">${user.first_name} ${user.last_name}</span><span class="email">${user.email}</span>`;

            wrapper.appendChild(checkbox);
            wrapper.appendChild(info);
            listContainer.appendChild(wrapper);

            checkbox.addEventListener("change", updateEditedAssignedUsers);
        });

        // Live search for editing list
        searchInput.addEventListener("input", () => {
            const query = searchInput.value.toLowerCase();
            const items = listContainer.querySelectorAll(".user-checkbox-item");
            items.forEach(item => {
                const name = item.querySelector(".name")?.textContent.toLowerCase() || "";
                const email = item.querySelector(".email")?.textContent.toLowerCase() || "";
                const match = name.includes(query) || email.includes(query);
                item.style.display = match ? "flex" : "none";
            });
        });

        updateEditedAssignedUsers();
    } catch (err) {
        console.error("❌ Failed to load assignable users in edit view", err);
    }
}

/**
 * Updates the "Updated Assigned Users" list based on checked boxes
 */
function updateEditedAssignedUsers() {
    const selected = Array.from(document.querySelectorAll(".edit-user-checkbox:checked"))
        .map(cb => cb.dataset.name);

    const display = document.getElementById("updated-assigned-list");
    display.innerHTML = selected.length > 0
        ? selected.map(name => `<li>${name}</li>`).join("")
        : "<li>No users selected.</li>";
}

/**
 * Builds user checkboxes for task editing screen
 * @param {Array} users - All users
 * @param {Array} assignedUserIds - IDs of users already assigned
 */
function renderUserCheckboxes(users, assignedUserIds) {
    const list = document.getElementById("edit-user-list");
    list.innerHTML = "";

    users.forEach(user => {
        const wrapper = document.createElement("div");
        wrapper.classList.add("user-checkbox-item");

        const isChecked = assignedUserIds.includes(user.id);

        wrapper.innerHTML = `
            <input type="checkbox" class="edit-user-checkbox" value="${user.id}" data-name="${user.first_name} ${user.last_name} (${user.email})" ${isChecked ? "checked" : ""}>
            <div class="user-info">
                <span class="name">${user.first_name} ${user.last_name}</span>
                <span class="email">${user.email}</span>
            </div>
        `;

        // Update display list on checkbox change
        wrapper.querySelector("input").addEventListener("change", updateEditAssignedLists);
        list.appendChild(wrapper);
    });

    updateEditAssignedLists(); // initial list render
}

/**
 * Filters user list in the edit view based on input
 */
function filterEditUserList() {
    const query = document.getElementById("edit-user-search").value.toLowerCase();
    const users = document.querySelectorAll("#edit-user-list .user-checkbox-item");

    users.forEach(user => {
        const name = user.querySelector(".name").textContent.toLowerCase();
        const email = user.querySelector(".email").textContent.toLowerCase();
        const matches = name.includes(query) || email.includes(query);
        user.style.display = matches ? "flex" : "none";
    });
}

/**
 * Updates current and updated assigned user displays in edit view
 */
function updateEditAssignedLists() {
    const checkboxes = document.querySelectorAll(".edit-user-checkbox");

    const currentList = document.getElementById("current-assigned-list");
    const updatedList = document.getElementById("updated-assigned-list");

    currentList.innerHTML = "";
    updatedList.innerHTML = "";

    checkboxes.forEach(cb => {
        const name = cb.dataset.name;
        if (cb.defaultChecked) {
            currentList.innerHTML += `<li>${name}</li>`;
        }
        if (cb.checked) {
            updatedList.innerHTML += `<li>${name}</li>`;
        }
    });
}

/**
 * Displays a toast-style notification message
 * @param {string} msg - Message to show
 * @param {string} [type="success"] - Type of message ("success" or "error")
 */
function showToast(msg, type = "success") {
    const toast = document.getElementById("toast");
    toast.className = `toast ${type}`;
    toast.innerText = msg;
    toast.classList.remove("hidden");
    setTimeout(() => toast.classList.add("show"), 10); // slight delay to trigger CSS animation
    setTimeout(() => {
        toast.classList.remove("show");
        setTimeout(() => toast.classList.add("hidden"), 300);
    }, 3000);
}
