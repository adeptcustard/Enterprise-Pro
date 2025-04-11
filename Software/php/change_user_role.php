<?php
//start or resume the session to access user credentials
session_start();

//include database connection file
require_once "db_connect.php";

//set the response content type to JSON
header("Content-Type: application/json");

//check if the current user is logged in and has Admin privileges
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(["success" => false, "message" => "Unauthorised access."]);
    exit;
}

//decode JSON data from request body into associative array
$data = json_decode(file_get_contents("php://input"), true);

//@param int $userId - the ID of the user whose role is to be changed
$userId = $data['user_id'] ?? null;

//@param string $newRole - the new role to assign to the user
$newRole = $data['new_role'] ?? null;

//define the list of allowed user roles
$validRoles = ['User', 'Supervisor', 'Admin'];

//validate that both inputs are present and new role is valid
if (!$userId || !in_array($newRole, $validRoles)) {
    echo json_encode(["success" => false, "message" => "Invalid user or role."]);
    exit;
}

//prevent admins from changing their own role to avoid accidental lockout
if ($userId == $_SESSION['user_id']) {
    echo json_encode(["success" => false, "message" => "You cannot change your own role."]);
    exit;
}

try {
    //check if the user ID exists in the database
    $check = $pdo->prepare("SELECT id FROM users WHERE id = :id");
    $check->execute([':id' => $userId]);

    //if no user found, return error
    if (!$check->fetch()) {
        echo json_encode(["success" => false, "message" => "User not found."]);
        exit;
    }

    //prepare update query to change the user's role
    $update = $pdo->prepare("UPDATE users SET role = :new_role WHERE id = :id");

    //bind values and execute the update
    $update->execute([
        ':new_role' => $newRole,
        ':id' => $userId
    ]);

    //return success response
    echo json_encode(["success" => true, "message" => "✅ Role updated successfully."]);
} 
catch (PDOException $e) {
    //log error to server logs for debugging (not exposed to user)
    error_log("❌ Role update error: " . $e->getMessage());

    //return a generic error message
    echo json_encode(["success" => false, "message" => "Database error occurred."]);
}
?>
