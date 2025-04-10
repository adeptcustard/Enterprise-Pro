<?php
//start session
session_start();

//set response type to JSON
header("Content-Type: application/json");

//get submitted otp from request
$data = json_decode(file_get_contents("php://input"), true);
$submittedOtp = trim($data['otp'] ?? '');

//check if otp session exists
if (!isset($_SESSION['password_reset_otp'], $_SESSION['password_reset_expires'])) {
    echo json_encode(["success" => false, "message" => "otp not set"]);
    exit;
}

//check if otp is expired
if (time() > $_SESSION['password_reset_expires']) {
    unset($_SESSION['password_reset_otp'], $_SESSION['password_reset_expires'], $_SESSION['password_reset_email']);
    echo json_encode(["success" => false, "message" => "otp expired"]);
    exit;
}

//check if otp matches
if ($submittedOtp === strval($_SESSION['password_reset_otp'])) {
    $_SESSION['otp_verified'] = true;
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "incorrect otp"]);
}
?>
