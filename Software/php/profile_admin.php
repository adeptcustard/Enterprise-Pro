<!DOCTYPE html>
<html lang="en">

<?php
//start a session to access the logged-in user's data
session_start();

//include the database connection to query user preferences
require_once 'db_connect.php';

//default classes for dark and dyslexic modes
$darkClass = '';
$dyslexicClass = '';

//if a user is logged in, fetch their theme preferences
if (isset($_SESSION['user_id'])) {
    //prepare and execute a query to fetch dark/dyslexic mode settings
    $stmt = $pdo->prepare("SELECT dark_mode, dyslexic_mode FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $prefs = $stmt->fetch(PDO::FETCH_ASSOC);

    //apply CSS classes based on user preference
    if ($prefs) {
        if ($prefs['dark_mode']) $darkClass = 'dark-mode';
        if ($prefs['dyslexic_mode']) $dyslexicClass = 'dyslexic-mode';
    }
}
?>

<head>
    <meta charset="UTF-8">
    <title>Admin Profile | YHROCU</title>

    <!-- link to the custom CSS for this page -->
    <link rel="stylesheet" href="../css/profile.css">

    <!-- link to the global shared CSS for layout and themes -->
    <link rel="stylesheet" href="../css/global.css">
</head>

<!-- apply user-specific dark and dyslexic mode classes to body -->
<body class="<?php echo $darkClass . ' ' . $dyslexicClass; ?>">

<!-- navigation bar specific to admin role -->
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

<!-- page heading -->
<h1 id="user-header">Admin Profile</h1>

<!-- main container for profile details and settings -->
<div class="profile-container">

    <!-- user profile display section (non-editable) -->
    <form id="profile-form">
        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" disabled>

        <label for="last_name">Last Name:</label>
        <input type="text" id="last_name" name="last_name" disabled>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" disabled>
    </form>

    <!-- password update section for changing current password -->
    <div class="change-password-section">
        <h2>Change Password</h2>
        <form id="password-form">
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" required>

            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required>

            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <button type="submit">Update Password</button>
        </form>
    </div>

    <!-- dark mode and dyslexic mode toggle buttons -->
    <br><br>
    <div class="mode-toggle">
        <button id="dark-mode-toggle">Toggle Dark Mode</button>
        <button id="dyslexic-toggle">Toggle Dyslexic Mode</button>
    </div>
</div>

<!-- include JavaScript logic for profile settings and theme toggles -->
<script src="../javascript/profile.js"></script>

<!-- toast notification container -->
<div id="toast" class="toast hidden"></div>

</body>
</html>
