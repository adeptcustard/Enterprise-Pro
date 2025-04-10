<?php
//start the session to access user session data
session_start();

//include the database connection file to access the $pdo object
require_once "db_connect.php";

//set the response content type to JSON
header("Content-Type: application/json");

//ensure task_id is provided in the GET request
if (!isset($_GET['task_id'])) {
    echo json_encode(["success" => false, "message" => "Task ID is required."]);
    exit;
}

//convert the task_id to an integer to ensure it's valid and safe
$task_id = intval($_GET['task_id']);

try {
    //fetch all comments related to the specified task
    $stmt_comments = $pdo->prepare("
        SELECT tc.comment, tc.created_at, u.first_name, u.last_name
        FROM task_comments tc
        JOIN users u ON tc.user_id = u.id
        WHERE tc.task_id = :task_id
        ORDER BY tc.created_at ASC
    ");

    //bind the task ID and execute the query
    $stmt_comments->execute([':task_id' => $task_id]);

    //fetch all comment results as an associative array
    $comments = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);

    //fetch all log entries (task activity) for the specified task
    $stmt_log = $pdo->prepare("
        SELECT tl.action, tl.created_at, u.first_name, u.last_name
        FROM task_log tl
        JOIN users u ON tl.user_id = u.id
        WHERE tl.task_id = :task_id
        ORDER BY tl.created_at ASC
    ");

    //execute the task log query using the task ID
    $stmt_log->execute([':task_id' => $task_id]);

    //retrieve all the task log entries
    $log_entries = $stmt_log->fetchAll(PDO::FETCH_ASSOC);

    //return a success response including both comments and log entries
    echo json_encode([
        "success" => true,
        "comments" => $comments,
        "log_entries" => $log_entries
    ]);

} 
catch (PDOException $e) {
    //return a failure response if a database error occurs
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
