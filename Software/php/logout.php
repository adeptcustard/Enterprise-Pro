<?php
//start session to manage user authentication
session_start();

//destroy all session data to log the user out
session_unset(); //removes all session variables
session_destroy(); //completely destroys the session

//redirect to the login page
header("Location: ../html/login.html");

//send a JSON response with the redirect location (optional for AJAX)
echo json_encode(["Location" => "../html/login.html"]);

//ensure script exits unless running automated tests
if (!defined('RUNNING_TESTS')) {
    exit;
}
?>
