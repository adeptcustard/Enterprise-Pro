<?php
//start session to track logged-in user
session_start();

//include database connection
require_once 'db_connect.php';

//set json response header
header('Content-Type: application/json');

//check if the user is logged in before proceeding
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'not logged in']);
    exit;
}

//read the raw input JSON and decode into an associative array
$data = json_decode(file_get_contents("php://input"), true);

//extract the setting being updated (e.g. 'dark_mode') from the input
$setting = $data['setting'] ?? '';

//extract the new value for the setting (should be 0 or 1)
$value = $data['value'] ?? 0;

//define a whitelist of settings that are allowed to be updated
$allowedSettings = ['dark_mode', 'dyslexic_mode'];

//validate the setting name against the allowed list
if (!in_array($setting, $allowedSettings)) {
    echo json_encode(['success' => false, 'message' => 'invalid setting']);
    exit;
}

//convert numeric value to PostgreSQL boolean string format
$boolValue = ($value) ? 'TRUE' : 'FALSE';

try {
    /**
     * dynamically construct the update SQL query to set the correct preference
     * @param string $setting the name of the setting to update (validated)
     * @param string $boolValue 'TRUE' or 'FALSE' as PostgreSQL expects
     * @param int $_SESSION['user_id'] the id of the current user
     */
    $query = "UPDATE users SET $setting = $boolValue WHERE id = :id";

    //prepare and execute the query safely (user_id is bound securely)
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $_SESSION['user_id']]);

    //return a success response if the update was successful
    echo json_encode(['success' => true, 'message' => 'preference updated successfully']);
} 
catch (Exception $e) {
    //catch and return a generic error message for failure
    echo json_encode(['success' => false, 'message' => 'error updating preference']);
}
