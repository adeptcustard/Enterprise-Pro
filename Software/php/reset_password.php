<?php
//start session to track the current user
session_start();

//include database connection using PDO
require_once "db_connect.php";

//set response content type to JSON
header("Content-Type: application/json");

//check if the user is logged in by verifying the session
if (!isset($_SESSION['user_id'])) {
    //if not authenticated, return error message
    echo json_encode(["success" => false, "message" => "Not authenticated."]);
    exit;
}

//read JSON input from the request body
$data = json_decode(file_get_contents("php://input"), true);

//retrieve and trim the new password value from the input
$newPassword = trim($data['newPassword'] ?? '');

//validate that a new password was provided
if (empty($newPassword)) {
    echo json_encode(["success" => false, "message" => "New password is required."]);
    exit;
}

//hash the new password using a secure algorithm (bcrypt)
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

try {
    //prepare SQL statement to update the password and clear must_change_password flag
    $stmt = $pdo->prepare("
        UPDATE users 
        SET password_hash = :password, must_change_password = false 
        WHERE id = :user_id
    ");

    //execute the statement with the hashed password and current user's ID
    $stmt->execute([
        ':password' => $hashedPassword,
        ':user_id' => $_SESSION['user_id']
    ]);

    //determine which page to redirect to after password update based on role
    $role = $_SESSION['role'];
    $redirectPage = match ($role) {
        'Admin' => '../php/tasks_admin.php',          //admin goes to admin dashboard
        'Supervisor' => '../php/tasks_supervisor.php',//supervisor goes to supervisor dashboard
        default => '../php/tasks_user.php'            //default for normal users
    };

    //return success and redirection path
    echo json_encode(["success" => true, "redirect" => $redirectPage]);

} 
catch (PDOException $e) {
    //log the error for debugging purposes
    error_log("❌ Password update error: " . $e->getMessage());

    //return a generic database error message
    echo json_encode(["success" => false, "message" => "Database error occurred."]);
}
