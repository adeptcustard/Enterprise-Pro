<?php 
//check if HTTPS is enabled in the server environment
if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
    //if HTTPS is enabled, use https in the base URI
    $uri = 'https://';
} else {
    //if HTTPS is not enabled, fall back to http
    $uri = 'http://';
}

//append the server host (e.g., localhost or 127.0.0.1) to the URI
$uri .= $_SERVER['HTTP_HOST'];

//redirect the browser to the /dashboard/ subdirectory
header('Location: '.$uri.'/dashboard/');

//terminate script execution after sending the redirect
exit;
?>

<!-- fallback message if redirect fails -->
Something is wrong with the XAMPP installation: 
