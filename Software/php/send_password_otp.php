<?php
//start session
session_start();

//set content type to JSON
header("Content-Type: application/json");

//include database and email utility
require_once "db_connect.php";
require_once "mail.php";

//get and validate email from request
$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data['email'] ?? '');

if (empty($email)) {
    echo json_encode(["success" => false, "message" => "email is required"]);
    exit;
}

//check if email exists in database
$stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["success" => false, "message" => "no account associated with this email"]);
    exit;
}

//generate otp and set expiry time
$otp = rand(100000, 999999);
$_SESSION['password_reset_otp'] = $otp;
$_SESSION['password_reset_email'] = $email;
$_SESSION['password_reset_expires'] = time() + (60 * 5); // 5 minutes

//compose email message
$subject = "Your One-Time Passcode (OTP) For Password Reset";
$body = "
    <p>Hi <strong>{$user['first_name']}</strong>,</p>
    <p>Your one-time passcode (OTP) is: <strong>{$otp}</strong></p>
    <p>This code is valid for <strong>5 minutes</strong>.</p>
    <p>If you did not request this code, please ignore this message.</p>
    <p>– YHROCU Workflow System</p>
";

//send email
$sent = sendCustomEmail($email, $user['first_name'], $subject, $body);

if ($sent) {
    echo json_encode(["success" => true, "message" => "otp sent to your email"]);
} else {
    echo json_encode(["success" => false, "message" => "failed to send otp"]);
}
?>
