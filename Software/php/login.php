<?php 
//start the session to manage user authentication
session_start();

//include the database connection file
require_once "db_connect.php"; 

//set the response header to return JSON data
header("Content-Type: application/json");

//function to prevent Cross-Site Scripting (XSS) attacks by sanitizing user input
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

//check if the request method is POST, if not, return an error message
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

//read the JSON input sent from the client
$inputJSON = file_get_contents("php://input");

//decode the JSON input into an associative array
$input = json_decode($inputJSON, true);

//extract email and password from the input data
$email = isset($input['email']) ? sanitize_input($input['email']) : '';
$password = isset($input['password']) ? trim($input['password']) : '';

//check if email and password fields are empty, if so, return an error message
if (empty($email) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Email and Password are required."]);
    exit;
}

//validate email format to ensure it is properly structured
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid Email Format."]);
    exit;
}

try {
    //prepare an SQL statement to find the user by email
    $stmt = $pdo->prepare("SELECT id, email, password_hash, role, first_name, last_name FROM users WHERE email = :email");

    //bind the email parameter to prevent SQL injection
    $stmt->bindParam(":email", $email);

    //execute the SQL query
    $stmt->execute();

    //fetch the user's data from the database
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    //check if the user exists and verify the password
    if ($user && password_verify($password, $user['password_hash'])) {
        //store user details in session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];

        //determine the redirect page based on the user's role
        $redirectPage = match ($user['role']) {
            'Admin' => '../php/tasks_admin.php',
            'Supervisor' => '../php/tasks_supervisor.php',
            default => '../php/tasks_user.php'
        };

        //log a message for debugging
        error_log("✅ Session set for user: " . $_SESSION['email']);

        //return a success response with the redirection link
        echo json_encode(["success" => true, "redirect" => $redirectPage]);

    } 
    else {
        //log failed login attempts for security monitoring
        error_log("❌ Login failed for email: " . $email);

        //return an error message for invalid credentials
        echo json_encode(["success" => false, "message" => "Invalid Email or Password"]);
    }

} 
catch (PDOException $e) {
    //log database errors for debugging purposes
    error_log("❌ Database Error: " . $e->getMessage());

    //return a generic error message to avoid exposing database details
    echo json_encode(["success" => false, "message" => "A server error occurred. Please try again later."]);
}
?>
