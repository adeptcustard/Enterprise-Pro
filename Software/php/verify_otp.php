<?php
//start session to access stored OTP and user session data
session_start();

//include database connection
require_once "db_connect.php";

//set response type to JSON
header("Content-Type: application/json");

//get the JSON input from the client and decode it
$data = json_decode(file_get_contents("php://input"), true);

//extract the otp value submitted from the frontend, trim to avoid accidental spaces
$submittedOtp = trim($data['otp'] ?? '');

//check if otp and user session variables exist, otherwise deny the request
if (!isset($_SESSION['otp'], $_SESSION['otp_expires'], $_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "OTP session expired or not set."]);
    exit;
}

//check if the stored otp has expired (5 minute expiry logic)
if (time() > $_SESSION['otp_expires']) {
    //remove expired otp from session to prevent misuse
    unset($_SESSION['otp'], $_SESSION['otp_expires']);
    echo json_encode(["success" => false, "message" => "OTP expired."]);
    exit;
}

//compare submitted otp with session-stored otp (casted to string for match)
if ($submittedOtp === strval($_SESSION['otp'])) {
    //if otp is valid, remove it from session to prevent reuse
    unset($_SESSION['otp'], $_SESSION['otp_expires']);

    try {
        //prepare SQL to check if the user still needs to change their temp password
        $stmt = $pdo->prepare("SELECT must_change_password FROM users WHERE id = :user_id");

        //execute the query with the user_id from session
        $stmt->execute([':user_id' => $_SESSION['user_id']]);

        //fetch result (should return 1 row with must_change_password boolean)
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        //debug log the user ID and their must_change_password value
        error_log("🧪 SESSION ID: " . $_SESSION['user_id']);
        error_log("📦 must_change_password result: " . json_encode($result));

        //if must_change_password is true, redirect to the first-time password reset screen
        if ($result && $result['must_change_password']) {
            echo json_encode(["success" => true, "redirect" => "../html/first_password_change.html"]);
        } 
        else {
            //otherwise redirect to their intended dashboard (stored during login)
            $redirectPage = $_SESSION['post_otp_redirect'] ?? '../php/tasks_user.php';
            echo json_encode(["success" => true, "redirect" => $redirectPage]);
        }
    } 
    catch (PDOException $e) {
        //log any SQL errors
        error_log("❌ DB error in verify_otp: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Database error."]);
    }
} 
else {
    //if otp is incorrect, return failure response
    echo json_encode(["success" => false, "message" => "Incorrect OTP."]);
}
