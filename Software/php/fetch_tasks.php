<?php
//start session to get current user info
session_start();

//include database connection
require_once "db_connect.php";

//set content type for JSON output
header("Content-Type: application/json");

//check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

//get the currently logged-in user's ID
$user_id = $_SESSION['user_id'];

try {
    //fetch tasks where the current user is assigned through task_assignments table

    //prepare SQL query to retrieve task details, primary user info, and task action stats
    $stmt = $pdo->prepare("
        SELECT 
            t.id, t.title, t.description, t.status, t.deadline, t.created_at,
            u.id AS primary_user_id, u.first_name AS primary_first_name,
            u.last_name AS primary_last_name, u.email AS primary_email,
            COALESCE(SUM(CASE WHEN ta.completed = TRUE THEN 1 ELSE 0 END), 0) AS completed_actions,
            COALESCE(COUNT(ta.id), 0) AS total_actions
        FROM tasks t
        LEFT JOIN users u ON t.assigned_to = u.id --join to get primary assigned user
        LEFT JOIN task_actions ta ON t.id = ta.task_id --join to calculate task action stats
        INNER JOIN task_assignments ta_user ON t.id = ta_user.task_id --join to filter tasks assigned to this user
        WHERE ta_user.user_id = :user_id
        GROUP BY t.id, u.id --grouping needed due to aggregation
        ORDER BY t.created_at DESC
    ");
    
    //execute query with user ID parameter
    $stmt->execute([':user_id' => $user_id]);
    
    //fetch tasks assigned to current user
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //fetch all task actions in the system (to be filtered and attached below)
    $stmt_actions = $pdo->query("
        SELECT id, task_id, action_description, completed, created_at
        FROM task_actions
    ");
    $actions = $stmt_actions->fetchAll(PDO::FETCH_ASSOC);

    //fetch all additional users assigned to tasks (via task_assignments table)
    $stmt_assignments = $pdo->query("
        SELECT ta.task_id, u.id, u.first_name, u.last_name, u.email, u.role
        FROM task_assignments ta
        JOIN users u ON ta.user_id = u.id
    ");
    $assignments = $stmt_assignments->fetchAll(PDO::FETCH_ASSOC);

    //loop through each task and attach structured data for:
    // - primary assigned user
    // - additional assigned users
    // - task actions
    foreach ($tasks as &$task) {
        //attach primary assigned user (from assigned_to field)
        $task['primary_user'] = $task['primary_user_id'] ? [
            "id" => $task['primary_user_id'],
            "first_name" => $task['primary_first_name'],
            "last_name" => $task['primary_last_name'],
            "email" => $task['primary_email']
        ] : null;

        //attach additional assigned users (filter by task_id match)
        $task['additional_users'] = array_values(array_filter($assignments, fn($a) => $a['task_id'] == $task['id']));

        //attach actions (filter by task_id match)
        $task['actions'] = array_values(array_filter($actions, fn($a) => $a['task_id'] == $task['id']));

        //remove unstructured user columns that were only used for building the primary_user object
        unset($task['primary_user_id'], $task['primary_first_name'], $task['primary_last_name'], $task['primary_email']);
    }

    //send final structured tasks list back to client
    echo json_encode(["success" => true, "tasks" => $tasks]);

} 
catch (PDOException $e) {
    //handle and report any DB errors
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
