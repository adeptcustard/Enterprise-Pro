<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//include PHPMailer core files
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

/**
 * sendCustomEmail - sends an HTML email using PHPMailer
 *
 * @param string $toEmail recipient's email address
 * @param string $recipientName name of the recipient (used in email header)
 * @param string $subject subject of the email
 * @param string $body HTML body content of the email
 * @return bool true if email was sent successfully, false on failure
 */
function sendCustomEmail($toEmail, $recipientName, $subject, $body) {
    //create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        //smtp configuration for Gmail
        $mail->isSMTP(); //use SMTP for sending
        $mail->Host       = 'smtp.gmail.com'; //SMTP server host
        $mail->SMTPAuth   = true; //enable SMTP authentication
        $mail->Username   = 'naqibrahman911@gmail.com'; //your SMTP username (email address)
        $mail->Password   = 'rdtdarflyjkrmxon'; //your SMTP app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; //encryption method (TLS)
        $mail->Port       = 587; //Gmail SMTP port

        //sender email and name
        $mail->setFrom('naqibrahman911@gmail.com', 'YHROCU Workflow');

        //add recipient and name
        $mail->addAddress($toEmail, $recipientName);

        //format email as HTML
        $mail->isHTML(true);
        $mail->Subject = $subject; //set the subject
        $mail->Body    = $body; //set the email body as HTML

        //send the email
        $mail->send();

        //return true if email sent successfully
        return true;
    } 
    catch (Exception $e) {
        //log the error to server logs if email sending fails
        error_log("❌ Email Error: " . $mail->ErrorInfo);

        //return false to indicate failure
        return false;
    }
}
