<?php
session_start();
require_once "db_connect.php"; 

header("Content-Type: application/json");

//ensure the user is logged in as is a supervisor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Supervisor') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {    
    //debugging log 
    error_log("Supervisor ID: " . $user_id);

    //fetch this specific supervisor tasks
    $stmt_my_tasks = $pdo->prepare("
        SELECT t.id, t.title, t.description, t.status, t.deadline, t.created_at, t.owner,
            COALESCE(SUM(CASE WHEN ta.completed = TRUE THEN 1 ELSE 0 END), 0) AS completed_actions,
            COALESCE(COUNT(ta.id), 0) AS total_actions
        FROM tasks t
        LEFT JOIN task_actions ta ON t.id = ta.task_id
        WHERE t.owner = :user_id
        GROUP BY t.id
        ORDER BY t.created_at DESC
    ");

    $stmt_my_tasks->execute([':user_id' => $user_id]);
    $my_tasks = $stmt_my_tasks->fetchAll(PDO::FETCH_ASSOC);

    //fetch all tasks in the database
    $stmt_all_tasks = $pdo->prepare("
        SELECT t.id, t.title, t.description, t.status, t.deadline, t.created_at, t.owner,
            COALESCE(SUM(CASE WHEN ta.completed = TRUE THEN 1 ELSE 0 END), 0) AS completed_actions,
            COALESCE(COUNT(ta.id), 0) AS total_actions
        FROM tasks t
        INNER JOIN task_assignments ta_user ON t.id = ta_user.task_id
        LEFT JOIN task_actions ta ON t.id = ta.task_id
        GROUP BY t.id
        ORDER BY t.created_at DESC
    ");

    $stmt_all_tasks->execute();
    $all_tasks = $stmt_all_tasks->fetchAll(PDO::FETCH_ASSOC);
    
    //function to fetch assigned users for a task
    function getAssignedUsers($pdo, $task_id) {
        $stmt = $pdo->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email, u.role
            FROM users u
            INNER JOIN task_assignments ta ON u.id = ta.user_id
            WHERE ta.task_id = :task_id
        ");
        $stmt->execute([':task_id' => $task_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //function to fetch primary assigned users 
    function getPrimaryUser($pdo, $user_id) {
        $stmt = $pdo->prepare("
            SELECT id, first_name, last_name, email, role
            FROM users
            WHERE id = :user_id
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    //function to fetch task actions for a task
    function getTaskActions($pdo, $task_id) {
        $stmt = $pdo->prepare("
            SELECT id, action_description, completed, created_at
            FROM task_actions
            WHERE task_id = :task_id
        ");
        $stmt->execute([':task_id' => $task_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //attach users and actions to My Tasks
    foreach ($my_tasks as &$task) {
        $task['primary_user'] = getPrimaryUser($pdo, $task['owner']);
        $task['assigned_users'] = getAssignedUsers($pdo, $task['id']);
        $task['actions'] = getTaskActions($pdo, $task['id']);
    }
    
    //attach users and actions to All Tasks
    foreach ($all_tasks as &$task) {
        $task['primary_user'] = getPrimaryUser($pdo, $task['owner']);
        $task['assigned_users'] = getAssignedUsers($pdo, $task['id']);
        $task['actions'] = getTaskActions($pdo, $task['id']);
    }

    //debugging logs 
    error_log("Fetched My Tasks (Owned by Supervisor): " . json_encode($my_tasks));
    error_log("Fetched All Tasks (Assigned to Users): " . json_encode($all_tasks));

    echo json_encode([
        "success" => true,
        "my_tasks" => $my_tasks,
        "all_tasks" => $all_tasks
    ]);

} catch (PDOException $e) {
    //error logging
    error_log("❌ Database Error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
