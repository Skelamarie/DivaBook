<?php
/**
 * navbar.php — Shared top navigation bar + mobile sidebar
 *
 * Usage: Set $activePage BEFORE including this file.
 * Accepted values: 'overview' | 'bookings' | 'customization'
 */

if (!isset($settings)) {
    require_once __DIR__ . '/customization_helper.php'; 
}

if (!isset($logoPath)) {
    $logoPath = !empty($settings['logo_path']) ? $settings['logo_path'] : '';
}

$activePage = $activePage ?? '';

// Helper: return 'active' class string when page matches
function navActive(string $page, string $current): string
{
    return $page === $current ? ' active' : '';
}
function sidebarActive(string $page, string $current): string
{
    return $page === $current
        ? ' active bg-light fw-bold'
        : ' text-secondary fw-semibold';
}
?>

<style>
    /* ── Shared Navbar Styles ─────────────────────────────── */
    .db-navbar {
        background-color: #ffffff;
        border-bottom: 1px solid #e5e7eb;
        height: 80px;
        display: flex;
        align-items: center;
        top: 0 !important;
        margin-top: 0 !important;
    }

    .db-navbar .navbar-brand {
        color: #5D2D1B !important; /* Premium Brown */
        font-size: 1.75rem;
        font-style: italic;
        font-weight: 500;
        font-family: 'Noto Serif', serif;
        margin-right: 3rem;
        text-decoration: none;
    }

    .db-navbar .nav-link {
        color: #6B7280;
        font-weight: 500;
        font-size: 1rem;
        padding: 0.5rem 1rem !important;
        margin: 0 0.5rem;
        transition: all 0.2s;
        border-bottom: 3px solid transparent;
    }

    .db-navbar .nav-link:hover {
        color: #5D2D1B;
    }

    .db-navbar .nav-link.active {
        color: #5D2D1B !important;
        border-bottom: 3px solid #5D2D1B;
        font-weight: 600;
    }

    /* Search */
    .db-search-container {
        position: relative;
        margin-right: 1rem;
    }

    .db-search-container .material-symbols-outlined {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #6B7280;
        font-size: 20px;
    }

    .db-search-input {
        background-color: #F3F4F1;
        border: none;
        border-radius: 25px;
        padding: 10px 20px 10px 44px;
        font-size: 0.9rem;
        width: 280px;
        color: #374151;
    }

    .db-search-input:focus {
        outline: none;
        box-shadow: 0 0 0 2px var(--primary-container, #064E3B);
    }

    /* Icon buttons */
    .db-icon-btn {
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

    .db-icon-btn:hover {
        background-color: #f8f9fa;
    }

    /* Profile/Logo Image styling */
    .db-profile-img {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid var(--outline-variant, #c0c9bc);
        cursor: pointer;
        background-color: #ffffff;
    }

    /* ── Responsive Adjustments ───────────────────────────── */
    @media (max-width: 992px) {
        .db-search-input {
            width: 200px;
        }
    }

    @media (max-width: 768px) {
        .db-navbar {
            height: 70px;
        }

        .db-navbar .navbar-brand {
            font-size: 1.4rem;
            margin-right: 0;
            flex: 1;
            text-align: center;
        }

        .db-search-container {
            display: none;
        }

        .db-navbar .container-fluid {
            justify-content: space-between;
        }
    }

    .db-sidebar .list-group-item.active {
        color: #5D2D1B !important;
        background-color: #FDF4F1 !important;
        border-left: 4px solid #5D2D1B !important;
    }
</style>

<nav class="navbar fixed-top navbar-expand-md db-navbar">
    <div class="container-fluid px-3 px-md-5">

        <button class="navbar-toggler border-0 shadow-none d-md-none p-0" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#dbMobileSidebar" aria-controls="dbMobileSidebar">
            <span class="material-symbols-outlined fs-1" style="color: #5D2D1B;">menu</span>
        </button>

        <a class="navbar-brand d-flex align-items-center justify-content-center justify-content-md-start m-0 gap-2"
            href="admin_overview.php">
            <span>DivaBook</span>
        </a>

        <div class="collapse navbar-collapse d-none d-md-flex justify-content-between" id="dbDesktopNav">
        <div class="navbar-nav mx-auto">
            <a class="nav-link<?= navActive('overview', $activePage) ?>" href="admin_overview.php">Overview</a>
            <a class="nav-link<?= navActive('bookings', $activePage) ?>" href="BnA.php">Bookings</a>
            <a class="nav-link<?= navActive('customization', $activePage) ?>"
                href="admin_customization.php">Customization</a>
        </div>

        <div class="d-flex align-items-center gap-2 gap-lg-3">
            <div class="db-search-container d-none d-lg-block">
                <span class="material-symbols-outlined">search</span>
                <input class="db-search-input" placeholder="Search appointments..." type="text">
            </div>
            
            <div class="dropdown">
                <button class="db-icon-btn d-none d-sm-flex" type="button" id="powerDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Power Options">
                    <span class="material-symbols-outlined text-danger">power_settings_new</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="powerDropdown" style="border: 1px solid #e5e7eb !important;">
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 text-danger fw-medium" href="logout.php">
                            <span class="material-symbols-outlined fs-5">logout</span>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
            
            <?php if (!empty($logoPath)): ?>
                <img alt="Salon profile" class="db-profile-img ms-1" src="<?= htmlspecialchars($logoPath) ?>">
            <?php else: ?>
                <img alt="Default profile" class="db-profile-img ms-1" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAuK6uzrvEbGxN78TuaFwzZbnA7Lox7u0gfyEsC1VMhO-Ij8INcZNvYRMtcERJ92_iADOymCGz4t3na25iVXJFdJHIo888ZQ9ysMbqMpoaU0u2vReJU2mdGyK3IFMMVCz4Dy5w4C5iZ9kX9f06wuzql_RzuI9T5Npq6e2IfZlP44XigaVJ4Z510H0iSmdk5nVdXbLs1lrM3HU-LRgl537h8mYrlP2clVjZeZJLqSEPIfccZ8qEoftx4kLlClzL4s2bJk52SOWVt3aE">
            <?php endif; ?>
        </div>
    </div>

        <div class="d-md-none d-flex align-items-center">
            <?php if (!empty($logoPath)): ?>
                <img alt="Salon profile" class="db-profile-img" src="<?= htmlspecialchars($logoPath) ?>">
            <?php else: ?>
                <img alt="Default profile" class="db-profile-img" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAuK6uzrvEbGxN78TuaFwzZbnA7Lox7u0gfyEsC1VMhO-Ij8INcZNvYRMtcERJ92_iADOymCGz4t3na25iVXJFdJHIo888ZQ9ysMbqMpoaU0u2vReJU2mdGyK3IFMMVCz4Dy5w4C5iZ9kX9f06wuzql_RzuI9T5Npq6e2IfZlP44XigaVJ4Z510H0iSmdk5nVdXbLs1lrM3HU-LRgl537h8mYrlP2clVjZeZJLqSEPIfccZ8qEoftx4kLlClzL4s2bJk52SOWVt3aE">
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="offcanvas offcanvas-start db-sidebar" tabindex="-1" id="dbMobileSidebar"
    aria-labelledby="dbMobileSidebarLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold" id="dbMobileSidebarLabel"
            style="color: var(--primary-container, #064E3B); font-style: italic; font-family: var(--font-heading, serif);">
            DivaBook
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-0 py-3 d-flex flex-column justify-content-between">
        <div class="list-group list-group-flush border-0">
            <a href="admin_overview.php"
                class="list-group-item list-group-item-action border-0 px-4 py-3<?= sidebarActive('overview', $activePage) ?>">
                Overview / Main Dashboard
            </a>
            <a href="BnA.php"
                class="list-group-item list-group-item-action border-0 px-4 py-3<?= sidebarActive('bookings', $activePage) ?>">
                Bookings
            </a>
            <a href="admin_customization.php"
                class="list-group-item list-group-item-action border-0 px-4 py-3<?= sidebarActive('customization', $activePage) ?>">
                Customization
            </a>
        </div>

        <div class="list-group list-group-flush border-0 mt-auto">
            <hr class="mx-4 my-2">
            <a href="logout.php" class="list-group-item list-group-item-action border-0 px-4 py-3 text-danger d-flex align-items-center gap-2">
                <span class="material-symbols-outlined fs-5">logout</span>
                Logout
            </a>
        </div>
    </div>
</div>