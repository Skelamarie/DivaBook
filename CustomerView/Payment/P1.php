<?php
session_start();

if (!isset($_SESSION['allowed_access']) || $_SESSION['allowed_access'] !== true) {
    header("Location: ../Booking_1/B1.php");
    exit(); 
}
require_once '../../db_connect.php';
use Cloudinary\Api\Upload\UploadApi;

$selectedAdminId = $_SESSION['selected_admin_id'] ?? '';
$salonProfile    = !empty($selectedAdminId) ? getSalonProfileByAdminId($selectedAdminId) : [];

$salonName      = !empty($salonProfile['salon_name'])      ? $salonProfile['salon_name']      : 'DivaBook';
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

$errors = [];
$showSuccessModal = false;

$servicePrices = [
    'soft-gel'   => ['name' => 'Soft Gel Extension', 'price' => 850],
    'gel-overlay'=> ['name' => 'Gel Overlay', 'price' => 550],
    'hard-biab'  => ['name' => 'Hard BIAB', 'price' => 750],
    'soft-biab'  => ['name' => 'Soft BIAB', 'price' => 650],
];
$sizePrices = [
    'xl'       => ['name' => 'Size: I - XXL', 'price' => 200],
    'standard' => ['name' => 'Standard', 'price' => 0],
    '0'        => ['name' => '', 'price' => 0],
];
$addonPrices = [
    'basic'    => ['name' => 'Basic Design', 'price' => 200],
    'moderate' => ['name' => 'Moderate Design', 'price' => 500],
    '0'        => ['name' => '', 'price' => 0],
];

$sessionData = $_SESSION['booking_data'] ?? [];

$selectedServiceKey = $sessionData['service'] ?? '';
$selectedSizeKey    = $sessionData['size']    ?? '0';
$selectedAddonKey   = $sessionData['addon']   ?? '0';
$isRemoval = isset($sessionData['removal']) && $sessionData['removal'] === 'yes';

$selectedDate = $sessionData['selected_date'] ?? 'Not scheduled';
$selectedTime = $sessionData['selected_time'] ?? '';

$totalPrice   = 0;
$serviceName  = 'Unknown Service';
$servicePrice = 0;

if (isset($servicePrices[$selectedServiceKey])) {
    $serviceName  = $servicePrices[$selectedServiceKey]['name'];
    $servicePrice = $servicePrices[$selectedServiceKey]['price'];
    $totalPrice  += $servicePrice;
}

$sizePrice = 0;
if (isset($sizePrices[$selectedSizeKey]) && $selectedSizeKey !== '0' && $selectedSizeKey !== 'standard') {
    $sizePrice   = $sizePrices[$selectedSizeKey]['price'];
    $totalPrice += $sizePrice;
}

$addonPrice = 0;
if (isset($addonPrices[$selectedAddonKey]) && $selectedAddonKey !== '0') {
    $addonPrice  = $addonPrices[$selectedAddonKey]['price'];
    $totalPrice += $addonPrice;
}

$removalPrice = 0;
if ($isRemoval) {
    $removalPrice = 150;
    $totalPrice  += $removalPrice;
}

$dateTimeString = ($selectedDate !== 'Not scheduled' && $selectedTime !== '')
    ? "{$selectedDate}<br>{$selectedTime}"
    : 'Not scheduled';

// Validate, Upload Receipt, Insert to DB
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // DETERMINE PAYMENT METHOD
    $paymentMethod = $_POST['payment_method'] ?? '';
    $prefix = match($paymentMethod) {
        'gcash'      => 'gcash',
        'paymaya'    => 'maya',
        'shopeepay'  => 'shopee',
        default      => ''
    };

    // PULL SENDER NAME
    $senderName = trim($_POST[$prefix . '_name'] ?? '');

    // VALIDATE
    if (empty($paymentMethod)) {
        $errors[] = "Please select a payment method.";
    }
    if (empty($senderName)) {
        $errors[] = "Please enter your sender name.";
    }
    if (empty($_FILES[$prefix . '_receipt']['name'])) {
        $errors[] = "Please upload your payment receipt.";
    }

    // UPLOAD TO CLOUD
    $receiptPath = '';
    if (empty($errors) && isset($_FILES[$prefix . '_receipt']) && $_FILES[$prefix . '_receipt']['error'] === UPLOAD_ERR_OK) {
        try {
            $uploadApi = new UploadApi();
            $response = $uploadApi->upload($_FILES[$prefix . '_receipt']['tmp_name'], [
                'folder' => "divabook/$selectedAdminId/receipt"
            ]);
            $receiptPath = $response['secure_url'];
        } catch (Exception $e) {
            $errors[] = "Receipt upload failed: " . $e->getMessage();
        }
    }

    // INSERT TO DB
    if (empty($errors)) {
    try {
        $bookingDocument = [
            'admin_id'         => $selectedAdminId, 
            'status'           => 'pending',
            'created_at'       => new MongoDB\BSON\UTCDateTime(), 

            // SERVICE DETAILS
            'service'          => $selectedServiceKey,
            'service_name'     => $serviceName,
            'service_price'    => $servicePrice,
            'size'             => $selectedSizeKey,
            'size_name'        => $sizePrices[$selectedSizeKey]['name'] ?? '',
            'size_price'       => $sizePrice,
            'addon'            => $selectedAddonKey,
            'addon_name'       => $addonPrices[$selectedAddonKey]['name'] ?? '',
            'addon_price'      => $addonPrice,
            'removal'          => $isRemoval,
            'removal_price'    => $removalPrice,
            'total_price'      => $totalPrice,
            'selected_date'    => $selectedDate,
            'selected_time'    => $selectedTime,
            'reference_image'  => $sessionData['reference_image_path'] ?? '',
            'provider_chooses' => isset($sessionData['provider_chooses_design']) && $sessionData['provider_chooses_design'] === 'yes',

            // CUSTOMER
            'first_name'       => $sessionData['first_name']        ?? '',
            'last_name'        => $sessionData['last_name']         ?? '',
            'email'            => $sessionData['email']             ?? '',
            'phone'            => '+63' . ($sessionData['phone']    ?? ''),
            'special_requests' => $sessionData['special_requests']  ?? '',

            // PAYMENT
            'payment_method'   => $paymentMethod,
            'sender_name'      => $senderName,
            'receipt_path'     => $receiptPath,
            'payment_strategy' => $_POST['payment_strategy'] ?? 'Downpayment',
            'amount_paid'      => (($_POST['payment_strategy'] ?? '') === 'full') ? $totalPrice : 450,
        ];

        $result = $bookingsCollection->insertOne($bookingDocument);

            if ($result->getInsertedCount() === 1) {
                $_SESSION['booking_data']   = [];
                $_SESSION['allowed_access'] = false;
                $showSuccessModal = true;
            } else {
                $errors[] = "Booking could not be saved. Please try again.";
            }

        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($salonName) ?> | Checkout</title>
    <!-- <link rel="stylesheet" href="header3.css"> -->
    <link rel="stylesheet" href="style3.css">
    <link rel="stylesheet" href="footer3.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Noto+Serif:ital,wght@0,400;1,400&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* ── CHANGEABLE BRANDING STUFF ── */
        :root {
            --brand-primary:   <?= htmlspecialchars($brandPrimary) ?>;
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
        .payment-method-card {
            border: 1.5px solid rgba(var(--brand-primary-rgb), 0.28) !important;
            background: rgba(var(--brand-primary-rgb), 0.08) !important;

        }
        .payment-method-card input[type="radio"]:checked ~ .method-header {
            border-color: var(--brand-primary) !important;
        }
        .summary-total {
            background: rgba(var(--brand-primary-rgb), 0.16) !important;
            border-radius: 8px;

        }
        .strategy-card {
            border-color: rgba(var(--brand-primary-rgb), 0.30) !important;

        }
        input[type="radio"]:checked + .strategy-card {
            border-color: var(--brand-primary) !important;
            background: rgba(var(--brand-primary-rgb), 0.12) !important;
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
            color: var(--brand-primary) !important;
        }
        .radio-circle {
            border-color: var(--brand-primary) !important;
        }
        input[type="radio"]:checked ~ .method-header .radio-circle::after {
            background: var(--brand-primary) !important;
        }
    </style>
</head>

<body>
    <!-- Loading Overlay for P1 -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-content">
            <h1 class="logo-loading">DIVABOOK</h1>
            <div class="loader-bar-container">
                <div class="loader-bar-progress"></div>
            </div>
            <p class="loading-text">RETURNING TO DETAILS...</p>
        </div>
    </div>

    <!-- NAV BAR -->
        <?php include '../navbar.php'; ?>
   
        <div class="container">        
            <div class="navigation-row">
                <button type="button" class="back-btn" onclick="window.location.href='../Booking_2/B2.php'">
                <span class="arrow">←</span> BACK
                </button>
            </div>

            <div class="progress-container">
                <div class="progress-step active">
                    <span class="step-label">STEP 3 OF 3</span>
                </div>
            </div>

        <div class="content-layout">
            <div class="payment-selection">
                <h2 class="section-title">Checkout</h2>
                <p class="section-subtitle">Choose your preferred payment method and upload your proof of payment.</p>
                
                <p class="method-label">PAYMENT STRATEGY</p>
                <div class="payment-strategy-container" style="display: flex; gap: 15px; margin-bottom: 30px;">
                    <input type="radio" name="payment_strategy" id="pay_downpayment" value="downpayment" checked style="display:none;">
                    <label for="pay_downpayment" class="strategy-card">
                        <span class="strategy-title">Downpayment</span>
                        <span class="strategy-desc">Pay ₱450.00 now to secure slot</span>
                    </label>

                    <input type="radio" name="payment_strategy" id="pay_full" value="full" style="display:none;">
                    <label for="pay_full" class="strategy-card">
                        <span class="strategy-title">Full Payment</span>
                        <span class="strategy-desc">Pay the total amount now</span>
                    </label>
                </div>
                
                <p class="method-label">SELECT PAYMENT METHOD</p>

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

                <form id="checkout-form" action="P1.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="payment_strategy" id="hidden_payment_strategy" value="downpayment">

                    <!-- GCash -->
                    <div class="payment-method-card">
                        <input type="radio" name="payment_method" id="gcash" value="gcash">
                        <label for="gcash" class="method-header">
                            <div class="radio-circle"></div>
                            <span>GCash</span>
                        </label>
                        <div class="method-details">
                            <div class="qr-container">
                                <p class="instruction">Scan the QR code below to pay via GCash</p>
                                <img src="qr/gcash.png" alt="GCash QR Code" class="qr-image">
                            </div>
                            <div class="input-group">
                                <label>SENDER NAME</label>
                                <input type="text" name="gcash_name" placeholder="Name on GCash Account">
                            </div>
                            <div class="input-group">
                                <label>UPLOAD RECEIPT</label>
                                <input type="file" name="gcash_receipt" class="file-input" accept="image/*">
                            </div>
                        </div>
                    </div>

                    <!-- PayMaya -->
                    <div class="payment-method-card">
                        <input type="radio" name="payment_method" id="paymaya" value="paymaya">
                        <label for="paymaya" class="method-header">
                            <div class="radio-circle"></div>
                            <span>PayMaya</span>
                        </label>
                        <div class="method-details">
                            <div class="qr-container">
                                <p class="instruction">Scan the QR code below to pay via PayMaya</p>
                                <img src="qr/maya.png" alt="PayMaya QR Code" class="qr-image">
                            </div>
                            <div class="input-group">
                                <label>SENDER NAME</label>
                                <input type="text" name="maya_name" placeholder="Name on Maya Account">
                            </div>
                            <div class="input-group">
                                <label>UPLOAD RECEIPT</label>
                                <input type="file" name="maya_receipt" class="file-input" accept="image/*">
                            </div>
                        </div>
                    </div>

                    <!-- ShopeePay -->
                    <div class="payment-method-card">
                        <input type="radio" name="payment_method" id="shopeepay" value="shopeepay">
                        <label for="shopeepay" class="method-header">
                            <div class="radio-circle"></div>
                            <span>ShopeePay</span>
                        </label>
                        <div class="method-details">
                            <div class="qr-container">
                                <p class="instruction">Scan the QR code below to pay via ShopeePay</p>
                                <img src="qr/spay.png" alt="ShopeePay QR Code" class="qr-image">
                            </div>
                            <div class="input-group">
                                <label>SENDER NAME</label>
                                <input type="text" name="shopee_name" placeholder="Name on ShopeePay Account">
                            </div>
                            <div class="input-group">
                                <label>UPLOAD RECEIPT</label>
                                <input type="file" name="shopee_receipt" class="file-input" accept="image/*">
                            </div>
                        </div>
                    </div>

                    <div id="submit-notice" style="color: #dc2626; font-size: 13px; margin-bottom: 15px; display: none; text-align: center; font-weight: 600;">
                        ⚠ Please select a payment method and fill up all required fields.
                    </div>
                    <button type="submit" id="confirm-pay-btn" class="cta-button">CONFIRM AND PAY</button>

                </form>
                <p class="secure-footer">🔒 SECURE ENCRYPTED PAYMENT</p>
            </div>

            
            <aside class="booking-summary">
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

            <!-- No Payment Method Popup -->
            <div id="no-payment-popup" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
                <div style="background:#fff; border-radius:12px; padding:36px 32px; max-width:360px; width:90%; text-align:center; box-shadow:0 8px 32px rgba(0,0,0,0.18);">
                    <div style="font-size:2.5rem; margin-bottom:12px;">⚠️</div>
                    <h3 style="font-family:'Playfair Display',serif; font-size:1.25rem; margin:0 0 10px; color:#1a1a1a;">No Payment Method Selected</h3>
                    <p style="font-family:'Inter',sans-serif; font-size:0.9rem; color:#6b7280; margin:0 0 24px;">Please choose a payment method (GCash, PayMaya, or ShopeePay) before proceeding.</p>
                    <button onclick="document.getElementById('no-payment-popup').style.display='none';" style="background:#1a1a1a; color:#fff; border:none; border-radius:6px; padding:12px 32px; font-family:'Inter',sans-serif; font-size:0.85rem; font-weight:600; letter-spacing:0.08em; cursor:pointer;">GOT IT</button>
                </div>
            </div>

            <!-- Confirmation Pop-up Modal -->
            <div id="confirmation-modal" class="modal-overlay" style="display: <?php echo $showSuccessModal ? 'flex' : 'none'; ?>">
                <div class="modal-content">
                    <div class="success-icon">✓</div>
                    <h2 class="modal-title">Booking Submitted</h2>
                    <p class="modal-message">Please wait for admin confirmation.</p>
                    <p class="modal-subtext">Check your email for your booking status and further updates.</p>
                    <button type="button" class="modal-close-btn" onclick="closeModal()">GOT IT</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../footer.php'; ?>

<script src="script3.js"></script>

</body>
</html>