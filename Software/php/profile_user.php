<!DOCTYPE html>
<html lang="en">

<?php
//start session to allow access to user data like dark/dyslexic mode
session_start();

//include the database connection
require_once 'db_connect.php';

//initialize class variables for applying themes
$darkClass = '';
$dyslexicClass = '';

//check if user is logged in
if (isset($_SESSION['user_id'])) {
    //prepare SQL to get visual preference settings
    $stmt = $pdo->prepare("SELECT dark_mode, dyslexic_mode FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $prefs = $stmt->fetch(PDO::FETCH_ASSOC);

    //apply CSS classes based on preferences
    if ($prefs) {
        if ($prefs['dark_mode']) $darkClass = 'dark-mode';
        if ($prefs['dyslexic_mode']) $dyslexicClass = 'dyslexic-mode';
    }
}
?>

<head>
    <!--set character encoding for proper text rendering-->
    <meta charset="UTF-8">

    <!--page title shown in browser tab-->
    <title>User Profile | YHROCU</title>

    <!--load CSS file for profile-specific styling-->
    <link rel="stylesheet" href="../css/profile.css">

    <!--load global CSS file for shared styles like layout and typography-->
    <link rel="stylesheet" href="../css/global.css">
</head>

<!--apply dynamic theme classes to the <body> tag based on user preferences-->
<body class="<?php echo $darkClass . ' ' . $dyslexicClass; ?>">

<!--navigation bar for users (User role version)-->
<nav class="admin-navbar">
    <ul>
        <!--link to user dashboard-->
        <li><a href="tasks_user.php">Dashboard</a></li>

        <!--link to help requests page-->
        <li><a href="help_requests.php">Help Requests</a></li>

        <!--logout link-->
        <li><a href="logout.php" class="logout">Logout</a></li>

        <!--highlight active profile page-->
        <li><a href="profile_user.php" class="active">Profile</a></li>
    </ul>
</nav>

<!--main heading for the profile page-->
<h1 id="user-header">Admin Profile</h1>

<!--container for profile form and password change section-->
<div class="profile-container">

    <!--form showing the user's personal details (read-only)-->
    <form id="profile-form">
        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" disabled>

        <label for="last_name">Last Name:</label>
        <input type="text" id="last_name" name="last_name" disabled>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" disabled>
    </form>

    <!--section to change the user's password-->
    <div class="change-password-section">
        <h2>Change Password</h2>
        <form id="password-form">
            <!--current password field-->
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" required>

            <!--new password input-->
            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required>

            <!--confirmation field to ensure new password is typed correctly-->
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <!--submit button to update the password-->
            <button type="submit">Update Password</button>
        </form>
    </div>

    <br><br>

    <!--buttons for toggling accessibility preferences-->
    <div class="mode-toggle">
        <button id="dark-mode-toggle">Toggle Dark Mode</button>
        <button id="dyslexic-toggle">Toggle Dyslexic Mode</button>
    </div>
</div>

<!--load JavaScript to handle theme toggling and password change logic-->
<script src="../javascript/profile.js"></script>

<!--toast element to display feedback messages-->
<div id="toast" class="toast hidden"></div>

</body>
</html>
