<?php
// Bring in the mail tools we need
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Go up one folder to find the mail tool files
require '../vendor/autoload.php'; 

// Check if someone actually clicked the "Send" button
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Clean up the typing so no bad code gets sent
    $fullname = htmlspecialchars($_POST['fullname']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $type = htmlspecialchars($_POST['type']);
    $message = htmlspecialchars($_POST['message']);

    // Create a new email
    $mail = new PHPMailer(true);

    try {
        // Log into our Gmail account behind the scenes
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'divabook01@gmail.com'; 
        $mail->Password   = 'buzo ocpl ycij hkog'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Tell the system who is sending and receiving this
        $mail->setFrom('divabook01@gmail.com', 'DivaBook Support');
        $mail->addAddress('divabook01@gmail.com'); 
        $mail->addReplyTo($email, $fullname); 

        // Write the actual email message
        $mail->isHTML(true);
        $mail->Subject = "Contact Inquiry: $type - From $fullname";
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #eee;'>
                <h2 style='color: #FF00BB;'>New Inquiry Received</h2>
                <p><strong>Name:</strong> $fullname</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Category:</strong> $type</p>
                <hr>
                <p><strong>Message:</strong></p>
                <p>$message</p>
            </div>
        ";

        // Send it!
        $mail->send();
        
        // Send the user back to the contact page and tell them it worked
        header("Location: ContactUs.php?status=success");
    } catch (Exception $e) {
        // Send the user back to the contact page and tell them there was an error
        header("Location: ContactUs.php?status=error");
    }
}
?>