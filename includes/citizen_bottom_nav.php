<?php
// includes/citizen_bottom_nav.php - Modern Native Mobile App Bottom Navigation Bar
$current_page = basename($_SERVER['PHP_SELF']);
$langCode = $selectedLang ?? 'en';
?>
<style>
/* ========================================================
   CIVICCONNECT MODERN MOBILE BOTTOM TAB BAR
   ======================================================== */
.mobile-bottom-nav {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 64px;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-top: 1.5px solid #e2e8f0;
    box-shadow: 0 -6px 25px rgba(15, 23, 42, 0.12);
    z-index: 999999;
    padding: 0 6px;
}

.mobile-bottom-nav-inner {
    display: flex;
    align-items: center;
    justify-content: space-around;
    height: 100%;
    max-width: 540px;
    margin: 0 auto;
}

.bottom-tab-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #64748b;
    font-size: 0.74rem;
    font-weight: 700;
    gap: 3px;
    padding: 6px 10px;
    border-radius: 12px;
    transition: all 0.2s ease;
    flex: 1;
    max-width: 85px;
    position: relative;
    -webkit-tap-highlight-color: transparent;
}

.bottom-tab-item i {
    font-size: 1.2rem;
    transition: transform 0.2s ease, color 0.2s ease;
}

.bottom-tab-item.active {
    color: #0284c7 !important;
}

.bottom-tab-item.active i {
    transform: translateY(-2px);
    color: #0284c7 !important;
}

.bottom-tab-item.active::after {
    content: '';
    position: absolute;
    bottom: 4px;
    width: 20px;
    height: 3.5px;
    background: linear-gradient(135deg, #10b981 0%, #0284c7 100%);
    border-radius: 4px;
}

.bottom-tab-item:hover {
    color: #0284c7;
}

.bottom-tab-logout {
    color: #ef4444 !important;
}
.bottom-tab-logout:hover {
    color: #dc2626 !important;
}

@media (max-width: 900px) {
    .mobile-bottom-nav {
        display: block !important;
    }
    body {
        padding-bottom: 80px !important;
    }
    .civicbot-launcher {
        bottom: 80px !important;
    }
    .civicbot-window {
        bottom: 145px !important;
    }
}
</style>

<!-- Mobile Bottom Navigation Bar -->
<nav class="mobile-bottom-nav" aria-label="Mobile Navigation Bar">
    <div class="mobile-bottom-nav-inner">
        <a href="peopledashboard.php" class="bottom-tab-item <?php echo $current_page === 'peopledashboard.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-house"></i>
            <span><?php echo $lang[$langCode]['dashboard'] ?? 'Home'; ?></span>
        </a>

        <a href="peoplemyproblems.php" class="bottom-tab-item <?php echo $current_page === 'peoplemyproblems.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-list-check"></i>
            <span>Complaints</span>
        </a>

        <a href="peoplekarma.php" class="bottom-tab-item <?php echo $current_page === 'peoplekarma.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-trophy"></i>
            <span>Karma</span>
        </a>

        <a href="peopleprofile.php" class="bottom-tab-item <?php echo $current_page === 'peopleprofile.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-user"></i>
            <span><?php echo $lang[$langCode]['profile'] ?? 'Profile'; ?></span>
        </a>

        <a href="../logout.php" class="bottom-tab-item bottom-tab-logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span><?php echo $lang[$langCode]['logout'] ?? 'Logout'; ?></span>
        </a>
    </div>
</nav>
