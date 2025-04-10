<?php 
//start session to access user authentication and role
session_start();

//include database connection file
require_once "db_connect.php";

//set the response type to JSON
header("Content-Type: application/json");

//ensure only Admin users are allowed to delete users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(["success" => false, "message" => "Unauthorised access."]);
    exit;
}

//read the raw input data and decode from JSON
$data = json_decode(file_get_contents("php://input"), true);

//get the user_id to delete or set to 0 if not found
$userIdToDelete = intval($data['user_id'] ?? 0);

//prevent deletion of own account or if no valid user ID provided
if ($userIdToDelete === 0 || $userIdToDelete == $_SESSION['user_id']) {
    echo json_encode(["success" => false, "message" => "Invalid user or you cannot delete your own account."]);
    exit;
}

try {
    //check if the specified user exists in the database
    $check = $pdo->prepare("SELECT id FROM users WHERE id = :id");
    $check->execute([':id' => $userIdToDelete]);

    //if no matching user found, return error
    if (!$check->fetch()) {
        echo json_encode(["success" => false, "message" => "User not found."]);
        exit;
    }

    //delete the user from the users table
    $delete = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $delete->execute([':id' => $userIdToDelete]);

    //return a success message
    echo json_encode(["success" => true, "message" => "User deleted successfully."]);

} 
catch (PDOException $e) {
    //log the database error to server log
    error_log("❌ Delete user error: " . $e->getMessage());

    //return a JSON error response
    echo json_encode(["success" => false, "message" => "Database error occurred."]);
}
?>
