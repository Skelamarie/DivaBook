<style>
    /* --- Bottom Footer Design --- */
    .main-footer { 
        background: #F5F5F4; 
        padding: 80px 5% 30px !important; 
        font-family: 'Inter', sans-serif !important;
        box-sizing: border-box !important;
    }

    /* Sets up 3 columns for our footer content */
    .footer-grid { 
        display: grid; 
        grid-template-columns: 2fr 1fr 1fr; 
        gap: 40px; 
    }

    /* Main brand text settings */
    .footer-brand h4 { 
        font-family: 'Crimson Pro', serif !important; 
        font-size: 24px !important; 
        margin-bottom: 15px !important; 
        color: #064E3B !important;
        margin-top: 0;
    }

    .footer-brand p { 
        color: #44403C !important; 
        max-width: 300px; 
        font-size: 14px !important; 
        line-height: 1.6;
    }

    /* Column Headers */
    .footer-links h5 { 
        font-size: 12px !important; 
        letter-spacing: 2px !important; 
        margin-bottom: 20px !important; 
        color: #0C0A09 !important;
        text-transform: uppercase;
    }

    /* Links inside the columns */
    .footer-hover { 
        display: block !important; 
        text-decoration: none !important; 
        color: rgba(6, 78, 59, 0.7) !important; 
        margin-bottom: 10px !important; 
        font-size: 14px !important;
        transition: 0.3s !important; 
    }

    .footer-hover:hover { 
        color: #064E3B !important; 
        padding-left: 5px !important; 
    }

    /* The tiny copyright text at the very bottom */
    .footer-bottom { 
        margin-top: 60px !important; 
        padding-top: 20px !important; 
        border-top: 1px solid #E7E5E4 !important; 
        text-align: center !important; 
        font-size: 12px !important; 
        color: #78716C !important; 
    }

    /* --- Mobile Phone Design --- */
    @media screen and (max-width: 768px) {
        .main-footer { 
            padding: 40px 5% 30px !important; 
        }

        /* Stacks the columns on top of each other */
        .footer-grid { 
            display: flex !important; 
            flex-direction: column !important; 
            gap: 32px !important; 
            text-align: left !important; 
        }

        .footer-brand p { 
            max-width: 100% !important; 
        }

        .footer-bottom { 
            text-align: left !important; 
            margin-top: 40px !important; 
        }
    }
</style>

<footer class="main-footer">
    <div class="footer-grid">
        <div class="footer-brand">
            <h4>DivaBook</h4>
            <p>Redefining luxury beauty through seamless technology and expert craftsmanship.</p>
        </div>

        <div class="footer-links">
            <h5>EXPLORE</h5>
            <a href="HomePage.php" class="footer-hover">About Us</a>
            <a href="../AdminView/Registration.php" class="footer-hover">Partner with Us</a>
        </div>

        <div class="footer-links">
            <h5>LEGAL</h5>
            <a href="#" class="footer-hover">Privacy Policy</a>
            <a href="#" class="footer-hover">Terms of Service</a>
        </div>
    </div>
    
    <div class="footer-bottom">© 2026 DivaBook. The Luxury of Care.</div>
</footer>