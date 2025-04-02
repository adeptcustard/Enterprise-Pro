<?php
//start session to manage user authentication
session_start();

//destroy all session data
session_unset();
session_destroy();

//redirect to login page
header("Location: ../html/login.html");
exit;
?>
