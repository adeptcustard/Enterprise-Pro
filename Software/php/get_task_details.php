<?php
//include database connection configuration
require 'db_connect.php';

//get the task ID from the URL query string (e.g., ?id=3)
$taskId = $_GET['id'] ?? null;

//check if task ID is provided
if (!$taskId) {
    echo json_encode(["success" => false, "message" => "❌ Task ID is required."]);
    exit;
}

try {
    //fetch the task's core details from the tasks table using the provided ID
    $stmt = $pdo->prepare("SELECT id, title, description, deadline, status, priority FROM tasks WHERE id = ?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();

    //if no task was found, return an error message
    if (!$task) {
        echo json_encode(["success" => false, "message" => "❌ Task not found."]);
        exit;
    }

    //fetch all users assigned to this task via the task_assignments table
    $assignedStmt = $pdo->prepare("SELECT user_id FROM task_assignments WHERE task_id = ?");
    $assignedStmt->execute([$taskId]);
    
    //return only an array of user IDs from the result set
    $assignedUsers = $assignedStmt->fetchAll(PDO::FETCH_COLUMN);

    //fetch all users in the system (used to display list of all assignable users)
    $usersStmt = $pdo->query("SELECT id, first_name, last_name, email FROM users ORDER BY first_name");
    $users = $usersStmt->fetchAll();

    //return a JSON response including task details, assigned users, and all available users
    echo json_encode([
        "success" => true,
        "task" => $task,
        "assigned_users" => $assignedUsers,
        "all_users" => $users
    ]);
} 
catch (PDOException $e) {
    //catch any database-related error and return a message
    echo json_encode(["success" => false, "message" => "❌ DB Error: " . $e->getMessage()]);
}
