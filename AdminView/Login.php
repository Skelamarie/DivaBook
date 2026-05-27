<?php 
session_start(); 

// 1. Define the variable so the "Undefined variable" error disappears
$sessionActive = isset($_SESSION['admin_id']);

// 2. Logic Check:
// If you want to BLOCK them from seeing the login page while logged in:
if ($sessionActive) {
    header("Location: admin_overview.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DivaBook - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="LoginStyle.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL,GRAD,opsz@400,0,0,24" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body>

    <header class="main-header">
        <div class="logo">DIVABOOK</div>
    </header>

    <main class="login-wrapper">
        <section class="login-card">
            
         
            <h1 class="welcome-title">Welcome Back</h1>
            <p class="welcome-subtitle">Enter your details to access your portal.</p>

            <?php if (isset($_GET['error'])): ?>
    <div style="color: #D32F2F; background: #FFEBEE; padding: 10px; border-radius: 4px; font-size: 12px; margin-bottom: 15px; text-align: center; border: 1px solid #FFCDD2;">
        <?php 
            if ($_GET['error'] == 'auth_failed') {
                echo "Invalid Admin-ID, Email, or Password.";
            } elseif ($_GET['error'] == 'empty_fields') {
                echo "Please fill in all fields.";
            } else {
                echo "An error occurred. Please try again.";
            }
        ?>
    </div>
<?php endif; ?>

            <!-- Corrected Action and Method[cite: 7, 8] -->
            <form id="loginForm" action="login_auth.php" method="POST">
                <div class="input-group">
                    <label>ADMIN-ID</label>
                    <input type="text" name="admin-id" placeholder="DB-XXXX" 
                         pattern="DB-\d{4}" title="Format: DB followed by 4 digits" required>
                </div>

                <div class="input-group">
                    <label>EMAIL ADDRESS</label>
                    <input type="email" name="email" placeholder="admin@gmail.com"required>
                </div>

                <div class="input-group">
                    <label>PASSWORD</label>
                        <div class="input-with-icon">
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                        <button type="button" id="togglePassword" class="icon-btn">
                        <i data-feather="eye"></i>
                        </button>
                    </div>
                </div>

                    <button type="submit" id="signInBtn" class="sign-in-btn">SIGN IN</button>
            </form>
            <!-- Explicitly force the .php extension here[cite: 10] -->
            <p class="register-link">New here? <a href="Registration.php">Register</a></p>
            
        </section>
    </main>

    <footer class="bottom-footer">
        <p>&copy; 2026 DIVABOOK WELLNESS</p>
    </footer>

    <div class="modal fade" id="deleteSuccessModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-body text-center p-5">
                    <div class="mb-4">
                        <span class="material-symbols-outlined text-success" style="font-size: 80px;">check_circle</span>
                    </div>
                    <h3 class="fw-bold">Account Deleted</h3>
                    <p class="text-muted">Your DivaBook account and all associated data have been permanently removed. Redirecting you shortly...</p>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // 1. Initialize Feather icons on initial page load
    feather.replace();

    // 2. Password Visibility Toggle Logic
    // Selects the button and the input field
    const togglePassword = document.querySelector('#togglePassword');
    const passwordField = document.querySelector('#password');

    if (togglePassword && passwordField) {
        togglePassword.addEventListener('click', function (e) {
            // Prevent accidental form submission
            e.preventDefault();

            // Toggle the type attribute between 'password' and 'text'
            const isPassword = passwordField.getAttribute('type') === 'password';
            passwordField.setAttribute('type', isPassword ? 'text' : 'password');

            // Switch the Feather icon based on the new type
            this.innerHTML = isPassword 
                ? '<i data-feather="eye-off"></i>' 
                : '<i data-feather="eye"></i>';

            // Re-render the icon so Feather replaces the <i> tag with the SVG
            feather.replace();
        });
    }

    // 3. Login Form Submission Handling
    const loginForm = document.getElementById('loginForm');
    const signInBtn = document.getElementById('signInBtn');

    if (loginForm && signInBtn) {
        loginForm.addEventListener('submit', function() {
            // Provide visual feedback to the user
            signInBtn.innerHTML = "VERIFYING...";
            signInBtn.style.opacity = "0.7";
            signInBtn.disabled = true; // Prevent double submission
        });
    }

    // 4. Post-Deletion Success Modal Logic
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Triggers specifically if the user was redirected after a successful deletion
        if (urlParams.get('status') === 'deleted_success') {
            const modalElement = document.getElementById('deleteSuccessModal');
            
            if (modalElement) {
                const deleteModal = new bootstrap.Modal(modalElement);
                deleteModal.show();

                // Wait 3 seconds, then redirect to clean the URL query string
                setTimeout(function() {
                    window.location.href = 'Login.php'; 
                }, 3000);
            }
        }
    });
</script>
</body>
</html>