<?php
//start the session to track user authentication
session_start();

//include database connection
require_once "db_connect.php"; 

//set the response header to return JSON data
header("Content-Type: application/json");

//check if the user is an admin; if not, return an unauthorized response
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

try {    
    //fetch all tasks with their primary assigned user details
    $stmt = $pdo->prepare("
        SELECT t.id, t.title, t.description, t.status, t.deadline, t.created_at, 
               u.id AS primary_user_id, u.first_name AS primary_first_name, u.last_name AS primary_last_name, u.email AS primary_email
        FROM tasks t
        LEFT JOIN users u ON t.assigned_to = u.id  -- Join to get the primary assigned user's details
        ORDER BY t.created_at DESC  -- Sort tasks by creation date in descending order
    ");
    
    //execute the query
    $stmt->execute();
    //fetch all tasks as an associative array
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //fetch all task actions associated with tasks
    $stmt_actions = $pdo->prepare("
        SELECT id, task_id, action_description, completed, created_at
        FROM task_actions
    ");
    
    //execute the query
    $stmt_actions->execute();
    //fetch all task actions as an associative array
    $actions = $stmt_actions->fetchAll(PDO::FETCH_ASSOC);

    //fetch additional assigned users from the `task_assignments` table
    $stmt_assignments = $pdo->prepare("
        SELECT ta.task_id, u.id AS user_id, u.first_name, u.last_name, u.email
        FROM task_assignments ta
        JOIN users u ON ta.user_id = u.id
    ");
    
    //execute the query
    $stmt_assignments->execute();
    // Fetch all additional task assignments as an associative array
    $assignments = $stmt_assignments->fetchAll(PDO::FETCH_ASSOC);

    //attach actions and additional assigned users to their respective tasks
    foreach ($tasks as &$task) {
        //filter and attach actions that belong to the current task
        $task['actions'] = array_values(array_filter($actions, fn($action) => $action['task_id'] == $task['id']));

        //filter and attach additional users assigned to the current task
        $task['additional_users'] = array_values(array_filter($assignments, fn($assign) => $assign['task_id'] == $task['id']));

        //include primary assigned user details from `tasks` table
        if ($task['primary_user_id']) {
            $task['primary_user'] = [
                "id" => $task['primary_user_id'],
                "first_name" => $task['primary_first_name'],
                "last_name" => $task['primary_last_name'],
                "email" => $task['primary_email']
            ];
        } 
        else {
            //set primary user as null if no primary user is assigned
            $task['primary_user'] = null;
        }

        //ensure `additional_users` always exists as an array even if empty
        if (!isset($task['additional_users']) || empty($task['additional_users'])) {
            $task['additional_users'] = [];
        }

        //remove unnecessary fields from the final output
        unset($task['primary_user_id'], $task['primary_first_name'], $task['primary_last_name'], $task['primary_email']);
    }

    //return the tasks data as a JSON response
    echo json_encode(["success" => true, "tasks" => $tasks]);
} 
catch (PDOException $e) {
    // Handle database errors and return an error response
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
