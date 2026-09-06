<?php
// includes/citizen_bottom_nav.php - Ultra-Minimal Icon-Only Floating Dock with Elevated Center "+ Report" Hero Button
$current_page = basename($_SERVER['PHP_SELF']);
$langCode = $selectedLang ?? 'en';
?>
<style>
/* ========================================================
   ULTRA-MINIMAL FLOATING GLASS DOCK (ICON-ONLY)
   ======================================================== */
.mobile-floating-dock {
    display: none;
    position: fixed;
    bottom: 12px;
    left: 14px;
    right: 14px;
    max-width: 360px;
    margin: 0 auto;
    height: 56px;
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1.5px solid rgba(255, 255, 255, 0.85);
    border-radius: 34px;
    box-shadow: 0 14px 34px -6px rgba(15, 23, 42, 0.18), 0 2px 10px rgba(15, 23, 42, 0.06);
    z-index: 999999;
    padding: 0 8px;
    box-sizing: border-box;
}

.dock-inner {
    display: flex;
    align-items: center;
    justify-content: space-around;
    height: 100%;
    width: 100%;
    position: relative;
}

/* Minimal Icon-Only Dock Tab Items */
.dock-item {
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #64748b;
    flex: 1;
    height: 100%;
    border-radius: 20px;
    transition: all 0.2s ease;
    -webkit-tap-highlight-color: transparent;
    position: relative;
}

.dock-item i {
    font-size: 1.32rem;
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), color 0.2s ease;
}

.dock-item.active {
    color: #0284c7 !important;
}

.dock-item.active i {
    transform: translateY(-2px);
    color: #0284c7 !important;
}

.dock-item.active::after {
    content: '';
    position: absolute;
    bottom: 8px;
    width: 6px;
    height: 6px;
    background: linear-gradient(135deg, #10b981 0%, #0284c7 100%);
    border-radius: 50%;
    box-shadow: 0 2px 6px rgba(2, 132, 199, 0.4);
}

.dock-item:active i {
    transform: scale(0.85);
}

/* Elevated Center Hero "+ Report" Action Button */
.dock-center-action {
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    position: relative;
    top: -14px;
    flex: 1;
    -webkit-tap-highlight-color: transparent;
}

.center-action-btn {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #10b981 0%, #0284c7 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    box-shadow: 0 10px 22px rgba(16, 185, 129, 0.45), 0 0 0 4px #ffffff;
    transition: all 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.dock-center-action:active .center-action-btn,
.dock-center-action:hover .center-action-btn {
    transform: scale(1.08) translateY(-2px);
    box-shadow: 0 14px 28px rgba(16, 185, 129, 0.55), 0 0 0 4px #ffffff;
}

@media (max-width: 900px) {
    .mobile-floating-dock {
        display: block !important;
    }
    body {
        padding-bottom: 86px !important;
    }
    .civicbot-launcher {
        bottom: 82px !important;
        right: 18px !important;
    }
    .civicbot-window {
        bottom: 148px !important;
    }
}
</style>

<!-- Modern App-First Floating Dock (Minimal Icon-Only) -->
<nav class="mobile-floating-dock" aria-label="Mobile Navigation Dock">
    <div class="dock-inner">
        <!-- 1. Home / Dashboard -->
        <a href="peopledashboard.php" class="dock-item <?php echo $current_page === 'peopledashboard.php' ? 'active' : ''; ?>" title="<?php echo $lang[$langCode]['dashboard'] ?? 'Home'; ?>" aria-label="Home">
            <i class="fa-solid fa-house"></i>
        </a>

        <!-- 2. Complaints Track -->
        <a href="peoplemyproblems.php" class="dock-item <?php echo $current_page === 'peoplemyproblems.php' ? 'active' : ''; ?>" title="<?php echo $lang[$langCode]['my_problems'] ?? 'Complaints'; ?>" aria-label="Complaints">
            <i class="fa-solid fa-list-check"></i>
        </a>

        <!-- 3. CENTER HERO ELEVATED "+ REPORT" BUTTON -->
        <a href="peopledashboard.php#reportSection" class="dock-center-action" title="Report a Problem" aria-label="Report a Problem">
            <div class="center-action-btn">
                <i class="fa-solid fa-plus"></i>
            </div>
        </a>

        <!-- 4. Civic Karma -->
        <a href="peoplekarma.php" class="dock-item <?php echo $current_page === 'peoplekarma.php' ? 'active' : ''; ?>" title="Civic Karma" aria-label="Karma">
            <i class="fa-solid fa-trophy"></i>
        </a>

        <!-- 5. Profile -->
        <a href="peopleprofile.php" class="dock-item <?php echo $current_page === 'peopleprofile.php' ? 'active' : ''; ?>" title="<?php echo $lang[$langCode]['profile'] ?? 'Profile'; ?>" aria-label="Profile">
            <i class="fa-solid fa-user"></i>
        </a>
    </div>
</nav>
