//wait for the entire document (DOM) to be fully loaded before executing the script
document.addEventListener("DOMContentLoaded", function () {
    console.log("✅ tasks_admin.js loaded!");

    //select necessary elements from the DOM
    const taskList = document.getElementById("task-list");
    const taskDetails = document.getElementById("task-details");
    const filterContainer = document.getElementById("filter-container");
    const userHeader = document.getElementById("user-header");
    const filterPanel = document.getElementById("filter-panel");
    const searchBar = document.getElementById("search-bar");
    const taskActionsList = document.getElementById("task-actions-list");

    //store tasks globally for filtering and searching
    let tasks = [];

    //function to format date and time in DD/MM/YYYY HH:MM format
    function formatDateTime(dateString){
        if (!dateString || dateString === "null") return "N/A";

        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, "0");
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, "0");
        const minutes = String(date.getMinutes()).padStart(2, "0");

        return `${day}/${month}/${year} ${hours}:${minutes}`;
    }
    
    //fetch tasks from the server
    async function fetchTasks() {
        try {
            const response = await fetch("../php/fetch_tasks_admin.php");
            const data = await response.json();

            console.log("✅ Server Response:", data);

            if (data.success) {
                tasks = data.tasks;
                displayTasks(tasks);
            } else {
                taskList.innerHTML = `<p class="error">${data.message}</p>`;
            }
        } catch (error) {
            console.error("❌ Error fetching tasks:", error);
            taskList.innerHTML = `<p class="error">Failed to load tasks. Please try again.</p>`;
        }
    }

    //display tasks in the task list
    function displayTasks(filteredTasks){
        taskList.innerHTML = "";
        taskDetails.style.display = "none";
        filterContainer.style.display = "block";
        userHeader.style.display = "block";

        if(filteredTasks.length === 0){
            taskList.innerHTML = "<p>No tasks found.</p>";
            return;
        }

        filteredTasks.forEach(task => {
            const li = document.createElement("li");
            li.innerHTML = `
                <strong>${task.title}</strong> 
                <p>Status: <span class="task-status">${task.status}</span></p>
                <p>Created: ${formatDateTime(task.created_at)} | Deadline: ${formatDateTime(task.deadline)}</p>
                <button onclick="openTask(${task.id})">View Details</button>
            `;
            taskList.appendChild(li);
        });
    }

    //function to search tasks based on user input
    window.searchTasks = function (){
        const searchText = searchBar.value.toLowerCase();
        const filteredTasks = tasks.filter(task => task.title.toLowerCase().includes(searchText));
        displayTasks(filteredTasks);
    };

    //function to filter tasks based on status, creation date, and deadline
    window.filterTasks = function () {
        let filteredTasks = [...tasks];

        const statusFilter = document.getElementById("filter-status").value;
        if (statusFilter){
            filteredTasks = filteredTasks.filter(task => task.status === statusFilter);
        }

        const sortCreated = document.getElementById("sort-created").value;
        if (sortCreated === "newest"){
            filteredTasks.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        } 
        else if (sortCreated === "oldest"){
            filteredTasks.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        }

        const sortDeadline = document.getElementById("sort-deadline").value;
        if (sortDeadline === "soonest"){
            filteredTasks.sort((a, b) => new Date(a.deadline) - new Date(b.deadline));
        } 
        else if (sortDeadline === "latest"){
            filteredTasks.sort((a, b) => new Date(b.deadline) - new Date(a.deadline));
        }

        displayTasks(filteredTasks);
    };

    //function to clear all applied filters
    window.clearFilters = function(){
        console.log("✅ Filters cleared!");

        document.getElementById("filter-status").value = "";
        document.getElementById("sort-created").value = "newest";
        document.getElementById("sort-deadline").value = "soonest";

        searchBar.value = "";

        displayTasks(tasks);
    };

    //function to open a selected task and display its details
    window.openTask = function (taskId) {
        console.log("🔍 Opening Task ID:", taskId);
    
        taskId = parseInt(taskId, 10);
        const task = tasks.find(t => parseInt(t.id, 10) === taskId);
    
        if(!task){
            console.error("❌ Task not found:", taskId);
            return;
        }
    
        document.getElementById("task-title").innerText = task.title;
        document.getElementById("task-description").innerText = task.description;
        document.getElementById("task-status").innerText = task.status;
        document.getElementById("task-created").innerText = formatDateTime(task.created_at);
        document.getElementById("task-deadline").innerText = formatDateTime(task.deadline);
        document.getElementById("task-actions").innerText = `${task.completed_actions ?? 0}/${task.total_actions ?? 0} completed`;
    
        const progressPercentage = task.total_actions > 0 ? (task.completed_actions / task.total_actions) * 100 : 0;
        document.getElementById("task-progress").style.width = `${progressPercentage}%`;
        
        
        const primaryAssignedUser = document.getElementById("primary-assigned-user");

        //display Primary Assigned User
        if (task.primary_user) {
            primaryAssignedUser.innerHTML = `${task.primary_user.first_name} ${task.primary_user.last_name} (${task.primary_user.email})`;
        } 
        else {
            primaryAssignedUser.innerHTML = "None";
        }
        
        const additionalUsersList = document.getElementById("additional-users-list");

        //display Additional Assigned Users
        additionalUsersList.innerHTML = "";

        if (!task.additional_users || task.additional_users.length === 0) {
            additionalUsersList.innerHTML = "<p>No additional users assigned to this task.</p>";
        } 
        else {
            task.additional_users.forEach(user => {
                additionalUsersList.innerHTML += `<li>${user.first_name} ${user.last_name} (${user.email})</li>`;
            });
        }

        //display task actions
        taskActionsList.innerHTML = "";
        if (!Array.isArray(task.actions) || task.actions.length === 0) {
            taskActionsList.innerHTML = "<p>No actions available for this task.</p>";
        } 
        else {
            task.actions.forEach(action => {
                const actionItem = document.createElement("li");
                actionItem.innerHTML = `
                    ${action.action_description} - 
                    <button onclick="toggleActionCompletion(${action.id})">
                        ${action.completed ? "✅ Completed" : "❌ Not Completed"}
                    </button>
                `;
                taskActionsList.appendChild(actionItem);
            });
        }
    
        taskList.style.display = "none";
        filterContainer.style.display = "none";
        userHeader.style.display = "none";
        taskDetails.style.display = "block";
    };

    //function to go back to the task list view from the task details view
    window.goBack = function(){
        taskList.style.display = "block";
        filterContainer.style.display = "block";
        userHeader.style.display = "block";
        taskDetails.style.display = "none";
    };

    //function to toggle the filter panel visibility
    window.toggleFilters = function(){
        filterPanel.classList.toggle("hidden");
    };

    //fetch tasks when the page loads
    fetchTasks();
});
