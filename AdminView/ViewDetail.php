<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//redirect to login if session variable is missing
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    session_unset();
    session_destroy();
    header("Location: Login.php?error=unauthorized");
    exit();
}

require_once '../db_connect.php';

$current_admin_id = $_SESSION['admin_id'];
$requestIdParam = $_GET['id'] ?? '';

if (empty($requestIdParam)) {
    header("Location: requests.php");
    exit();
}

try {
    $requestId = new MongoDB\BSON\ObjectId($requestIdParam);
    
    $booking = $bookingsCollection->findOne([
        '_id' => $requestId,
        'admin_id' => $current_admin_id
    ]);
    
} catch (Exception $e) {
    echo "Invalid Request ID.";
    exit();
}

if (!$booking) {
    die("<div style='padding:50px; text-align:center; font-family:sans-serif;'>
            <h2>Access Denied</h2>
            <p>You do not have permission to view this booking record.</p>
            <a href='requests.php'>Return to Requests</a>
         </div>");
}

function getImageUrl($path) {
    if (empty($path)) return 'img/placeholder.png';
    $path = trim($path);
    if (strpos($path, 'http') === 0) return $path;
    //otherwise, assume local
    return "../AdminView/" . ltrim($path, '/');
}

require_once 'customization_helper.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>DivaBook - Appointment Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL,GRAD,opsz@400,0,0,24" rel="stylesheet">
    <link rel="stylesheet" href="BnA.css">
    <style>
        <?= getCustomStyles($colorPrimary, $colorSecondary) ?>

        .detail-grid { 
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr; 
            grid-template-rows: auto auto; 
            gap: 1.5rem; 
            margin-top: 2rem;
            grid-template-areas: 
                "left-stack payment reference"
                "left-stack payment reference";
        }

        .left-column-stack { 
            grid-area: left-stack; 
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .payment-card { 
            grid-area: payment; 
            display: flex; 
            flex-direction: column; 
        }

        .reference-card { 
            grid-area: reference; 
            display: flex; 
            flex-direction: column; 
        }

        .detail-card { 
            background: white; 
            border: 1px solid var(--outline); 
            border-radius: 12px; 
            padding: 1.5rem; 
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            height: 100%;
        }

        .section-header { 
            border-bottom: 1px solid #f0f0f0; 
            padding-bottom: 0.75rem; 
            margin-bottom: 1.25rem; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            color: var(--primary); 
            font-weight: 700; 
        }

        .img-container {
            width: 100%;
            flex-grow: 1;
            margin-top: 15px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--outline);
            background: #f9fafb;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 350px;
        }

        .img-preview { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
            display: block;
        }

        .info-row { 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            margin-bottom: 0.85rem; 
            font-size: 0.9rem; 
        }

        .info-label { color: var(--secondary); font-weight: 600; }
        .info-value { font-weight: 700; color: var(--text-main); text-align: right; }

        @media (max-width: 1200px) {
            .detail-grid {
                grid-template-columns: 1fr 1fr;
                grid-template-areas: 
                    "left-stack payment"
                    "left-stack reference";
            }
        }

        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
                grid-template-areas: "left-stack" "payment" "reference";
            }
        }
    </style>
</head>

<body class="app-body">
    <?php $activePage = 'details'; require_once 'navbar.php'; ?>

    <main class="main-content">
        <div class="breadcrumb">
            <a href="requests.php" class="back-link">
                <span class="material-symbols-outlined">arrow_back</span> Back to Requests
            </a>
        </div>

        <div class="page-header mt-3">
            <div>
                <h1 class="page-title"><?= h($booking['first_name'] . ' ' . $booking['last_name']) ?></h1>
                <div class="status-header">
                    <span class="status-badge status-<?= $booking['status'] ?? 'pending' ?>">
                        <?= strtoupper($booking['status'] ?? 'PENDING') ?>
                    </span>
                </div>
            </div>
        </div>

<div class="detail-grid">
            
            <div class="left-column-stack">
                <div class="detail-card">
                    <div class="section-header">
                        <span class="material-symbols-outlined">content_cut</span> Service Details
                    </div>
                    <div class="info-row">
                        <span class="info-label">Main Service</span>
                        <span class="info-value"><?= h($booking['service_name']) ?> (₱<?= number_format($booking['service_price'], 2) ?>)</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Size Option</span>
                        <span class="info-value"><?= h($booking['size_name'] ?? 'None') ?> (+₱<?= number_format($booking['size_price'] ?? 0, 2) ?>)</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Add-ons</span>
                        <span class="info-value"><?= h($booking['addon_name'] ?? 'None') ?> (+₱<?= number_format($booking['addon_price'] ?? 0, 2) ?>)</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Removal</span>
                        <span class="info-value"><?= h($booking['removal'] === true ? 'Yes' : 'No') ?> (+₱<?= number_format($booking['removal_price'] ?? 0, 2) ?>)</span>
                    </div>
                    <hr>
                    <div class="info-row">
                        <span class="info-label">Schedule</span>
                        <span class="info-value text-primary"><?= h($booking['selected_date']) ?> @ <?= h($booking['selected_time']) ?></span>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="section-header">
                        <span class="material-symbols-outlined">person</span> Client Information
                    </div>
                    <div class="info-row"><span class="info-label">Email</span><span class="info-value"><?= h($booking['email']) ?></span></div>
                    <div class="info-row"><span class="info-label">Phone</span><span class="info-value"><?= h($booking['phone'] ?? 'N/A') ?></span></div>
                    <div class="mt-3">
                        <span class="info-label">Requests:</span>
                        <p class="cell-faded mt-1" style="background: #f9fafb; padding: 12px; border-radius: 8px; font-style: italic;">
                            "<?= h($booking['special_requests'] ?? 'None') ?>"
                        </p>
                    </div>
                </div>
                </div> <div class="detail-card payment-card">
                    <div class="section-header">
                        <span class="material-symbols-outlined">payments</span> Payment Summary
                    </div>
                    <div class="info-row"><span class="info-label">Strategy</span><span class="strategy-label"><?= ucfirst($booking['payment_strategy'] ?? 'N/A') ?></span></div>
                    <div class="info-row"><span class="info-label">Amount Paid</span><span class="info-value text-success">₱<?= number_format($booking['amount_paid'] ?? 0, 2) ?></span></div>
                    <div class="info-row"><span class="info-label">Total Contract</span><span class="info-value">₱<?= number_format($booking['total_price'] ?? 0, 2) ?></span></div>
                    
                    <div class="img-container">
                        <?php if (!empty($booking['receipt_path'])): ?>
                            <img src="<?= getImageUrl($booking['receipt_path']) ?>" 
                                class="img-preview" 
                                alt="Receipt" 
                                onclick="openModal(this)" 
                                style="cursor: pointer;">
                             <?php else: ?>
                            <div class="placeholder-text" style="color: #6b7280; font-size: 0.9rem; text-align: center;">
                                No receipt
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <div class="detail-card reference-card">
                <div class="section-header">
                    <span class="material-symbols-outlined">image</span> Reference & Style
                </div>
                <div class="img-container">
                    <?php 
                        $refImg = $booking['reference_image'] ?? '';
                        if (!empty($refImg)): 
                    ?>
                        <img src="<?= getImageUrl($refImg) ?>" 
                            class="img-preview" 
                            alt="Reference" 
                            onclick="openModal(this)" 
                            style="cursor: pointer;">  
                       <?php else: ?>
                        <div class="placeholder-text" style="color: #6b7280; font-size: 0.9rem; text-align: center;">
                            No Reference photo
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <div id="imageModal" class="image-zoom-modal" onclick="closeModal()" 
        style="display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; 
                background-color: rgba(0, 0, 0, 0.9); backdrop-filter: blur(8px); 
                align-items: center; justify-content: center; cursor: zoom-out;">
        
        <img class="modal-content" id="zoomedImg" 
            style="display: block; 
                    width: auto;           
                    height: auto;          
                    max-width: 95%;        
                    max-height: 90vh;      
                    object-fit: contain;   
                    border: 4px solid white; 
                    border-radius: 8px; 
                    box-shadow: 0 25px 50px rgba(0,0,0,0.5); 
                    animation: popOutEffect 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;">
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    function openModal(imgElement) {
        const modal = document.getElementById("imageModal");
        const modalImg = document.getElementById("zoomedImg");
        
        if (modal && modalImg) {
            modalImg.src = imgElement.src;
            modal.style.display = "flex";
            document.body.style.overflow = "hidden"; 
        }
    }

    function closeModal() {
        const modal = document.getElementById("imageModal");
        if (modal) {
            modal.style.display = "none";
            document.body.style.overflow = "auto"; 
        }
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            closeModal();
        }
    });
    </script>
</body>
</html>