<?php 
//start the session to manage user authentication
session_start();

//include database connection
require_once 'db_connect.php';

//redirect to login if user is not a supervisor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Supervisor') {
    header("Location: ../html/login.html");
    exit;
}

//initialize dark and dyslexic mode classes
$darkClass = '';
$dyslexicClass = '';

//fetch the current user's display preferences
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
    <!-- set character encoding -->
    <meta charset="UTF-8">

    <!-- page title -->
    <title>Supervisor | Tasks Management | YHROCU</title>

    <!-- link stylesheets -->
    <link rel="stylesheet" href="../css/tasks_management.css">
    <link rel="stylesheet" href="../css/global.css">
</head>

<!-- apply dark/dyslexic mode classes to body -->
<body class="<?php echo $darkClass . ' ' . $dyslexicClass; ?>">

<!-- supervisor navigation bar -->
<nav class="admin-navbar">
    <ul>
        <li><a href="tasks_supervisor.php">Dashboard</a></li>
        <li><a href="help_requests.php">Help Requests</a></li>
        <li><a href="tasks_supervisor_management.php">Tasks Management</a></li>
        <li><a href="logout.php" class="logout">Logout</a></li>
        <li><a href="profile_supervisor.php" class="active">Profile</a></li>
    </ul>
</nav>

<!-- main heading -->
<h1>Tasks Management</h1>

<!-- create new task collapsible section -->
<section class="collapsible-section">
  <button class="collapsible">➕ Create New Task</button>
  <div class="collapsible-content">
    
    <!-- task title input -->
    <label for="task-title"><strong>Enter Task Title:</strong></label>
    <input type="text" id="task-title" placeholder="Task Title...">

    <!-- task description input -->
    <label for="task-description"><strong>Enter Task Description:</strong></label>
    <textarea id="task-description" placeholder="Task Description..."></textarea>

    <!-- task deadline input -->
    <label for="task-deadline"><strong>Deadline:</strong></label>
    <input type="datetime-local" id="task-deadline">

    <!-- task priority dropdown -->
    <label for="task-priority"><strong>Choose Priority:</strong></label>
    <select id="task-priority">
      <option value="Low">Low</option>
      <option value="Medium">Medium</option>
      <option value="High">High</option>
    </select>

    <!-- team name input -->
    <label for="task-team"><strong>Choose Team:</strong></label>
    <input id="task-team" type="text" placeholder="Enter Team Name...">

    <!-- additional users assignment -->
    <div class="collapsible-subsection">
      <button class="collapsible">👥 Assign Additional Users</button>
      <div class="collapsible-content" id="additional-user-container">
          <input type="text" id="user-search" placeholder="Search users...">
          <div id="assignable-users-list"></div>
          <div id="selected-users-display" class="selected-users-box">
              <p>No additional users selected.</p>
          </div>
      </div>
    </div>

    <br>

    <!-- task actions section -->
    <div id="task-actions-container">
      <label><strong>Task Actions:</strong></label>
      <div id="task-actions-list"></div>
      <input type="text" id="task-action-input" placeholder="Add an action...">
      <button type="button" onclick="addTaskAction()">➕ Add Action</button>
    </div>

    <!-- create task button -->
    <button onclick="createTask()" id="create-task-btn">➕ Create Task</button>
  </div>
</section>

<!-- view all tasks collapsible section -->
<section class="collapsible-section" id="task-list-section">
  <button class="collapsible">📋 View All Tasks</button>
  <div class="collapsible-content">
      <input type="text" id="search-tasks" placeholder="Search tasks...">
      <ul id="task-list"></ul>
  </div>
</section>

<!-- placeholder for dynamic task details view -->
<section id="task-details" class="collapsible-section" style="display: none;"></section>

<!-- include JavaScript logic -->
<script src="../javascript/tasks_management.js"></script>

<!-- toast notifications container -->
<div id="toast" class="toast hidden"></div>

</body>
</html>
