<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if session variable is missing
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    session_unset();
    session_destroy();
    header("Location: Login.php?error=unauthorized");
    exit();
}

$adminId = $_SESSION['admin_id'];
$uploadsDir = 'uploads/';

require_once __DIR__ . '/../db_connect.php'; 
require_once 'customization_helper.php';

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

// Fetch partner document
$partnersCollection = $db->partners;
$partnerDoc = $partnersCollection->findOne(['admin_id' => $adminId]);
$partnerData = $partnerDoc ? (array)$partnerDoc : [];

// Access nested branding obj
$branding = $partnerData['branding'] ?? [];

// Keeps updated fallback logic, but point it to the branding obj
$displaySalonName = !empty($branding['salon_name']) 
                    ? $branding['salon_name'] 
                    : ($partnerData['shop_name'] ?? 'DivaBook');

$displayEmail = !empty($branding['email']) 
                ? $branding['email'] 
                : ($partnerData['email'] ?? '');

$displayContact = !empty($branding['contact_info']) 
                  ? $branding['contact_info'] 
                  : ($partnerData['phone'] ?? '');

$displayLocation = !empty($branding['location']) 
                   ? $branding['location'] 
                   : ($partnerData['address'] ?? '');

// Set branding colors and logo
$colorPrimary = $branding['color_primary'] ?? '#064E3B';
$colorSecondary = $branding['color_secondary'] ?? '#7cbf7f';
$logoPath = $branding['logo_path'] ?? '';
$settings = $branding;

// Fetch only this admin's services
$servicesCollection = $db->services;
$servicesCursor = $servicesCollection->find(['admin_id' => $adminId]);
$services = [];
foreach ($servicesCursor as $doc) {
    $serviceArray = (array) $doc;
    $serviceArray['id'] = (string) $doc['_id'];
    $services[] = $serviceArray;
}


// HANDLE FORM SUBMISSIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

   // Add or Edit Services
    if ($action === 'add_service' || $action === 'edit_service') {
        $serviceId = $_POST['service_id'] ?? null;
        $photos = !empty($_POST['existing_photos']) ? json_decode($_POST['existing_photos'], true) : [];

        $uploadApi = new UploadApi();
        for ($i = 1; $i <= 3; $i++) {
            $fileKey = "service_photo_$i";
            
            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK && !empty($_FILES[$fileKey]['tmp_name'])) {
                try {
                    $uniqueSuffix = $serviceId ?: "new_" . uniqid();
                    $publicId = "service_{$uniqueSuffix}_slot_{$i}";

                    $response = $uploadApi->upload($_FILES[$fileKey]['tmp_name'], [
                        'folder' => "divabook_services/$adminId",
                        'public_id' => $publicId,
                        'overwrite' => true,
                        'invalidate' => true
                    ]);
                    
                    $photos[$i - 1] = $response['secure_url']; 
                } catch (Exception $e) {
                    $errorMsg = "Photo $i Upload Failed: " . $e->getMessage();
                }
            }
        }

        $serviceData = [
            'admin_id' => $adminId,
            'name' => trim($_POST['service_name'] ?? ''),
            'category' => trim($_POST['category'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'rate' => trim($_POST['rate'] ?? ''),
            'photos' => $photos 
        ];

        if ($action === 'add_service') {
            $servicesCollection->insertOne($serviceData);
            header("Location: admin_customization.php?status=service_added");
            exit();
        }
        else {
            try {
                $servicesCollection->updateOne(
                    ['_id' => new MongoDB\BSON\ObjectId($serviceId)],
                    ['$set' => $serviceData]
                );
            } catch (Exception $e) {
                $errorMsg = "Invalid Service ID format.";
            }
        }
        header("Location: admin_customization.php?status=saved");
        exit();

    } 
    // Delete account
    elseif ($action === 'delete_full_account') {
        $adminIdToDelete = $_POST['admin_id'] ?? '';
        if ($adminIdToDelete === $_SESSION['admin_id']) {
            if (deletePartnerData($adminIdToDelete)) { 
                session_destroy(); 
                header("Location: ../AdminView/Login.php?status=deleted_success");                
                exit();
            }
        }
    }
    // Delete a service
    elseif ($action === 'delete_service') {
        $serviceId = $_POST['service_id'];
        try {
            $service = $servicesCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($serviceId)]);
            if ($service && !empty($service['photos'])) {
                $uploadApi = new UploadApi();
                foreach ((array)$service['photos'] as $url) {
                    if (!empty($url)) {
                        $pathParts = explode('/', parse_url($url, PHP_URL_PATH));
                        $fileName = pathinfo(end($pathParts), PATHINFO_FILENAME);
                        $publicId = "divabook_services/$adminId/" . $fileName;
                        $uploadApi->destroy($publicId);
                    }
                }
            }
            $servicesCollection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($serviceId)]);
            header("Location: admin_customization.php?status=service_deleted");
                    exit();
                } catch (Exception $e) {
                    $errorMsg = "Delete failed: " . $e->getMessage();
                }
        }
    // Save salon profile info
    elseif ($action === 'save_salon_profile') {
        $brandingUpdate = [
            'salon_name'      => trim($_POST['salon_name'] ?? ''),
            'description'     => trim($_POST['description'] ?? ''),
            'credentials'     => trim($_POST['credentials'] ?? ''),
            'location'        => trim($_POST['location'] ?? ''),
            'contact_info'    => trim($_POST['contact_info'] ?? ''),
            'email'           => trim($_POST['email'] ?? ''),
            'facebook'        => trim($_POST['facebook'] ?? ''),
            'facebook_url'    => trim($_POST['facebook_url'] ?? ''),
            'instagram'       => trim($_POST['instagram'] ?? ''),
            'instagram_url'   => trim($_POST['instagram_url'] ?? ''),
            'color_primary'   => $_POST['color_primary'] ?? '#064E3B',
            'color_secondary' => $_POST['color_secondary'] ?? '#7cbf7f',
        ];

        // Check if a new file was uploaded
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK && !empty($_FILES['logo']['tmp_name'])) {
            try {
                $uploadApi = new UploadApi();
                $response = $uploadApi->upload($_FILES['logo']['tmp_name'], [
                    'folder' => "divabook_branding/$adminId",
                    'public_id' => "salon_logo", 
                    'overwrite' => true,
                    'invalidate' => true 
                ]);
                $brandingUpdate['logo_path'] = $response['secure_url'];
            } catch (Exception $e) {
                $errorMsg = "Logo Upload Failed: " . $e->getMessage();
            }
        } else { $brandingUpdate['logo_path'] = $logoPath; }
        
        $partnersCollection->updateOne(
            ['admin_id' => $adminId],
            ['$set' => ['branding' => $brandingUpdate]]
        );

        header("Location: admin_customization.php?status=profile_customized"); // Specific status
        exit();
        
        header("Location: admin_customization.php?status=saved");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title><?= htmlspecialchars($displaySalonName) ?> - Customization</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL,GRAD,opsz@400,0,0,24" rel="stylesheet">

    <style>
        <?= getCustomStyles($colorPrimary, $colorSecondary) ?>
    </style>
    <link rel="stylesheet" href="admin_customization.css">
</head>

<body class="overflow-x-hidden" style="margin: 0;">
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-content">
            <h1 class="logo-loading">DIVABOOK</h1>
            <div class="loader-bar"></div>
            <p class="loading-text" id="dynamic-loading-text">Applying your changes...</p>
        </div>
    </div>
        <?php $activePage = 'customization'; require_once 'navbar.php'; ?>

    <main class="container-fluid px-4 px-md-5 py-4 pb-5">
        <div class="admin-id-hero-card mb-4">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="id-icon-circle me-3">
                        <span class="material-symbols-outlined">badge</span>
                    </div>
                    <div>
                        <small class="text-uppercase fw-bold opacity-75 d-block">System Identifier</small>
                        <span class="admin-id-label">Admin ID: 
                            <strong class="admin-id-display"><?= htmlspecialchars($adminId) ?></strong>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <h1 class="mb-1 text-primary-theme">Customization</h1>
            <p class="text-muted">Personalize your booking system and manage your salon's profile.</p>
        </div>

        <div class="custom-card p-2 p-md-4">
            <ul class="nav nav-pills custom-tabs mb-4 px-3 px-md-0" id="customizationTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active d-flex align-items-center" id="salon-profile-tab" data-bs-toggle="tab" data-bs-target="#salon-profile" type="button" role="tab"><span class="material-symbols-outlined me-2">storefront</span> Salon Profile</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link d-flex align-items-center" id="service-catalog-tab" data-bs-toggle="tab" data-bs-target="#service-catalog" type="button" role="tab"><span class="material-symbols-outlined me-2">list_alt</span> Service Catalog</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link d-flex align-items-center" id="template-booking-tab" data-bs-toggle="tab" data-bs-target="#template-booking" type="button" role="tab"><span class="material-symbols-outlined me-2">web</span> Template for Booking</button>
                </li>
            </ul>

            <hr class="mb-4 text-muted mx-3 mx-md-0">

            <div class="tab-content px-3 px-md-0" id="customizationTabsContent">
                <div class="tab-pane fade show active" id="salon-profile" role="tabpanel">
                    <form action="admin_customization.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save_salon_profile">
                        <div class="row g-5">
                            <div class="col-lg-7">
                                <h4 class="fw-bold mb-4 basic-info-heading">Basic Information</h4>
                                <div class="mb-4">
                                    <label class="form-label fw-bold text-muted small text-uppercase">Business Name</label>
                                    <input type="text" class="form-control" name="salon_name" 
                                        value="<?= htmlspecialchars($displaySalonName) ?>" required>                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-bold text-muted small text-uppercase">Description</label>
                                    <textarea class="form-control bg-light py-2" name="description" rows="3"><?= htmlspecialchars($settings['description'] ?? '') ?></textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold text-muted small text-uppercase">Credentials</label>
                                    <textarea class="form-control bg-light py-2" name="credentials" rows="3" placeholder="Experience, background, or certificates..."><?= htmlspecialchars($settings['credentials'] ?? '') ?></textarea>
                                </div>

                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small text-uppercase">Business Address</label>
                                        <input type="text" class="form-control" name="location" 
                                            value="<?= htmlspecialchars($displayLocation) ?>">                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted small text-uppercase">Contact Info</label>
                                        <input type="text" class="form-control" name="contact_info" 
                                            value="<?= htmlspecialchars($displayContact) ?>">
                                            </div>
                                </div>

                                <h5 class="fw-bold mb-3 mt-2 social-section-heading">
                                    <span class="material-symbols-outlined align-middle me-1" style="font-size:20px;">share</span> Email & Social Media
                                </h5>

                                <div class="mb-3">
                                    <label class="form-label fw-bold text-muted small text-uppercase">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="text-muted" viewBox="0 0 16 16"><path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414.05 3.555ZM0 4.697v7.104l5.803-3.558L0 4.697ZM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586l-1.239-.757Zm3.436-.586L16 11.801V4.697l-5.803 3.546Z"/></svg>
                                        </span>
                                        <input type="email" class="form-control bg-light border-start-0 py-2" name="email" placeholder="shop@example.com" value="<?= htmlspecialchars($displayEmail) ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold text-muted small text-uppercase">Facebook</label>
                                    <div class="row g-2">
                                        <div class="col-md-5">
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0" style="color: #1877F2;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                                </span>
                                                <input type="text" class="form-control bg-light border-start-0 py-2" name="facebook" placeholder="@username" value="<?= htmlspecialchars($settings['facebook'] ?? '') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-7">
                                            <input type="url" class="form-control bg-light py-2 h-100" name="facebook_url" placeholder="https://www.facebook.com/..." value="<?= htmlspecialchars($settings['facebook_url'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold text-muted small text-uppercase">Instagram</label>
                                    <div class="row g-2">
                                        <div class="col-md-5">
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0" style="color: #E4405F;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                                </span>
                                                <input type="text" class="form-control bg-light border-start-0 py-2" name="instagram" placeholder="@username" value="<?= htmlspecialchars($settings['instagram'] ?? '') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-7">
                                            <input type="url" class="form-control bg-light py-2 h-100" name="instagram_url" placeholder="https://www.instagram.com/..." value="<?= htmlspecialchars($settings['instagram_url'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <div id="brandingPanel" class="p-4 rounded-3 border">
                                    <h4 class="fw-bold mb-3">Branding Elements</h4>
                                    <div class="color-preview-bar" id="colorPreviewBar"></div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-muted small text-uppercase">Logo Image</label>
                                        <div class="card bg-light border border-dashed text-center p-3 mb-3 shadow-sm">
                                            <?php if (!empty($settings['logo_path'])): ?>
                                                <img src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="Salon Logo" class="img-fluid rounded mx-auto d-block" style="max-height: 120px; object-fit: contain;">
                                                <p class="small text-muted mt-2 mb-0">Current Logo</p>
                                            <?php else: ?>
                                                <div class="text-muted my-4">
                                                    <span class="material-symbols-outlined" style="font-size: 48px;">image</span>
                                                    <p class="mb-0 small mt-2">No logo uploaded yet</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <input class="form-control form-control-sm" name="logo" type="file" accept="image/*">
                                    </div>
                                    <hr class="text-muted my-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted small text-uppercase">Desired Color Palette</label>
                                        <div class="color-row">
                                            <input type="color" class="color-swatch" id="colorPrimary" name="color_primary" value="<?= htmlspecialchars($colorPrimary) ?>">
                                            <div class="flex-grow-1"><label class="mb-1 fw-bold d-block text-dark small">Primary Color</label></div>
                                            <input type="text" class="hex-input" id="hexPrimary" value="<?= htmlspecialchars($colorPrimary) ?>" maxlength="7">
                                        </div>
                                        <div class="color-row">
                                            <input type="color" class="color-swatch" id="colorSecondary" name="color_secondary" value="<?= htmlspecialchars($colorSecondary) ?>">
                                            <div class="flex-grow-1"><label class="mb-1 fw-bold d-block text-dark small">Secondary Color</label></div>
                                            <input type="text" class="hex-input" id="hexSecondary" value="<?= htmlspecialchars($colorSecondary) ?>" maxlength="7">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                            <button type="submit" class="btn btn-success px-5 py-3 fw-bold d-flex align-items-center gap-2">
                                <span class="material-symbols-outlined">save</span> Save All Changes
                            </button>
                        </div>
                    </form>
                    <div class="mt-5 pt-5 border-top">
                        <h4 class="text-danger fw-bold">Danger Zone</h4>
                        <p class="text-muted small">Deleting your account will permanently remove your salon profile and all services.</p>
                        <form id="deleteAccountForm" action="admin_customization.php" method="POST">
                            <input type="hidden" name="action" value="delete_full_account">
                            <input type="hidden" name="admin_id" value="<?= htmlspecialchars($adminId) ?>">
                            
                            <button type="button" class="btn btn-outline-danger px-4" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">
                                <span class="material-symbols-outlined align-middle me-1">delete_forever</span> Delete My Entire Salon Account
                            </button>
                        </form>
                    </div>
                </div>

                <div class="tab-pane fade" id="service-catalog" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-4 px-3 px-md-0">
                        <div><h4 class="fw-bold mb-1">Your Service Portfolio</h4><p class="text-muted small">Showcase your services with descriptions and rates.</p></div>
                        <button class="btn btn-success d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#serviceModal" onclick="prepareServiceModal('add')">
                            <span class="material-symbols-outlined">add</span> Add New Service
                        </button>
                    </div>
                    <?php if (empty($services)): ?>
                        <div class="text-center py-5 my-3 bg-light rounded-4 border border-dashed"><h5 class="fw-bold text-dark">No Services Yet</h5><button class="btn btn-outline-success px-4" data-bs-toggle="modal" data-bs-target="#serviceModal" onclick="prepareServiceModal('add')">Add My First Service</button></div>
                    <?php else: ?>
                        <div class="row g-4 px-3 px-md-0">
                            <?php foreach ($services as $service): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card h-100 border-0 shadow-sm overflow-hidden service-card">
                                        <?php 
                                        $photos = isset($service['photos']) ? (array)$service['photos'] : [];
                                        $catalogPhoto = !empty($photos) && isset($photos[0]) ? $photos[0] : null;
                                        if ($catalogPhoto): ?>
                                            <img src="<?= htmlspecialchars($catalogPhoto) ?>" class="card-img-top" style="height: 180px; width: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 180px;"><span class="material-symbols-outlined text-muted" style="font-size: 3rem;">image</span></div>
                                        <?php endif; ?>
                                        <div class="card-body p-4">
                                            <div class="d-flex justify-content-between align-items-start mb-2"><span class="badge badge-primary-theme px-2 py-1 small"><?= htmlspecialchars($service['category']) ?></span><span class="fw-bold text-dark">₱<?= htmlspecialchars($service['rate']) ?></span></div>
                                            <h5 class="card-title fw-bold text-dark mb-2"><?= htmlspecialchars($service['name']) ?></h5>
                                            <p class="card-text text-muted small mb-4"><?= htmlspecialchars(substr($service['description'], 0, 100)) ?>...</p>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-edit-theme btn-sm flex-grow-1 fw-bold d-flex align-items-center justify-content-center gap-1" onclick="prepareServiceModal('edit', <?= htmlspecialchars(json_encode($service)) ?>)" data-bs-toggle="modal" data-bs-target="#serviceModal">
                                                    <span class="material-symbols-outlined fs-6">edit</span> Edit
                                                </button>
                                                <form action="admin_customization.php" method="POST" onsubmit="return confirm('Delete this service?')">
                                                    <input type="hidden" name="action" value="delete_service">
                                                    <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                                                    <button type="button" class="btn btn-outline-danger btn-sm" 
                                                        onclick="prepareDeleteModal('<?= $service['id'] ?>', '<?= htmlspecialchars($service['name'], ENT_QUOTES) ?>')" 
                                                        data-bs-toggle="modal" data-bs-target="#confirmServiceDeleteModal">
                                                        <span class="material-symbols-outlined fs-6">delete</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="template-booking" role="tabpanel"><div class="text-center py-5 my-5"><h3 class="fw-bold text-dark">Booking Template</h3><p class="text-muted">Currently under development.</p></div></div>
            </div>
        </div>
        <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                    <div class="modal-header border-0 pt-4 px-4">
                        <h5 class="modal-title fw-bold text-danger" id="confirmDeleteModalLabel">Confirm Account Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <div class="mb-3">
                            <span class="material-symbols-outlined text-danger" style="font-size: 64px;">warning</span>
                        </div>
                        <h4 class="fw-bold mb-2">Are you absolutely sure?</h4>
                        <p class="text-muted">This action is permanent. All your salon profile data, services, and branding will be deleted forever.</p>
                    </div>
                    <div class="modal-footer border-0 pb-4 px-4">
                        <button type="button" class="btn btn-light px-4 fw-bold flex-grow-1" data-bs-dismiss="modal">No, Keep My Account</button>
                        <button type="button" class="btn btn-danger px-4 fw-bold flex-grow-1" id="confirmDeleteBtn">Yes, Delete Everything</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="confirmServiceDeleteModal" tabindex="-1" aria-labelledby="confirmServiceDeleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                    <div class="modal-header border-0 pt-4 px-4">
                        <h5 class="modal-title fw-bold text-danger" id="confirmServiceDeleteModalLabel">Delete Service</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <div class="mb-3">
                            <span class="material-symbols-outlined text-danger" style="font-size: 64px;">warning</span>
                        </div>
                        <h4 class="fw-bold mb-2">Remove this service?</h4>
                        <p class="text-muted">This will permanently delete <strong id="deleteServiceNameText"></strong> from your catalog. This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer border-0 pb-4 px-4">
                        <button type="button" class="btn btn-light px-4 fw-bold flex-grow-1" data-bs-dismiss="modal">Cancel</button>
                        <form id="deleteServiceForm" action="admin_customization.php" method="POST" class="flex-grow-1">
                            <input type="hidden" name="action" value="delete_service">
                            <input type="hidden" name="service_id" id="deleteServiceIdInput">
                            <button type="submit" class="btn btn-danger px-4 fw-bold w-100">Delete Service</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>


    <div class="modal fade" id="serviceModal" tabindex="-1" aria-labelledby="serviceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <form action="admin_customization.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header border-bottom-0 p-4">
                        <h5 class="modal-title fw-bold" id="serviceModalLabel">Add New Service</h5>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 pt-0">
                        <input type="hidden" name="action" id="modalAction" value="add_service">
                        <input type="hidden" name="service_id" id="modalServiceId">

                        <div class="row g-4">
                            <div class="col-md-7">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-uppercase text-muted">Service
                                        Name</label>
                                    <input type="text" class="form-control py-2" name="service_name"
                                        id="modalServiceName" placeholder="E.g. Balayage Transformation" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-uppercase text-muted">Category</label>
                                    <select class="form-select py-2" name="category" id="modalCategory" required>
                                        <option value="" disabled selected>Select category</option>
                                        <option value="Hair">Hair</option>
                                        <option value="Nails">Nails</option>
                                        <option value="Skin">Skin</option>
                                        <option value="Massage">Massage</option>
                                        <option value="Others">Others</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-uppercase text-muted">Service Rate
                                        (₱)</label>
                                    <input type="number" class="form-control py-2" name="rate" id="modalRate"
                                        placeholder="0.00" required>
                                </div>
                                <div class="mb-0">
                                    <label
                                        class="form-label fw-bold small text-uppercase text-muted">Description</label>
                                    <textarea class="form-control py-2" name="description" id="modalDescription"
                                        rows="4" placeholder="Describe the service details..."></textarea>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold small text-uppercase text-muted">Inspiration Photos (Max 3)</label>
                                <input type="hidden" name="existing_photos" id="modalExistingPhotos">
                                
                                <div class="row g-2">
                                    <?php for ($i = 1; $i <= 3; $i++): ?>
                                    <div class="col-4">
                                        <div class="card bg-light border border-dashed text-center position-relative" 
                                            style="height: 100px; display: flex; align-items: center; justify-content: center; overflow: hidden; border-style: dashed !important;">
                                            
                                            <img id="photoPreview<?= $i ?>" src="" class="img-fluid d-none" style="height: 100%; width: 100%; object-fit: cover;">
                                            
                                            <div id="photoPlaceholder<?= $i ?>">
                                                <span class="material-symbols-outlined text-muted">add_a_photo</span>
                                            </div>

                                            <input type="file" name="service_photo_<?= $i ?>" 
                                                class="position-absolute opacity-0 w-100 h-100" style="cursor: pointer; z-index: 10;"
                                                accept="image/*" onchange="previewMultipleImages(this, <?= $i ?>)">
                                        </div>
                                    </div>
                                    <?php endfor; ?>
                                </div>
                                <p class="small text-muted mt-2">Upload up to 3 photos of your work or inspiration for this service.</p>
                            </div>
                                                    </div>
                    </div>
                    <div class="modal-footer border-top-0 p-4">
                        <button type="button" class="btn btn-light px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success px-5 fw-bold"
                            style="background-color: var(--primary-container); border-color: var(--primary-container);">Save
                            Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="saveSuccessModal" tabindex="-1" aria-labelledby="saveSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-body text-center p-5">
                    <div class="mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-success-subtle rounded-circle"
                            style="width: 80px; height: 80px;">
                            <span class="material-symbols-outlined text-success" style="font-size: 48px;">check_circle</span>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-3" id="successModalTitle">Success!</h3>
                    <p class="text-muted mb-4 fs-5" id="successModalMessage">Your changes have been saved.</p>
                    
                    <button type="button" class="btn btn-success px-5 py-3 fw-bold w-100" data-bs-dismiss="modal"
                        style="background-color: var(--primary-container); border-color: var(--primary-container); border-radius: 12px;">
                        Great!
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="admin_customization.js"></script>
</body>
</html>