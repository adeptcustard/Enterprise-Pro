<?php
//start or resume the session to validate the user's credentials
session_start();

//include the database connection configuration
require_once "db_connect.php";

//set the response format to JSON
header("Content-Type: application/json");

//only allow access if the user is logged in and is an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit;
}

//decode JSON input from request body into associative array
$data = json_decode(file_get_contents("php://input"), true);

//@param int $userId - ID of the user whose password is being changed
$userId = $data['user_id'] ?? null;

//@param string $newPassword - the new plain text password provided by the admin
$newPassword = trim($data['new_password'] ?? '');

//check if both required fields are provided
if (!$userId || empty($newPassword)) {
    echo json_encode(["success" => false, "message" => "User ID and new password are required."]);
    exit;
}

//hash the new password using PHP's built-in password hashing algorithm
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

try {
    //prepare an update statement to change the user's password
    $stmt = $pdo->prepare("UPDATE users SET password_hash = :password WHERE id = :id");

    //execute the update with bound parameters
    $stmt->execute([
        ':password' => $hashedPassword,
        ':id' => $userId
    ]);

    //return a success response
    echo json_encode(["success" => true, "message" => "Password updated successfully."]);
} 
catch (PDOException $e) {
    //log the actual database error to server logs (not exposed to user)
    error_log("❌ Password update error: " . $e->getMessage());

    //return a generic error response to the client
    echo json_encode(["success" => false, "message" => "Database error occurred."]);
}
?>
