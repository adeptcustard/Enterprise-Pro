<?php
//start session to manage user authentication
session_start();

//include database connection
require_once 'db_connect.php';

//ensure user is logged in and has the 'User' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'User') {
    //redirect to login if not authenticated
    header("Location: ../html/login.html");
    exit;
}

//set dark/dyslexic mode based on user preferences
$darkClass = '';
$dyslexicClass = '';

$stmt = $pdo->prepare("SELECT dark_mode, dyslexic_mode FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$prefs = $stmt->fetch(PDO::FETCH_ASSOC);

//apply theme preferences if available
if ($prefs) {
    if ($prefs['dark_mode']) $darkClass = 'dark-mode';
    if ($prefs['dyslexic_mode']) $dyslexicClass = 'dyslexic-mode';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Set character encoding and ensure responsiveness -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | YHROCU</title>

    <!-- Load styling files -->
    <link rel="stylesheet" href="../css/tasks_user.css">
    <link rel="stylesheet" href="../css/global.css">

    <script>
        // prevent user from navigating back to login page
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            location.reload();
        };
    </script>
</head>

<!-- Apply dark and dyslexic mode classes to body -->
<body class="<?php echo $darkClass . ' ' . $dyslexicClass; ?>">

<!-- Navigation bar for users -->
<nav class="admin-navbar">
    <ul>
        <li><a href="tasks_user.php">Dashboard</a></li>
        <li><a href="help_requests.php">Help Requests</a></li>
        <li><a href="logout.php" class="logout">Logout</a></li>
        <li><a href="profile_user.php" class="active">Profile</a></li>
    </ul>
</nav>

<!-- Welcome header -->
<div id="user-header">
    <h1>Welcome, <span id="user-name"><?php echo $_SESSION['first_name'] . " " . $_SESSION['last_name']; ?></span> to the YHROCU WorkFlow Management System</h1>
    <h2>View Your Assigned Tasks Here</h2>
</div>

<!-- Filters and search area -->
<div id="filter-container">
    <div class="search-container">
        <input type="text" id="search-bar" placeholder="Search tasks..." onkeyup="searchTasks()">
        <button id="filter-icon" onclick="toggleFilters()">⚙️ Filters</button>
    </div>

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

        <button id="clear-filters" onclick="clearFilters()">Clear Filters</button>
    </div>
</div>

<!-- List of tasks assigned to user -->
<ul id="task-list"></ul>

<!-- Task detail view (hidden by default) -->
<div id="task-details" class="task-card hidden">
    <input type="hidden" id="task-id">

    <button class="back-button" onclick="goBack()">⬅ Back to Tasks</button>

    <!-- Status guide table -->
    <div class="status-table-container">
        <button id="toggle-status-table" onclick="toggleStatusTable()">ℹ️ View Task Status Guide</button>
        <div id="status-info" class="collapsible hidden">
            <table class="status-table">
                <thead>
                    <tr><th>Status</th><th>Description</th></tr>
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

    <!-- Task info -->
    <h2 id="task-title"></h2>
    <p id="task-description"></p>
    <p><strong>Status:</strong> <span id="task-status"></span></p>

    <!-- Status update dropdown -->
    <label for="status-dropdown"><strong>Change Task Status:</strong></label>
    <select id="status-dropdown" onchange="updateTaskStatus(document.getElementById('task-id').value, this.value)">
        <option value="Pending">Pending</option>
        <option value="In Progress">In Progress</option>
        <option value="To Be Reviewed">To Be Reviewed</option>
        <option value="Complete">Complete</option>
    </select>

    <!-- Dates -->
    <p><strong>Created:</strong> <span id="task-created"></span></p>
    <p><strong>Deadline:</strong> <span id="task-deadline"></span></p>

    <!-- Assigned users -->
    <h3>Created By:</h3>
    <p id="primary-assigned-user">None</p>

    <h3>Additionally Assigned Users:</h3>
    <ul id="additional-users-list"></ul>

    <!-- Actions section -->
    <h3>Task Actions</h3>
    <ul id="task-actions-list"></ul>

    <!-- Progress bar -->
    <h3>Task Progress</h3>
    <p>Actions Completed: <span id="task-actions"></span></p>
    <div class="progress-bar">
        <div class="progress" id="task-progress"></div>
    </div>

    <!-- Comment section -->
    <h3>Add Comment</h3>
    <textarea id="new-comment" placeholder="Write a comment..."></textarea>
    <button onclick="addComment()">Add Comment</button>

    <!-- Files section -->
    <h3>Uploaded Files</h3>
    <ul id="file-list"></ul>

    <!-- Upload file -->
    <div id="file-upload-section">
        <h3>Upload File</h3>
        <form id="upload-form" enctype="multipart/form-data">
            <input type="hidden" id="upload-task-id" name="task_id">
            <input type="file" name="file" required>
            <button type="submit">Upload</button>
        </form>
        <ul id="file-list"></ul>
    </div>

    <!-- Comments list -->
    <h3>Comments</h3>
    <ul id="comment-list"></ul>

    <!-- Running log -->
    <h3>Running Log</h3>
    <ul id="task-log"></ul>
</div>

<!-- Load task functionality -->
<script src="../javascript/tasks_user.js"></script>

<!-- Toast messages -->
<div id="toast" class="toast hidden"></div>

</body>
</html>
