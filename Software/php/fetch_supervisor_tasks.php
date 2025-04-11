<?php
//start the session to access session variables like user_id and role
session_start();

//include the database connection
require_once "db_connect.php";

//set content type for JSON response
header("Content-Type: application/json");

//ensure the user is logged in and has the Supervisor role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Supervisor') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

//store the logged-in supervisor’s user ID
$user_id = $_SESSION['user_id'];

try {
    //debug log for which supervisor is fetching tasks
    error_log("Supervisor ID: " . $user_id);

    //prepare SQL query to fetch all tasks where the supervisor is the owner
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

    //prepare SQL query to fetch all tasks that are assigned to any user
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

    /**
     * getAssignedUsers
     * fetches all users assigned to a given task (not the primary owner)
     * @param PDO $pdo - the database connection
     * @param int $task_id - the task ID
     * @return array of assigned users
     */
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

    /**
     * getPrimaryUser
     * fetches the primary user (task owner)
     * @param PDO $pdo - the database connection
     * @param int $user_id - user ID of the owner
     * @return array|null of user info
     */
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

    /**
     * getTaskActions
     * retrieves all actions associated with a specific task
     * @param PDO $pdo - the database connection
     * @param int $task_id - task ID
     * @return array of task actions
     */
    function getTaskActions($pdo, $task_id) {
        $stmt = $pdo->prepare("
            SELECT id, action_description, completed, created_at
            FROM task_actions
            WHERE task_id = :task_id
        ");
        $stmt->execute([':task_id' => $task_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //loop through each of the supervisor's own tasks and append extra data
    foreach ($my_tasks as &$task) {
        $task['primary_user'] = getPrimaryUser($pdo, $task['owner']);
        $task['assigned_users'] = getAssignedUsers($pdo, $task['id']);
        $task['actions'] = getTaskActions($pdo, $task['id']);
    }

    //loop through all tasks assigned to anyone and append extra data
    foreach ($all_tasks as &$task) {
        $task['primary_user'] = getPrimaryUser($pdo, $task['owner']);
        $task['assigned_users'] = getAssignedUsers($pdo, $task['id']);
        $task['actions'] = getTaskActions($pdo, $task['id']);
    }

    //log debug output of fetched task data
    error_log("Fetched My Tasks (Owned by Supervisor): " . json_encode($my_tasks));
    error_log("Fetched All Tasks (Assigned to Users): " . json_encode($all_tasks));

    //return success response with both task lists
    echo json_encode([
        "success" => true,
        "my_tasks" => $my_tasks,
        "all_tasks" => $all_tasks
    ]);

} 
catch (PDOException $e) {
    //log any exception for debugging purposes
    error_log("❌ Database Error: " . $e->getMessage());

    //return failure JSON with message
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
