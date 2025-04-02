<?php
//start session to access session variables for authentication
session_start();

//check if user is logged in and has the 'Supervisor' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Supervisor') {
    //redirect unauthorized users to the login page
    header("Location: ../html/login.html");
    exit; 
}
?>

<!-- html page -->
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Set character encoding to UTF-8 for proper text rendering -->
    <meta charset="UTF-8">
    
    <!-- Ensure the page is responsive on different devices -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Define the title of the webpage -->
    <title>Supervisor Dashboard | YHROCU</title>
    
    <!-- Link external CSS file for styling -->
    <link rel="stylesheet" href="../css/tasks_supervisor.css"> 
    <link rel="stylesheet" href="../css/tasks_admin.css"> 

    <script>
        //prevent the user from navigating back to the login page after logging in
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            // reloads the page when the back button is clicked
            location.reload();
        };
    </script>
</head> 

<body>

    <!-- Navigation bar specific to Supervisor role -->
    <nav class="admin-navbar">
        <ul>
            <li><a href="tasks_supervisor.php">Dashboard</a></li> 
            <li><a href="help_requests.php">Help Requests</a></li>
            <li><a href=".php">Tasks Management</a></li>
            <li><a href=".php">Users Management</a></li>
            <li><a href="../php/logout.php" class="logout">Logout</a></li>
            <li><a href=".php">Profile</a></li>     
        </ul>
    </nav>

    <!-- Main page heading -->
    <div id="user-header">
        <h1>Supervisor Dashboard</h1>
        <h1>Welcome, <span id="user-name"><?php echo $_SESSION['first_name'] . " " . $_SESSION['last_name']; ?></span> to the YHROCU WorkFlow Management System</h1>
    </div>
    
    <!-- Buttons to toggle between "My Tasks" and "All Tasks" -->
    <div class="task-toggle">
        <button data-view="my-tasks" onclick="toggleTaskView('my-tasks')">📌 My Tasks</button> 
        <button data-view="all-tasks" onclick="toggleTaskView('all-tasks')">📂 All Tasks</button> 
    </div>
    
    <!-- Filter and search section -->
    <div id="filter-container">
        <div class="search-container">
            <!-- Search bar for filtering tasks dynamically -->
            <input type="text" id="search-bar" placeholder="Search tasks..." onkeyup="searchTasks()">
            
            <!-- Button to toggle filter the options -->
            <button id="filter-icon" onclick="toggleFilters()">⚙️ Filters</button>
        </div>

        <!-- Filter options panel (hidden by default - is opened on click of filters button) -->
        <div id="filter-panel" class="filter-panel hidden">
            <label for="filter-status">Filter by Status:</label>
            <select id="filter-status" onchange="filterTasks()">
                <option value="">All Statuses</option>
                <option value="Pending">Pending</option>
                <option value="In Progress">In Progress</option>
                <option value="To Be Reviewed">To Be Reviewed</option>
                <option value="Complete">Complete</option>
            </select>

            <label for="sort-created">Sort by Created Date:</label>
            <select id="sort-created" onchange="filterTasks()">
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
            </select>

            <label for="sort-deadline">Sort by Deadline:</label>
            <select id="sort-deadline" onchange="filterTasks()">
                <option value="soonest">Soonest First</option>
                <option value="latest">Latest First</option>
            </select>

            <!-- Button to clear all applied filters -->
            <button id="clear-filters" onclick="clearFilters()">Clear Filters</button>
        </div>
    </div>

    <!-- Container to dynamically display the task list -->
    <div id="task-container">
        <ul id="task-list"></ul> 
    </div>

    <div id="task-details" class="hidden">
        <input type="hidden" id="task-id">
        
        <button class="back-button" onclick="goBack()">⬅ Back to Tasks</button>

        <h2 id="task-title"></h2>
        <p id="task-description"></p>
        <p><strong>Status:</strong> <span id="task-status"></span></p>
        <p><strong>Created:</strong> <span id="task-created"></span></p>
        <p><strong>Deadline:</strong> <span id="task-deadline"></span></p>

        <h3>Primary Assigned User</h3>
        <p id="primary-assigned-user">None</p>

        <h3>Additional Assigned Users</h3>
        <ul id="additional-users-list"></ul>

        <h3>Task Actions</h3>
        <ul id="task-actions-list"></ul>

        <h3>Task Progress</h3>
        <p>Actions Completed: <span id="task-actions"></span></p>
        <div class="progress-bar">
            <div class="progress" id="task-progress"></div>
        </div>

        <h3>Add Comment</h3>
        <textarea id="new-comment" placeholder="Write a comment..."></textarea>
        <button onclick="addComment()">Add Comment</button>

        <h3>Upload Files</h3>
        <input type="file" id="file-upload">
        <button onclick="uploadFile()">Upload File</button>

        <h3>Comments</h3>
        <ul id="comment-list"></ul>

        <h3>Running Log</h3>
        <ul id="task-log"></ul>
    </div>


    <!-- JavaScript file to handle dynamic functionality like task fetching, filtering, and displaying details -->
    <script src="../javascript/tasks_supervisor.js"></script>

</body>
</html>
