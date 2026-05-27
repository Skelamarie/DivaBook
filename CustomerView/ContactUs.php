<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | DivaBook</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="ContactUs.css">
</head>
<body>

    <?php 
        // Tell the navigation bar we are currently on the contact page
        $current_page = 'contact'; 
        require_once 'navbar.php'; 
    ?>

    <main class="contact-wrapper">
        <section class="contact-header">
            <h2 class="section-title">Contact Us</h2>
            <p class="section-subtitle">We are here to assist with your beauty and wellness journey.</p>
        </section>

        <div class="contact-container">
            <div class="contact-info">
                <div class="info-group">
                    <label>MAIN ADDRESS</label>
                    <p>Inigo Street, Obrero <br>Davao City, 8000</p>
                </div>

                <div class="info-row">
                    <div class="info-group">
                        <label>SUPPORT</label>
                        <p><a href="mailto:concierge@divabook.com">concierge@divabook.com</a></p>
                    </div>
                    <div class="info-group">
                        <label>PARTNERSHIPS</label>
                        <p><a href="mailto:partners@divabook.com">partners@divabook.com</a></p>
                    </div>
                </div>

                <div class="map-box">
                    <span>MAP VIEW</span>
                </div>
            </div>

            <div class="contact-form-card">
                <h3>Inquiry Form</h3>
                <form id="inquiryForm" action="process_contact.php" method="POST">
                    <div class="form-grid">
                        <div class="input-field">
                            <label>FULL NAME</label>
                            <input type="text" name="fullname" placeholder="Jane Doe" required>
                        </div>
                        <div class="input-field">
                            <label>EMAIL ADDRESS</label>
                            <input type="email" name="email" placeholder="jane@example.com" required>
                        </div>
                    </div>
                    
                    <div class="input-field">
                        <label>INQUIRY TYPE</label>
                        <select name="type" required>
                            <option value="" disabled selected>Select a category</option>
                            <option>General Support</option>
                            <option>Partnership Interest</option>
                        </select>
                    </div>

                    <div class="input-field">
                        <label>MESSAGE</label>
                        <textarea name="message" placeholder="How can we assist you?" required></textarea>
                    </div>

                    <button type="submit" class="submit-btn" id="sendBtn">
                        <span>SEND INQUIRY</span>
                        <i data-feather="send" class="btn-icon"></i>
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Make the tiny icons show up
        feather.replace();

        // Change the button text when someone clicks "Send"
        document.getElementById('inquiryForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('sendBtn');
            btn.classList.add('loading');
            btn.querySelector('span').innerText = "SENDING...";
        });
    </script>
</body>
</html>