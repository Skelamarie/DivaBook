<?php
// Connect to our database
require_once '../db_connect.php';

// Get all services from the database so we can show them on the page
$servicesCursor = $db->services->find([]);
$servicesList = iterator_to_array($servicesCursor, false);

$pageTitle    = 'All Available Services';
$pageSubtitle = 'Explore our complete catalog of premium beauty and wellness treatments across all our business partner locations.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DivaBook | <?= htmlspecialchars($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:ital,wght@0,400;1,400;1,600&family=Inter:wght@400;500;700&family=Noto+Serif:ital,wght@0,400;0,700;1,400&family=Manrope:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php
    $current_page = 'services';
    include 'navbar.php';
    ?>

    <main class="services-page">

        <header class="service-intro">
            <a href="HomePage.php" class="back-link">← BACK TO HOME</a>
            <h1 class="service-title"><?= htmlspecialchars($pageTitle) ?></h1>
            <p class="service-subtitle"><?= htmlspecialchars($pageSubtitle) ?></p>
        </header>

        <section class="available-boutiques">
            <div class="list-header">
                <div>
                    <h2>Complete Service Catalog</h2>
                    <span class="count-tag"><?= count($servicesList) ?> total services available</span>
                </div>
            </div>

            <div class="services-grid">
                <?php if (empty($servicesList)): ?>
                    <div class="empty-state">
                        <p>Our service catalog is currently being updated. Please check back soon.</p>
                        <a href="HomePage.php">Return to Home</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($servicesList as $svc): 
                        // Find the business partner details using their ID
                        $shop = $db->partners->findOne(['admin_id' => $svc['admin_id']]);
                        
                        // Look for the partner's salon name, or use a backup name
                        $branding = $shop['branding'] ?? [];
                        $displayShopName = htmlspecialchars($branding['salon_name'] ?? $shop['shop_name'] ?? 'Premier Partner');
                        
                        // Check if the service has a picture, otherwise use a placeholder
                        $photosArray = isset($svc['photos']) ? (array)$svc['photos'] : [];
                        $rawPhoto = !empty($photosArray) ? $photosArray[0] : ($svc['photo'] ?? '');

                        if (filter_var($rawPhoto, FILTER_VALIDATE_URL)) {
                            $svcPhoto = $rawPhoto; 
                        } else {
                            $svcPhoto = !empty($rawPhoto) ? "../AdminView/" . $rawPhoto : 'img/placeholder.png';
                        }
                    ?>
                        <div class="boutique-item">
                                <div class="boutique-img-box">
                                    <?php if (!empty($rawPhoto) && $rawPhoto !== 'img/placeholder.png'): ?>
                                        <img src="<?= $svcPhoto ?>" alt="<?= htmlspecialchars($svc['name']) ?>">
                                    <?php else: ?>
                                        <div class="logo-placeholder">
                                            <span>Service Image<br>Unavailable</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <div class="boutique-details">
                                <div class="name-rating">
                                    <h3><?= htmlspecialchars($svc['name']) ?></h3>
                                    <span class="price">₱<?= htmlspecialchars($svc['rate']) ?></span>
                                </div>
                                <p><?= htmlspecialchars($svc['description']) ?></p>
                                
                                <p style="font-size: 12px; color: #78716C; text-transform: uppercase; letter-spacing: 1px; margin: 10px 0;">
                                    Category: <span style="color: var(--primary-green); font-weight: 700;"><?= htmlspecialchars($svc['category'] ?? 'General') ?></span>
                                </p>

                                <p style="font-size: 13px; color: #064E3B; font-weight: 700; margin-bottom: 20px;">
                                    📍 Business Name: <?= $displayShopName ?>
                                </p>
                                
                                <button class="btn-dark-view" 
                                    onclick="location.href='ServicePage3.php?id=<?= (string)$svc['_id'] ?>'">
                                    VIEW DETAILS & BOOK
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <section class="philosophy-footer">
            <p class="tagline">THE PHILOSOPHY</p>
            <blockquote class="quote">
                "Beauty is not a luxury — it is a ritual of self-respect, and every great ritual deserves a great stage."
            </blockquote>
            <div class="short-divider"></div>
        </section>
    </main>

    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
</body>
</html>