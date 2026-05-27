<?php
// Connect to our database
require_once '../db_connect.php';

// Check if the user clicked a specific category link (like 'nails' or 'spa')
$categoryFilter = isset($_GET['category']) ? strtolower(trim($_GET['category'])) : '';

// Get the right business partners based on the chosen category, or get all of them
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

$totalCount = count($boutiques);
$allCategories = ['nails', 'hair', 'makeup', 'spa'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DivaBook | Discover Our Service Providers</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:ital,wght@0,400;1,400;1,600&family=Inter:wght@400;500;700&family=Noto+Serif:ital,wght@0,400;0,700;1,400&family=Manrope:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Top category filter links */
        .filter-tabs {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 48px;
        }
        .filter-tabs a {
            text-decoration: none;
            padding: 9px 22px;
            border: 1px solid #E7E5E4;
            font-size: 12px;
            font-family: 'Manrope', sans-serif;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            transition: 0.25s;
        }
        .filter-tabs a.active,
        .filter-tabs a:hover {
            background: var(--primary-green);
            color: white;
            border-color: var(--primary-green);
        }
        
        /* Small text showing how many results we found */
        .boutique-count {
            font-family: 'Manrope', sans-serif;
            font-size: 13px;
            color: #78716C;
            letter-spacing: 1px;
            margin-bottom: 32px;
        }
        
        /* Message shown when no partners are found */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 0;
            color: #78716C;
        }
        .empty-state p { font-size: 18px; margin-bottom: 12px; }
        .empty-state a { color: var(--primary-green); text-decoration: underline; }
    </style>
</head>
<body>

    <?php
    $current_page = 'services';
    include 'navbar.php';
    ?>

    <main class="boutiques-page">

        <header class="boutiques-header">
            <a href="HomePage.php" class="back-link">← BACK TO HOME</a>
            <div class="header-content">
                <div class="title-area">
                    <h1 class="boutiques-title">Discover Our <br>Service Providers</h1>
                    <p class="boutiques-subtitle">
                        A curated collection of the world's most prestigious wellness and beauty sanctuaries. From
                        avant-garde nail artistry to restorative spa retreats, discover excellence in every corner
                        of our network.
                    </p>
                </div>
                <div class="decorative-line"></div>
            </div>
        </header>

        <section class="available-boutiques">
            <div class="list-header">
                <div>
                    <h2>Available Service Providers</h2>
                    <span class="count-tag"><?= $totalCount ?> service providers found</span>
                </div>
            </div>

            <div class="services-grid">
                <?php if (empty($boutiques)): ?>
                    <div class="empty-state">
                        <p>No Service Providers found in this category yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($boutiques as $boutique): 
                        // Find this specific business partner's ID
                        $boutiqueId = mongoId($boutique);
                        $adminId = $boutique['admin_id'] ?? '';

                        // Get their profile details (like their name and logo)
                        $profile = getSalonProfileByAdminId($adminId);
                        $rawLogo = $profile['logo_path'] ?? ''; 

                        // Check if they have a valid logo, otherwise use a default picture
                        $hasLogo = !empty($rawLogo);
                        $logoUrl = '';

                        if ($hasLogo) {
                            if (filter_var($rawLogo, FILTER_VALIDATE_URL)) {
                                $logoUrl = $rawLogo;
                            } else {
                                $logoUrl = "../AdminView/" . $rawLogo;
                            }
                        }
                        
                        $name = h($profile['salon_name'] ?? $boutique['shop_name'] ?? 'Unnamed Service Provider');
                    ?>
                        <div class="boutique-item">
                            <div class="boutique-img-box">
                                <?php if ($hasLogo): ?>
                                    <img src="<?= h($logoUrl) ?>" alt="<?= h($name) ?>">
                                <?php else: ?>
                                    <div class="logo-placeholder">
                                        <span>Business Logo<br>Unavailable</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <div class="boutique-details">
                            <div class="name-rating">
                                <h3><?= htmlspecialchars_decode(h($name)) ?></h3>
                                <span class="rating">⭐ <?= number_format((float)($boutique['rating'] ?? 0), 1) ?></span>
                            </div>
                            <p><?= h($profile['description'] ?? $boutique['description'] ?? '') ?></p>
                            <button class="btn-dark-view" 
                                onclick="location.href='ServicePage2.php?boutique=<?= urlencode($boutiqueId) ?>'">
                                VIEW SERVICE PROVIDER
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
</body>
</html>