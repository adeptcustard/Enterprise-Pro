<?php 
//use statements to import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//include PHPMailer class files from local directory
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

/**
//sends a welcome email with a temporary password to the user
//used when a new user is created by an Admin
//
//@param string $toEmail the recipient's email address
//@param string $firstName the recipient's first name
//@param string $tempPassword the temporary password generated for the user
//@return boolean true if email was sent successfully, false otherwise
*/
function sendWelcomeEmail($toEmail, $firstName, $tempPassword) {
    //create a new PHPMailer instance with exception handling enabled
    $mail = new PHPMailer(true);

    try {
        //configure SMTP server settings (Gmail SMTP used here)
        $mail->isSMTP();                                      //use SMTP for sending
        $mail->Host       = 'smtp.gmail.com';                 //Gmail SMTP server
        $mail->SMTPAuth   = true;                             //enable SMTP authentication
        $mail->Username   = 'naqibrahman911@gmail.com';       //Gmail username (sender)
        $mail->Password   = 'rdtdarflyjkrmxon';               //app password (do not share publicly)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   //TLS encryption for security
        $mail->Port       = 587;                              //TLS port for Gmail

        //set the sender and recipient details
        $mail->setFrom('naqibrahman911@gmail.com', 'YHROCU Workflow'); //from address and name
        $mail->addAddress($toEmail, $firstName);                       //recipient address and name

        //format the email as HTML
        $mail->isHTML(true);                                           //enable HTML content
        $mail->Subject = 'Welcome to the YHROCU Workflow System';      //email subject

        //build the HTML email body
        $mail->Body = "
            <p>Dear <strong>$firstName</strong>,</p>
            <p>You have been added to the <strong>YHROCU Workflow System</strong>.</p>
            <p>Your temporary password is: <strong>$tempPassword</strong></p>
            <p>Please log in and update your password on your first login.</p>
            <p><a href='http://localhost/Enterprise-Pro/Group-Project/html/index.html'>Login Here</a></p>
            <p>Regards,<br>The YHROCU Admin Team</p>
        ";

        //send the email
        $mail->send();
        //email sent successfully
        return true;

    } 
    catch (Exception $e) {
        //log the error message for debugging
        error_log("📧 Email Error: {$mail->ErrorInfo}");
        //email sending failed
        return false; 
    }
}
