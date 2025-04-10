<?php
//start session to access user authentication and preferences
session_start();

//include database connection
require_once 'db_connect.php';

//redirect to login page if user is not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../html/login.html");
    exit;
}

//initialize theme classes
$darkClass = '';
$dyslexicClass = '';

//retrieve user's dark and dyslexic mode preferences
$stmt = $pdo->prepare("SELECT dark_mode, dyslexic_mode FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$prefs = $stmt->fetch(PDO::FETCH_ASSOC);

//apply preferences if available
if ($prefs) {
    if ($prefs['dark_mode']) $darkClass = 'dark-mode';
    if ($prefs['dyslexic_mode']) $dyslexicClass = 'dyslexic-mode';
}
?>

<!-- html page -->
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- set character encoding to UTF-8 for proper text rendering -->
    <meta charset="UTF-8">

    <!-- ensure responsive layout on all devices -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- page title -->
    <title>Admin Dashboard | YHROCU</title>

    <!-- link to admin task page styling and global styles -->
    <link rel="stylesheet" href="../css/tasks_admin.css">
    <link rel="stylesheet" href="../css/global.css">

    <script>
        //this script disables back navigation after login
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            location.reload();
        };
    </script>
</head>

<!-- apply user-selected accessibility classes -->
<body class="<?php echo $darkClass . ' ' . $dyslexicClass; ?>">

<!-- navigation bar for admin pages -->
<nav class="admin-navbar">
    <ul>
        <li><a href="tasks_admin.php">Dashboard</a></li>
        <li><a href="help_requests.php">Help Requests</a></li>
        <li><a href="tasks_admin_management.php">Tasks Management</a></li>
        <li><a href="users_admin.php">Users Management</a></li>
        <li><a href="logout.php" class="logout">Logout</a></li>
        <li><a href="profile_admin.php" class="active">Profile</a></li>
    </ul>
</nav>

<!-- welcome section displaying admin name -->
<div id="user-header">
    <h1>Welcome, <span id="user-name"><?php echo $_SESSION['first_name'] . " " . $_SESSION['last_name']; ?></span> to the YHROCU WorkFlow Management System</h1>
    <h2>View All Tasks Here</h2>
</div>

<!-- filter and search container -->
<div id="filter-container">
    <div class="search-container">
        <!-- search bar input -->
        <input type="text" id="search-bar" placeholder="Search tasks..." onkeyup="searchTasks()">

        <!-- filter icon triggers filter panel visibility -->
        <button id="filter-icon" onclick="toggleFilters()">⚙️ Filters</button>
    </div>

    <!-- collapsible filter panel for status and sorting -->
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

        <!-- reset filters -->
        <button id="clear-filters" onclick="clearFilters()">Clear Filters</button>
    </div>
</div>

<!-- list of tasks rendered here -->
<ul id="task-list"></ul>

<!-- hidden task detail view shown upon task click -->
<div id="task-details" class="task-card hidden">
    <input type="hidden" id="task-id">

    <!-- back button to return to task list -->
    <button class="back-button" onclick="goBack()">⬅ Back to Tasks</button>

    <!-- collapsible status explanation table -->
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
                    <tr><td>Pending</td><td>The task has not been started yet.</td></tr>
                    <tr><td>In Progress</td><td>The task is currently being worked on.</td></tr>
                    <tr><td>To Be Reviewed</td><td>The task is complete and awaiting supervisor review.</td></tr>
                    <tr><td>Complete</td><td>The task has been reviewed and marked as completed.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- task detail information -->
    <h2 id="task-title"></h2>
    <p id="task-description"></p>
    <p><strong>Status:</strong> <span id="task-status"></span></p>

    <!-- dropdown to update task status -->
    <label for="status-dropdown"><strong>Change Task Status:</strong></label>
    <select id="status-dropdown" onchange="updateTaskStatus(document.getElementById('task-id').value, this.value)">
        <option value="Pending">Pending</option>
        <option value="In Progress">In Progress</option>
        <option value="To Be Reviewed">To Be Reviewed</option>
        <option value="Complete">Complete</option>
    </select>

    <p><strong>Created:</strong> <span id="task-created"></span></p>
    <p><strong>Deadline:</strong> <span id="task-deadline"></span></p>

    <!-- primary and additional users -->
    <h3>Created By:</h3>
    <p id="primary-assigned-user">None</p>

    <h3>Additionally Assigned Users:</h3>
    <ul id="additional-users-list"></ul>

    <!-- task action checklist -->
    <h3>Task Actions</h3>
    <ul id="task-actions-list"></ul>

    <!-- progress bar for task completion -->
    <h3>Task Progress</h3>
    <p>Actions Completed: <span id="task-actions"></span></p>
    <div class="progress-bar">
        <div class="progress" id="task-progress"></div>
    </div>

    <!-- comment submission -->
    <h3>Add Comment</h3>
    <textarea id="new-comment" placeholder="Write a comment..."></textarea>
    <button onclick="addComment()">Add Comment</button>

    <!-- uploaded files section -->
    <h3>Uploaded Files</h3>
    <ul id="file-list"></ul>

    <!-- upload form -->
    <div id="file-upload-section">
        <h3>Upload File</h3>
        <form id="upload-form" enctype="multipart/form-data">
            <input type="hidden" id="upload-task-id" name="task_id">
            <input type="file" name="file" required>
            <button type="submit">Upload</button>
        </form>
        <ul id="file-list"></ul>
    </div>

    <!-- list of all comments -->
    <h3>Comments</h3>
    <ul id="comment-list"></ul>

    <!-- log of all changes made to the task -->
    <h3>Running Log</h3>
    <ul id="task-log"></ul>
</div>

<!-- include JS logic -->
<script src="../javascript/tasks_admin.js"></script>

<!-- toast for status messages -->
<div id="toast" class="toast hidden"></div>

</body>
</html>
