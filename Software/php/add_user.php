<?php
//start or resume the current session
session_start();

//include the database connection and mail configuration files
require_once "db_connect.php";
require_once "mail_config.php"; 

//set the response header to JSON
header("Content-Type: application/json");

//retrieve and decode the incoming JSON payload as an associative array
$data = json_decode(file_get_contents("php://input"), true);

//@param string $firstName - user's first name from the input
$firstName = trim($data['first_name'] ?? '');

//@param string $lastName - user's last name from the input
$lastName = trim($data['last_name'] ?? '');

//@param string $email - user's email address from the input
$email = trim($data['email'] ?? '');

//@param string $password - raw password from the input (will be hashed)
$password = $data['password'] ?? '';

//@param string $role - user's role (default is 'User')
$role = $data['role'] ?? 'User';

//check if any required field is missing
if (!$firstName || !$lastName || !$email || !$password || !$role) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

//check if the provided email already exists in the users table
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);

//if the email already exists, return an error response
if ($stmt->fetch()) {
    echo json_encode(["success" => false, "message" => "Email already in use"]);
    exit;
}

try {
    //hash the password securely using bcrypt
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    //insert new user into the users table with must_change_password = true
    $insertStmt = $pdo->prepare("
        INSERT INTO users (first_name, last_name, email, password_hash, role, must_change_password)
        VALUES (:first_name, :last_name, :email, :password, :role, :must_change_password)
    ");

    //execute the insert statement with user input values
    $insertStmt->execute([
        ':first_name' => $firstName,
        ':last_name'  => $lastName,
        ':email'      => $email,
        ':password'   => $hashedPassword,
        ':role'       => $role,
        ':must_change_password' => true  //✅ user must change password on first login
    ]);

    //send welcome email to the newly created user including their temporary password
    $emailSent = sendWelcomeEmail($email, $firstName, $password);

    //if email fails to send, notify the admin but user was still created
    if (!$emailSent) {
        echo json_encode([
            "success" => false,
            "message" => "User created but email failed to send."
        ]);
        exit;
    }

    //return success response
    echo json_encode(["success" => true]);

} 
catch (PDOException $e) {
    //handle and return database-related errors
    echo json_encode(["success" => false, "message" => "DB Error: " . $e->getMessage()]);
}
?>
