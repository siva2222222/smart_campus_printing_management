<?php

session_start();

// Clear any error or flash messages stored in the session
unset($_SESSION["error"], $_SESSION["msg"], $_SESSION["flash"]);

// Clear all session data
$_SESSION = [];

// Destroy the session
session_destroy();

// Prevent cached pages after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect to login page
header("Location: index.php");
exit();

?>
