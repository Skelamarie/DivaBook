<?php
session_start();
$_SESSION = array(); // Clear all variables
session_destroy();   // Destroy the session
header("Location: Login.php");
exit();
?>