<?php
//start session to access user session data
session_start();

//include database connection
require_once "db_connect.php";

//set content type to JSON for API response
header("Content-Type: application/json");

//this block ensures only Admins and Supervisors can access this endpoint
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Supervisor'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

try {
    //fetch all tasks and join primary assigned user (from 'assigned_to' field)
    $stmt = $pdo->prepare("
        SELECT 
            t.id, t.title, t.description, t.status, t.deadline, t.created_at,
            u.id AS primary_user_id, u.first_name AS primary_first_name, 
            u.last_name AS primary_last_name, u.email AS primary_email,
            COALESCE(SUM(CASE WHEN ta.completed = TRUE THEN 1 ELSE 0 END), 0) AS completed_actions,
            COALESCE(COUNT(ta.id), 0) AS total_actions
        FROM tasks t
        LEFT JOIN users u ON t.assigned_to = u.id --join primary assigned user
        LEFT JOIN task_actions ta ON t.id = ta.task_id --join task actions to compute totals
        GROUP BY t.id, u.id --group by task and user to allow aggregates
        ORDER BY t.created_at DESC
    ");
    $stmt->execute();

    //fetch all task rows with joined user data
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //fetch all additional users assigned to tasks from the task_assignments table
    $stmt_assignments = $pdo->prepare("
        SELECT ta.task_id, u.id, u.first_name, u.last_name, u.email, u.role
        FROM task_assignments ta
        JOIN users u ON ta.user_id = u.id
    ");
    $stmt_assignments->execute();
    $assignments = $stmt_assignments->fetchAll(PDO::FETCH_ASSOC);

    //fetch all actions for all tasks from task_actions table
    $stmt_actions = $pdo->prepare("
        SELECT id, task_id, action_description, completed, created_at
        FROM task_actions
    ");
    $stmt_actions->execute();
    $actions = $stmt_actions->fetchAll(PDO::FETCH_ASSOC);

    //loop through each task and attach related user and action info
    foreach ($tasks as &$task) {
        //attach the primary assigned user if present
        $task['primary_user'] = $task['primary_user_id'] ? [
            "id" => $task['primary_user_id'],
            "first_name" => $task['primary_first_name'],
            "last_name" => $task['primary_last_name'],
            "email" => $task['primary_email']
        ] : null;

        //attach additional users by filtering assignments based on task ID
        $task['additional_users'] = array_values(array_filter($assignments, fn($a) => $a['task_id'] == $task['id']));

        //attach task actions by filtering all actions based on task ID
        $task['actions'] = array_values(array_filter($actions, fn($a) => $a['task_id'] == $task['id']));

        //remove unstructured user columns after restructuring data
        unset($task['primary_user_id'], $task['primary_first_name'], $task['primary_last_name'], $task['primary_email']);
    }

    //return all structured task data as JSON
    echo json_encode(["success" => true, "tasks" => $tasks]);

}
catch (PDOException $e) {
    //catch and return database error
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
