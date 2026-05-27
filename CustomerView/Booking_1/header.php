<?php
$current_file = basename($_SERVER['PHP_SELF']);
$path_prefix = ($current_file === 'P1.php') ? '../' : '';
?>


<style>
    /* VARBAR STYLE */
    .navbar { 
        display: flex !important; 
        justify-content: space-between !important; 
        align-items: center !important; 
        padding: 25px 5% !important; 
        background: #14532d !important; 
        position: sticky !important; 
        top: 0 !important; 
        z-index: 100 !important; 
        font-family: 'Inter', sans-serif !important;
        box-sizing: border-box !important;
    }
    
    .navbar * {
        box-sizing: border-box !important;
    }

    .logo a { 
        text-decoration: none !important; 
        color: #F9F8F6 !important; 
        font-family: 'Crimson Pro', serif !important; 
        font-size: 32px !important; 
        letter-spacing: 2px !important; 
    }

    .nav-links a { 
        text-decoration: none !important; 
        color: #ffffff !important; 
        font-size: 13px !important; 
        margin-right: 30px !important; 
        letter-spacing: 1.5px !important; 
        transition: color 0.3s !important; 
        border-bottom: 2px solid transparent !important;
    }

    .nav-links a.active { 
        color: #0C0A09 !important; 
        border-bottom: 2px solid #0C0A09 !important; 
    }

    .nav-links a.hover-link:hover { 
        color: #064E3B !important; 
    }

    .nav-links.right {
        display: flex !important;
        align-items: center !important;
    }

    .search-icon {
        color: #ffffff !important;
        cursor: pointer !important;
    }

    /* MOBILE SYUFF (HIDDEN IF DESKTOP) */
    .hamburger-menu {  
        display: none !important; 
    }
    .mobile-sidebar {  
        display: none !important; 
    }

    /* RESPONSIVE (MOBILE) */
    @media screen and (max-width: 768px) {
        .navbar { 
            display: flex !important; 
            flex-direction: row !important; 
            justify-content: flex-end !important; 
            align-items: center !important; 
            height: 70px !important; 
            padding: 0 6% !important; 
            position: relative !important; 
        }

        .logo { 
            position: absolute !important; 
            left: 50% !important; 
            top: 50% !important; 
            transform: translate(-50%, -50%) !important; 
            display: flex !important; 
            align-items: center !important; 
            height: auto !important; 
            margin: 0 !important; 
            padding: 0 !important; 
            z-index: 105 !important; 
        }

        .logo a { 
            font-size: 24px !important; 
            line-height: 1 !important; 
            white-space: nowrap !important; 
        }

        .nav-links, .nav-links.right { 
            display: none !important; 
        }

        /*HAMBURGER ICON*/
        .hamburger-menu { 
            display: flex !important; 
            flex-direction: column !important; 
            justify-content: space-between !important; 
            width: 24px !important; 
            height: 16px !important; 
            cursor: pointer !important; 
            z-index: 110 !important; 
            color: #ffffff !important;
        }
        
        .hamburger-menu span { 
            display: block !important; 
            height: 2px !important; 
            width: 100% !important; 
            background-color: #ffffff !important; 
            transition: 0.3s !important; 
        }

        /*MOBILE SIDEBAR */
        .mobile-sidebar { 
            display: flex !important; 
            flex-direction: column !important; 
            position: fixed !important; 
            top: 0 !important; 
            right: -100% !important; 
            width: 80% !important; 
            max-width: 300px !important; 
            height: 100vh !important; 
            background-color: #F9F8F6 !important; 
            box-shadow: -10px 0 30px rgba(0,0,0,0.1) !important; 
            padding: 80px 40px !important; 
            gap: 25px !important; 
            z-index: 120 !important; 
            transition: right 0.4s ease-in-out !important; 
        }
        
        .mobile-sidebar.open { 
            right: 0 !important; 
        }

        .mobile-sidebar a { 
            text-decoration: none !important; 
            color: #44403C !important; 
            font-size: 18px !important; 
            letter-spacing: 1.5px !important; 
            font-weight: 500 !important; 
            transition: color 0.3s !important; 
            border: none !important; 
        }
        
        .mobile-sidebar a.active, .mobile-sidebar a:hover { 
            color: #064E3B !important; 
        }

        .close-sidebar { 
            position: absolute !important; 
            top: 22px !important; 
            right: 24px !important; 
            font-size: 28px !important; 
            cursor: pointer !important; 
            color: #0C0A09 !important; 
        }
    }
</style>

<nav class="navbar">
    <div class="nav-links">
        <a href="<?php echo $path_prefix; ?>../HomePage.php" class="<?php echo ($current_page == 'home') ? 'active' : 'hover-link'; ?>">HOME</a>
        <a href="<?php echo $path_prefix; ?>../Servicepage4.php" class="<?php echo ($current_page == 'services') ? 'active' : 'hover-link'; ?>">SERVICES</a>
    </div>
    
    <div class="logo">
        <a href="<?php echo $path_prefix; ?>../HomePage.php"><i>DIVABOOK</i></a>
    </div>
    
    <div class="nav-links right">
        <a href="../ContactUs.php" class="hover-link">CONTACT US</a>
        <span class="search-icon">🔍</span>
    </div>
    <div class="hamburger-menu">
        <span></span>
        <span></span>
        <span></span>
    </div>
    <div class="mobile-sidebar">
        <div class="close-sidebar">✕</div>
        <a href="../HomePage.php" class="<?php echo ($current_page == 'home') ? 'active' : ''; ?>">HOME</a>
        <a href="../Servicepage4.php" class="<?php echo ($current_page == 'services') ? 'active' : ''; ?>">SERVICES</a>
        <a href="../ContactUs.php">CONTACT US</a>
    </div>
</nav>