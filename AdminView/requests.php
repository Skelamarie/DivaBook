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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $requestId = new MongoDB\BSON\ObjectId($_POST['request_id']);
    $newStatus = ($_POST['action'] === 'approve') ? 'approved' : 'declined';

    $bookingsCollection->updateOne(
        ['_id' => $requestId],
        ['$set' => ['status' => $newStatus]]
    );

    header("Location: requests.php");
    exit();
}

$current_admin_id = $_SESSION['admin_id'] ?? '';
$filterStatus = $_GET['status'] ?? 'all';
$criteria = ['admin_id' => $current_admin_id];

if ($filterStatus !== 'all') {
    $criteria['status'] = $filterStatus;
}

$bookings = $bookingsCollection->find($criteria, ['sort' => ['created_at' => -1]])->toArray();

require_once 'customization_helper.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>DivaBook - Service Requests</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL,GRAD,opsz@400,0,0,24" rel="stylesheet">

    <link rel="stylesheet" href="BnA.css">
    <style>
        <?= getCustomStyles($colorPrimary, $colorSecondary) ?>
    </style>
</head>

<body class="app-body">

    <?php $activePage = 'bookings'; require_once 'navbar.php'; ?>

    <main class="main-content">
        <div class="requests-header">
            <div class="header-main">
                <div class="breadcrumb">
                    <a href="BnA.php" class="back-link">
                        <span class="material-symbols-outlined">arrow_back</span>
                    </a>
                </div>
                <h1 class="page-title">Service Requests</h1>
                <p class="page-subtitle">Review and approve incoming customer service inquiries.</p>
            </div>

            <div class="filter-bar">
                <div class="search-wrapper">
                    <span class="material-symbols-outlined search-icon">search</span>
                    <input type="text" id="requestSearch" onkeyup="filterRequests()" placeholder="Search customers..."
                        class="search-input">
                </div>
                <select id="statusFilter" onchange="filterRequests()" class="status-dropdown">
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                </select>
            </div>
        </div>

        <div class="data-table-container">
            <div class="scroll-wrapper">
                <table class="data-table" id="requestsTable">
                    <thead>
                        <tr>
                            <th class="col-contact">Customer Details</th>
                            <th class="col-service">Service Type</th>
                            <th class="col-date">Requested Date</th>
                            <th class="col-payment">Payment Strategy</th>
                            <th class="col-budget">Amount Paid</th>
                            <th class="col-status">Status</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="requestsBody">
                        <?php foreach ($bookings as $req): ?>
                        <tr class="table-row-hover">
                        <td data-label="Customer Details">
                            <div class="contact-cell">
                                <span class="contact-name text-primary"><?php echo htmlspecialchars(($req['first_name'] ?? '') . ' ' . ($req['last_name'] ?? '')); ?></span>
                                <span class="contact-email text-secondary"><?php echo htmlspecialchars($req['email'] ?? ''); ?></span>
                            </div>
                        </td>
                        <td data-label="Service Type">
                            <span class="service-badge"><?php echo htmlspecialchars($req['service_name'] ?? 'Service'); ?></span>
                        </td>
                        <td data-label="Requested Date">
                            <span class="cell-faded"><?php echo htmlspecialchars($req['selected_date'] ?? 'N/A') . ' • ' . htmlspecialchars($req['selected_time'] ?? ''); ?></span>
                        </td>
                        <td data-label="Payment Strategy">
                            <span class="strategy-label"><?php echo ucfirst(htmlspecialchars($req['payment_strategy'] ?? 'N/A')); ?></span>
                        </td>
                        <td data-label="Amount Paid">
                            <span class="cell-bold">₱<?php echo number_format($req['amount_paid'] ?? 0, 2); ?></span>
                        </td>
                        <td data-label="Status">
                            <span class="status-badge status-<?php echo htmlspecialchars($req['status'] ?? 'pending'); ?>">
                                <?php echo strtoupper($req['status'] ?? 'PENDING'); ?>
                            </span>
                        </td>
                            <td class="cell-right">
                                <?php if (($req['status'] ?? 'pending') === 'pending'): ?>
                                    <form method="POST" style="display: contents;">
                                        <input type="hidden" name="request_id" value="<?php echo $req['_id']; ?>">
                                        
                                        <div class="action-buttons-row">
                                            <button type="submit" name="action" value="approve" class="btn-approve-sm">Approve</button>
                                            <button type="submit" name="action" value="decline" class="btn-decline-sm">Decline</button>
                                        </div>
                                    </form>
                                <?php endif; ?>

                                <a href="ViewDetail.php?id=<?php echo (string)$req['_id']; ?>" class="btn-details">View Details</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>