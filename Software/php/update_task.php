<?php 
//include database connection
require_once 'db_connect.php';

//set the content type to JSON
header('Content-Type: application/json');

//get the raw JSON input sent in the request body
$data = json_decode(file_get_contents("php://input"), true);

//check that all required fields are present (status is not required)
if (
    !isset($data['id']) || 
    !isset($data['title']) || 
    !isset($data['description']) || 
    !isset($data['deadline']) || 
    !isset($data['priority'])
) {
    //return error if any required fields are missing
    echo json_encode(["success" => false, "message" => "❌ missing required fields."]);
    exit;
}

//extract fields from the request
$taskId = $data['id']; //task id to update
$title = $data['title']; //updated title
$description = $data['description']; //updated description
$deadline = $data['deadline']; //new deadline datetime
$priority = $data['priority']; //task priority (Low/Medium/High)
$additionalUsers = $data['additional_users'] ?? []; //optional list of additional user IDs

try {
    //begin transaction to ensure consistency between update and user re-assignment
    $pdo->beginTransaction();

    /**
     * update the task's metadata in the tasks table
     * @param int $taskId the ID of the task being updated
     * @param string $title new title
     * @param string $description new description
     * @param string $deadline updated deadline datetime
     * @param string $priority task priority level
     */
    $updateStmt = $pdo->prepare("
        UPDATE tasks 
        SET title = :title, description = :description, deadline = :deadline, 
            priority = :priority, last_updated = NOW()
        WHERE id = :id
    ");

    //execute update query
    $updateStmt->execute([
        ":title" => $title,
        ":description" => $description,
        ":deadline" => $deadline,
        ":priority" => $priority,
        ":id" => $taskId
    ]);

    /**
     * remove all previous additional users assigned to this task
     * ensures clean slate before inserting new ones
     */
    $pdo->prepare("DELETE FROM task_assignments WHERE task_id = :task_id")
        ->execute([":task_id" => $taskId]);

    /**
     * re-insert all currently selected additional users
     * @param array $additionalUsers list of user IDs
     */
    $addStmt = $pdo->prepare("INSERT INTO task_assignments (task_id, user_id) VALUES (:task_id, :user_id)");
    foreach ($additionalUsers as $userId) {
        $addStmt->execute([
            ":task_id" => $taskId,
            ":user_id" => $userId
        ]);
    }

    //commit the transaction after successful updates
    $pdo->commit();

    //return success response
    echo json_encode(["success" => true, "message" => "✅ task updated successfully."]);
} 
catch (Exception $e) {
    //rollback any changes if an error occurs
    $pdo->rollBack();

    //return failure response with error message
    echo json_encode(["success" => false, "message" => "❌ error updating task: " . $e->getMessage()]);
}
