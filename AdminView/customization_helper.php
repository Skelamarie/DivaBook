<?php
// customization_helper.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db_connect.php'; 

$branding = []; 

if (isset($_SESSION['admin_id'])) {
    $adminId = $_SESSION['admin_id'];
    $partnerDoc = $partnersCollection->findOne(['admin_id' => $adminId]);
    
    if ($partnerDoc && isset($partnerDoc['branding'])) {
        $branding = (array)$partnerDoc['branding'];
    }
}

$displaySalonName = !empty($branding['salon_name']) ? $branding['salon_name'] : 'DivaBook';
$colorPrimary = !empty($branding['color_primary']) ? $branding['color_primary'] : '#064E3B';
$colorSecondary = !empty($branding['color_secondary']) ? $branding['color_secondary'] : '#7cbf7f';
$logoPath = !empty($branding['logo_path']) ? $branding['logo_path'] : '';

function getCustomStyles($colorPrimary, $colorSecondary) {
    $hex = str_replace('#', '', $colorPrimary);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    $primaryRgb = "{$r}, {$g}, {$b}";

    return "
        :root {
            --primary-container: {$colorPrimary};
            --primary-rgb: {$primaryRgb};
            --on-primary-container: {$colorSecondary};
            --background: #faf9f6;
            --on-background: #1a1c1a;
            --outline-variant: #c0c9bc;
            --surface-container-low: #f4f4f0;
            --surface-container-lowest: #ffffff;
            --secondary-container: #cfe5d1;
            --on-secondary-container: #546757;
            --error: #ba1a1a;
            --warning: #eab308;
            --font-body: 'Manrope', sans-serif;
            --font-heading: 'Noto Serif', serif;
        }
        .text-primary-theme { color: var(--primary-container) !important; }
        .bg-primary-theme { background-color: var(--primary-container) !important; }
        .btn-primary-theme { 
            background-color: var(--primary-container) !important; 
            color: white !important;
            border-color: var(--primary-container) !important;
        }
        .badge-primary-theme {
            background-color: var(--primary-container);
            color: white;
            border-radius: 4px;
        }
    ";
}
?>