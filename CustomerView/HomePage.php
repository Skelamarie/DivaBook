<?php
// Connect to the database and helper functions
require_once '../db_connect.php';

// Get the top 3 partner to show on the page
$recommended = getRecommendedBoutiques(3);

// Get 1 partner for each category to see if we have active services
$catalogCategories = [
    'hair'   => getBoutiquesByCategory('hair',   1),
    'nails'  => getBoutiquesByCategory('nails',  1),
    'makeup' => getBoutiquesByCategory('makeup', 1),
    'spa'    => getBoutiquesByCategory('spa',    1),
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DivaBook | Premium Beauty Booking</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:ital,wght@0,400;1,400;1,600&family=Inter:wght@400;500;700&family=Noto+Serif:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php
        $current_page = 'home';
        include 'navbar.php';
    ?>

    <header class="hero">
        <p class="subtitle">ESTABLISHED EXCELLENCE</p>
        <h1 class="hero-title">Elevate Your <br><i>Self-Care Ritual</i></h1>
        <hr class="divider">
        <p class="hero-text">Experience curated beauty and wellness in the most exclusive service providers across the city.</p>
        <button class="btn-hero" onclick="location.href='GenServices.php'">EXPLORE SERVICES</button>
    </header>

    <section id="services" class="catalog-grid">

        <?php 
        $hairLink = !empty($catalogCategories['hair']) ? 'ServicePage1.php?category=hair' : '#';
        ?>
        <a href="ServicePage1.php?category=Hair" class="card large haircut-img">
            <div class="card-content">
                <div class="tags"><span class="tag">Styling</span> <span class="tag">Coloring</span></div>
                <h3>Haircut &amp; Styling</h3>
                <p>Precision cuts and bespoke coloring treatments tailored to your unique profile and aesthetic goals.</p>
            </div>
        </a>

        <?php 
        $nailsLink = !empty($catalogCategories['nails']) ? 'ServicePage1.php?category=nails' : '#';
        ?>
        <a href="ServicePage1.php?category=Nails" class="card small nails-img">            
            <div class="card-content">
                <div>
                    <span class="tag">Manicures</span>
                    <span class="tag">Extensions</span>
                </div>
                <h3>Nail Artistry</h3>
                <p>Advanced manicures, pedicures, and custom extensions using premium products.</p>
            </div>
        </a>

        <?php 
        $makeupLink = !empty($catalogCategories['makeup']) ? 'ServicePage1.php?category=makeup' : '#';
        ?>
        <a href="ServicePage1.php?category=Makeup" class="card small makeup-img">
            <div class="card-content">
                <h3>Professional Makeup</h3>
                <p>From bridal elegance to editorial glam, our artists craft the perfect look for any occasion.</p>
            </div>
        </a>

        <?php 
        $spaLink = !empty($catalogCategories['spa']) ? 'ServicePage1.php?category=spa' : '#';
        ?>
        <a href="ServicePage1.php?category=Skin" class="card large spa-img">
            <div class="card-content">
                <div class="tags"><span class="tag">Relaxation</span> <span class="tag">Skin Health</span></div>
                <h3>Spa &amp; Wellness</h3>
                <p>Immersive massages and rejuvenating facials that restore balance to both body and mind.</p>
            </div>
        </a>

    </section>

    <section class="recommended-section">
        <div class="section-header">
            <div class="title-group">
                <p class="section-tag">OFFICIAL PARTNERS</p>
                <h2 class="recommended-title">Our Service <br>Providers</h2>
            </div>
            <a href="ServicePage4.php" class="view-all">VIEW ALL SERVICE PROVIDERS</a>
        </div>

        <div class="store-grid">
            <?php if (!empty($recommended)): ?>
                <?php foreach ($recommended as $b): ?>
                    <?php 
                        // Get identification keys for the partner
                        $boutiqueId = mongoId($b);
                        $adminId = $b['admin_id'] ?? '';

                        // Find the partner profile using the manager key
                        $profile = getSalonProfileByAdminId($adminId);

                        // Pick the correct image link for the logo
                        $rawLogo = $profile['logo_path'] ?? ''; 
                        if (filter_var($rawLogo, FILTER_VALIDATE_URL)) {
                            $logo = $rawLogo;
                        } else {
                            $logo = !empty($rawLogo) ? "../AdminView/" . $rawLogo : 'img/placeholder.png';
                        }               

                        // Find the real name of the business
                        $name = h($profile['salon_name'] ?? $b['shop_name'] ?? 'Unnamed Service Provider');
                    ?>

                    <div class="recommended-card"> 
                        <div class="recommended-img-wrapper">
                            <?php if (!empty($rawLogo) && $rawLogo !== 'img/placeholder.png'): ?>
                                <img src="<?= $logo ?>" alt="<?= $name ?>">
                            <?php else: ?>
                                <div class="logo-placeholder">
                                    <span>Business Logo<br>Unavailable</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="recommended-info">
                            <h3><?= $name ?></h3>
                            <p style="color: #666; font-size: 0.9rem; margin-bottom: 5px;">
                                <?= h($b['biz_type'] ?? 'Luxury Service') ?>
                            </p>
                            <div class="recommended-rating">
                                <span class="stars" style="color: #d4af37;">★★★★★</span>
                                <span class="rating-val">(5.0)</span>
                            </div>
                            <p class="recommended-desc">
                                <?= h($b['description'] ?? 'Premium service provider.') ?>
                            </p>
                            <a href="ServicePage2.php?boutique=<?= $boutiqueId ?>" class="btn-gold">
                                VIEW PARTNER
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No recommended partners found.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="cta-section">
        <h2 class="cta-header">For a more efficient booking...</h2>
        <p class="cta-description">Join our community! Level up your salon business and beauty experience with a smart booking system made for you. Join a growing community that values convenience, style, and self-care.</p>
        <button class="btn-cta" onclick="location.href='../AdminView/Registration.php'">Add my business</button>
    </section>

    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
</body>
</html>