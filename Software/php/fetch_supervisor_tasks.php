<?php
session_start();
require_once "db_connect.php"; 

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Supervisor') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {    
    //fetch "My Tasks" or "All Tasks"
    $query = "
        SELECT t.id, t.title, t.description, t.status, t.deadline, t.created_at,
            COALESCE((SELECT COUNT(*) FROM task_actions ta WHERE ta.task_id = t.id AND ta.completed = TRUE), 0) AS completed_actions,
            COALESCE((SELECT COUNT(*) FROM task_actions ta WHERE ta.task_id = t.id), 0) AS total_actions
        FROM tasks t
    ";

    if (isset($_GET['my_tasks'])) {
        $query .= " WHERE assigned_to = :user_id";
    }
    
    $query .= " ORDER BY t.created_at DESC";

    $stmt = $pdo->prepare($query);
    if (isset($_GET['my_tasks'])) {
        $stmt->execute([':user_id' => $user_id]);
    } else {
        $stmt->execute();
    }

    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //debugging Output
    error_log("Supervisor ID: " . $user_id);
    error_log("Fetched Tasks: " . json_encode($tasks));

    echo json_encode(["success" => true, "tasks" => $tasks]);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage()); // Log error to server
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
