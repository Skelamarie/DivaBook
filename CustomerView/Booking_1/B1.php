<?php
session_start();
$errors = [];

// TO ACCESS DB AND CLOUD
require_once '../../db_connect.php';
use Cloudinary\Api\Upload\UploadApi;

$postData = $_SESSION['booking_data'] ?? [];

$selectedAdminId = $_SESSION['selected_admin_id'] ?? '';

// BLOCK BOOKED SLOTS
$occupiedSlotsMap = [];

if (!empty($selectedAdminId)) {
    $bookedDocs = $bookingsCollection->find([
        'admin_id' => $selectedAdminId,
        'status'   => ['$in' => ['pending', 'approved', 'confirmed']]
    ]);

    foreach ($bookedDocs as $doc) {
        $d = (string)($doc['selected_date'] ?? '');
        $t = (string)($doc['selected_time'] ?? '');
        if ($d !== '' && $t !== '') {
            $occupiedSlotsMap[$d][] = $t;
        }
    }
}

// MAP IN JAVASCRIPT
echo "<script>window.occupiedSlotsMap = " . json_encode($occupiedSlotsMap) . ";</script>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service = $_POST['service'] ?? '0';
    $size = $_POST['size'] ?? '0';
    $addon = $_POST['addon'] ?? '0';
    $date = $_POST['selected_date'] ?? '';
    $time = $_POST['selected_time'] ?? '';
    $providerChooses = isset($_POST['provider_chooses_design']) && $_POST['provider_chooses_design'] === 'yes';

    // VALIDATION
    if ($service === '0') $errors[] = "Please select a valid base coat service.";
    if ($size === '0') $errors[] = "Please select a base coat size.";
    if (empty($date) || $date === "null") $errors[] = "Please select a date on the calendar.";
    if (empty($time)) $errors[] = "Please select a time slot.";
    if ($addon === '0') $errors[] = "Please select a design add-on.";

    // FOR IMAGE UPLOAD
    if (!$providerChooses) {
        if (!isset($_FILES['reference_image']) || $_FILES['reference_image']['error'] !== UPLOAD_ERR_OK) {
            if (empty($_SESSION['booking_data']['reference_image_path'])) {
                $errors[] = "Please upload a reference image or let the provider choose.";
            }
        } else {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($_FILES['reference_image']['type'], $allowedTypes)) {
                $errors[] = "Only JPG, PNG, and WEBP files are allowed.";
            }
        }
    }

    // DOUBLE CHECK BOOKING
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['selected_date'] ?? '';
    $time = $_POST['selected_time'] ?? '';

    if (empty($errors)) {
        $existing = $bookingsCollection->findOne([
            'admin_id'      => $selectedAdminId,
            'selected_date' => $date,
            'selected_time' => $time,
            'status'        => ['$in' => ['pending', 'approved', 'confirmed']],
        ]);

        if ($existing) {
            $errors[] = "The slot " . htmlspecialchars($time) . " on " . htmlspecialchars($date) . " is no longer available.";
        }
    }
}

    // SAVE SESSION AND CLOUD
    if (empty($errors)) {
        $_SESSION['booking_data'] = $_POST;
        
        //CLOUDINARY UPLOAD LOGIC
        if (isset($_FILES['reference_image']) && $_FILES['reference_image']['error'] === UPLOAD_ERR_OK) {
            try {
                $uploadApi = new UploadApi();
                $response = $uploadApi->upload($_FILES['reference_image']['tmp_name'], [
                    'folder' => "divabook/$selectedAdminId/inspo"
                ]);
                
                // SAVE TO CLOUD INSTEAD NA SA LOCAL STORGAE
                $_SESSION['booking_data']['reference_image_path'] = $response['secure_url'];
            } catch (Exception $e) {
                $errors[] = "Image upload failed: " . $e->getMessage();
            }
        }

        // REDIRECT IF NO ERROR MAKITA
        if (empty($errors)) {
            $_SESSION['allowed_access'] = true;
            header("Location: ../Booking_2/B2.php");
            exit();
        }
    }
}

// ─── GET SALON PROFILE BRANDING ───
$salonProfile    = !empty($selectedAdminId) ? getSalonProfileByAdminId($selectedAdminId) : [];
$salonName      = !empty($salonProfile['salon_name'])      ? $salonProfile['salon_name']      : 'DivaBook';
$salonLogo      = !empty($salonProfile['logo_path'])       ? $salonProfile['logo_path']       : '';
$salonLocation  = !empty($salonProfile['location'])        ? $salonProfile['location']        : 'MTS, Davao City, Philippines';
$brandPrimary   = !empty($salonProfile['color_primary'])   ? $salonProfile['color_primary']   : '#064E3B';
$brandSecondary = !empty($salonProfile['color_secondary']) ? $salonProfile['color_secondary'] : '#7cbf7f';

// CONVERT TO RGB
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
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($salonName) ?> | Book Appointment</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Noto+Serif&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="footer.css">
    <style>
        /* ── CHANGEABLE BRANDING STUFF ── */
        :root {
            --brand-primary:   <?= htmlspecialchars($brandPrimary) ?>;
            --brand-secondary: <?= htmlspecialchars($brandSecondary) ?>;
            --brand-primary-rgb: <?= $primaryRgb ?>;
            --brand-secondary-rgb: <?= $secondaryRgb ?>;
        }
        /* APPLY COLOR */
        .day.active          { background: var(--brand-primary) !important; color: #fff !important; }
        .slot-btn.active     { border-color: var(--brand-primary); color: var(--brand-primary); background: rgba(0,0,0,0.04); }
        .slot-btn            { color: var(--brand-primary); }
        .confirm-btn         { background: var(--brand-secondary) !important; }
        .confirm-btn:hover   { background: rgba(var(--brand-secondary-rgb), 0.82) !important; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(var(--brand-secondary-rgb), 0.35); }
        .summary-price       { color: var(--brand-primary); }
        .addon-card.selected { border-color: var(--brand-primary) !important; background: rgba(var(--brand-primary-rgb), 0.04); }
        .custom-dropdown     { border-color: var(--brand-primary); }
        .stepper-item.active .step-line { background-color: var(--brand-primary); }
        .stepper-item.active .step-label { color: var(--brand-primary); }

        /* ── Branded Container Boxes ── */
        .calendar-card {
            background: rgba(var(--brand-primary-rgb), 0.12) !important;
            border: 1.5px solid rgba(var(--brand-primary-rgb), 0.30) !important;
            border-radius: 12px;

        }
        .summary-section {
            background: rgba(var(--brand-primary-rgb), 0.16) !important;
            border: 1.5px solid rgba(var(--brand-primary-rgb), 0.35) !important;
            border-radius: 12px;
            padding: 20px;

        }
        .upload-inner-card {
            background: rgba(var(--brand-primary-rgb), 0.10) !important;
            border: 1px solid rgba(var(--brand-primary-rgb), 0.28) !important;
            border-radius: 10px;

        }
        .addon-card {
            border: 1.5px solid rgba(var(--brand-primary-rgb), 0.28) !important;
            background: rgba(var(--brand-primary-rgb), 0.08) !important;

        }
        .proceed-btn {
            background: var(--brand-secondary) !important;
        }

        /* IDENTITY */
        .salon-identity {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .b1-logo-wrap {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            overflow: hidden;
            border: 2.5px solid var(--brand-primary);
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.10);
        }
        .b1-logo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .b1-logo-placeholder {
            font-size: 22px;
            font-family: 'Noto Serif', serif;
            font-style: italic;
            font-weight: 700;
            color: var(--brand-primary);
        }
        .salon-name-text {
            font-family: 'Noto Serif', serif;
            font-size: 26px;
            font-weight: 700;
            color: var(--brand-primary);
            margin: 0;
            line-height: 1.1;
        }
        .salon-addr-text {
            font-size: 12px;
            color: #78716C;
            letter-spacing: 0.5px;
            margin: 4px 0 0;
        }


        .slot-occupied,
        .slot-selected {
            opacity: 0.45;
            cursor: not-allowed;
            text-decoration: line-through;
            background-color: #d1d5db !important;
            color: #6b7280 !important;
        }

        .slot-selected {
            border: 2px solid #ef4444;
        }
        
    </style>
</head>

<body>

    <!-- FOR LOADING OVERLAY -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-content">
            <h1 class="logo-loading">DIVABOOK</h1>
            <div class="loader-bar"></div>
            <p class="loading-text">Securing your slot...</p>
        </div>
    </div>

    <!-- POLICY (POPUP)-->
    <div id="policy-overlay" class="policy-overlay <?php echo ($_SERVER['REQUEST_METHOD'] === 'POST') ? '' : 'active'; ?>">
        <div class="policy-modal">
            <button type="button" class="close-x-btn" id="policy-close-btn" aria-label="Close">×</button>           
            <h1 class="policy-modal-header">Booking Policy</h1>

            <div class="policy-grid">
                <!-- FEES FOR LATE CUSTOMER -->
                <div class="policy-section">
                    <h3 class="policy-heading">LATE FEES:</h3>
                    <ul class="policy-list">
                        <li>10 minutes late – P200</li>
                        <li>20 minutes or more – P500</li>
                        <li>Late fees help ensure timely appointments.</li>
                    </ul>
                </div>

                <!-- DEPOSIT -->
                <div class="policy-section">
                    <h3 class="policy-heading">DEPOSIT : P450</h3>
                    <ul class="policy-list">
                        <li>Deposits are non-refundable.</li>
                        <li>Rescheduling results in a forfeited deposit.</li>
                    </ul>
                </div>

                <!-- WARRANTY -->
                <div class="policy-section">
                    <h3 class="policy-heading">WARRANTY</h3>
                    <ul class="policy-list">
                        <li>We offer a 1-week service warranty.</li>
                        <li>Inform us of any skin allergies or medical conditions.</li>
                    </ul>
                </div>
            </div>

            <div class="agreement-container">
                <label class="custom-checkbox">
                    <input type="checkbox" id="policy-agree-check">
                    <span class="checkmark"></span>
                    <span class="agreement-text">I have read and agree to the Booking Policy.</span>
                </label>
            </div>

            <div class="policy-button-group">
                <button type="button" id="proceed-booking-btn" class="proceed-btn" disabled>
                    PROCEED TO BOOKING
                </button>
            </div>
        </div>
    </div>


    <!-- HEADER / NABVAR -->
    <?php include 'header.php'; ?>

        <div class="booking-wrapper">
            <form action="B1.php" method="POST" enctype="multipart/form-data" id="bookingForm" style="display: contents;">
                <input type="hidden" name="selected_date" id="selected_date" value="<?php echo htmlspecialchars($_POST['selected_date'] ?? ''); ?>">
                <input type="hidden" name="selected_time" id="selected_time" value="<?php echo htmlspecialchars($_POST['selected_time'] ?? '09:00 AM'); ?>">

            <?php if (!empty($errors)): ?>
                <div style="background: #fee2e2; color: #dc2626; padding: 15px; margin-bottom: 20px; border-radius: 6px; font-family: 'Inter', sans-serif; grid-column: 1 / -1; width: 100%;">
                    <strong style="display: block; margin-bottom: 5px;">Please fix the following errors:</strong>
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <h1 id="title"> schedule an APPOINTMENT</h1>

            <div class="stepper-container">
                <div class="stepper-item active">
                    <span class="step-label">STEP 1 OF 3</span>
                    <div class="step-line"></div>
                </div>
                <div class="stepper-item">
                    <div class="step-line"></div>
                </div>
                <div class="stepper-item">
                    <div class="step-line"></div>
                </div>
            </div>

        <main class="left-column">
            <div class="section-title-row">
                <!-- SALON INFO. -->
                <div class="salon-identity">
                    <div class="b1-logo-wrap">
                        <?php if (!empty($salonLogo)): ?>
                            <img src="<?= htmlspecialchars($salonLogo) ?>" alt="<?= htmlspecialchars($salonName) ?> Logo">
                        <?php else: ?>
                            <span class="b1-logo-placeholder"><?= htmlspecialchars(mb_strtoupper(mb_substr($salonName, 0, 1))) ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h2 class="salon-name-text" id="dynamicStoreName"><?= htmlspecialchars($salonName) ?></h2>
                        <p class="salon-addr-text">LOCATION: <?= htmlspecialchars($salonLocation) ?></p>
                    </div>
                </div>
            </div>

            <div class="dropdown-container">
                <label for="service-select" class="heading-serif">Choose a Base Coat <span class="required-star"></span></label>
                <select id="service-select" name="service" class="custom-dropdown text-inter">
                    <option value="0" data-price="0">-- Select a Base Coat --</option>
                    <option value="soft-gel" data-price="850" <?php echo (($_POST['service'] ?? '') === 'soft-gel') ? 'selected' : ''; ?>>Soft Gel Extension - ₱850</option>
                    <option value="gel-overlay" data-price="550" <?php echo (($_POST['service'] ?? '') === 'gel-overlay') ? 'selected' : ''; ?>>Gel Overlay - ₱550</option>
                    <option value="hard-biab" data-price="750" <?php echo (($_POST['service'] ?? '') === 'hard-biab') ? 'selected' : ''; ?>>Hard BIAB - ₱750</option>
                    <option value="soft-biab" data-price="650" <?php echo (($_POST['service'] ?? '') === 'soft-biab') ? 'selected' : ''; ?>>Soft BIAB - ₱650</option>
                </select>

                <label for="size-select" class="heading-serif">Choose Base Coat Size <span class="required-star"></span></label>
                <select id="size-select" name="size" class="custom-dropdown text-inter">
                    <option value="0" data-price="0">-- Select a Base Coat Size --</option>
                    <option value="xl" data-price="200" <?php echo (($_POST['size'] ?? '') === 'xl') ? 'selected' : ''; ?>>Size: I - XXL - ₱200</option>
                    <option value="standard" data-price="0" <?php echo (($_POST['size'] ?? '') === 'standard') ? 'selected' : ''; ?>>Standard - ₱0</option>
                </select>
            
            <!-- FOR THE ADD ONS -->
            <h3 class="heading-serif" style="margin-top: 30px; font-size: 25px; color: #064E3B;">Design Add-ons</h3>
            <h3 class="text-inter" style="margin-top: 30px; font-size: 13px; opacity: 75%; margin-top: 0;">REMINDER: Prices may vary depending on the complexity of your chosen design, and more detailed requests may come with an additional cost. Please note that all add-on services must be paid in cash, and you can check the pricing grids below as your guide.</h3>
                                                                                                                     
            <div class="addon-grid">
                <!-- BASIC-->
                <div class="addon-card" data-price="200" data-name="Basic Design">
                    <div class="addon-img-box">
                        <img src="img/basic.png" alt="Basic">
                    </div>
                    <div class="addon-info">
                        <h4 class="heading-serif">Basic</h4>
                        <p class="text-inter"> Minimal Design<br>1-2 accent Stones<br>Plain • French Tip<br>1-2 (3d design like flowers etc)<br></p>
                        <span class="addon-price">+₱200</span>
                    </div>
                </div>

                <!-- FOR MODERATE -->
                <div class="addon-card" data-price="500" data-name="Moderate Design">
                    <div class="addon-img-box">
                        <img src="img/moderate.png" alt="Moderate">
                    </div>
                    <div class="addon-info">
                        <h4 class="heading-serif">Moderate</h4>
                        <p class="text-inter">Cat Eye • Stickers • Chromes<br>Embossed Design<br>Hand Drawn Art with chromes<br>3-5 3D flowers etc<br></p>
                        <span class="addon-price">+₱500</span>
                    </div>
                </div>

                <!-- HEAVY-->
                <div class="addon-card" data-price="
                " data-name="Heavy Design">
                    <div class="addon-img-box">
                        <img src="img/heavy.png" alt="Heavy">
                    </div>
                    <div class="addon-info">
                        <h4 class="heading-serif">Heavy</h4>
                        <p class="text-inter">Freehand Nail Art<br>Unlimited Stones<br>Full 3D nail art<br>and more</p>
                        <span class="addon-price">+₱1000 - ₱2000</span>
                    </div>
                </div>
            </div>  
            
            <label for="addon-select" class="heading-serif">Choose Design Add On <span class="required-star"></span></label>
            <select id="addon-select" name="addon" class="custom-dropdown text-inter">
                <option value="0" data-price="0">-- Select Add On --</option>
                <option value="basic" data-price="200" <?php echo (($_POST['addon'] ?? '') === 'basic') ? 'selected' : ''; ?>>Basic</option>
                <option value="moderate" data-price="500" <?php echo (($_POST['addon'] ?? '') === 'moderate') ? 'selected' : ''; ?>>Moderate</option>
                <option value="heavy" data-price="" <?php echo (($_POST['addon'] ?? '') === 'heavy') ? 'selected' : ''; ?>>Heavy</option>
            </select>

            <div class="removal-container" style="margin-top: 15px;">
                <label class="custom-checkbox">
                    <input type="checkbox" name="removal" id="removal-check" data-price="150" value="yes" <?php echo (isset($_POST['removal']) && $_POST['removal'] === 'yes') ? 'checked' : ''; ?>>
                    <span class="checkmark"></span>
                    <span class="text-inter" style="font-weight: 600;">Add on Removal - ₱150 (Own Work)</span>
                </label>
            </div>


            </div>

            <div class="upload-container">
                <div class="provider-choice-container" style="margin-bottom: 15px;">
                    <label class="custom-checkbox">
                        <input type="checkbox" name="provider_chooses_design" id="provider-chooses-check" value="yes" <?php echo (isset($_POST['provider_chooses_design']) && $_POST['provider_chooses_design'] === 'yes') ? 'checked' : ''; ?>>
                        <span class="checkmark"></span>
                        <span class="text-inter" style="font-weight: 600; color: #064E3B;">Service provider will choose the design</span>
                    </label>
                </div>
                <div class="upload-inner-card" id="upload-inner-card">
                    <h3 class="heading-serif">Reference Images *</h3>
                    <p class="text-inter">Upload an image that inspires you.</p>
                    <input type="file" name="reference_image" id="fileInput" hidden accept="image/*">
                    <div class="upload-dropzone text-inter" id="dropzone">
                        <span id="upload-text">CLICK TO UPLOAD IMAGE</span>
                        <button type="button" id="delete-img-btn" class="delete-btn" title="Remove Image">&times;</button>
                    </div>
                </div>
            </div>
        </main>

        <aside class="right-column">
            <div class="calendar-card">
                <h3 class="heading-serif" style="font-size: 25px; color: #064E3B;">Set Date and Time<br><br></h3>
                <div class="calendar-header">
                    <span id="monthYear" class="text-inter" style="font-weight: 600;"></span>
                    <div class="cal-nav">
                        <span id="prevMonth" class="nav-arrow">&lt;</span>
                        <span id="nextMonth" class="nav-arrow">&gt;</span>
                    </div>
                </div>
                <div class="calendar-grid text-inter" id="calendarDays"></div>

                <div class="slots-container">
                    <label class="text-inter" style="font-size: 10px; font-weight: 600; letter-spacing: 1px;">AVAILABLE
                        SLOTS</label>
                    <div class="slots-grid">
                        <?php 
                        $selected_time = $_POST['selected_time'] ?? '09:00 AM';
                        $slots = ['09:00 AM', '10:30 AM', '01:00 PM', '02:30 PM'];
                        foreach ($slots as $slot) {
                            $active = ($slot === $selected_time) ? 'active' : '';
                            echo "<button type=\"button\" class=\"slot-btn $active\">$slot</button>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="summary-section">
                <div class="summary-line">
                    <div class="text-inter">
                        <span
                            style="font-weight:600; font-size:10px; color:#78716C; text-transform:uppercase;">Summary</span><br>
                        <span id="summaryText">Select a date</span>
                    </div>
                    <div class="summary-price">₱<span id="totalPrice">0</span></div>
                </div>
                <button type="submit" id="main-confirm-btn" class="confirm-btn">CONFIRM APPOINTMENT</button>
                <p class="next-step">NEXT: PERSONAL INFORMATION</p>
            </div>
        </aside>
            </form>
    </div>

       <!-- FOOTER -->
        <?php include '../footer.php'; ?>



    <script>
        window.restoredDateStr = "<?php echo addslashes($_POST['selected_date'] ?? ''); ?>";
        window.restoredSize = "<?php echo addslashes($_POST['size'] ?? ''); ?>";
</script>

    <script src="script.js"></script>
</body>

</html>