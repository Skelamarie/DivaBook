<?php
session_start();
$error = "";

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'empty_fields':
            $error = "All fields are strictly required.";
            break;
        case 'invalid_phone':
            $error = "Phone number must be exactly 11 digits (e.g., 09123456789).";
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DivaBook - Partner Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="RegistrationStyle.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        .error-banner {
            background: #fee2e2;
            color: #b91c1c;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
            font-weight: 600;
            border: 1px solid #fca5a5;
        }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="logo">DIVABOOK</div>
    </header>

    <main class="registration-container">
        <section class="visual-panel">
            <div class="visual-overlay">
                <div class="visual-content">
                    <h1>Elevate your wellness business to an art form.</h1>
                    <p>Join an exclusive community of boutique wellness providers.</p>
                </div>
            </div>
        </section>

        <section class="form-panel">
            <div class="form-content">
                <p class="step-count">STEP 01 OF 02</p>
                <h2 class="form-title">Partner Registration</h2>
                <p class="form-subtitle">Enter your business details to begin.</p>

                <?php if ($error): ?>
                    <div class="error-banner"><?php echo $error; ?></div>
                <?php endif; ?>

                <form id="regForm" action="register_auth.php" method="POST">
                    <div class="form-row">
                        <div class="input-group">
                            <label>FULL NAME</label>
                            <input type="text" name="name" placeholder="Jane Doe" required>
                        </div>
                        <div class="input-group">
                            <label>BUSINESS NAME</label>
                            <input type="text" name="shop_name" placeholder="Lumina Wellness" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="input-group">
                            <label>BUSINESS EMAIL</label>
                            <input type="email" name="email" placeholder="contact@business.com" required>
                        </div>
                        <div class="input-group">
                            <label>PASSWORD</label>
                            <div class="input-with-icon">
                                <input type="password" name="password" id="password" placeholder="••••••••" required>
                                <button type="button" class="icon-btn" id="togglePassword">
                                    <i data-feather="eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="input-group">
                            <label>PHONE NUMBER</label>
                            <input type="tel" name="phone" placeholder="09123456789" required>
                        </div>
                        <div class="input-group address-field">
                            <label>BUSINESS ADDRESS</label>
                            <input type="text" name="address" placeholder="123 Serenity Way, Suite 400" required>
                        </div>
                    </div>

                    <div class="terms-container">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to the Partner Terms & Privacy Policy.</label>
                    </div>

                    <button type="submit" class="submit-btn" id="submitBtn">REGISTER AS PARTNER</button>
                    <p class="login-redirect">Already have an account? <a href="Login.php">Log in</a></p>
                </form>
            </div>
        </section>
    </main>

    <footer class="bottom-footer">
        <p>&copy; 2026 DIVABOOK WELLNESS</p>
    </footer>

    <script>
        feather.replace();
        const togglePassword = document.querySelector('#togglePassword');
        const passwordField = document.querySelector('#password');

        togglePassword.addEventListener('click', function () {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i data-feather="eye"></i>' : '<i data-feather="eye-off"></i>';
            feather.replace();
        });

        document.getElementById('regForm').addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                return false;
            }
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = "CREATING ACCOUNT...";
            btn.style.opacity = "0.7";
            btn.style.pointerEvents = "none";
        });
    </script>
</body>
</html>