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
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-top: 1px solid #e2e8f0;
    box-shadow: 0 -4px 25px rgba(15, 23, 42, 0.08);
    z-index: 9998;
    padding: 0 8px;
}

.mobile-bottom-nav-inner {
    display: flex;
    align-items: center;
    justify-content: space-around;
    height: 100%;
    max-width: 480px;
    margin: 0 auto;
}

.bottom-tab-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 700;
    gap: 3px;
    padding: 6px 10px;
    border-radius: 12px;
    transition: all 0.2s ease;
    flex: 1;
    max-width: 80px;
    position: relative;
}

.bottom-tab-item i {
    font-size: 1.15rem;
    transition: transform 0.2s ease, color 0.2s ease;
}

.bottom-tab-item.active {
    color: #0284c7;
}

.bottom-tab-item.active i {
    transform: translateY(-2px);
    color: #0284c7;
}

.bottom-tab-item.active::after {
    content: '';
    position: absolute;
    bottom: 4px;
    width: 18px;
    height: 3px;
    background: linear-gradient(135deg, #10b981 0%, #0284c7 100%);
    border-radius: 4px;
}

.bottom-tab-item:hover {
    color: #0284c7;
}

.bottom-tab-logout {
    color: #ef4444;
}
.bottom-tab-logout:hover {
    color: #dc2626;
}

@media (max-width: 768px) {
    .mobile-bottom-nav {
        display: block !important;
    }
    body {
        padding-bottom: 74px !important;
    }
    .civicbot-launcher {
        bottom: 76px !important;
    }
    .civicbot-window {
        bottom: 140px !important;
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
