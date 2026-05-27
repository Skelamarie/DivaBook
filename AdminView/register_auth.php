<?php
session_start();
require_once '../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Check for empty fields
    foreach ($_POST as $key => $value) {
        if (empty(trim($value))) {
            header("Location: registration.php?error=empty_fields");
            exit();
        }
    }

    // 2. Sanitize and Validate Email
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Redirect back with an error if email is invalid
        header("Location: registration.php?error=invalid_email");
        exit();
    }

    // 3. Prepare other variables
    $name      = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
    $shop_name = htmlspecialchars(trim($_POST['shop_name']), ENT_QUOTES, 'UTF-8');
    $phone     = htmlspecialchars(trim($_POST['phone']), ENT_QUOTES, 'UTF-8');
    $address   = htmlspecialchars(trim($_POST['address']), ENT_QUOTES, 'UTF-8');
    

    // 4. Secure password hashing
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // 5. Generate Admin-ID
    $generated_admin_id = "DB-" . rand(1000, 9999);

    // 6. Define Branding
    $defaultBranding = [
        'salon_name'      => $shop_name, 
        'location'        => $address,   
        'contact_info'    => $phone,     
        'email'           => $email,     
        'color_primary'   => '#064E3B',
        'color_secondary' => '#7cbf7f',
        'description' => '', 'credentials' => '', 'facebook' => '', 'instagram' => '', 'logo_path' => ''
    ];

    try {
        // 7. Insert into MongoDB
        $insertResult = $partnersCollection->insertOne([
            'admin_id'   => $generated_admin_id,
            'name'       => $name,
            'shop_name'  => $shop_name,
            'email'      => $email,
            'password'   => $password,
            'phone'      => $phone,
            'address'    => $address,
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'branding'   => $defaultBranding 
        ]);

        if ($insertResult->getInsertedCount()) {
            $_SESSION['admin_id'] = $generated_admin_id;
            header("Location: admin_customization.php?signup=success&id=" . $generated_admin_id);
            exit();
        }

    } catch (Exception $e) {
        error_log("MongoDB Insert Error: " . $e->getMessage());
        header("Location: registration.php?error=system_error");
        exit();
    }
}