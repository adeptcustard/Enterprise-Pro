<?php
session_start();

require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Supervisor') {
    //redirect unauthorized users to the login page
    header("Location: ../html/login.html");
    exit; 
}

$darkClass = '';
$dyslexicClass = '';

$stmt = $pdo->prepare("SELECT dark_mode, dyslexic_mode FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$prefs = $stmt->fetch(PDO::FETCH_ASSOC);

if ($prefs) {
    if ($prefs['dark_mode']) $darkClass = 'dark-mode';
    if ($prefs['dyslexic_mode']) $dyslexicClass = 'dyslexic-mode';
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
    <link rel="stylesheet" href="../css/global.css">

    <script>
        //prevent the user from navigating back to the login page after logging in
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            // reloads the page when the back button is clicked
            location.reload();
        };
    </script>
</head> 

<body class="<?php echo $darkClass . ' ' . $dyslexicClass; ?>">

    <!-- Navigation bar specific to Supervisor role -->
    <nav class="admin-navbar">
        <ul>
            <li><a href="tasks_supervisor.php">Dashboard</a></li>
            <li><a href="help_requests.php">Help Requests</a></li>
            <li><a href="tasks_supervisor_management.php">Tasks Management</a></li>
            <li><a href="logout.php" class="logout">Logout</a></li>
            <li><a href="profile_supervisor.php" class="active">Profile</a></li>     
        </ul>
    </nav>

    <!-- Main page heading -->
    <div id="user-header">
        <h1 class="dash-header" >Supervisor Dashboard</h1>
        <h1>Welcome, <span id="user-name"><?php echo $_SESSION['first_name'] . " " . $_SESSION['last_name']; ?></span> to the YHROCU WorkFlow Management System</h1>
    </div>

    <!-- Buttons to toggle between "My Tasks" and "All Tasks" -->
    <div class="task-toggle" id="task-toggle">
        <button data-view="my-tasks" onclick="toggleTaskView('my-tasks')">📌 My Tasks</button> 
        <button data-view="all-tasks" onclick="toggleTaskView('all-tasks')">📂 All Tasks</button> 
    </div>
        
    <h2 id="current-view-label">📋 Viewing: My Tasks</h2>

    
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
    
    <!-- Task details section (hidden by default - opened on click of view details button) -->
    <div id="task-details" class="task-card hidden">
        <input type="hidden" id="task-id">
        
        <!-- Button to navigate back to the task list -->
        <button class="back-button" onclick="goBack()">⬅ Back to Tasks</button>

        <!--Task Status Description -->
        <div class="status-table-container">
            <button id="toggle-status-table" onclick="toggleStatusTable()">ℹ️ View Task Status Guide</button>

            <div id="status-info" class="collapsible hidden">
                <table class="status-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Pending</td>
                            <td>The task has not been started yet.</td>
                        </tr>
                        <tr>
                            <td>In Progress</td>
                            <td>The task is currently being worked on.</td>
                        </tr>
                        <tr>
                            <td>To Be Reviewed</td>
                            <td>The task is complete and awaiting supervisor review.</td>
                        </tr>
                        <tr>
                            <td>Complete</td>
                            <td>The task has been reviewed and marked as completed.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>


        <!-- Display task details -->
        <h2 id="task-title"></h2>
        <p id="task-description"></p>
        <p><strong>Status:</strong> <span id="task-status"></span></p>

        <!-- Status Change Section -->            
        <label for="status-dropdown"><strong>Change Task Status:</strong></label>
        <select id="status-dropdown" onchange="updateTaskStatus(document.getElementById('task-id').value, this.value)">
            <option value="Pending">Pending</option>
            <option value="In Progress">In Progress</option>
            <option value="To Be Reviewed">To Be Reviewed</option>
            <option value="Complete">Complete</option>
        </select>




        <p><strong>Created:</strong> <span id="task-created"></span></p>
        <p><strong>Deadline:</strong> <span id="task-deadline"></span></p>

        <!-- Primary Assigned User -->
        <h3>Created By: </h3>
        <p id="primary-assigned-user">None</p>

        <!-- Additional Assigned Users -->
        <h3>Additionally Assigned Users: </h3>
        <ul id="additional-users-list"></ul>

        <!-- Section for task actions -->
        <h3>Task Actions</h3>
        <ul id="task-actions-list"></ul>

        <!-- Task progress tracking -->
        <h3>Task Progress</h3>
        <p>Actions Completed: <span id="task-actions"></span></p>
        <div class="progress-bar">
            <div class="progress" id="task-progress"></div>
        </div>

        <!-- Section for adding comments -->
        <h3>Add Comment</h3>
        <textarea id="new-comment" placeholder="Write a comment..."></textarea>
        <button onclick="addComment()">Add Comment</button>

        <!-- Section for uploaded files list -->
        <h3>Uploaded Files</h3>
        <ul id="file-list"></ul>

        <!-- Section for uploading files -->
        <div id="file-upload-section">
            <h3>Upload File</h3>

            <form id="upload-form" enctype="multipart/form-data">
                <input type="hidden" id="upload-task-id" name="task_id">
                <input type="file" name="file" required>
                <button type="submit">Upload</button>
            </form>

            <ul id="file-list"></ul>
        </div>

        <!-- Comment log of task-related comments -->
        <h3>Comments</h3>
        <ul id="comment-list"></ul>

        <!-- Running log of task-related updates -->
        <h3>Running Log</h3>
        <ul id="task-log"></ul>
    </div>

    <!-- JavaScript file to handle dynamic functionality like task fetching, filtering, and displaying details -->
    <script src="../javascript/tasks_supervisor.js"></script>

    <div id="toast" class="toast hidden"></div>

</body>
</html>
