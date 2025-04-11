<?php
//start or resume the user session
session_start();

//set content type to JSON for API response
header("Content-Type: application/json");

//check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not authenticated"]);
    exit;
}

//include database connection file (makes $pdo available)
require_once "db_connect.php";

//get and decode JSON payload from POST request
$data = json_decode(file_get_contents("php://input"), true);

//validate that JSON payload exists
if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid JSON data."]);
    exit;
}

//@param string $title - title of the new task
$title = trim($data['title'] ?? '');

//@param string $description - description of the task
$description = trim($data['description'] ?? '');

//@param string $deadline - deadline date/time for the task
$deadline = $data['deadline'] ?? '';

//@param string $priority - priority level (e.g. Low, Medium, High)
$priority = $data['priority'] ?? '';

//@param string $team - team assigned to the task
$team = trim($data['team'] ?? '');

//@param array $actions - array of task action descriptions
$actions = $data['actions'] ?? [];

//@param array $additionalUsers - list of user IDs to assign in addition to the owner
$additionalUsers = $data['additional_users'] ?? [];

//store the ID of the user who is currently logged in
$ownerId = $_SESSION['user_id'];

//check for any missing required fields or empty actions
if (!$title || !$description || !$deadline || !$priority || !$team || empty($actions)) {
    echo json_encode(["success" => false, "message" => "❌ Missing required fields."]);
    exit;
}

try {
    //begin database transaction
    $pdo->beginTransaction();

    //prepare query to insert the main task
    $stmt = $pdo->prepare("
        INSERT INTO tasks (title, description, deadline, priority, team, owner, assigned_to, created_at, status)
        VALUES (:title, :description, :deadline, :priority, :team, :owner, :assigned_to, NOW(), 'Pending')
    ");

    //execute insert with parameters, assigning task to creator by default
    $stmt->execute([
        ':title' => $title,
        ':description' => $description,
        ':deadline' => $deadline,
        ':priority' => $priority,
        ':team' => $team,
        ':owner' => $ownerId,
        ':assigned_to' => $ownerId
    ]);

    //get the newly inserted task ID
    $taskId = $pdo->lastInsertId();

    //prepare insert statement for task actions
    $actionStmt = $pdo->prepare("
        INSERT INTO task_actions (task_id, action_description, completed, created_at)
        VALUES (:task_id, :desc, false, NOW())
    ");

    //loop through each action and insert if not empty
    foreach ($actions as $action) {
        if (trim($action)) {
            $actionStmt->execute([
                ':task_id' => $taskId,
                ':desc' => $action
            ]);
        }
    }

    //prepare assignment insert for additional users
    if (!empty($additionalUsers)) {
        $assignStmt = $pdo->prepare("
            INSERT INTO task_assignments (task_id, user_id)
            VALUES (:task_id, :user_id)
        ");

        //loop and insert each additional user into task_assignments
        foreach ($additionalUsers as $userId) {
            $assignStmt->execute([
                ':task_id' => $taskId,
                ':user_id' => $userId
            ]);
        }
    }

    //commit all changes to the database
    $pdo->commit();

    //send back a successful JSON response
    echo json_encode(["success" => true, "message" => "✅ Task created successfully."]);
} 
catch (PDOException $e) {
    //rollback any changes made during the transaction if an error occurs
    $pdo->rollBack();

    //return an error message in JSON format including the exception message
    echo json_encode([
        "success" => false,
        "message" => "❌ Failed to create task.",
        "error" => $e->getMessage()
    ]);
}
?>
