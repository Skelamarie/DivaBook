<?php
// Connect to our database
require_once '../db_connect.php';

// Check which business partner the user clicked on
$boutiqueIdParam = isset($_GET['boutique']) ? trim($_GET['boutique']) : '';

// If there is no partner ID, send them back to the first page
if (empty($boutiqueIdParam)) {
    header("Location: ServicePage1.php");
    exit();
}

// Get the partner's information from the database
$boutique = getBoutiqueById($boutiqueIdParam);

if ($boutique) {
    $adminId = $boutique['admin_id'] ?? '';
    // Get all the services this partner offers
    $services = getServicesByBoutique($adminId);

    // Get the partner's custom profile details (colors, name, etc.)
    $branding = $boutique['branding'] ?? [];

    $boutiqueName = h(!empty($branding['salon_name']) ? $branding['salon_name'] : ($boutique['shop_name'] ?? 'Partner'));
    $boutiqueDesc = h(!empty($branding['description']) ? $branding['description'] : ($boutique['description'] ?? 'Welcome to our Services Offered.'));
    
    // Custom colors chosen by the partner
    $brandPrimary = $branding['color_primary'] ?? '#064E3B';
    $brandSecondary = $branding['color_secondary'] ?? '#7cbf7f';
    $salonLogo = $branding['logo_path'] ?? '';
    $salonCredentials = $branding['credentials'] ?? ($boutique['credentials'] ?? '');

    // Contact info
    $displayLocation = !empty($branding['location']) ? $branding['location'] : ($boutique['location'] ?? '');
    $displayContact = !empty($branding['contact_info']) ? $branding['contact_info'] : (!empty($branding['contact_number']) ? $branding['contact_number'] : ($boutique['contact_info'] ?? ($boutique['phone'] ?? '')));
    $displayEmail = !empty($branding['email']) ? $branding['email'] : ($boutique['email'] ?? '');
    
    // Social media links
    $fbUser = $branding['facebook'] ?? '';
    $fbUrl  = $branding['facebook_url'] ?? '';
    $igUser = $branding['instagram'] ?? '';
    $igUrl  = $branding['instagram_url'] ?? '';
}

// Helper tool to convert a color code into a format we can make see-through
if (!function_exists('hexToRgbStr')) {
    function hexToRgbStr($hex)
    {
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
$primaryRgb = hexToRgbStr($brandPrimary ?? '#064E3B');

// Set up the back button link
$category = $boutique['category'] ?? '';
$backLink = !empty($category)
    ? 'ServicePage4.php?category=' . urlencode($category)
    : 'ServicePage4.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DivaBook | <?= $boutiqueName ?> — Service Menu</title>
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
        }

        .menu-page { padding-top: 0; }

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

        .salon-logo-wrap img { width: 100%; height: 100%; object-fit: cover; }

        .menu-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            margin-top: 20px;
        }

        .menu-card {
            display: flex;
            border: 1px solid #eee !important;
            transition: transform 0.3s ease;
            overflow: hidden;
            border-radius: 8px;
        }

        .menu-card:hover { transform: translateY(-5px); }

        .card-primary { background: color-mix(in srgb, var(--brand-primary) 5%, #fff) !important; }
        .card-primary .rating-badge { color: var(--brand-primary) !important; }
        .card-primary .btn-dark-select { background: var(--brand-primary) !important; color: #fff; }

        .card-secondary { background: color-mix(in srgb, var(--brand-secondary) 5%, #fff) !important; }
        .card-secondary .rating-badge { color: var(--brand-secondary) !important; }
        .card-secondary .btn-dark-select { background: var(--brand-secondary) !important; color: #fff; }

        .card-primary.dark-theme { background: var(--brand-primary) !important; color: #fff !important; }
        .card-secondary.dark-theme { background: var(--brand-secondary) !important; color: #fff !important; }

        .dark-theme .description,
        .dark-theme h3 { color: #fff !important; }

        @media (max-width: 992px) {
            .menu-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php $current_page = 'services';
    include 'navbar.php'; ?>

    <main class="menu-page">

        <div class="salon-profile-banner">
            <div class="salon-logo-wrap">
                <?php if (!empty($salonLogo)): ?>
                    <img src="<?= h($salonLogo) ?>" alt="Logo">
                <?php else: ?>
                    <div style="font-size: 36px; color: var(--brand-primary); font-family: 'Noto Serif', serif; font-weight: 700;">
                        <?= substr($boutiqueName, 0, 1) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="salon-profile-info">
                <p style="font-weight: 700; text-transform: uppercase; letter-spacing: 2px; font-size: 11px; margin-bottom: 5px; color: var(--brand-primary);">
                    SERVICE PROVIDER</p>
                <h2 style="font-family: 'Noto Serif', serif; font-size: 28px; font-weight: 700; margin: 0; color: #1a1a1a;">
                    <?= $boutiqueName ?></h2>
                <p style="font-size: 14px; color: #555; margin-bottom: 15px;">
                    <?= $boutiqueDesc ?></p>

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

        <header class="menu-header" style="padding: 0 48px;">
            <a href="<?= h($backLink) ?>" class="back-link">← BACK TO SERVICE PROVIDERS</a>
            <h1 class="menu-title"><?= $boutiqueName ?> — Service Menu</h1>
            <p class="menu-subtitle"><?= $boutiqueDesc ?></p>
        </header>

        <div style="padding: 0 48px;">
            <section class="menu-grid">
                <?php if (empty($services)): ?>
                    <div class="menu-empty">
                        <p>No services listed yet for this service provider.</p>
                        <p style="margin-top:8px;font-size:14px;">Check back soon!</p>
                    </div>
                <?php else: ?>
                    <?php
                    // Set up different ways to show the service cards to look nice
                    $cardPatterns = ['horizontal', 'vertical', 'vertical', 'horizontal-dark'];
                    $i = 0;

                    foreach ($services as $service):
                        $serviceId = mongoId($service);
                        $svcName = h($service['name'] ?? 'Service');
                        $svcDesc = h($service['description'] ?? '');
                        $svcPrice = '₱' . h($service['rate'] ?? '0');

                        // Check if there's a picture for this service
                        $photosArray = isset($service['photos']) ? (array) $service['photos'] : [];
                        $rawPhoto = !empty($photosArray) ? $photosArray[0] : ($service['photo'] ?? '');
                        $svcImage = filter_var($rawPhoto, FILTER_VALIDATE_URL) ? $rawPhoto : (!empty($rawPhoto) ? "../AdminView/" . $rawPhoto : 'img/placeholder.png');

                        // Pick the color and style for the card
                        $colorClass = ($i % 2 == 0) ? 'card-primary' : 'card-secondary';
                        $pattern = $cardPatterns[$i % count($cardPatterns)];
                        $i++;

                        if ($pattern === 'horizontal') {
                            $layoutClass = 'horizontal-card';
                            $btnClass = 'btn-text-select';
                        } elseif ($pattern === 'horizontal-dark') {
                            $layoutClass = 'horizontal-card dark-theme';
                            $btnClass = 'btn-text-select light';
                        } else {
                            $layoutClass = 'vertical-card';
                            $btnClass = 'btn-dark-select';
                        }
                        ?>

                        <div class="menu-card <?= $layoutClass ?> <?= $colorClass ?>">
                            <?php if ($pattern !== 'horizontal-dark'): ?>
                                <div class="menu-img-wrapper">
                                    <?php if (!empty($rawPhoto) && $rawPhoto !== 'img/placeholder.png'): ?>
                                        <img src="<?= $svcImage ?>" alt="<?= $svcName ?>">
                                    <?php else: ?>
                                        <div class="logo-placeholder" style="height: 100%; min-height: 250px;">
                                            <span>Image Unavailable</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="menu-info">
                                <p class="rating-badge">★ PREMIUM SERVICE</p>
                                <h3><?= $svcName ?></h3>
                                <p class="description"><?= $svcDesc ?></p>
                                <div class="footer-action">
                                    <span class="price"><?= $svcPrice ?></span>
                                    <button class="<?= $btnClass ?>"
                                        onclick="window.location.href='ServicePage3.php?id=<?= $serviceId ?>'">SELECT</button>
                                </div>
                            </div>

                            <?php if ($pattern === 'horizontal-dark'): ?>
                                <div class="menu-img-wrapper"><img src="<?= $svcImage ?>" alt="<?= $svcName ?>"></div>
                            <?php endif; ?>
                        </div>

                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <?php include 'footer.php'; ?>
    <script src="script.js"></script>
</body>
</html>