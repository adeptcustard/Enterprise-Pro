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

//initialize classes for dark mode and dyslexic mode
$darkClass = '';
$dyslexicClass = '';

//fetch user preferences for accessibility modes
$stmt = $pdo->prepare("SELECT dark_mode, dyslexic_mode FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$prefs = $stmt->fetch(PDO::FETCH_ASSOC);

//apply user preferences if available
if ($prefs) {
    if ($prefs['dark_mode']) $darkClass = 'dark-mode';
    if ($prefs['dyslexic_mode']) $dyslexicClass = 'dyslexic-mode';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Tasks Management | YHROCU</title>

    <!-- link stylesheet for task management layout -->
    <link rel="stylesheet" href="../css/tasks_management.css">

    <!-- link global styling for layout, fonts, and accessibility -->
    <link rel="stylesheet" href="../css/global.css">
</head>

<!-- apply dark and dyslexic mode classes dynamically -->
<body class="<?php echo $darkClass . ' ' . $dyslexicClass; ?>">

<!-- navigation menu for admin -->
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

<!-- heading for the tasks management page -->
<h1>Tasks Management</h1>

<!-- section for creating a new task -->
<section class="collapsible-section">
    <button class="collapsible">➕ Create New Task</button>
    <div class="collapsible-content">
        <label for="task-title"><strong>Enter Task Title:</strong></label>
        <input type="text" id="task-title" placeholder="Task Title...">

        <label for="task-description"><strong>Enter Task Description:</strong></label>
        <textarea id="task-description" placeholder="Task Description..."></textarea>

        <label for="task-deadline"><strong>Deadline:</strong></label>
        <input type="datetime-local" id="task-deadline">

        <label for="task-priority"><strong>Choose Priority:</strong></label>
        <select id="task-priority">
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
        </select>

        <label for="task-team"><strong>Choose Team:</strong></label>
        <input id="task-team" type="text" placeholder="Enter Team Name...">

        <!-- subsection to assign additional users to the task -->
        <div class="collapsible-subsection">
            <button class="collapsible">👥 Assign Additional Users</button>
            <div class="collapsible-content" id="additional-user-container">
                <input type="text" id="user-search" placeholder="Search users...">
                <div id="assignable-users-list"></div>

                <!-- display selected users dynamically -->
                <div id="selected-users-display" class="selected-users-box">
                    <p>No additional users selected.</p>
                </div>
            </div>
        </div>

        <br>

        <!-- section to add task-specific actions -->
        <div id="task-actions-container">
            <label><strong>Task Actions:</strong></label>
            <div id="task-actions-list"></div>
            <input type="text" id="task-action-input" placeholder="Add an action...">
            <button type="button" onclick="addTaskAction()">➕ Add Action</button>
        </div>

        <!-- button to create task -->
        <button onclick="createTask()" id="create-task-btn">➕ Create Task</button>
    </div>
</section>

<!-- section to view and search all tasks -->
<section class="collapsible-section" id="task-list-section">
    <button class="collapsible">📋 View All Tasks</button>
    <div class="collapsible-content">
        <input type="text" id="search-tasks" placeholder="Search tasks...">
        <ul id="task-list"></ul>
    </div>
</section>

<!-- hidden section that displays task details once selected -->
<section id="task-details" class="collapsible-section" style="display: none;"></section>

<!-- include JavaScript logic for task management -->
<script src="../javascript/tasks_management.js"></script>

<!-- toast element for feedback messages -->
<div id="toast" class="toast hidden"></div>

</body>
</html>
