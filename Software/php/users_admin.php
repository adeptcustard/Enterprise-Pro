<?php
//start session to access user authentication
session_start();

//include database connection
require_once 'db_connect.php';

//check if the user is logged in and has the 'admin' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    //redirect unauthorized user to login page
    header("Location: ../html/login.html");
    exit;
}

//initialize CSS class variables for theme preferences
$darkClass = '';
$dyslexicClass = '';

//fetch current user's theme preferences from database
$stmt = $pdo->prepare("SELECT dark_mode, dyslexic_mode FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$prefs = $stmt->fetch(PDO::FETCH_ASSOC);

//apply CSS class based on preferences
if ($prefs) {
    if ($prefs['dark_mode']) $darkClass = 'dark-mode';
    if ($prefs['dyslexic_mode']) $dyslexicClass = 'dyslexic-mode';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!--define character set and page title-->
    <meta charset="UTF-8">
    <title>Admin | User Management | YHROCU</title>

    <!--link custom stylesheets-->
    <link rel="stylesheet" href="../css/users_admin.css">
    <link rel="stylesheet" href="../css/global.css">
</head>

<!--apply dark and dyslexic mode classes to the body if set-->
<body class="<?php echo $darkClass . ' ' . $dyslexicClass; ?>">

<!--admin navigation menu for switching between dashboard sections-->
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

<!--main page heading-->
<h1>Users Management</h1>

<!--section to add a new user account-->
<section class="collapsible-section">
    <button class="collapsible">➕ Add New User</button>
    <div class="collapsible-content">
        <input type="text" id="user-first-name" placeholder="First Name" required>
        <input type="text" id="user-last-name" placeholder="Last Name" required>
        <input type="email" id="user-email" placeholder="Email" required>
        <input type="password" id="user-password" placeholder="Temporary Password" required>
        <select id="user-role">
            <option value="User">User</option>
            <option value="Supervisor">Supervisor</option>
            <option value="Admin">Admin</option>
        </select>
        <button onclick="addUser()">➕ Add User</button>
    </div>
</section>

<!--section to view a searchable list of all existing users-->
<section class="collapsible-section">
    <button class="collapsible">👥 View Existing Users</button>
    <div class="collapsible-content">
        <input type="text" id="search-existing-users" placeholder="Search users...">
        <ul id="users-list"></ul>
    </div>
</section>

<!--section to change a selected user's password-->
<section class="collapsible-section">
    <button class="collapsible">🔑 Change User Password</button>
    <div class="collapsible-content">
        <input type="text" id="search-users-change-pass" placeholder="Search users...">

        <br id="pass-br">

        <!--user list container rendered as buttons-->
        <div id="password-user-list-container" class="user-collapsible-list"></div>

        <!--password form shown after user is selected-->
        <div id="password-form" style="display:none;">
            <p id="password-selected-name" class="selected-user"></p>
            <input type="password" id="new-password" placeholder="New Password">
            <button onclick="changePassword()">Update Password</button>
            <button class="reset-btn" onclick="resetPasswordSelection()">🔁 Change User</button>
        </div>
    </div>
</section>

<!--section to change a selected user's role-->
<section class="collapsible-section">
    <button class="collapsible">🔄 Change User Role</button>
    <div class="collapsible-content">
        <input type="text" id="search-users-change-role" placeholder="Search users...">

        <br id="role-br">

        <!--list of users as role buttons-->
        <div id="role-user-list-container" class="user-collapsible-list"></div>

        <!--role form shown after user is selected-->
        <div id="role-form" style="display:none;">
            <p id="role-selected-name" class="selected-user"></p>
            <select id="new-role">
                <option value="User">User</option>
                <option value="Supervisor">Supervisor</option>
                <option value="Admin">Admin</option>
            </select>
            <button onclick="changeUserRole()">Update Role</button>
            <button class="reset-btn" onclick="resetRoleSelection()">🔁 Change User</button>
        </div>
    </div>
</section>

<!--include JavaScript logic for user management functions-->
<script src="../javascript/users_admin.js"></script>

<!--toast container for showing status messages to the user-->
<div id="toast" class="toast hidden"></div>

</body>
</html>
