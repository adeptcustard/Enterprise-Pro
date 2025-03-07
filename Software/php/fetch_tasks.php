<?php
//start the session to access user session data
session_start();

//include the database connection file
require_once "db_connect.php"; 

//set the response header to return JSON data
header("Content-Type: application/json");

//check if the user is logged in, otherwise return an error message
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

//retrieve the logged-in user's ID from the session
$user_id = $_SESSION['user_id'];

try {    
    //fetch tasks assigned to the logged-in user
    $stmt = $pdo->prepare("
        SELECT t.id, t.title, t.description, t.status, t.deadline, t.created_at
        FROM tasks t
        WHERE t.assigned_to = :user_id
        ORDER BY t.created_at DESC
    ");
    
    //execute the query with the user's ID
    $stmt->execute([':user_id' => $user_id]);

    //fetch all assigned tasks as an associative array
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //fetch all task actions from the database
    $stmt_actions = $pdo->prepare("
        SELECT id, task_id, action_description, completed, created_at
        FROM task_actions
    ");
    
    //execute the query
    $stmt_actions->execute();

    //fetch all task actions as an associative array
    $actions = $stmt_actions->fetchAll(PDO::FETCH_ASSOC);

    //attach actions to their respective tasks and calculate completed/total actions
    foreach ($tasks as &$task) {
        //filter actions related to the current task
        $task['actions'] = array_values(array_filter($actions, fn($action) => $action['task_id'] == $task['id']));
        
        //count the number of completed actions for the task
        $task['completed_actions'] = count(array_filter($task['actions'], fn($action) => $action['completed'] == true));
        
        //count the total number of actions for the task
        $task['total_actions'] = count($task['actions']);

        //ensure the `actions` field is always an array, even if no actions exist
        if (!isset($task['actions']) || empty($task['actions'])) {
            $task['actions'] = [];
        }
    }

    //if no tasks are found for the user, return a failure response
    if (empty($tasks)) {
        echo json_encode(["success" => false, "message" => "No tasks found for the user."]);
        exit;
    }

    //return a success response with the retrieved tasks and their details
    echo json_encode(["success" => true, "tasks" => $tasks]);

} catch (PDOException $e) {
    //handle database errors and return an appropriate error message
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
