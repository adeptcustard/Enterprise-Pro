<?php
//start session to access user data
session_start();

//include database connection
require_once "db_connect.php";

//set response content type to JSON
header("Content-Type: application/json");

//check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "user not logged in"]);
    exit;
}

//get current user id from session
$user_id = $_SESSION['user_id'];

//check if both task_id and file are sent in request
if (!isset($_POST['task_id']) || !isset($_FILES['file'])) {
    echo json_encode(["success" => false, "message" => "missing task id or file"]);
    exit;
}

//extract submitted task_id and file data
$task_id = $_POST['task_id'];
$file = $_FILES['file'];

//define directory where uploaded files will be saved
$target_dir = "../uploads/tasks/";

//create directory if it doesn't exist
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

//generate a unique filename using timestamp and original filename
$filename = time() . "_" . basename($file["name"]);
$target_file = $target_dir . $filename;

/**
 * move uploaded file from temporary location to permanent directory
 * @param string $file["tmp_name"] temporary file path
 * @param string $target_file final destination path
 */
if (move_uploaded_file($file["tmp_name"], $target_file)) {
    try {
        /**
         * insert file metadata into task_files table
         * @param int $task_id the related task
         * @param string $file["name"] original filename
         * @param string $target_file full path on server
         * @param int $user_id uploader's user ID
         */
        $stmt = $pdo->prepare("
            INSERT INTO task_files (task_id, file_name, file_path, uploaded_by)
            VALUES (:task_id, :file_name, :file_path, :uploaded_by)
        ");
        $stmt->execute([
            ':task_id' => $task_id,
            ':file_name' => $file["name"],
            ':file_path' => $target_file,
            ':uploaded_by' => $user_id
        ]);

        //return success response
        echo json_encode(["success" => true]);
    } 
    catch (PDOException $e) {
        //return database error if insert fails
        echo json_encode(["success" => false, "message" => "database error: " . $e->getMessage()]);
    }
} 
else {
    //file upload failed (permission or IO error)
    echo json_encode(["success" => false, "message" => "file upload failed"]);
}
?>
