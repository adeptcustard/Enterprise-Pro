<?php
//start the session to track user authentication
session_start();

//include the database connection file
require_once "db_connect.php"; 

//set the response content type to JSON
header("Content-Type: application/json");

//check if the user is logged in AND has either the Admin or Supervisor role
//this prevents unauthorised users from accessing the user list
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Supervisor'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

try {    
    //prepare SQL query to retrieve all users except the currently logged-in admin or supervisor
    //this prevents an admin from accidentally managing or deleting themselves
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, role 
        FROM users 
        WHERE id != :admin_id
        ORDER BY first_name ASC
    ");
    
    //bind the current user's ID to the query so their own account is excluded
    $stmt->execute([':admin_id' => $_SESSION['user_id']]);
    
    //fetch all matching users as an associative array
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //return success response with users
    echo json_encode(["success" => true, "users" => $users]);
}
catch (PDOException $e) {
    //if a database error occurs, log it and return a generic error message
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
