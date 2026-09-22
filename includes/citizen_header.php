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
    gap: 20px;
}

.unified-desktop-nav a {
    color: #64748b;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.92rem;
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 6px 12px;
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

/* Mobile Adjustments */
@media (max-width: 900px) {
    .unified-header {
        padding: 12px 18px;
    }
    .unified-desktop-nav {
        display: none !important;
    }
    .unified-brand-title {
        font-size: 1.25rem;
    }
    .unified-brand-badge {
        width: 34px;
        height: 34px;
    }
    .unified-lang-select {
        padding: 6px 10px;
        font-size: 0.8rem;
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

    <!-- Desktop Navigation Links (Hidden on Mobile) -->
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

    <!-- Language Selector -->
    <div class="unified-header-actions">
        <form method="POST" style="display:inline-flex; align-items:center; margin:0;">
            <select name="language" onchange="this.form.submit()" class="unified-lang-select" title="Change Language">
                <option value="en" <?php if ($langCode=='en') echo 'selected'; ?>>🌐 English</option>
                <option value="te" <?php if ($langCode=='te') echo 'selected'; ?>>🌐 తెలుగు (Telugu)</option>
                <option value="hn" <?php if ($langCode=='hn') echo 'selected'; ?>>🌐 हिंदी (Hindi)</option>
                <option value="kn" <?php if ($langCode=='kn') echo 'selected'; ?>>🌐 ಕನ್ನಡ (Kannada)</option>
            </select>
        </form>
    </div>
</header>
