<?php
session_start();

// If session exists, go to Overview, otherwise go to Login
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_overview.php");
} else {
    header("Location: Login.php");
}
exit();
?>