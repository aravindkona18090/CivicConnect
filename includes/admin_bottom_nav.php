<?php
// includes/admin_bottom_nav.php - Modern Native Mobile App Bottom Navigation Bar for Admin Portal
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
/* ========================================================
   ADMIN MODERN MOBILE BOTTOM TAB BAR
   ======================================================== */
.admin-mobile-bottom-nav {
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

.admin-mobile-bottom-nav-inner {
    display: flex;
    align-items: center;
    justify-content: space-around;
    height: 100%;
    max-width: 480px;
    margin: 0 auto;
}

.admin-tab-item {
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

.admin-tab-item i {
    font-size: 1.15rem;
    transition: transform 0.2s ease, color 0.2s ease;
}

.admin-tab-item.active {
    color: #0284c7;
}

.admin-tab-item.active i {
    transform: translateY(-2px);
    color: #0284c7;
}

.admin-tab-item.active::after {
    content: '';
    position: absolute;
    bottom: 4px;
    width: 18px;
    height: 3px;
    background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%);
    border-radius: 4px;
}

.admin-tab-item:hover {
    color: #0284c7;
}

.admin-tab-logout {
    color: #ef4444;
}
.admin-tab-logout:hover {
    color: #dc2626;
}

@media (max-width: 768px) {
    .admin-mobile-bottom-nav {
        display: block !important;
    }
    body {
        padding-bottom: 74px !important;
    }
}
</style>

<!-- Admin Mobile Bottom Navigation Bar -->
<nav class="admin-mobile-bottom-nav" aria-label="Admin Mobile Navigation Bar">
    <div class="admin-mobile-bottom-nav-inner">
        <a href="admindashboard.php" class="admin-tab-item <?php echo $current_page === 'admindashboard.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-pie"></i>
            <span>Dashboard</span>
        </a>

        <a href="allproblems.php" class="admin-tab-item <?php echo $current_page === 'allproblems.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-list-check"></i>
            <span>Complaints</span>
        </a>

        <a href="completedproblems.php" class="admin-tab-item <?php echo $current_page === 'completedproblems.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-circle-check"></i>
            <span>Archive</span>
        </a>

        <a href="workers.php" class="admin-tab-item <?php echo $current_page === 'workers.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-hard-hat"></i>
            <span>Officers</span>
        </a>

        <a href="../logout.php" class="admin-tab-item admin-tab-logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </div>
</nav>
