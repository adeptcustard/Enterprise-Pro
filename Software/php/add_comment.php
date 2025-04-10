<?php
//start a new session or resume the current one
session_start();

//include the database connection file
require_once "db_connect.php";

//set response content type to JSON
header("Content-Type: application/json");

//check if the request is valid: user must be logged in and task_id and comment must be present in POST
if (!isset($_SESSION['user_id']) || !isset($_POST['task_id']) || !isset($_POST['comment'])) {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
    exit;
}

//@param int $user_id - the ID of the logged-in user from session
$user_id = $_SESSION['user_id'];

//@param int $task_id - the task ID provided in the POST request
$task_id = intval($_POST['task_id']);

//@param string $comment - the trimmed comment text from POST
$comment = trim($_POST['comment']);

//check if the comment is empty
if (empty($comment)) {
    echo json_encode(["success" => false, "message" => "Comment cannot be empty."]);
    exit;
}

try {
    //insert the user's comment into the task_comments table
    $stmt = $pdo->prepare("
        INSERT INTO task_comments (task_id, user_id, comment) 
        VALUES (:task_id, :user_id, :comment)
    ");

    //execute the insert with actual values
    $stmt->execute([
        ':task_id' => $task_id,
        ':user_id' => $user_id,
        ':comment' => $comment
    ]);

    //log the comment action into task_log table for audit purposes
    $stmt_log = $pdo->prepare("
        INSERT INTO task_log (task_id, user_id, action) 
        VALUES (:task_id, :user_id, :action)
    ");

    //generate a log entry that includes the comment content
    $stmt_log->execute([
        ':task_id' => $task_id,
        ':user_id' => $user_id,
        ':action' => "Added a comment: \"$comment\""
    ]);

    //return a success response
    echo json_encode(["success" => true, "message" => "Comment added successfully."]);

} 
catch (PDOException $e) {
    //handle database errors gracefully
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
