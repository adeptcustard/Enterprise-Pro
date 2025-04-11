<?php
//set response content type to JSON
header("Content-Type: application/json");

//include database connection file (provides $pdo)
require_once("db_connect.php"); 

//check if the HTTP request method is DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    //reject requests that aren't DELETE
    echo json_encode(["success" => false, "message" => "❌ Invalid request method."]);
    exit;
}

//parse the raw request body (DELETE requests don't populate $_POST)
parse_str(file_get_contents("php://input"), $deleteData);

//get the task ID from parsed data or set to null if missing
$taskId = $deleteData['id'] ?? null;

//check if task ID was provided
if (!$taskId) {
    echo json_encode(["success" => false, "message" => "❌ Task ID is required."]);
    exit;
}

try {
    //start a database transaction to ensure consistency
    $pdo->beginTransaction();

    //delete all task actions related to the task
    $stmtActions = $pdo->prepare("DELETE FROM task_actions WHERE task_id = ?");
    $stmtActions->execute([$taskId]);

    //delete all task assignments related to the task
    $stmtAssignments = $pdo->prepare("DELETE FROM task_assignments WHERE task_id = ?");
    $stmtAssignments->execute([$taskId]);

    //delete the task itself from the tasks table
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$taskId]);

    //commit all deletions to the database
    $pdo->commit();

    //return a success response to the frontend
    echo json_encode(["success" => true, "message" => "🗑 Task deleted successfully."]);
} 
catch (PDOException $e) {
    //rollback changes if any error occurs to maintain database integrity
    $pdo->rollBack();

    //return error message in JSON format
    echo json_encode(["success" => false, "message" => "❌ Failed to delete task.", "error" => $e->getMessage()]);
}
?>
