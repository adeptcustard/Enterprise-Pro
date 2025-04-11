<?php
//start the session to access session variables
session_start();

//include the database connection file to use $pdo
require_once "db_connect.php";

//set response header to JSON format
header("Content-Type: application/json");

//check if the user is logged in by verifying the session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

//check if the task_id is provided via GET parameters
if (!isset($_GET['task_id'])) {
    echo json_encode(["success" => false, "message" => "Missing task ID"]);
    exit;
}

//store the task ID passed from the frontend
$task_id = $_GET['task_id'];

try {
    //prepare SQL statement to fetch all files uploaded for this task
    $stmt = $pdo->prepare("
        SELECT tf.id, tf.file_name, tf.file_path, tf.uploaded_at,
               u.first_name, u.last_name
        FROM task_files tf
        LEFT JOIN users u ON tf.uploaded_by = u.id
        WHERE tf.task_id = :task_id
        ORDER BY tf.uploaded_at DESC
    ");

    //bind the task_id and execute the query
    $stmt->execute([':task_id' => $task_id]);

    //fetch all matching records as associative array
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //return successful response with all task files
    echo json_encode(["success" => true, "files" => $files]);

} 
catch (PDOException $e) {
    //return failure response with database error message
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
