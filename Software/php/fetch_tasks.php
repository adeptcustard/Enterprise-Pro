<?php
session_start();
require_once "db_connect.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    //fetch tasks where the user is assigned via task_assignments
    //prepare sql statement
    $stmt = $pdo->prepare("
        SELECT 
            t.id, t.title, t.description, t.status, t.deadline, t.created_at,
            u.id AS primary_user_id, u.first_name AS primary_first_name,
            u.last_name AS primary_last_name, u.email AS primary_email,
            COALESCE(SUM(CASE WHEN ta.completed = TRUE THEN 1 ELSE 0 END), 0) AS completed_actions,
            COALESCE(COUNT(ta.id), 0) AS total_actions
        FROM tasks t
        LEFT JOIN users u ON t.assigned_to = u.id
        LEFT JOIN task_actions ta ON t.id = ta.task_id
        INNER JOIN task_assignments ta_user ON t.id = ta_user.task_id
        WHERE ta_user.user_id = :user_id
        GROUP BY t.id, u.id
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([':user_id' => $user_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //fetch all task actions
    $stmt_actions = $pdo->query("
        SELECT id, task_id, action_description, completed, created_at
        FROM task_actions
    ");
    $actions = $stmt_actions->fetchAll(PDO::FETCH_ASSOC);

    //fetch all additional assigned users
    $stmt_assignments = $pdo->query("
        SELECT ta.task_id, u.id, u.first_name, u.last_name, u.email, u.role
        FROM task_assignments ta
        JOIN users u ON ta.user_id = u.id
    ");
    $assignments = $stmt_assignments->fetchAll(PDO::FETCH_ASSOC);

    //attach users & actions to each task
    foreach ($tasks as &$task) {
        $task['primary_user'] = $task['primary_user_id'] ? [
            "id" => $task['primary_user_id'],
            "first_name" => $task['primary_first_name'],
            "last_name" => $task['primary_last_name'],
            "email" => $task['primary_email']
        ] : null;

        $task['additional_users'] = array_values(array_filter($assignments, fn($a) => $a['task_id'] == $task['id']));
        $task['actions'] = array_values(array_filter($actions, fn($a) => $a['task_id'] == $task['id']));

        //remove raw DB fields no longer needed
        unset($task['primary_user_id'], $task['primary_first_name'], $task['primary_last_name'], $task['primary_email']);
    }

    echo json_encode(["success" => true, "tasks" => $tasks]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
