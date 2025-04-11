<?php
//start session to track logged-in user
session_start();

//include database connection
require_once 'db_connect.php';

//set json response header so the client knows the response is json
header('Content-Type: application/json');

//get the raw input stream and decode json into an associative array
$data = json_decode(file_get_contents("php://input"), true);

//extract the current password entered by the user, or fallback to an empty string if missing
$current = $data['current'] ?? '';

//extract the new password the user wants to use, or fallback to an empty string if missing
$new = $data['new_password'] ?? '';

//check if the user is logged in by verifying session contains a user_id
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'not logged in']);
    exit;
}

try {
    /**
     * fetch the stored password hash for the currently logged-in user
     * @param int $_SESSION['user_id'] the id of the user currently logged in
     */
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $stored = $stmt->fetch(PDO::FETCH_ASSOC);

    //verify the provided current password matches the stored hash
    if (!$stored || !password_verify($current, $stored['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'current password is incorrect']);
        exit;
    }

    /**
     * hash the new password securely using PHP’s default algorithm (bcrypt or better depending on version)
     * @param string $new the new password to be saved
     * @return string the securely hashed password
     */
    $newHash = password_hash($new, PASSWORD_DEFAULT);

    /**
     * update the user’s password in the database
     * @param int $_SESSION['user_id'] the id of the current user
     * @param string $newHash the newly hashed password
     */
    $update = $pdo->prepare("UPDATE users SET password_hash = :new WHERE id = :id");

    $update->execute([
        ':new' => $newHash,
        ':id' => $_SESSION['user_id']
    ]);
    
    //return success response back to frontend in json format
    echo json_encode(['success' => true, 'message' => '✅ password updated']);
} 
catch (PDOException $e) {
    /**
     * handle database-related errors (e.g. connection failure, SQL error)
     * logs the error server-side and returns a generic error message to avoid exposing details
     */
    error_log("❌ password update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'database error occurred']);
}
