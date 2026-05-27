
<?php
session_start();

$specialRequests = $_SESSION['booking_data']['special_requests'] ?? '';

if (!isset($_SESSION['allowed_access']) || $_SESSION['allowed_access'] !== true) {
    header("Location: ../Booking_1/B1.php");
    exit(); 
}

require_once '../../db_connect.php';

$selectedAdminId = $_SESSION['selected_admin_id'] ?? '';
$salonProfile    = !empty($selectedAdminId) ? getSalonProfileByAdminId($selectedAdminId) : [];

$salonName      = !empty($salonProfile['salon_name'])      ? $salonProfile['salon_name']      : 'DivaBook';
$salonLogo      = !empty($salonProfile['logo_path'])       ? $salonProfile['logo_path']       : '';
$brandPrimary   = !empty($salonProfile['color_primary'])   ? $salonProfile['color_primary']   : '#064E3B';
$brandSecondary = !empty($salonProfile['color_secondary']) ? $salonProfile['color_secondary'] : '#7cbf7f';

if (!function_exists('hexToRgbStr')) {
    function hexToRgbStr($hex) {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        return "$r, $g, $b";
    }
}
$primaryRgb   = hexToRgbStr($brandPrimary);
$secondaryRgb = hexToRgbStr($brandSecondary);

// TO IDENTIFY PRICE AND NAME
$servicePrices = [
    'soft-gel' => ['name' => 'Soft Gel Extension', 'price' => 850],
    'gel-overlay' => ['name' => 'Gel Overlay', 'price' => 550],
    'hard-biab' => ['name' => 'Hard BIAB', 'price' => 750],
    'soft-biab' => ['name' => 'Soft BIAB', 'price' => 650],
];
$sizePrices = [
    'xl' => ['name' => 'Size: I - XXL', 'price' => 200],
    'standard' => ['name' => 'Standard', 'price' => 0],
    '0' => ['name' => '', 'price' => 0],
];
$addonPrices = [
    'basic' => ['name' => 'Basic Design', 'price' => 200],
    'moderate' => ['name' => 'Moderate Design', 'price' => 500],
    'heavy'    => ['name' => 'Heavy Design', 'price' => 0],
    '0' => ['name' => '', 'price' => 0],
];

$sessionData = $_SESSION['booking_data'] ?? [];

$selectedServiceKey = $sessionData['service'] ?? '';
$selectedSizeKey = $sessionData['size'] ?? '0';
$selectedAddonKey = $sessionData['addon'] ?? '0';
$isRemoval = isset($sessionData['removal']) && $sessionData['removal'] === 'yes';

$selectedDate = $sessionData['selected_date'] ?? 'Not scheduled';
$selectedTime = $sessionData['selected_time'] ?? '';

$totalPrice = 0;
$serviceName = 'Unknown Service';
$servicePrice = 0;

if (isset($servicePrices[$selectedServiceKey])) {
    $serviceName = $servicePrices[$selectedServiceKey]['name'];
    $servicePrice = $servicePrices[$selectedServiceKey]['price'];
    $totalPrice += $servicePrice;
}

$sizePrice = 0;
if (isset($sizePrices[$selectedSizeKey]) && $selectedSizeKey !== '0' && $selectedSizeKey !== 'standard') {
    $sizePrice = $sizePrices[$selectedSizeKey]['price'];
    $totalPrice += $sizePrice;
}

$addonPrice = 0;
if (isset($addonPrices[$selectedAddonKey]) && $selectedAddonKey !== '0') {
    $addonPrice = $addonPrices[$selectedAddonKey]['price'] ?? 0;
    $totalPrice += $addonPrice;
}

$removalPrice = 0;
if ($isRemoval) {
    $removalPrice = 150;
    $totalPrice += $removalPrice;
}

$dateTimeString = ($selectedDate !== 'Not scheduled' && $selectedTime !== '') ? "{$selectedDate}<br>{$selectedTime}" : 'Not scheduled';

$errors = [];
$firstName = $_POST['first_name'] ?? '';
$lastName = $_POST['last_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';

// TO SANITIZE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim(htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'));
    $lastName = trim(htmlspecialchars($lastName, ENT_QUOTES, 'UTF-8'));
    $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    $phone = preg_replace('/[^0-9]/', '', $phone); 

    // VALIDATION
    if (empty($firstName) || empty($lastName)) $errors[] = "Full name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (strlen($phone) !== 10) $errors[] = "Phone number must be exactly 10 digits.";
    if (!isset($_POST['terms_agreed'])) $errors[] = "You must agree to the Booking Policies.";

    if (empty($errors)) {
        // PRESERVE THE ADDMIN_ID AND REF IMG 
        $_SESSION['booking_data'] = array_merge($_SESSION['booking_data'], [
            'first_name'       => $firstName,
            'last_name'        => $lastName,
            'email'            => $email,
            'phone'            => $phone,
            'special_requests' => $_POST['special_requests'] ?? '',
        ]);

        // KEEP ALLOWED ACCESS ALIVE TO P1
        $_SESSION['allowed_access'] = true;

        header("Location: ../Payment/P1.php");
        exit();
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($salonName) ?> | Personal Info</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Noto+Serif&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style2.css">
    <style>
        /* ── CHANGEABLE BRANDING STUFF ── */
        :root {
            --primary-dark:    <?= htmlspecialchars($brandPrimary) ?>;
            --brand-secondary: <?= htmlspecialchars($brandSecondary) ?>;
            --brand-primary-rgb: <?= $primaryRgb ?>;
            --brand-secondary-rgb: <?= $secondaryRgb ?>;
        }

        /* FOR BOOKING SUMMARY*/
        .booking-summary {
            background: rgba(var(--brand-primary-rgb), 0.16) !important;
            border: 1.5px solid rgba(var(--brand-primary-rgb), 0.35) !important;
            border-radius: 12px;

        }
        .personal-details-form {
            background: rgba(var(--brand-primary-rgb), 0.08);
            border: 1px solid rgba(var(--brand-primary-rgb), 0.25);
            border-radius: 12px;
            padding: 28px;

        }
        .summary-total {
            background: rgba(var(--brand-primary-rgb), 0.16) !important;
            border-radius: 8px;

        }

        .cta-button {
            background: var(--brand-secondary) !important;
            border-color: var(--brand-secondary) !important;
        }
        .cta-button:hover {
            background: rgba(var(--brand-secondary-rgb), 0.82) !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(var(--brand-secondary-rgb), 0.35);
        }
        .progress-step.active .step-label {
            color: var(--primary-dark) !important;
        }
        .salon-identity-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 0 20px;
            border-bottom: 1px solid #e8e5df;
            margin-bottom: 20px;
        }
        .b2-logo-wrap {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--primary-dark);
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.10);
        }
        .b2-logo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .b2-logo-placeholder {
            font-size: 18px;
            font-family: 'Noto Serif', serif;
            font-style: italic;
            font-weight: 700;
            color: var(--primary-dark);
        }
        .b2-salon-name {
            font-family: 'Noto Serif', serif;
            font-size: 16px;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0;
            line-height: 1.2;
        }
        .b2-salon-label {
            font-size: 9px;
            letter-spacing: 1.5px;
            color: #999;
            text-transform: uppercase;
            margin: 2px 0 0;
        }
    </style>
</head>
<body>

    <!-- NAV BAR -->
    <?php
        $current_page = 'home';
        include 'header2.php';
    ?>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-content">
            <h1 class="logo-loading">DIVABOOK</h1>
            <div class="loader-bar-container">
                <div class="loader-bar-progress"></div>
            </div>
            <p class="loading-text">RETURNING TO SCHEDULE...</p>
        </div>
</div>

    <main class="container">

        <h1 id="title">PERSONAL INFORMATION</h1>

        <section class="booking-flow">

            <div class="navigation-row">
                <button type="button" class="back-btn" onclick="goBack()">
                    <span class="arrow">←</span> BACK
                </button>
            </div>

            <div class="progress-container">
                <div class="progress-step active">
                    <span class="step-label">STEP 2 OF 3</span>
                </div>
                <div class="progress-step">
                </div>
            </div>

            <div class="content-layout">
                <form class="personal-details-form" id="b2Form" action="B2.php" method="POST">
                    
                    <!-- PHP Error Display -->
                    <?php if (!empty($errors)): ?>
                        <div style="background: #fee2e2; color: #dc2626; padding: 15px; margin-bottom: 20px; border-radius: 6px; font-family: 'Inter', sans-serif; border: 1px solid #fca5a5;">
                            <strong style="display: block; margin-bottom: 5px;">Please fix the following:</strong>
                            <ul style="margin: 0; padding-left: 20px;">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <h2 class="section-title">Tell us about yourself</h2>
                    
                    <div class="input-row">
                        <div class="input-group">
                            <label>FIRST NAME</label>
                            <input type="text" name="first_name" placeholder="First Name" value="<?php echo htmlspecialchars($firstName); ?>" required>
                        </div>
                        <div class="input-group">
                            <label>LAST NAME</label>
                            <input type="text" name="last_name" placeholder="Last Name" value="<?php echo htmlspecialchars($lastName); ?>" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>EMAIL ADDRESS</label>
                        <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                    <div class="input-group">
                        <label>PHONE NUMBER</label>
                            <div class="phone-input">
                                <span class="country-code">+63</span>
                                <input 
                                    type="tel" 
                                    name="phone" 
                                    placeholder="9XXXXXXXXX" 
                                    value="<?php echo htmlspecialchars($phone); ?>" 
                                    pattern="\d{10}" 
                                    maxlength="10" 
                                    title="Please enter exactly 10 digits (e.g., 9XXXXXXXX)" 
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '');"required>
                            </div>
                    </div>

                    <div class="input-group">
                        <label>SPECIAL REQUESTS</label>
                        <textarea name="special_requests" placeholder="Let us know about any allergies or preferences..."><?php echo htmlspecialchars($specialRequests); ?></textarea>
                    </div>

                    <div class="marketing-opt-in" style="margin-top: 10px;">
                        <input type="checkbox" name="terms_agreed" id="terms" value="yes" required <?php echo (isset($_POST['terms_agreed']) && $_POST['terms_agreed'] === 'yes') ? 'checked' : ''; ?>>
                        <label for="terms">I agree to the Terms and Conditions and Booking Policies.</label>
                    </div>

                    <button type="submit" class="cta-button">CONTINUE TO PAYMENT</button>
                    <p class="secure-footer">SECURE CHECKOUT POWERED BY DIVABOOK</p>

                </form>


            <aside class="booking-summary">
                <!-- SALON IDENTITY-->
                <div class="salon-identity-bar">
                    <div class="b2-logo-wrap">
                        <?php if (!empty($salonLogo)): ?>
                            <img src="<?= htmlspecialchars($salonLogo) ?>" alt="<?= htmlspecialchars($salonName) ?> Logo">
                        <?php else: ?>
                            <span class="b2-logo-placeholder"><?= htmlspecialchars(mb_strtoupper(mb_substr($salonName, 0, 1))) ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="b2-salon-label">Service Provider</p>
                        <p class="b2-salon-name"><?= htmlspecialchars($salonName) ?></p>
                    </div>
                </div>

                <h3>Booking Summary</h3>
                
                <div class="summary-item">
                    <div>
                        <span class="label">TREATMENT</span>
                        <p class="value" style="font-weight: 600;"><?php echo htmlspecialchars($serviceName); ?></p> 
                    </div>
                    <span class="price">₱<?php echo number_format($servicePrice, 2); ?></span>
                </div>
                
                <?php if ($selectedAddonKey === 'heavy'): ?>
                <div class="summary-item" style="margin-top: -10px;">
                    <div>
                        <p class="value" style="font-size: 0.9em; color: #6b7280; margin-right: 73px; ">+ Hard Design - Rates may vary based on design complexity.</p>
                    </div>
                    <span class="price" style="font-size: 0.9em; color: #6b7280;">TBD</span>
                </div>

                <?php elseif ($addonPrice > 0): ?>
                <div class="summary-item" style="margin-top: -10px;">
                    <div>
                        <p class="value" style="font-size: 0.9em; color: #6b7280;">+ <?php echo htmlspecialchars($addonPrices[$selectedAddonKey]['name']); ?></p>
                    </div>
                    <span class="price" style="font-size: 0.9em; color: #6b7280;">₱<?php echo number_format($addonPrice, 2); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($sizePrice > 0): ?>
                <div class="summary-item" style="padding-top: 0; border-top: none; margin-top: -10px;">
                    <div>
                        <p class="value" style="font-size: 0.9em; color: #6b7280;">+ <?php echo htmlspecialchars($sizePrices[$selectedSizeKey]['name']); ?></p>
                    </div>
                    <span class="price" style="font-size: 0.9em; color: #6b7280;">₱<?php echo number_format($sizePrice, 2); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($removalPrice > 0): ?>
                <div class="summary-item" style="padding-top: 0; border-top: none; margin-top: -10px;">
                    <div>
                        <p class="value" style="font-size: 0.9em; color: #6b7280;">+ Removal</p>
                    </div>
                    <span class="price" style="font-size: 0.9em; color: #6b7280;">₱<?php echo number_format($removalPrice, 2); ?></span>
                </div>
                <?php endif; ?>

                <div class="summary-item">
                    <div>
                        <span class="label">DATE & TIME</span>
                        <p class="value"><?php echo $dateTimeString; ?></p>
                    </div>
                </div>
                <div class="summary-total">
                    <span>TOTAL</span>
                    <span class="total-price">₱<?php echo number_format($totalPrice, 2); ?></span>
                </div>
            </aside>


            </div>
        </section>
    </main>

<script src="script2.js"></script>

        <!--  FOOTER -->
        <?php include '../footer.php'; ?>


</body>
</html>