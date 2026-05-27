<?php
// Start a session so we can remember things when the user goes to book
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connect to our database
require_once '../db_connect.php';

// Helper tool to convert a color code into a format we can make see-through
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

// Check which specific service the user clicked on
$serviceIdParam = isset($_GET['id']) ? trim($_GET['id']) : '';
if (empty($serviceIdParam)) {
    header("Location: ServicePage1.php");
    exit();
}

$service = null;
$servicesCollection = $db->services;

// Find the service in the database using its ID
try {
    if (ctype_xdigit($serviceIdParam) && strlen($serviceIdParam) === 24) {
        $serviceDoc = $servicesCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($serviceIdParam)]);
        if ($serviceDoc) $service = iterator_to_array($serviceDoc);
    } else {
        $intId = filter_var($serviceIdParam, FILTER_VALIDATE_INT);
        if ($intId !== false && $intId > 0) {
            $serviceDoc = $servicesCollection->findOne(['legacy_id' => (int) $intId]);
            if ($serviceDoc) $service = iterator_to_array($serviceDoc);
        }
    }
} catch (Exception $e) {
    // If it fails, do nothing and it will send them back below
}

// If we can't find the service, send them back
if (!$service) {
    header("Location: ServicePage2.php");
    exit();
}

$serviceId = (string)$service['_id'];
$adminId = isset($service['admin_id']) ? (string)$service['admin_id'] : '';

// Find the business partner who offers this service
$partnersCollection = $db->partners;
$partnerDoc = $partnersCollection->findOne(['admin_id' => $adminId]);
$partnerData = $partnerDoc ? (array)$partnerDoc : [];

// Get the partner's custom profile details
$branding = $partnerData['branding'] ?? [];

$salonName = !empty($branding['salon_name']) ? $branding['salon_name'] : ($partnerData['shop_name'] ?? 'DivaBook');
$salonDesc = !empty($branding['description']) ? $branding['description'] : '';
$salonCredentials = !empty($branding['credentials']) ? $branding['credentials'] : '';

$displayLocation = !empty($branding['location']) ? $branding['location'] : ($partnerData['address'] ?? '');
$displayContact  = !empty($branding['contact_info']) ? $branding['contact_info'] : (!empty($branding['contact_number']) ? $branding['contact_number'] : ($partnerData['phone'] ?? ''));
$displayEmail    = !empty($branding['email']) ? $branding['email'] : ($partnerData['email'] ?? '');

// Get the partner's custom colors
$brandPrimary   = $branding['color_primary']   ?? '#064E3B';
$brandSecondary = $branding['color_secondary'] ?? '#7cbf7f';
$salonLogo      = $branding['logo_path']       ?? '';

$fbUser = $branding['facebook']      ?? '';
$fbUrl  = $branding['facebook_url']  ?? '';
$igUser = $branding['instagram']     ?? '';
$igUrl  = $branding['instagram_url'] ?? '';

$primaryRgb = hexToRgbStr($brandPrimary);
$secondaryRgb = hexToRgbStr($brandSecondary);

// Save this info so the booking page remembers what we selected
$_SESSION['selected_admin_id'] = $adminId;
$_SESSION['selected_service_id'] = $serviceId;
$_SESSION['selected_service_name'] = $service['name'] ?? '';
$_SESSION['selected_partner_id'] = isset($partnerData['_id']) ? (string)$partnerData['_id'] : '';

// Get details for the specific service (price, name, category)
$svcName = h($service['name'] ?? 'Service');
$svcDesc = $service['description'] ?? '';
$svcCategory = $service['category'] ?? '';
$svcRate = !empty($service['rate']) ? $service['rate'] : '0';
$svcPrice = '₱' . number_format((float)$svcRate, 0);

// Get the picture for the service
$photosArray = isset($service['photos']) ? (array)$service['photos'] : [];
$rawBanner = !empty($photosArray) ? $photosArray[0] : '';
$svcBanner = filter_var($rawBanner, FILTER_VALIDATE_URL) ? $rawBanner : (!empty($rawBanner) ? "../AdminView/" . $rawBanner : 'img/placeholder.png');

$backLink = !empty($svcCategory) ? 'ServicePage2.php?boutique=' . urlencode($_SESSION['selected_partner_id']) : 'ServicePage1.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($salonName) ?> — <?= $svcName ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:ital,wght@0,400;1,400;1,600&family=Inter:wght@400;500;700&family=Noto+Serif:ital,wght@0,400;0,700;1,400&family=Manrope:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL,GRAD,opsz@400,0,0,24" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* This sets the custom colors for the page based on what the partner chose */
        :root {
            --brand-primary: <?= $brandPrimary ?>;
            --brand-secondary: <?= $brandSecondary ?>;
            --brand-primary-rgb: <?= $primaryRgb ?>;
            --brand-secondary-rgb: <?= $secondaryRgb ?>;
        }

        body {
            background-color: #fff;
            color: #292524;
        }

        .salon-profile-banner {
            display: flex;
            align-items: center;
            gap: 32px;
            padding: 36px 48px;
            background: linear-gradient(135deg, color-mix(in srgb, var(--brand-primary) 12%, #fff), color-mix(in srgb, var(--brand-secondary) 8%, #fff));
            border-left: 5px solid var(--brand-primary);
            margin-bottom: 40px;
        }

        .salon-logo-wrap {
            flex-shrink: 0;
            width: 96px;
            height: 96px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--brand-primary);
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .salon-logo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .service-headline-wrap {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e7e5e4;
        }

        .menu-title-bridge {
            font-family: 'Noto Serif', serif;
            font-size: 26px;
            color: #1c1917;
            margin: 0;
        }

        .menu-title-bridge span { color: var(--brand-primary); }

        .back-link {
            font-family: 'Manrope', sans-serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #78716c;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            transition: color 0.2s;
        }

        .back-link:hover { color: var(--brand-primary); }

        .booking-card {
            background: rgba(var(--brand-primary-rgb), 0.05) !important;
            border: 1px solid rgba(var(--brand-primary-rgb), 0.15) !important;
            border-radius: 12px;
            padding: 30px;
        }

        .btn-book-appointment {
            background: var(--brand-primary) !important;
            color: #fff !important;
            font-weight: 700 !important;
            border: none !important;
            transition: opacity 0.2s;
        }

        .btn-book-appointment:hover { opacity: 0.9; }

        .selection-title, .stat .label {
            color: var(--brand-primary) !important;
            font-weight: 700 !important;
        }

        .tag-outline {
            color: var(--brand-primary) !important;
            border: 1px solid rgba(var(--brand-primary-rgb), 0.3) !important;
            background: rgba(var(--brand-primary-rgb), 0.04);
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 4px;
        }

        .inspiration-placeholder {
            width: 100%;
            height: 240px;
            background: #f5f5f4;
            border: 1px dashed #d6d3d1;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #a8a29e;
            font-size: 13px;
        }

        @media (max-width: 992px) {
            .salon-profile-banner {
                flex-direction: column;
                text-align: center;
                padding: 30px 20px;
                gap: 20px;
            }
            .salon-meta-chips { justify-content: center; }
        }
    </style>
</head>
<body>

    <?php 
    $current_page = 'services';
    include 'navbar.php'; 
    ?>

    <main class="service-detail-page" style="max-width: 1300px; margin: 0 auto; padding: 0 24px 80px 24px;">
        
        <div class="salon-profile-banner">
            <div class="salon-logo-wrap">
                <?php if (!empty($salonLogo)): ?>
                    <img src="<?= h($salonLogo) ?>" alt="<?= h($salonName) ?> Logo">
                <?php else: ?>
                    <div style="font-size: 36px; color: var(--brand-primary); font-family: 'Noto Serif', serif; font-weight: 700;">
                        <?= substr(h($salonName), 0, 1) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="salon-profile-info">
                <p style="font-weight: 700; text-transform: uppercase; letter-spacing: 2px; font-size: 11px; margin-bottom: 5px; color: var(--brand-primary);">
                    SERVICE PROVIDER</p>
                <h2 style="font-family: 'Noto Serif', serif; font-size: 28px; font-weight: 700; margin: 0; color: #1a1a1a;">
                    <?= h($salonName) ?></h2>
                <p style="font-size: 14px; color: #555; margin-bottom: 15px;">
                    <?= h($salonDesc) ?></p>

                <?php if (!empty($salonCredentials)): ?>
                    <div class="salon-credentials"
                        style="display: flex; align-items: flex-start; gap: 8px; font-size: 13px; color: #444; margin-bottom: 15px; padding: 10px 14px; background: rgba(var(--brand-primary-rgb), 0.07); border-radius: 8px; border-left: 3px solid var(--brand-primary);">
                        <span class="material-symbols-outlined" style="font-size: 18px; color: var(--brand-primary); margin-top: 1px;">workspace_premium</span>
                        <span style="line-height: 1.5;"><?= h($salonCredentials) ?></span>
                    </div>
                <?php endif; ?>

                <div class="salon-meta-chips" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                    <?php if (!empty($displayLocation)): ?>
                        <span style="display: flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600;">
                            <span class="material-symbols-outlined" style="font-size: 18px;">location_on</span>
                            <?= h($displayLocation) ?>
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($displayContact)): ?>
                        <span style="display: flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600;">
                            <span class="material-symbols-outlined" style="font-size: 18px;">call</span>
                            <?= h($displayContact) ?>
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($displayEmail)): ?>
                        <a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?= urlencode($displayEmail) ?>" target="_blank" style="display: flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600; text-decoration: none; color: inherit;">
                            <img src="https://img.icons8.com/color/48/new-post.png" alt="email" style="width: 18px; height: 18px;" />
                            <?= h($displayEmail) ?>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($fbUser) && !empty($fbUrl)): ?>
                        <a href="<?= h($fbUrl) ?>" target="_blank" style="display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; text-decoration: none; color: inherit;">
                            <img src="https://img.icons8.com/color/48/facebook-new.png" alt="facebook" style="width: 18px; height: 18px;" />
                            <?= h($fbUser) ?>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($igUser) && !empty($igUrl)): ?>
                        <a href="<?= h($igUrl) ?>" target="_blank" style="display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; text-decoration: none; color: inherit;">
                            <img src="https://img.icons8.com/color/48/instagram-new.png" alt="instagram" style="width: 18px; height: 18px;" />
                            <?= h($igUser) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <header class="detail-header" style="margin-top: 10px;">
            <a href="<?= h($backLink) ?>" class="back-link">← BACK TO SERVICE MENU</a>
            
            <div class="service-headline-wrap">
                <h1 class="menu-title-bridge"><?= h($salonName) ?> — <span><?= $svcName ?></span></h1>
            </div>

            <div class="hero-banner" style="position: relative; overflow: hidden; border-radius: 12px; height: 440px; margin-bottom: 40px; background: #f5f5f4;">
                <?php if (!empty($rawBanner)): ?>
                    <img src="<?= h($svcBanner) ?>" alt="<?= $svcName ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <div style="display: flex; height: 100%; align-items: center; justify-content: center; color: #a8a29e; font-size: 15px;">
                        <span>No cover photo available for this service.</span>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <div class="content-layout">
            <div class="left-column">
                <section class="service-description" style="margin-bottom: 40px;">
                    <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 16px;">
                        <span class="tag-outline"><?= h(strtoupper($svcCategory)) ?></span>
                    </div>

                    <div class="description-text" style="font-size: 15px; line-height: 1.7; color: #44403c;">
                        <?php if (!empty($svcDesc)): ?>
                            <p style="margin: 0; white-space: pre-line;"><?= h($svcDesc) ?></p>
                        <?php else: ?>
                            <p style="font-style: italic; color: #878584;">No description details provided for this entry.</p>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="style-inspiration">
                    <h3 style="font-family: 'Noto Serif', serif; font-size: 20px; margin-bottom: 20px; color: #1c1917;">Style Inspiration</h3>
                    <div class="inspiration-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
                        <?php for ($i = 0; $i < 3; $i++): ?>
                            <div class="inspiration-item">
                                <?php if (!empty($photosArray[$i])): 
                                    $imgSrc = $photosArray[$i];
                                    $resolvedSrc = filter_var($imgSrc, FILTER_VALIDATE_URL) ? $imgSrc : "../AdminView/" . ltrim($imgSrc, '/');
                                ?>
                                    <img src="<?= h($resolvedSrc) ?>" alt="Inspiration View" onclick="openModal(this)" style="width: 100%; height: 240px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 1px solid #e7e5e4;">
                                <?php else: ?>
                                    <div class="inspiration-placeholder">Image placeholder</div>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </section>
            </div>

            <aside class="right-column">
                <div class="booking-card">
                    <div class="stats-row" style="display: flex; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid rgba(var(--brand-primary-rgb), 0.15);">
                        <div class="stat">
                            <span class="label" style="display: block; font-size: 11px; letter-spacing: 0.5px; margin-bottom: 4px;">STARTING FROM</span>
                            <span class="value" style="font-size: 24px; font-weight: 700; color: #1c1917;"><?= $svcPrice ?></span>
                        </div>
                        <div class="stat" style="text-align: right;">
                            <span class="label" style="display: block; font-size: 11px; letter-spacing: 0.5px; margin-bottom: 4px;">EST. TIME</span>
                            <span class="value" style="font-size: 24px; font-weight: 700; color: #1c1917;"><?= (!empty($service['duration_mins'])) ? (int)$service['duration_mins'] . " mins" : "TBC" ?></span>
                        </div>
                    </div>

                    <div class="artist-selection" style="margin-bottom: 24px;">
                        <p class="selection-title" style="font-size: 11px; letter-spacing: 1px; margin-bottom: 10px;">AVAILABLE ARTISTS</p>
                        <p style="font-size: 13px; color: #57524e; margin: 0; line-height: 1.5;">Staff assignments and individual artist schedules will be coordinated during the checkout step.</p>
                    </div>

                    <button class="btn-book-appointment" style="width: 100%; padding: 15px; border-radius: 6px; font-size: 14px; letter-spacing: 0.5px; display: flex; align-items: center; justify-content: center; gap: 8px;" onclick="location.href='Booking_1/B1.php?service=<?= urlencode($serviceId) ?>&boutique=<?= urlencode($_SESSION['selected_partner_id']) ?>'">
                        <span class="material-symbols-outlined" style="font-size: 18px;">calendar_month</span> BOOK APPOINTMENT
                    </button>
                </div>

                <div class="sustainability-box" style="margin-top: 20px; padding: 20px; border-radius: 8px; border: 1px dashed rgba(var(--brand-primary-rgb), 0.3); background: rgba(var(--brand-primary-rgb), 0.02);">
                    <div class="box-title" style="font-weight: 700; font-size: 12px; color: var(--brand-primary); margin-bottom: 6px; display: flex; align-items: center; gap: 4px;">
                        <span class="material-symbols-outlined" style="font-size: 16px;">potted_plant</span> SUSTAINABILITY PROMISE
                    </div>
                    <p style="font-size: 13px; margin: 0; font-style: italic; color: #57524e;">"We source only premium, organic, and cruelty-free alternatives for your health."</p>
                </div>
            </aside>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <div id="imageModal" class="image-zoom-modal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.85); align-items: center; justify-content: center;" onclick="closeModal()">
        <img class="modal-content" id="zoomedImg" style="max-width: 85%; max-height: 85%; object-fit: contain; border-radius: 4px;">
    </div>

    <script>
        // Shows the large image when you click it
        function openModal(element) {
            var modal = document.getElementById("imageModal");
            var modalImg = document.getElementById("zoomedImg");
            modal.style.display = "flex";
            modalImg.src = element.src;
        }
        // Hides the image when you click outside of it
        function closeModal() {
            document.getElementById("imageModal").style.display = "none";
        }
    </script>
</body>
</html>