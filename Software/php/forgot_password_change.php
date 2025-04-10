<?php
//start session
session_start();

//set content type
header("Content-Type: application/json");

//include db connection
require_once "db_connect.php";

//check if otp was verified
if (!isset($_SESSION['otp_verified'], $_SESSION['password_reset_email']) || $_SESSION['otp_verified'] !== true) {
    echo json_encode(["success" => false, "message" => "unauthorised request"]);
    exit;
}

//get email from session
$email = $_SESSION['password_reset_email'];

//get new password from request
$data = json_decode(file_get_contents("php://input"), true);
$newPassword = trim($data['new_password'] ?? '');
$confirmPassword = trim($data['confirm_password'] ?? '');

//validate inputs
if (empty($newPassword) || empty($confirmPassword)) {
    echo json_encode(["success" => false, "message" => "password fields are required"]);
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(["success" => false, "message" => "passwords do not match"]);
    exit;
}

//hash new password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

//update in database
try {
    $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE email = :email");
    $stmt->execute([
        ':hash' => $hashedPassword,
        ':email' => $email
    ]);

    //clear password reset session
    unset($_SESSION['password_reset_email'], $_SESSION['otp_verified'], $_SESSION['password_reset_otp'], $_SESSION['password_reset_expires']);

    echo json_encode(["success" => true, "message" => "password changed successfully"]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "database error: " . $e->getMessage()]);
}
?>
