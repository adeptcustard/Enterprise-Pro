<!DOCTYPE html>
<html lang="en">

<?php
//start session to access user preferences and authentication
session_start();

//include database connection to retrieve stored user preferences
require_once 'db_connect.php';

//initialize variables for CSS class toggles
$darkClass = '';
$dyslexicClass = '';

//check if a user is logged in and fetch display preferences
if (isset($_SESSION['user_id'])) {
    //prepare query to retrieve dark mode and dyslexic mode preferences
    $stmt = $pdo->prepare("SELECT dark_mode, dyslexic_mode FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $prefs = $stmt->fetch(PDO::FETCH_ASSOC);

    //if preferences exist, apply appropriate CSS classes
    if ($prefs) {
        if ($prefs['dark_mode']) $darkClass = 'dark-mode';
        if ($prefs['dyslexic_mode']) $dyslexicClass = 'dyslexic-mode';
    }
}
?>

<head>
    <!-- set character encoding for page -->
    <meta charset="UTF-8">

    <!-- set page title for browser tab -->
    <title>Supervisor Profile | YHROCU</title>

    <!-- include CSS for supervisor profile layout -->
    <link rel="stylesheet" href="../css/profile.css">

    <!-- include global site-wide styles -->
    <link rel="stylesheet" href="../css/global.css">
</head>

<!-- apply accessibility CSS classes dynamically based on user settings -->
<body class="<?php echo $darkClass . ' ' . $dyslexicClass; ?>">

<!-- supervisor-specific navigation bar -->
<nav class="admin-navbar">
    <ul>
        <!-- dashboard link for task overview -->
        <li><a href="tasks_supervisor.php">Dashboard</a></li>

        <!-- help request handling -->
        <li><a href="help_requests.php">Help Requests</a></li>

        <!-- access task management features -->
        <li><a href="tasks_supervisor_management.php">Tasks Management</a></li>

        <!-- logout functionality -->
        <li><a href="logout.php" class="logout">Logout</a></li>

        <!-- currently active profile page -->
        <li><a href="profile_supervisor.php" class="active">Profile</a></li>
    </ul>
</nav>

<!-- profile section heading -->
<h1 id="user-header">Supervisor Profile</h1>

<!-- container for user profile content -->
<div class="profile-container">

    <!-- form displaying user's personal details (non-editable) -->
    <form id="profile-form">
        <!-- first name field -->
        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" disabled>

        <!-- last name field -->
        <label for="last_name">Last Name:</label>
        <input type="text" id="last_name" name="last_name" disabled>

        <!-- email address field -->
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" disabled>
    </form>

    <!-- section for updating current password -->
    <div class="change-password-section">
        <h2>Change Password</h2>

        <!-- password update form -->
        <form id="password-form">
            <!-- current password input -->
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" required>

            <!-- new password input -->
            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required>

            <!-- confirmation of new password -->
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <!-- submit button to trigger update -->
            <button type="submit">Update Password</button>
        </form>
    </div>

    <br><br>

    <!-- section for toggling dark mode and dyslexic font -->
    <div class="mode-toggle">
        <button id="dark-mode-toggle">Toggle Dark Mode</button>
        <button id="dyslexic-toggle">Toggle Dyslexic Mode</button>
    </div>
</div>

<!-- include JavaScript for profile page behavior -->
<script src="../javascript/profile.js"></script>

<!-- toast notification area (e.g. for success/failure messages) -->
<div id="toast" class="toast hidden"></div>

</body>
</html>
