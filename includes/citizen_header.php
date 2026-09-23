<?php
// includes/citizen_header.php - Unified Top Navigation Header for all Citizen Pages
$current_page = basename($_SERVER['PHP_SELF']);
$langCode = $selectedLang ?? 'en';
?>
<style>
/* ========================================================
   UNIFIED CIVICCONNECT TOP HEADER NAVBAR
   ======================================================== */
.unified-header {
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid #e2e8f0;
    padding: 14px 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
}

.unified-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
}

.unified-brand-badge {
    width: 38px;
    height: 38px;
    background: linear-gradient(135deg, #10b981 0%, #0284c7 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    flex-shrink: 0;
}

.unified-brand-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}

.unified-brand-title span {
    color: #0284c7;
}

/* Desktop Horizontal Nav Links */
.unified-desktop-nav {
    display: flex;
    align-items: center;
    gap: 16px;
}

.unified-desktop-nav a {
    color: #64748b;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.92rem;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 14px;
    border-radius: 10px;
    transition: all 0.2s ease;
}

.unified-desktop-nav a:hover {
    color: #0284c7;
    background: #f1f5f9;
}

.unified-desktop-nav a.active {
    color: #0284c7;
    font-weight: 800;
    background: #eff6ff;
}

.unified-desktop-logout {
    color: #dc2626 !important;
    background: #fee2e2 !important;
    font-weight: 700 !important;
}

.unified-desktop-logout:hover {
    background: #fecaca !important;
}

/* Header Right Actions */
.unified-header-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.unified-lang-select {
    padding: 7px 12px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    font-family: inherit;
    font-weight: 700;
    font-size: 0.85rem;
    color: #0f172a;
    background: #ffffff;
    cursor: pointer;
    outline: none;
    transition: border-color 0.2s;
}

.unified-lang-select:focus {
    border-color: #0284c7;
}

/* Mobile Nav Toggle Button */
.unified-nav-toggle {
    display: none;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: #f1f5f9;
    border: 1.5px solid #cbd5e1;
    color: #0f172a;
    font-size: 1.15rem;
    cursor: pointer;
    transition: all 0.2s ease;
    -webkit-tap-highlight-color: transparent;
}

.unified-nav-toggle:hover, .unified-nav-toggle:active {
    background: #e2e8f0;
    color: #0284c7;
}

/* Backdrop Overlay */
.unified-nav-backdrop {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 99990;
    opacity: 0;
    transition: opacity 0.25s ease;
}

.unified-nav-backdrop.is-open {
    display: block;
    opacity: 1;
}

/* Mobile Dropdown Drawer Menu */
.unified-mobile-menu {
    display: none;
    position: fixed;
    top: 64px;
    left: 12px;
    right: 12px;
    max-width: 480px;
    margin: 0 auto;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.25);
    z-index: 99999;
    padding: 16px;
    box-sizing: border-box;
}

.unified-mobile-menu.is-open {
    display: block !important;
    animation: unifiedMenuSlide 0.24s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes unifiedMenuSlide {
    from {
        opacity: 0;
        transform: translateY(-12px) scale(0.97);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.unified-mobile-menu-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 12px;
    margin-bottom: 8px;
    border-bottom: 1px solid #f1f5f9;
}

.unified-menu-heading {
    font-size: 0.8rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #94a3b8;
}

.unified-menu-close {
    background: #f1f5f9;
    border: none;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    font-size: 1.1rem;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.unified-menu-close:hover {
    background: #fee2e2;
    color: #dc2626;
}

.unified-mobile-links {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.unified-mobile-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 14px;
    text-decoration: none;
    background: transparent;
    border: 1px solid transparent;
    transition: all 0.15s ease;
    -webkit-tap-highlight-color: transparent;
}

.unified-mobile-link:hover {
    background: #f8fafc;
}

.unified-mobile-link.active {
    background: #eff6ff;
    border-color: #bfdbfe;
}

.menu-icon-pill {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.icon-blue { background: #e0f2fe; color: #0284c7; }
.icon-amber { background: #fef3c7; color: #d97706; }
.icon-emerald { background: #d1fae5; color: #059669; }
.icon-purple { background: #ede9fe; color: #7c3aed; }
.icon-rose { background: #fee2e2; color: #dc2626; }

.menu-link-text {
    flex: 1;
    min-width: 0;
}

.menu-link-title {
    font-size: 0.94rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}

.unified-mobile-link.active .menu-link-title {
    color: #0284c7;
}

.menu-link-sub {
    font-size: 0.76rem;
    color: #64748b;
    margin-top: 2px;
}

.menu-chevron {
    color: #cbd5e1;
    font-size: 0.8rem;
    transition: transform 0.15s ease, color 0.15s ease;
}

.unified-mobile-link:hover .menu-chevron {
    transform: translateX(2px);
    color: #0284c7;
}

.menu-divider {
    height: 1px;
    background: #f1f5f9;
    margin: 4px 0;
}

.link-logout {
    background: #fff5f5 !important;
}

.link-logout .menu-link-title {
    color: #dc2626 !important;
}

/* Mobile Adjustments */
@media (max-width: 900px) {
    .unified-header {
        padding: 10px 16px;
    }
    .unified-desktop-nav {
        display: none !important;
    }
    .unified-nav-toggle {
        display: inline-flex !important;
    }
    .unified-brand-title {
        font-size: 1.2rem;
    }
    .unified-brand-badge {
        width: 34px;
        height: 34px;
    }
    .unified-lang-select {
        padding: 5px 8px;
        font-size: 0.78rem;
        max-width: 110px;
    }
}
</style>

<header class="unified-header">
    <a href="peopledashboard.php" class="unified-brand">
        <div class="unified-brand-badge">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 22S20 18 20 12V5L12 2L4 5V12C4 18 12 22 12 22Z" fill="rgba(255,255,255,0.25)" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9 12L11 14L15 10" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <span class="unified-brand-title">Civic<span>Connect</span></span>
    </a>

    <!-- Desktop Navigation Links (Restored Original Text + Icons) -->
    <nav class="unified-desktop-nav">
        <a href="peopledashboard.php" class="<?php echo $current_page === 'peopledashboard.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-house"></i> <?php echo $lang[$langCode]['dashboard'] ?? 'Dashboard'; ?>
        </a>
        <a href="peoplemyproblems.php" class="<?php echo $current_page === 'peoplemyproblems.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-list-check"></i> <?php echo $lang[$langCode]['my_problems'] ?? 'My Complaints'; ?>
        </a>
        <a href="peoplekarma.php" class="<?php echo $current_page === 'peoplekarma.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-trophy"></i> Civic Karma
        </a>
        <a href="peopleprofile.php" class="<?php echo $current_page === 'peopleprofile.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-user"></i> <?php echo $lang[$langCode]['profile'] ?? 'Profile'; ?>
        </a>
        <a href="../logout.php" class="unified-desktop-logout">
            <i class="fa-solid fa-right-from-bracket"></i> <?php echo $lang[$langCode]['logout'] ?? 'Logout'; ?>
        </a>
    </nav>

    <!-- Header Right: Language Selector + Mobile Hamburger Toggle -->
    <div class="unified-header-actions">
        <form method="POST" style="display:inline-flex; align-items:center; margin:0;">
            <select name="language" onchange="this.form.submit()" class="unified-lang-select" title="Change Language">
                <option value="en" <?php if ($langCode=='en') echo 'selected'; ?>>English</option>
                <option value="te" <?php if ($langCode=='te') echo 'selected'; ?>>తెలుగు</option>
                <option value="hn" <?php if ($langCode=='hn') echo 'selected'; ?>>हिंदी</option>
                <option value="kn" <?php if ($langCode=='kn') echo 'selected'; ?>>ಕನ್ನಡ</option>
            </select>
        </form>

        <button type="button" id="unifiedNavToggle" class="unified-nav-toggle" aria-label="Toggle navigation menu" onclick="toggleUnifiedCitizenNav()">
            <i class="fa-solid fa-bars" id="unifiedNavIcon"></i>
        </button>
    </div>
</header>

<!-- Backdrop Overlay for Mobile Menu -->
<div id="unifiedNavBackdrop" class="unified-nav-backdrop" onclick="toggleUnifiedCitizenNav()"></div>

<!-- Mobile Dropdown Navigation Drawer -->
<div id="unifiedCitizenMobileMenu" class="unified-mobile-menu" role="dialog" aria-label="Navigation Menu">
    <div class="unified-mobile-menu-header">
        <span class="unified-menu-heading">Navigation</span>
        <button type="button" class="unified-menu-close" onclick="toggleUnifiedCitizenNav()" aria-label="Close menu">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <div class="unified-mobile-links">
        <a href="peopledashboard.php" class="unified-mobile-link <?php echo $current_page === 'peopledashboard.php' ? 'active' : ''; ?>">
            <div class="menu-icon-pill icon-blue"><i class="fa-solid fa-house"></i></div>
            <div class="menu-link-text">
                <div class="menu-link-title"><?php echo $lang[$langCode]['dashboard'] ?? 'Dashboard'; ?></div>
                <div class="menu-link-sub">Home & report problems</div>
            </div>
            <i class="fa-solid fa-chevron-right menu-chevron"></i>
        </a>

        <a href="peoplemyproblems.php" class="unified-mobile-link <?php echo $current_page === 'peoplemyproblems.php' ? 'active' : ''; ?>">
            <div class="menu-icon-pill icon-amber"><i class="fa-solid fa-list-check"></i></div>
            <div class="menu-link-text">
                <div class="menu-link-title"><?php echo $lang[$langCode]['my_problems'] ?? 'My Complaints'; ?></div>
                <div class="menu-link-sub">Track real-time status</div>
            </div>
            <i class="fa-solid fa-chevron-right menu-chevron"></i>
        </a>

        <a href="peoplekarma.php" class="unified-mobile-link <?php echo $current_page === 'peoplekarma.php' ? 'active' : ''; ?>">
            <div class="menu-icon-pill icon-emerald"><i class="fa-solid fa-trophy"></i></div>
            <div class="menu-link-text">
                <div class="menu-link-title">Civic Karma</div>
                <div class="menu-link-sub">Points, rank & rewards</div>
            </div>
            <i class="fa-solid fa-chevron-right menu-chevron"></i>
        </a>

        <a href="peopleprofile.php" class="unified-mobile-link <?php echo $current_page === 'peopleprofile.php' ? 'active' : ''; ?>">
            <div class="menu-icon-pill icon-purple"><i class="fa-solid fa-user"></i></div>
            <div class="menu-link-text">
                <div class="menu-link-title"><?php echo $lang[$langCode]['profile'] ?? 'My Profile'; ?></div>
                <div class="menu-link-sub">Account & security settings</div>
            </div>
            <i class="fa-solid fa-chevron-right menu-chevron"></i>
        </a>

        <div class="menu-divider"></div>

        <a href="../logout.php" class="unified-mobile-link link-logout">
            <div class="menu-icon-pill icon-rose"><i class="fa-solid fa-right-from-bracket"></i></div>
            <div class="menu-link-text">
                <div class="menu-link-title"><?php echo $lang[$langCode]['logout'] ?? 'Logout'; ?></div>
                <div class="menu-link-sub">Sign out safely</div>
            </div>
            <i class="fa-solid fa-chevron-right menu-chevron"></i>
        </a>
    </div>
</div>

<script>
function toggleUnifiedCitizenNav() {
    var menu = document.getElementById('unifiedCitizenMobileMenu');
    var backdrop = document.getElementById('unifiedNavBackdrop');
    var icon = document.getElementById('unifiedNavIcon');
    if (!menu) return;
    
    var isOpen = menu.classList.contains('is-open');
    if (isOpen) {
        menu.classList.remove('is-open');
        if (backdrop) backdrop.classList.remove('is-open');
        if (icon) {
            icon.classList.remove('fa-xmark');
            icon.classList.add('fa-bars');
        }
    } else {
        menu.classList.add('is-open');
        if (backdrop) backdrop.classList.add('is-open');
        if (icon) {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-xmark');
        }
    }
}
</script>
