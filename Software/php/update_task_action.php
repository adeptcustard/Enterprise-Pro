<?php 
//start session to access logged-in user's session data
session_start();

//include database connection
require_once "db_connect.php";

//set content type to json
header("Content-Type: application/json");

//check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "not logged in"]);
    exit;
}

//check if action_id is provided in GET request
if (!isset($_GET['action_id'])) {
    echo json_encode(["success" => false, "message" => "action id missing"]);
    exit;
}

//sanitize and convert action_id to integer
$actionId = intval($_GET['action_id']);

try {
    /**
     * fetch the action details using action ID
     * @param int $actionId the unique ID of the task action being toggled
     */
    $stmt = $pdo->prepare("SELECT id, completed, task_id, action_description FROM task_actions WHERE id = :id");
    $stmt->execute([':id' => $actionId]);
    $action = $stmt->fetch(PDO::FETCH_ASSOC);

    //check if action exists
    if (!$action) {
        echo json_encode(["success" => false, "message" => "action not found"]);
        exit;
    }

    //toggle the action completion status
    $newStatus = $action['completed'] ? 0 : 1;

    //update the action in the database with the new status
    $updateStmt = $pdo->prepare("UPDATE task_actions SET completed = :status WHERE id = :id");
    $updateStmt->execute([
        ':status' => $newStatus,
        ':id' => $actionId
    ]);

    /**
     * retrieve the user's full name for logging
     * @param int $_SESSION['user_id'] the currently logged-in user's ID
     */
    $userStmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = :id");
    $userStmt->execute([':id' => $_SESSION['user_id']]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    //fallback in case user is not found (shouldn't happen)
    $userName = $user ? "{$user['first_name']} {$user['last_name']}" : "unknown user";

    //determine new status text for logging
    $statusText = $newStatus ? "marked as complete" : "marked as incomplete";

    //build log message for task_log
    $logMessage = "Action: {$action['action_description']} was {$statusText} by {$userName}.";

    /**
     * insert a new log entry for this action change
     * @param int $action['task_id'] the ID of the related task
     * @param int $_SESSION['user_id'] the user making the update
     * @param string $logMessage the formatted log message
     */
    $logStmt = $pdo->prepare("
        INSERT INTO task_log (task_id, user_id, action)
        VALUES (:task_id, :user_id, :action)
    ");
    
    $logStmt->execute([
        ':task_id' => $action['task_id'],
        ':user_id' => $_SESSION['user_id'],
        ':action' => $logMessage
    ]);

    //respond with success
    echo json_encode(["success" => true]);

} 
catch (PDOException $e) {
    //return database error with error message
    echo json_encode(["success" => false, "message" => "db error: " . $e->getMessage()]);
}
