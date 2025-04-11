<?php
//start the session to manage user authentication
session_start();

//include the database connection file to access $pdo
require_once "db_connect.php";

//set the response header to return JSON data
header("Content-Type: application/json");

//define helper function to sanitize user input if it hasn't already been defined
if (!function_exists('sanitize_input')) {
    function sanitize_input($data)
    {
        //this function strips HTML tags and escapes characters to prevent XSS
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}

//check if the request method is defined (useful for some edge cases or testing frameworks)
if (!isset($_SERVER["REQUEST_METHOD"])) {
    echo json_encode(["success" => false, "message" => "Server request method not defined."]);
    if (!defined('RUNNING_TESTS')) {
        exit;
    }
}

//check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    if (!defined('RUNNING_TESTS')) {
        exit;
    }
}

if (!defined('RUNNING_TESTS')) {
    //if not running tests, retrieve raw JSON input from request
    $inputJSON = file_get_contents("php://input");

    //decode JSON into associative array, or fallback to empty array
    $input = json_decode($inputJSON, true) ?: [];
} 
else {
    //use global mock input when running tests
    $input = $GLOBALS['input'] ?? [];
}

//extract and sanitize email and password from input
if (!defined('RUNNING_TESTS')) {
    $email = isset($input['email']) ? sanitize_input($input['email']) : '';
    $password = isset($input['password']) ? trim($input['password']) : '';
} 
else {
    $email = isset($GLOBALS['input']['email']) ? sanitize_input($GLOBALS['input']['email']) : '';
    $password = isset($GLOBALS['input']['password']) ? trim($GLOBALS['input']['password']) : '';
}

//check if required fields are empty
if (empty($email) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Email and Password are required."]);
    echo json_encode(["emptyField" => "Email and Password are required."]);
    if (!defined('RUNNING_TESTS')) {
        exit;
    }
}

//validate the structure of the email address
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid Email Format."]);
    if (!defined('RUNNING_TESTS')) {
        exit;
    }
}

try {
    //access the database connection variable globally
    global $pdo;

    //prepare SQL to find user by email
    $stmt = $pdo->prepare("
        SELECT id, email, password_hash, role, first_name, last_name, must_change_password 
        FROM users 
        WHERE email = :email
    ");

    //bind the email to the query safely
    $stmt->bindParam(":email", $email);

    //execute the query
    $stmt->execute();

    //fetch user result as associative array
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    //check if user exists and the password matches
    if ($user && password_verify($password, $user['password_hash'])) {
        //store key user details in session for later use
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];

        //generate a 6-digit OTP and store it in session
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_expires'] = time() + (60 * 5); // valid for 5 minutes

        //include the mail sending function
        require_once "mail.php";

        //prepare OTP email content
        $subject = "Your One-Time Passcode (OTP)";
        $body = "
            <p>Hi <strong>{$user['first_name']}</strong>,</p>
            <p>Your one-time passcode (OTP) is: <strong>{$otp}</strong></p>
            <p>This code is valid for <strong>5 minutes</strong>.</p>
            <p>If you did not request this code, please ignore this message.</p>
            <p>– YHROCU Workflow System</p>
        ";

        //send the OTP email using PHPMailer
        sendCustomEmail($user['email'], $user['first_name'], $subject, $body);

        //define which dashboard to redirect to after OTP verification
        $_SESSION['post_otp_redirect'] = match ($user['role']) {
            'Admin' => '../php/tasks_admin.php',
            'Supervisor' => '../php/tasks_supervisor.php',
            default => '../php/tasks_user.php'
        };

        //store the user's role in session
        $_SESSION['user_role'] = $user['role'];

        //debug log to confirm session is set
        error_log("✅ Session set for user: " . $_SESSION['email']);

        //respond with redirect URL to OTP verification page
        echo json_encode([
            "success" => true,
            "redirect" => "../html/verify_otp.html"
        ]);
        exit;

    }
    else {
        //log the failed login attempt for security monitoring
        error_log("❌ Login failed for email: " . $email);

        //return error if user not found or password doesn't match
        echo json_encode(["success" => false, "message" => "Invalid Email or Password"]);
    }

} 
catch (PDOException $e) {
    //log the actual PDO error for debugging (not exposed to client)
    error_log("❌ Database Error: " . $e->getMessage());

    //return generic error message to avoid exposing internal logic
    echo json_encode(["success" => false, "message" => "A server error occurred. Please try again later."]);
}

//destroy session if not running automated tests
if (!defined('RUNNING_TESTS')) {
    session_unset();
    session_destroy();
}
