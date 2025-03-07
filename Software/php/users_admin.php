<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../html/login.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - User Management</title>
    <link rel="stylesheet" href="../css/users_admin.css">
</head>
<body>

    <!-- Admin Navigation Bar -->
    <nav class="admin-navbar">
        <ul>
            <li><a href="tasks_admin.php">Dashboard</a></li>
            <li><a href="tasks_management.php">Tasks Management</a></li>
            <li><a href="users_admin.php">Users Management</a></li>
            <li><a href="vehicles_admin.php">Vehicle Inventory</a></li>
            <li><a href="help_requests.php">Help Requests</a></li>
            <li><a href="../php/logout.php" class="logout">Logout</a></li>
        </ul>
    </nav>

    <h1>User Management</h1>

    <!-- Add New User Form -->
    <section>
        <h2>Add User</h2>
        <input type="email" id="user-email" placeholder="Email" required>
        <input type="password" id="user-password" placeholder="Temp Password" required>
        <select id="user-role">
            <option value="User">User</option>
            <option value="Supervisor">Supervisor</option>
            <option value="Admin">Admin</option>
        </select>
        <button onclick="addUser()">➕ Add User</button>
    </section>

    <!-- Existing Users List (With Delete & Role Change Options) -->
    <section>
        <h2>Existing Users</h2>
        <ul id="users-list"></ul>
    </section>

    <!-- Change Password Form -->
    <section>
        <h2>Change User Password</h2>
        <div class="dropdown-container">
            <input type="text" id="search-password-user" placeholder="Search user...">
            <select id="password-user-select"></select>
        </div>
        <input type="password" id="new-password" placeholder="New Password">
        <button onclick="changePassword()">🔑 Change Password</button>
    </section>

    <!-- Role Management -->
    <section>
        <h2>Change User Role</h2>
        <div class="dropdown-container">
            <input type="text" id="search-role-user" placeholder="Search user...">
            <select id="role-user-select"></select>
        </div>
        <select id="new-role">
            <option value="User">User</option>
            <option value="Supervisor">Supervisor</option>
            <option value="Admin">Admin</option>
        </select>
        <button onclick="changeUserRole()">🔄 Change Role</button>
    </section>

    <script src="../javascript/users_admin.js"></script>
</body>
</html>
