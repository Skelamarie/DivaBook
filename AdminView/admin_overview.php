<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// redirect to login if session variable is missing
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    session_unset();
    session_destroy();
    header("Location: Login.php?error=unauthorized");
    exit();
}

// Load c_helper to get $colorPrimary and $colorSecondary
require_once 'customization_helper.php';

// Load DB Connection for the stats count
require_once '../db_connect.php';

//timezone for updated date
$current_admin_id = $_SESSION['admin_id'] ?? '';
date_default_timezone_set('Asia/Manila');
$todayFormatted = date('F j, Y');

// Stats Calculations
$totalBookings = $bookingsCollection->countDocuments(['admin_id' => $current_admin_id]);
$pendingApprovals = $bookingsCollection->countDocuments(['admin_id' => $current_admin_id, 'status' => 'pending']);
$todayAppointments = $bookingsCollection->countDocuments([
    'admin_id' => $current_admin_id,
    'selected_date' => $todayFormatted,
    'status' => ['$ne' => 'declined']
]);
$cancelledToday = $bookingsCollection->countDocuments([
    'admin_id' => $current_admin_id,
    '$or' => [
        ['status' => 'declined'],
        ['status' => 'cancelled']
    ],
    'selected_date' => $todayFormatted
]);
?>
<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title><?= htmlspecialchars($displaySalonName) ?> - Admin Overview</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Playfair+Display:wght@700;900&display=swap"
        rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL,GRAD,opsz@400,0,0,24"
        rel="stylesheet">

    <style>
        /* applies saved colors into the CSS variables below*/
        <?= getCustomStyles($colorPrimary, $colorSecondary) ?>

        html,
        body {
            margin: 0;
            padding: 0;
            max-width: 100vw;
            overflow-x: hidden;
        }

        body {
            background-color: var(--background);
            color: var(--on-background);
            font-family: var(--font-body);
            padding-top: 80px;
            
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .navbar-brand {
            font-family: var(--font-heading);
            font-weight: 700;
        }

        .navbar {
            background-color: #ffffff;
            border-bottom: 1px solid var(--outline-variant);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            height: 76px;
        }

        .navbar-brand {
            color: var(--primary-container) !important;
            font-size: 1.5rem;
            font-style: italic;
            font-weight: 900;
        }

        .nav-link {
            color: #6c757d;
            font-weight: 600;
            font-size: 0.9rem;
            margin-right: 1.5rem;
        }

        .nav-link:hover {
            color: var(--primary-container);
        }

        .nav-link.active {
            color: var(--primary-container);
            border-bottom: 2px solid var(--primary-container);
            padding-bottom: 0.25rem;
        }

        .search-container {
            position: relative;
        }

        .search-container .material-symbols-outlined {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 20px;
        }

        .search-input {
            background-color: var(--surface-container-low);
            border: none;
            border-radius: 20px;
            padding: 8px 16px 8px 36px;
            font-size: 0.875rem;
            width: 250px;
        }

        .search-input:focus {
            outline: none;
            box-shadow: 0 0 0 2px var(--primary-container);
        }

        .icon-btn {
            background: none;
            border: none;
            padding: 8px;
            border-radius: 6px;
            color: #41493f;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
        }

        .icon-btn:hover {
            background-color: #f8f9fa;
        }

        .profile-img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--outline-variant);
            cursor: pointer;
        }

        .custom-card {
            border: 1px solid var(--outline-variant);
            border-radius: 12px;
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            height: 100%;
        }

        .card-header-custom {
            background-color: var(--surface-container-lowest);
            border-bottom: 1px solid var(--outline-variant);
            padding: 1rem 1.25rem;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        .border-left-warning {
            border-left: 4px solid var(--warning) !important;
        }

        .border-left-danger {
            border-left: 4px solid var(--error) !important;
        }

        .border-left-primary {
            border-left: 4px solid var(--primary-container) !important;
        }

        /* Queued Customers List */
        .queued-item {
            padding: 0.75rem;
            border-radius: 8px;
            background-color: var(--surface-container-low);
            margin-bottom: 0.5rem;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s;
        }

        .queued-item:hover {
            background-color: #e2e3df;
            border-color: var(--outline-variant);
        }

        .queued-details {
            background-color: #ffffff;
            border: 1px solid var(--outline-variant);
            border-radius: 8px;
            padding: 1rem;
            margin-top: -4px;
            margin-bottom: 0.5rem;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
            border-top: none;
        }

        .stat-btn {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .stat-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
        }

        .border-left-warning.stat-btn:hover {
            border-left-color: #d97706 !important;
            background-color: #fefce8 !important;
        }

        .border-left-danger.stat-btn:hover {
            border-left-color: #b91c1c !important;
            background-color: #fef2f2 !important;
        }

        .table-custom th {
            font-family: var(--font-body);
            font-weight: 600;
            color: #6c757d;
            background-color: var(--surface-container-low);
            border-bottom: 1px solid var(--outline-variant);
            padding: 1rem;
        }

        .table-custom td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid rgba(192, 201, 188, 0.3);
        }

        .table-custom tbody tr:hover {
            background-color: var(--surface-container-low);
        }

        .badge-service {
            background-color: rgba(6, 78, 27, 0.1);
            color: var(--primary-container);
            border: 1px solid rgba(6, 78, 27, 0.2);
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Responsiveness */
        @media (max-width: 991.98px) {
            .table-custom thead {
                display: none;
            }

            .table-custom,
            .table-custom tbody,
            .table-custom tr,
            .table-custom td {
                display: block;
                width: 100%;
            }

            .table-custom tr {
                margin-bottom: 0;
                padding-bottom: 0;
            }

            .table-custom td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.75rem 1rem;
                border-bottom: 1px solid rgba(192, 201, 188, 0.3);
                text-align: right;
            }

            .table-custom td:last-child {
                justify-content: flex-end;
            }

            .table-custom td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #6c757d;
                text-align: left;
                margin-right: 1rem;
                font-size: 0.9rem;
            }
        }

        /* Rating Stars */
        .rating-bar {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
            overflow: hidden;
        }

        .rating-fill {
            height: 100%;
            background-color: #fbbc04;
            border-radius: 4px;
        }
    </style>

</head>

<body class="overflow-x-hidden" style="margin: 0;"><?php $activePage = 'overview';
require_once 'navbar.php'; ?>

    <main class="container-fluid px-4 px-md-5 py-4 pb-5">

        <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <h1 class="mb-1 text-dark">Overview</h1>
                <p class="text-muted mb-0">Manage your daily schedule and client requests.</p>
            </div>
            <div class="text-md-end">
                <span class="badge bg-light text-dark border p-2 px-3 fw-bold shadow-sm d-flex align-items-center gap-2"
                    style="font-size: 0.9rem;">
                    <span class="material-symbols-outlined text-secondary"
                        style="font-size: 20px;">calendar_today</span>
                    <span id="liveDate"><?= date('l, F j, Y') ?></span>
                </span>
            </div>
        </div>

        <div class="row g-3 g-md-4 mb-5">
            <div class="col-12">
                <div class="custom-card p-4 border-left-primary d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <p class="text-muted small mb-1 fw-bold text-uppercase">Total Bookings</p>
                        <div class="d-flex gap-2">
                            <select id="monthSelect"
                                class="form-select form-select-sm bg-light border-0 fw-bold text-secondary d-none"
                                style="width: auto; font-size: 0.75rem; cursor: pointer;">
                                <option value="jan">January</option>
                                <option value="feb">February</option>
                                <option value="mar">March</option>
                                <option value="apr">April</option>
                                <option value="may" selected>May</option>
                                <option value="jun">June</option>
                                <option value="jul">July</option>
                                <option value="aug">August</option>
                                <option value="sep">September</option>
                                <option value="oct">October</option>
                                <option value="nov">November</option>
                                <option value="dec">December</option>
                            </select>

                            <select id="bookingsChartToggle"
                                class="form-select form-select-sm bg-light border-0 fw-bold text-secondary"
                                style="width: auto; font-size: 0.75rem; cursor: pointer;">
                                <option value="weekly" selected>Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <h3 class="mb-0 fw-bold" id="totalBookingsCount"><?= htmlspecialchars($totalBookings) ?></h3>
                        <small class="text-success fw-bold" id="totalBookingsTrend"><span
                                class="material-symbols-outlined align-text-bottom"
                                style="font-size: 14px;">trending_up</span> Live from DB</small>
                    </div>
                    <div class="flex-grow-1" style="position: relative; min-height: 120px; width: 100%;">
                        <canvas id="bookingsChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div
                    class="custom-card stat-btn p-3 p-md-4 border-left-warning bg-white d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <p class="text-muted small mb-0 fw-bold text-uppercase" style="line-height: 1.2;">
                            Pending<br>Approvals</p>
                        <span
                            class="material-symbols-outlined text-warning bg-warning bg-opacity-10 rounded p-1 flex-shrink-0"
                            style="font-size: 20px;">pending_actions</span>
                    </div>
                    <h2 class="mb-0 fw-bold text-dark fs-3 fs-md-2 mt-auto" data-stat="pending-approvals">
                        <?= htmlspecialchars($pendingApprovals) ?>
                    </h2>
                </div>
            </div>

            <div class="col-6">
                <div
                    class="custom-card stat-btn p-3 p-md-4 border-left-danger bg-white d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <p class="text-muted small mb-0 fw-bold text-uppercase" style="line-height: 1.2;">
                            Cancelled<br>Today</p>
                        <span
                            class="material-symbols-outlined text-danger bg-danger bg-opacity-10 rounded p-1 flex-shrink-0"
                            style="font-size: 20px;">cancel</span>
                    </div>
                    <h2 class="mb-0 fw-bold text-dark fs-3 fs-md-2 mt-auto" data-stat="cancelled-today">
                        <?= htmlspecialchars($cancelledToday) ?>
                    </h2>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">

            <div class="col-12">
                <div class="custom-card">
                    <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 gap-md-3">
                            <h3 class="fs-6 fs-md-5 mb-0 fw-bold m-0 text-uppercase">TODAY'S APPOINTMENTS</h3>
                            <span class="rounded-circle bg-danger d-inline-block" data-stat="today-appts-count"
                                style="width: 12px; height: 12px; display: none;"></span>
                        </div>
                        <a href="BnA.php" class="btn btn-sm btn-outline-secondary fw-bold px-2 px-md-3">View More</a>
                    </div>
                    <div class="p-3 p-md-4" data-container="today-appts">

                        <div class="queued-item d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 gap-sm-3 mb-3 border bg-white"
                            style="cursor: default;">
                            <div class="d-flex align-items-center gap-3">
                                <span class="material-symbols-outlined text-danger fs-5">push_pin</span>
                                <div class="flex-grow-1">
                                    <p class="mb-0 fw-bold text-dark text-break fs-6">Loading...</p>
                                    <p class="mb-0 small text-muted text-break">Fetching appointments</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        <div class="row g-4 mb-5">
            <div class="col-12">
                <div class="custom-card">
                    <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 gap-md-3">
                            <h3 class="fs-6 fs-md-5 mb-0 fw-bold m-0 text-uppercase">PENDING SERVICE APPROVALS</h3>
                            <span class="rounded-circle bg-danger d-inline-block" data-stat="pending-service-count"
                                style="width: 12px; height: 12px; display: none;"></span>
                        </div>
                        <a href="requests.php" class="btn btn-sm btn-outline-secondary fw-bold px-2 px-md-3">View
                            More</a>
                    </div>
                    <div class="p-3 p-md-4" data-container="pending-service">

                        <div class="queued-item d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 gap-sm-3 mb-3 border bg-white"
                            style="cursor: default;">
                            <div class="d-flex align-items-center gap-3">
                                <span class="material-symbols-outlined text-warning fs-5">pending_actions</span>
                                <div class="flex-grow-1">
                                    <p class="mb-0 fw-bold text-dark text-break fs-6">Loading...</p>
                                    <p class="mb-0 small text-muted text-break">Fetching pending approvals</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="row justify-content-center mb-4">
            <div class="col-md-8 col-lg-6">
                <div class="custom-card p-4 p-md-5 text-center">
                    <h3 class="h4 mb-4 fw-bold">Customer Satisfaction</h3>

                    <div class="d-flex justify-content-center align-items-center gap-4 mb-4">
                        <h1 class="display-3 fw-bold mb-0" style="color: var(--on-background);">4.8</h1>
                        <div class="text-start">
                            <div class="d-flex align-items-center mb-1" style="color: #fbbc04;">
                                <span class="material-symbols-outlined"
                                    style="font-variation-settings: 'FILL' 1; font-size: 28px;">star</span>
                                <span class="material-symbols-outlined"
                                    style="font-variation-settings: 'FILL' 1; font-size: 28px;">star</span>
                                <span class="material-symbols-outlined"
                                    style="font-variation-settings: 'FILL' 1; font-size: 28px;">star</span>
                                <span class="material-symbols-outlined"
                                    style="font-variation-settings: 'FILL' 1; font-size: 28px;">star</span>
                                <span class="material-symbols-outlined"
                                    style="font-variation-settings: 'FILL' 1; font-size: 28px;">star_half</span>
                            </div>
                            <span class="text-muted small fw-semibold">Based on 1,284 reviews</span>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <span class="small fw-bold text-muted" style="width: 20px;">5</span>
                            <div class="rating-bar flex-grow-1">
                                <div class="rating-fill" style="width: 85%;"></div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="small fw-bold text-muted" style="width: 20px;">4</span>
                            <div class="rating-bar flex-grow-1">
                                <div class="rating-fill" style="width: 10%;"></div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="small fw-bold text-muted" style="width: 20px;">3</span>
                            <div class="rating-bar flex-grow-1">
                                <div class="rating-fill" style="width: 3%;"></div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="small fw-bold text-muted" style="width: 20px;">2</span>
                            <div class="rating-bar flex-grow-1">
                                <div class="rating-fill" style="width: 1%;"></div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="small fw-bold text-muted" style="width: 20px;">1</span>
                            <div class="rating-bar flex-grow-1">
                                <div class="rating-fill" style="width: 1%;"></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </main>
    <div class="modal fade" id="doneModal" tabindex="-1" aria-labelledby="doneModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-sm" style="border-radius: 12px;">
                <div class="modal-header border-bottom-0 pb-0">
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body text-center pb-4 px-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 rounded-circle mb-3"
                        style="width: 64px; height: 64px;">
                        <span class="material-symbols-outlined text-success"
                            style="font-size: 32px;">check_circle</span>
                    </div>
                    <h4 class="fw-bold mb-2 text-dark">Service Completed</h4>
                    <p class="text-muted mb-0">You have completed the service.</p>
                </div>
                <div class="modal-footer border-top-0 d-flex justify-content-center pb-4 pt-0">
                    <button type="button" class="btn btn-success px-4 py-2 fw-semibold border-0" data-bs-dismiss="modal"
                        style="background-color: var(--primary-container);">Great!</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmCancelModal" tabindex="-1" aria-labelledby="confirmCancelModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-sm" style="border-radius: 12px;">
                <div class="modal-header border-bottom-0 pb-0">
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body text-center pb-4 px-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 rounded-circle mb-3"
                        style="width: 64px; height: 64px;">
                        <span class="material-symbols-outlined text-danger" style="font-size: 32px;">warning</span>
                    </div>
                    <h4 class="fw-bold mb-2 text-dark">Cancel Appointment?</h4>
                    <p class="text-muted mb-0">Are you sure you want to cancel? This action cannot be undone.</p>
                </div>
                <div class="modal-footer border-top-0 d-flex justify-content-center gap-2 pb-4 pt-0">
                    <button type="button" class="btn btn-light border px-4 py-2 fw-semibold" data-bs-dismiss="modal">No,
                        Keep It</button>
                    <button type="button" class="btn btn-danger px-4 py-2 fw-semibold" id="confirmCancelBtn">Yes,
                        Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cancelSuccessModal" tabindex="-1" aria-labelledby="cancelSuccessModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-sm" style="border-radius: 12px;">
                <div class="modal-header border-bottom-0 pb-0">
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body text-center pb-4 px-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 rounded-circle mb-3"
                        style="width: 64px; height: 64px;">
                        <span class="material-symbols-outlined text-danger" style="font-size: 32px;">delete</span>
                    </div>
                    <h4 class="fw-bold mb-2 text-dark">Appointment Cancelled</h4>
                    <p class="text-muted mb-0">You have successfully cancelled this booking.</p>
                </div>
                <div class="modal-footer border-top-0 d-flex justify-content-center pb-4 pt-0">
                    <button type="button" class="btn btn-light border px-4 py-2 fw-semibold"
                        data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const collapses = document.querySelectorAll('.collapse');
            collapses.forEach(collapse => {
                collapse.addEventListener('show.bs.collapse', function (e) {
                    const toggleBtn = document.querySelector(`[data-bs-target="#${e.target.id}"] .material-symbols-outlined`);
                    if (toggleBtn) toggleBtn.textContent = 'expand_less';
                });
                collapse.addEventListener('hide.bs.collapse', function (e) {
                    const toggleBtn = document.querySelector(`[data-bs-target="#${e.target.id}"] .material-symbols-outlined`);
                    if (toggleBtn) toggleBtn.textContent = 'expand_more';
                });
            });

            // --- REAL-TIME DASHBOARD ---
            const ctx = document.getElementById('bookingsChart').getContext('2d');
            const toggle = document.getElementById('bookingsChartToggle');
            const monthSelect = document.getElementById('monthSelect');
            const countDisplay = document.getElementById('totalBookingsCount');
            const trendDisplay = document.getElementById('totalBookingsTrend');

            let liveData = {};
            let bookingsChart = null;
            let previousData = null;

            const primaryColor = '<?= htmlspecialchars($colorPrimary) ?>';

            const chartConfig = {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Bookings',
                        data: [],
                        backgroundColor: primaryColor + 'D9',
                        hoverBackgroundColor: primaryColor,
                        borderRadius: 6,
                        borderSkipped: false,
                        maxBarThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 600, easing: 'easeOutQuart' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: primaryColor,
                            titleFont: { family: 'Manrope', size: 13 },
                            bodyFont: { family: 'Manrope', size: 13 },
                            padding: 10,
                            displayColors: false,
                            callbacks: {
                                label: function (context) { return context.parsed.y + ' Bookings'; }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: { font: { family: 'Manrope', size: 10 }, color: '#6c757d' }
                        },
                        y: {
                            display: false,
                            grid: { display: false },
                            beginAtZero: true
                        }
                    },
                    interaction: { intersect: false, mode: 'index' }
                }
            };

            bookingsChart = new Chart(ctx, chartConfig);


            // count update
            function animateCount(el, newVal) {
                const current = parseInt(el.textContent) || 0;
                if (current === newVal) return;
                const diff = newVal - current;
                const steps = Math.min(Math.abs(diff), 20);
                const stepVal = diff / steps;
                let step = 0;
                const interval = setInterval(() => {
                    step++;
                    el.textContent = Math.round(current + stepVal * step);
                    if (step >= steps) {
                        el.textContent = newVal;
                        clearInterval(interval);
                    }
                }, 30);
                
                el.style.transition = 'transform 0.3s ease';
                el.style.transform = 'scale(1.15)';
                setTimeout(() => { el.style.transform = 'scale(1)'; }, 300);
            }

            // FETCH LIVE OVERVIEW DATA 
            async function fetchLiveData() {
                try {
                    const response = await fetch('api_overview_stats.php?t=' + Date.now());
                    const data = await response.json();
                    if (data.success) {
                        liveData = data;
                        updateAllDashboard();
                        updateChartView();
                    }
                } catch (error) {
                    console.error('Error fetching live data:', error);
                }
            }

            // UPDATE ALL DASHBOARD ELEMENTS
            function updateAllDashboard() {
                // Total Bookings
                animateCount(countDisplay, liveData.totalBookings || 0);

                // Pending Approvals
                const pendingEl = document.querySelector('[data-stat="pending-approvals"]');
                if (pendingEl) animateCount(pendingEl, liveData.pendingApprovals || 0);

                // Cancelled Today
                const cancelledEl = document.querySelector('[data-stat="cancelled-today"]');
                if (cancelledEl) animateCount(cancelledEl, liveData.cancelledToday || 0);

                // Today's Appointments red dot
                const todayDot = document.querySelector('[data-stat="today-appts-count"]');
                if (todayDot) {
                    todayDot.style.display = (liveData.todayAppointments?.count > 0) ? 'inline-block' : 'none';
                }

                // Pending Service Approvals red dot
                const pendingDot = document.querySelector('[data-stat="pending-service-count"]');
                if (pendingDot) {
                    pendingDot.style.display = (liveData.pendingServiceApprovals?.count > 0) ? 'inline-block' : 'none';
                }

                updateTodayAppointmentsList();
                updatePendingServiceList();

                // Update live date display
                const liveDateEl = document.getElementById('liveDate');
                if (liveDateEl) {
                    const now = new Date();
                    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                    liveDateEl.textContent = now.toLocaleDateString('en-US', options);
                }
            }

            // Status badge helper
            function getStatusBadge(status) {
                const s = (status || 'pending').toLowerCase();
                if (s === 'approved') return '<span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 ms-2" style="font-size:0.7rem;">Approved</span>';
                if (s === 'pending') return '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-2 py-1 ms-2" style="font-size:0.7rem;">Pending</span>';
                if (s === 'cancelled' || s === 'declined') return '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1 ms-2" style="font-size:0.7rem;">Cancelled</span>';
                return '<span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1 ms-2" style="font-size:0.7rem;">' + status + '</span>';
            }

            // UPDATE TODAY'S APPOINTMENTS LIST
            function updateTodayAppointmentsList() {
                const container = document.querySelector('[data-container="today-appts"]');
                if (!container || !liveData.todayAppointments?.items) return;

                if (liveData.todayAppointments.items.length === 0) {
                    container.innerHTML = '<p class="text-muted text-center py-3">No appointments scheduled for today</p>';
                    return;
                }

                container.innerHTML = liveData.todayAppointments.items.map(appt => `
                    <div class="queued-item d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 gap-sm-3 mb-3 border bg-white" style="cursor: default;">
                        <div class="d-flex align-items-center gap-3">
                            <span class="material-symbols-outlined text-danger fs-5">push_pin</span>
                            <div class="flex-grow-1">
                                <p class="mb-0 fw-bold text-dark text-break fs-6">${appt.name} ${getStatusBadge(appt.status)}</p>
                                <p class="mb-0 small text-muted text-break">${appt.service} &bull; ${appt.time}</p>
                            </div>
                        </div>
                    </div>
                `).join('');
            }

            // UPDATE PENDING SERVICE APPROVALS LIST
            function updatePendingServiceList() {
                const container = document.querySelector('[data-container="pending-service"]');
                if (!container || !liveData.pendingServiceApprovals?.items) return;

                if (liveData.pendingServiceApprovals.items.length === 0) {
                    container.innerHTML = '<p class="text-muted text-center py-3">No pending approvals</p>';
                    return;
                }

                container.innerHTML = liveData.pendingServiceApprovals.items.map(req => `
                    <div class="queued-item d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 gap-sm-3 mb-3 border bg-white" style="cursor: default;">
                        <div class="d-flex align-items-center gap-3">
                            <span class="material-symbols-outlined text-warning fs-5">pending_actions</span>
                            <div class="flex-grow-1">
                                <p class="mb-0 fw-bold text-dark text-break fs-6">${req.name}</p>
                                <p class="mb-0 small text-muted text-break">${req.service} &bull; ${req.date}, ${req.time}</p>
                            </div>
                        </div>
                    </div>
                `).join('');
            }

            // UPDATE CHART VIEW WITH LIVE BREAKDOWN DATA
            function updateChartView() {
                const selected = toggle.value;
                let labels = [], data = [], trendHtml = '', trendClass = 'text-secondary';

                if (selected === 'weekly' && liveData.weekly) {
                    monthSelect.classList.add('d-none');
                    labels = liveData.weekly.labels || [];
                    data = liveData.weekly.data || [];
                    countDisplay.textContent = liveData.weekly.count;
                    trendHtml = `<span class="material-symbols-outlined align-text-bottom" style="font-size:14px;">${liveData.weekly.trendIcon}</span> ${Math.abs(liveData.weekly.trend)}% vs last week`;
                    trendClass = liveData.weekly.trendClass;
                } else if (selected === 'monthly' && liveData.monthly) {
                    monthSelect.classList.add('d-none');
                    labels = liveData.monthly.labels || [];
                    data = liveData.monthly.data || [];
                    countDisplay.textContent = liveData.monthly.count;
                    trendHtml = `<span class="material-symbols-outlined align-text-bottom" style="font-size:14px;">${liveData.monthly.trendIcon}</span> ${Math.abs(liveData.monthly.trend)}% vs last month`;
                    trendClass = liveData.monthly.trendClass;
                } else if (selected === 'yearly' && liveData.yearly) {
                    monthSelect.classList.add('d-none');
                    labels = liveData.yearly.labels || [];
                    data = liveData.yearly.data || [];
                    countDisplay.textContent = liveData.yearly.count;
                    trendHtml = `<span class="material-symbols-outlined align-text-bottom" style="font-size:14px;">${liveData.yearly.trendIcon}</span> ${Math.abs(liveData.yearly.trend)}% vs last year`;
                    trendClass = liveData.yearly.trendClass;
                } else {
                    trendHtml = 'Loading...';
                }

                trendDisplay.innerHTML = trendHtml;
                trendDisplay.className = `fw-bold ${trendClass}`;

                bookingsChart.data.labels = labels;
                bookingsChart.data.datasets[0].data = data;
                bookingsChart.update();
            }

            toggle.addEventListener('change', updateChartView);
            monthSelect.addEventListener('change', updateChartView);

            fetchLiveData();
            setInterval(fetchLiveData, 5000);

            // Done and cancel appointment
            const doneModal = new bootstrap.Modal(document.getElementById('doneModal'));
            const confirmCancelModal = new bootstrap.Modal(document.getElementById('confirmCancelModal'));
            const cancelSuccessModal = new bootstrap.Modal(document.getElementById('cancelSuccessModal'));
            const confirmCancelBtn = document.getElementById('confirmCancelBtn');

            document.querySelectorAll('.btn-appt-done').forEach(btn => {
                btn.addEventListener('click', function () {
                    doneModal.show();
                    setTimeout(fetchLiveData, 1000);
                });
            });
            document.querySelectorAll('.btn-appt-cancel').forEach(btn => {
                btn.addEventListener('click', function () { confirmCancelModal.show(); });
            });
            if (confirmCancelBtn) {
                confirmCancelBtn.addEventListener('click', function () {
                    confirmCancelModal.hide();
                    cancelSuccessModal.show();
                    setTimeout(fetchLiveData, 1000);
                });
            }
        });
    </script>
</body>
</html>