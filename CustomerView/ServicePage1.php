<?php
// Connect to our database
require_once '../db_connect.php';

// Get the exact category from the URL (this keeps capital letters safe for the database)
$exactCategory = isset($_GET['category']) ? trim($_GET['category']) : '';

// Make a lowercase version just to match our page titles below
$categoryFilter = strtolower($exactCategory);

// Get the right business partners based on the chosen category
if (!empty($categoryFilter)) {
    $boutiques = getBoutiquesByCategory($categoryFilter);
} else {
    $boutiques = getAllBoutiques();
}

// Titles for each category
$categoryLabels = [
    'nails'  => 'Nail Artistry',
    'hair'   => 'Haircut & Styling',
    'makeup' => 'Professional Makeup',
    'spa'    => 'Spa & Wellness',
];

// Short descriptions for each category
$categorySubtitles = [
    'nails'  => 'Curated professional nail care and bespoke artistry. Discover the finest business partners specializing in luxury manicures.',
    'hair'   => 'World-class stylists offering precision cuts in the most exclusive partner salons.',
    'makeup' => 'Professional makeup artists crafting every look — from bridal elegance to everyday glamour.',
    'spa'    => 'Restorative spa retreats and wellness sanctuaries from our trusted partners.',
];

// Set the page title and subtitle depending on the chosen category
$pageTitle = !empty($categoryFilter) && isset($categoryLabels[$categoryFilter])
    ? $categoryLabels[$categoryFilter]
    : 'All Service Providers';

$pageSubtitle = !empty($categoryFilter) && isset($categorySubtitles[$categoryFilter])
    ? $categorySubtitles[$categoryFilter]
    : 'Browse our full network of premium business partners, carefully checked for quality and excellence.';

// List of all categories to show in the filter bar
$allCategories = ['nails', 'hair', 'makeup', 'spa'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DivaBook | <?= h($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:ital,wght@0,400;1,400;1,600&family=Inter:wght@400;500;700&family=Noto+Serif:ital,wght@0,400;0,700;1,400&family=Manrope:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Top category filter links */
        .category-filter {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }
        .category-filter a {
            text-decoration: none;
            padding: 8px 20px;
            border: 1px solid #E7E5E4;
            font-size: 12px;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            transition: 0.3s;
        }
        .category-filter a.active,
        .category-filter a:hover {
            background: var(--primary-green);
            color: white;
            border-color: var(--primary-green);
        }
        
        /* Message shown when no services are found */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 0;
            color: #78716C;
        }
        .empty-state p { font-size: 18px; margin-bottom: 12px; }
        .empty-state a {
            color: var(--primary-green);
            text-decoration: underline;
            font-size: 14px;
        }
        
        /* Small text showing how many results we found */
        .count-tag {
            font-size: 13px;
            color: #78716C;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>

    <?php
    $current_page = 'services';
    include 'navbar.php';
    ?>

    <main class="services-page">

        <header class="service-intro">
            <a href="HomePage.php" class="back-link">← BACK TO HOME</a>
            <h1 class="service-title"><?= h($pageTitle) ?></h1>
            <p class="service-subtitle"><?= h($pageSubtitle) ?></p>
        </header>

        <section class="available-boutiques">
            <div class="list-header">
                <div>
                    <h2>Available Services</h2>
                    <?php 
                    // Grab only the services that match our category using the EXACT spelling from the URL
                    // We use $exactCategory because the database is case-sensitive (it wants "Nails", not "nails")
                    $servicesCursor = $db->services->find(['category' => $exactCategory]);
                    $servicesList = iterator_to_array($servicesCursor, false);
                    ?>
                    <span class="count-tag"><?= count($servicesList) ?> services found</span>
                </div>

                <nav class="category-filter">
                    </nav>
            </div>

            <div class="services-grid">
                <?php if (empty($servicesList)): ?>
                    <div class="empty-state">
                        <p>No services found in this category yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($servicesList as $svc): 
                        // Find the business partner linked to this service
                        $shop = $db->partners->findOne(['admin_id' => $svc['admin_id']]);
                        
                        // Get the partner's display details
                        $branding = $shop['branding'] ?? [];
                        $displayShopName = htmlspecialchars($branding['salon_name'] ?? $shop['shop_name'] ?? 'Unnamed Partner');
                        
                        // Check for a valid image, otherwise use a default picture
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
                                
                                <p style="font-size: 13px; color: #064E3B; font-weight: 700; margin: 10px 0;">
                                    📍 Business Name: <?= $displayShopName ?>
                                </p>
                                
                                <button class="btn-dark-view" 
                                    onclick="location.href='ServicePage3.php?id=<?= (string)$svc['_id'] ?>'">
                                    BOOK SERVICE
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