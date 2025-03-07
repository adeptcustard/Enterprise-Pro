// wait for the entire document (DOM) to be fully loaded before executing the script
document.addEventListener("DOMContentLoaded", function () {
    console.log("✅ tasks_supervisor.js loaded!");

    // select necessary elements from the DOM
    const taskList = document.getElementById("task-list");
    const taskDetails = document.getElementById("task-details");
    const filterContainer = document.getElementById("filter-container");
    const userHeader = document.getElementById("user-header");
    const filterPanel = document.getElementById("filter-panel");
    const searchBar = document.getElementById("search-bar");
    const taskActionsList = document.getElementById("task-actions-list");

    // store tasks globally for filtering and searching
    let tasks = [];

    // function to format date and time in DD/MM/YYYY HH:MM format
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

    // fetch tasks from the server
    async function fetchTasks(viewType = "my-tasks") {
        try {
            // determine the API endpoint based on the view type
            const endpoint = viewType === "my-tasks"
                ? "../php/fetch_supervisor_tasks.php?my_tasks=1"
                : "../php/fetch_supervisor_tasks.php?all_tasks=1";

            const response = await fetch(endpoint);
            const data = await response.json();

            console.log("✅ Server Response:", data);

            if (data.success) {
                //store tasks globally and display them
                tasks = data.tasks;
                displayTasks(tasks);
            } 
            else {
                //display an error message if tasks couldn't be fetched
                taskList.innerHTML = `<p class="error">${data.message}</p>`;
            }
        } 
        catch (error) {
            console.error("❌ Error fetching tasks:", error);
            taskList.innerHTML = `<p class="error">Failed to load tasks. Please try again.</p>`;
        }
    }

    // function to dynamically display the list of tasks on the webpage
    function displayTasks(tasks) {
        if (!taskList || !taskDetails) {
            console.error("❌ Error: One or more elements are missing! Check HTML IDs.");
            return;
        }

        //clear previous tasks and hide task details initially
        taskList.innerHTML = "";
        taskDetails.style.display = "none"; 

        //if there are no tasks, display a message
        if (tasks.length === 0) {
            taskList.innerHTML = "<p>No tasks found.</p>";
            return;
        }

        //loop through each task and create a list item
        tasks.forEach(task => {
            const li = document.createElement("li");
            li.innerHTML = `
                <strong>${task.title}</strong> 
                <p>Status: ${task.status}</p>
                <p>Created: ${formatDateTime(task.created_at)} | Deadline: ${formatDateTime(task.deadline)}</p>
                <button onclick="openTask(${task.id})">View Details</button>
            `;
            taskList.appendChild(li);
        });

        console.log("✅ Tasks Displayed in UI!");
    }

    //function to search tasks based on user input
    window.searchTasks = function () {
        const searchText = searchBar.value.toLowerCase();
        //filter tasks based on title
        const filteredTasks = tasks.filter(task => task.title.toLowerCase().includes(searchText));
        displayTasks(filteredTasks);
    };

    //function to filter tasks based on status, creation date, and deadline
    window.filterTasks = function () {
        let filteredTasks = [...tasks];

        //filter by task status
        const statusFilter = document.getElementById("filter-status").value;
        if (statusFilter) {
            filteredTasks = filteredTasks.filter(task => task.status === statusFilter);
        }

        //sort by creation date
        const sortCreated = document.getElementById("sort-created").value;
        if (sortCreated === "newest") {
            filteredTasks.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        } else if (sortCreated === "oldest") {
            filteredTasks.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        }

        //sort by deadline
        const sortDeadline = document.getElementById("sort-deadline").value;
        if (sortDeadline === "soonest") {
            filteredTasks.sort((a, b) => new Date(a.deadline) - new Date(b.deadline));
        } 
        else if (sortDeadline === "latest") {
            filteredTasks.sort((a, b) => new Date(b.deadline) - new Date(a.deadline));
        }

        displayTasks(filteredTasks);
    };

    //function to clear all applied filters
    window.clearFilters = function () {
        console.log("✅ Filters cleared!");

        //reset all filter selections
        document.getElementById("filter-status").value = "";
        document.getElementById("sort-created").value = "newest";
        document.getElementById("sort-deadline").value = "soonest";
        searchBar.value = "";

        //display all tasks again
        displayTasks(tasks);
    };

    //function to open a selected task and display its details
    window.openTask = function (taskId) {
        console.log("🔍 Opening Task ID:", taskId);

        taskId = parseInt(taskId, 10);
        const task = tasks.find(t => parseInt(t.id, 10) === taskId);

        if (!task) {
            console.error("❌ Task not found:", taskId);
            return;
        }

        //update task details section with relevant task information
        document.getElementById("task-title").innerText = task.title;
        document.getElementById("task-description").innerText = task.description;
        document.getElementById("task-status").innerText = task.status;
        document.getElementById("task-created").innerText = formatDateTime(task.created_at);
        document.getElementById("task-deadline").innerText = formatDateTime(task.deadline);
        document.getElementById("task-actions").innerText = `${task.completed_actions ?? 0}/${task.total_actions ?? 0} completed`;

        const progressPercentage = task.total_actions > 0 ? (task.completed_actions / task.total_actions) * 100 : 0;
        document.getElementById("task-progress").style.width = `${progressPercentage}%`;

        //hide task list and filters, show task details
        taskList.style.display = "none";
        filterContainer.style.display = "none";
        userHeader.style.display = "none";
        taskDetails.style.display = "block";
    };

    //function to go back to the task list view from the task details view
    window.goBack = function () {
        console.log("🔄 Returning to Task List");

        //show task list and filters, hide task details
        taskDetails.style.display = "none";
        taskList.style.display = "block";
        filterContainer.style.display = "block";
        userHeader.style.display = "block";

        console.log("✅ Task Details Cleared and Task List Restored!");
    };

    //function to toggle the filter panel visibility
    window.toggleFilters = function () {
        filterPanel.classList.toggle("hidden");
    };

    //function to switch between "My Tasks" and "All Tasks"
    window.toggleTaskView = function (viewType) {
        console.log("📌 Switching to:", viewType);
        fetchTasks(viewType);
    };

    //fetch tasks when the page loads
    fetchTasks();
});
