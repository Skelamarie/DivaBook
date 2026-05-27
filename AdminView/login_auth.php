<?php
// Secure session configuration
ini_set('session.cookie_httponly', 1); // Prevents JavaScript from accessing cookies (XSS protection)
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);   // Only send cookies over HTTPS

session_start();

require_once '../db_connect.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. SANITIZATION: Remove whitespace and tags
    $adminId = trim(strip_tags($_POST['admin-id'] ?? ''));
    $email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    // 2. VALIDATION: Ensure fields aren't empty
    if (empty($adminId) || empty($email) || empty($password)) {
        header("Location: Login.php?error=empty_fields");
        exit();
    }

    // 3. SECURE EMAIL FORMAT CHECK
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: Login.php?error=invalid_email");
        exit();
    }

    try {
        // MongoDB driver handles query escaping automatically, preventing NoSQL injection
        $user = $partnersCollection->findOne([
            'email'    => $email,
            'admin_id' => $adminId
        ]);

        if ($user && password_verify($password, $user['password'])) {
            // SUCCESS: Clear any old session data first
            session_unset();
            session_destroy();
            session_start();

            // REGENERATE ID: Prevents Session Fixation attacks
            session_regenerate_id(true); 

            // STORE SESSION DATA
            $_SESSION['admin_id'] = $user['admin_id'];
            $_SESSION['shop_name'] = htmlspecialchars($user['shop_name'], ENT_QUOTES, 'UTF-8'); // XSS Protection
            $_SESSION['last_login'] = time();

            header("Location: admin_overview.php");
            exit();
        } else {
            // FAIL: Generic error to prevent "account harvesting"
            header("Location: Login.php?error=auth_failed");
            exit();
        }
    } catch (Exception $e) {
        // Log error privately, show generic message to user
        error_log("Login Error: " . $e->getMessage());
        die("A system error occurred. Please try again later.");
    }
}
?>