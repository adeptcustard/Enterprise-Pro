<?php
//start session to access the current user's session data
session_start();

//include database connection using PDO
require_once 'db_connect.php';

//set the response type to JSON so the client knows what to expect
header('Content-Type: application/json');

//check if the user is logged in by verifying session data
if (!isset($_SESSION['user_id'])) {
    //return error response if the user is not authenticated
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

//prepare SQL query to fetch user profile data including preferences
$stmt = $pdo->prepare("
    SELECT 
        first_name,         -- user's first name
        last_name,          -- user's last name
        email,              -- user's email address
        dark_mode,          -- boolean: is dark mode enabled
        dyslexic_mode       -- boolean: is dyslexic-friendly font enabled
    FROM users
    WHERE id = :id         -- match against current user's session ID
");

//execute the SQL query with the user ID from session
$stmt->execute([':id' => $_SESSION['user_id']]);

//fetch the resulting user profile as an associative array
$user = $stmt->fetch(PDO::FETCH_ASSOC);

//return the user data in JSON format along with success status
echo json_encode(['success' => true, 'user' => $user]);
