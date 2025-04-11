<?php
//start session to access user authentication data
session_start();

//include database connection script
require_once "db_connect.php";

//set the header to return JSON
header("Content-Type: application/json");

//ensure user is logged in before continuing
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "not logged in"]);
    exit;
}

//store logged-in user's ID
$userId = $_SESSION['user_id'];

//determine user role (default to 'User' if not set)
$role = $_SESSION['role'] ?? 'User';

//get task ID and new status from POST request
$taskId = $_POST['task_id'] ?? null;
$newStatus = $_POST['new_status'] ?? null;

//define all allowed task statuses
$validStatuses = ['Pending', 'In Progress', 'To Be Reviewed', 'Complete'];

//check if taskId or newStatus is missing or invalid
if (!$taskId || !$newStatus || !in_array($newStatus, $validStatuses)) {
    echo json_encode(["success" => false, "message" => "invalid input"]);
    exit;
}

try {
    /**
     * fetch the current status of the task to determine if transition is allowed
     * @param int $taskId task to be updated
     */
    $stmt = $pdo->prepare("SELECT status FROM tasks WHERE id = :task_id");
    $stmt->execute([':task_id' => $taskId]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    //return error if task does not exist
    if (!$current) {
        echo json_encode(["success" => false, "message" => "task not found"]);
        exit;
    }

    //get the current status from the fetched data
    $currentStatus = $current['status'];

    //define valid transitions from each current status
    $allowed = [
        'Pending' => ['In Progress', 'To Be Reviewed', 'Complete'],
        'In Progress' => ['Pending', 'To Be Reviewed', 'Complete'],
        'To Be Reviewed' => ['In Progress', 'Complete'],
        'Complete' => ['To Be Reviewed']
    ];

    //check if new status is allowed from the current status
    if (!in_array($newStatus, $allowed[$currentStatus])) {
        echo json_encode(["success" => false, "message" => "invalid status transition"]);
        exit;
    }

    //if attempting to move to 'To Be Reviewed' or 'Complete', ensure all actions are completed
    if (in_array($newStatus, ['To Be Reviewed', 'Complete'])) {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN completed THEN 1 ELSE 0 END) AS completed
            FROM task_actions
            WHERE task_id = :task_id
        ");
        $stmt->execute([':task_id' => $taskId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        //prevent transition if not all actions are completed
        if ($result['total'] == 0 || $result['completed'] < $result['total']) {
            echo json_encode(["success" => false, "message" => "all actions must be completed before changing status to '$newStatus'."]);
            exit;
        }
    }

    //only allow 'Complete' status if user is Supervisor or Admin
    if ($newStatus === 'Complete' && !in_array($role, ['Supervisor', 'Admin'])) {
        echo json_encode(["success" => false, "message" => "only supervisors and admins can mark tasks as complete."]);
        exit;
    }

    /**
     * update the task status and record the last updated details
     * @param int $taskId the task being updated
     * @param string $newStatus the status being set
     * @param int $userId the user making the change
     */
    $updateStmt = $pdo->prepare("
        UPDATE tasks 
        SET status = :status, last_updated = NOW(), last_updated_by = :user_id 
        WHERE id = :task_id
    ");
    $updateStmt->execute([
        ':status' => $newStatus,
        ':user_id' => $userId,
        ':task_id' => $taskId
    ]);

    /**
     * log the status change in the task log
     * @param string $logMessage description of the action performed
     */
    $logStmt = $pdo->prepare("
        INSERT INTO task_log (task_id, user_id, action) 
        VALUES (:task_id, :user_id, :action)
    ");
    $logMessage = "Status changed from $currentStatus to $newStatus";
    $logStmt->execute([
        ':task_id' => $taskId,
        ':user_id' => $userId,
        ':action' => $logMessage
    ]);

    //return success response to frontend
    echo json_encode(["success" => true]);

} 
catch (PDOException $e) {
    //handle and log any database errors
    echo json_encode(["success" => false, "message" => "db error: " . $e->getMessage()]);
}
