<?php
//start session to access user information
session_start();

//include PHPMailer configuration and helper function
require_once "mail_config.php"; 

//set the response content type to JSON
header("Content-Type: application/json");

//check if the session contains necessary user data (user ID, email, and name)
if (!isset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['first_name'])) {
    echo json_encode(["success" => false, "message" => "User not authenticated."]);
    exit;
}

//extract required user details from session
$userId = $_SESSION['user_id'];
$email = $_SESSION['email'];
$firstName = $_SESSION['first_name'];

//generate a random 6-digit OTP (one-time passcode)
$otp = rand(100000, 999999);

//set the expiration timestamp for the OTP to 5 minutes from now
$expiry = time() + (5 * 60); // 5 minutes

//store the OTP and expiration in session variables
$_SESSION['otp_code'] = $otp;
$_SESSION['otp_expires_at'] = $expiry;

//compose the subject and body of the OTP email message
$subject = "Your One-Time Passcode (OTP)";
$body = "
    <p>Hi <strong>{$firstName}</strong>,</p>
    <p>Your one-time passcode (OTP) is: <strong>{$otp}</strong></p>
    <p>This code is valid for <strong>5 minutes</strong>.</p>
    <p>If you did not request this code, please ignore this message.</p>
    <p>– YHROCU Workflow System</p>
";

//send the email using the reusable PHPMailer helper function
$sent = sendCustomEmail($email, $firstName, $subject, $body);

//return success response if email sent successfully, otherwise return an error
if ($sent) {
    echo json_encode(["success" => true, "message" => "OTP sent to your email."]);
} 
else {
    echo json_encode(["success" => false, "message" => "Failed to send OTP."]);
}
?>
