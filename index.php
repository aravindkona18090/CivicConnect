<?php
session_start();
include("db/connection.php");
include("lang.php");

if (isset($_POST['language'])) {
    $_SESSION['language'] = $_POST['language'];
}
$selectedLang = $_SESSION['language'] ?? 'en';

// Calculate live database metrics
$totalReported = 0;
$totalResolved = 0;
$totalInProgress = 0;
$aiScannedCount = 0;

$res1 = mysqli_query($conn, "SELECT * FROM problems");
if($res1) $totalReported = mysqli_num_rows($res1);

$res2 = mysqli_query($conn, "SELECT * FROM problems WHERE status='Completed'");
if($res2) $totalResolved = mysqli_num_rows($res2);

$res3 = mysqli_query($conn, "SELECT * FROM problems WHERE status='In Progress'");
if($res3) $totalInProgress = mysqli_num_rows($res3);

$res4 = mysqli_query($conn, "SELECT * FROM problems WHERE photo IS NOT NULL AND photo != ''");
if($res4) $aiScannedCount = mysqli_num_rows($res4);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CivicConnect - Smart Civic Issue Reporting Platform</title>

<!-- Font Awesome & Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
:root {
    --primary: #2563eb;
    --primary-hover: #1d4ed8;
    --primary-gradient: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
    --emerald-gradient: linear-gradient(135deg, #059669 0%, #10b981 100%);
    --bg-slate: #f8fafc;
    --card-bg: #ffffff;
    --text-dark: #0f172a;
    --text-muted: #64748b;
    --border-color: #e2e8f0;
    --shadow-light: 0 10px 25px -5px rgba(15, 23, 42, 0.06);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: var(--bg-slate);
    color: var(--text-dark);
    line-height: 1.6;
    overflow-x: hidden;
}

/* ---------------- Header ---------------- */
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(12px);
    padding: 18px 40px;
    border-bottom: 1px solid var(--border-color);
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}

.logo-group {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
}

.logo-badge {
    width: 42px;
    height: 42px;
    background: var(--primary-gradient);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.logo-title {
    font-size: 1.4rem;
    font-weight: 800;
    background: linear-gradient(135deg, #0f172a 0%, #2563eb 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -0.02em;
}

.top-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

.lang-select {
    padding: 8px 14px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    font-family: inherit;
    font-weight: 600;
    font-size: 0.9rem;
    outline: none;
    background: white;
    cursor: pointer;
}

.nav-link-btn {
    background: #eff6ff;
    color: #2563eb;
    padding: 10px 18px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.9rem;
    border: 1px solid #bfdbfe;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.nav-link-btn:hover {
    background: #2563eb;
    color: white;
}

/* ---------------- Hero Banner ---------------- */
.hero-section {
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #2563eb 100%);
    color: white;
    padding: 80px 24px 100px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.08) 0%, transparent 60%);
    pointer-events: none;
}

.hero-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    padding: 8px 20px;
    border-radius: 30px;
    font-size: 0.9rem;
    font-weight: 700;
    margin-bottom: 24px;
}

.hero-title {
    font-size: 3.2rem;
    font-weight: 800;
    line-height: 1.15;
    letter-spacing: -0.03em;
    max-width: 900px;
    margin: 0 auto 20px;
}

.hero-title span {
    background: linear-gradient(135deg, #60a5fa 0%, #a7f3d0 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.hero-subtitle {
    font-size: 1.2rem;
    color: #cbd5e1;
    max-width: 680px;
    margin: 0 auto 40px;
}

.hero-actions {
    display: flex;
    justify-content: center;
    gap: 18px;
    flex-wrap: wrap;
}

.btn-hero-primary {
    background: var(--emerald-gradient);
    color: white;
    padding: 18px 36px;
    border-radius: 14px;
    font-size: 1.1rem;
    font-weight: 800;
    text-decoration: none;
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-hero-primary:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 12px 25px rgba(16, 185, 129, 0.5);
}

.btn-hero-secondary {
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(10px);
    color: white;
    padding: 18px 36px;
    border-radius: 14px;
    font-size: 1.1rem;
    font-weight: 700;
    text-decoration: none;
    border: 1px solid rgba(255, 255, 255, 0.3);
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-hero-secondary:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-3px);
}

/* ---------------- Stats Section ---------------- */
.stats-container {
    max-width: 1100px;
    margin: -50px auto 60px;
    padding: 0 24px;
    position: relative;
    z-index: 10;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
}

.stat-card {
    background: #ffffff;
    border: 1px solid var(--border-color);
    border-radius: 20px;
    padding: 28px 24px;
    text-align: center;
    box-shadow: var(--shadow-xl);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-6px);
}

.stat-icon {
    width: 50px;
    height: 50px;
    margin: 0 auto 14px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}

.icon-blue { background: rgba(37, 99, 235, 0.1); color: #2563eb; }
.icon-amber { background: rgba(217, 119, 6, 0.1); color: #d97706; }
.icon-emerald { background: rgba(16, 185, 129, 0.1); color: #059669; }
.icon-purple { background: rgba(147, 51, 234, 0.1); color: #9333ea; }

.stat-number {
    font-size: 2.4rem;
    font-weight: 800;
    color: var(--text-dark);
    line-height: 1;
    margin-bottom: 6px;
}

.stat-label {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-muted);
}

/* ---------------- Features Section ---------------- */
.features-section {
    max-width: 1100px;
    margin: 0 auto 80px;
    padding: 0 24px;
}

.section-title {
    text-align: center;
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 12px;
    letter-spacing: -0.02em;
}

.section-subtitle {
    text-align: center;
    color: var(--text-muted);
    font-size: 1.05rem;
    margin-bottom: 48px;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 28px;
}

.feature-card {
    background: #ffffff;
    border: 1px solid var(--border-color);
    border-radius: 20px;
    padding: 32px;
    box-shadow: var(--shadow-light);
    transition: all 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-xl);
    border-color: rgba(37, 99, 235, 0.3);
}

.feature-icon-box {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 20px;
}

.feature-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 10px;
}

.feature-desc {
    color: var(--text-muted);
    font-size: 0.95rem;
    line-height: 1.6;
}

/* ---------------- Workflow Section ---------------- */
.workflow-section {
    background: #ffffff;
    border-top: 1px solid var(--border-color);
    border-bottom: 1px solid var(--border-color);
    padding: 80px 24px;
}

.workflow-container {
    max-width: 1100px;
    margin: 0 auto;
}

.workflow-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 24px;
    margin-top: 40px;
}

.step-card {
    background: var(--bg-slate);
    border-radius: 18px;
    padding: 28px 20px;
    text-align: center;
    border: 1px solid #e2e8f0;
    position: relative;
}

.step-num {
    width: 36px;
    height: 36px;
    background: var(--primary-gradient);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    margin: 0 auto 16px;
    font-size: 0.95rem;
}

.step-title {
    font-weight: 700;
    font-size: 1.05rem;
    margin-bottom: 8px;
}

.step-desc {
    font-size: 0.85rem;
    color: var(--text-muted);
}

/* ---------------- Carousel Section ---------------- */
.carousel-section {
    max-width: 1100px;
    margin: 80px auto;
    padding: 0 24px;
}

.carousel-wrapper {
    position: relative;
    overflow: hidden;
    border-radius: 20px;
    box-shadow: var(--shadow-xl);
}

.carousel-track {
    display: flex;
    gap: 20px;
    transition: transform 0.5s ease-in-out;
}

.carousel-slide {
    min-width: 280px;
    height: 240px;
    border-radius: 16px;
    overflow: hidden;
    flex-shrink: 0;
    position: relative;
}

.carousel-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Footer */
footer {
    background: #0f172a;
    color: #94a3b8;
    text-align: center;
    padding: 32px 24px;
    font-size: 0.9rem;
    border-top: 1px solid #1e293b;
}

@media (max-width: 768px) {
    .hero-title { font-size: 2.2rem; }
    header { padding: 14px 20px; }
}
</style>
</head>
<body>

<!-- Header -->
<header>
    <a href="index.php" class="logo-group">
        <div class="logo-badge" style="background:linear-gradient(135deg, #10b981 0%, #0284c7 100%); box-shadow:0 4px 14px rgba(16, 185, 129, 0.35);"><i class="fa-solid fa-handshake"></i></div>
        <span class="logo-title">Civic<span style="color:#0284c7;">Connect</span></span>
    </a>
    
    <div class="top-right">
        <form method="POST" style="display:inline-flex; align-items:center; gap:6px;">
            <label style="font-weight:700; font-size:0.9rem; color:var(--text-dark);"><i class="fa-solid fa-globe" style="color:var(--primary);"></i></label>
            <select name="language" onchange="this.form.submit()" class="lang-select" title="Change Language">
                <option value="en" <?php if($selectedLang=='en') echo 'selected'; ?>>🌐 English</option>
                <option value="te" <?php if($selectedLang=='te') echo 'selected'; ?>>🌐 తెలుగు (Telugu)</option>
                <option value="hn" <?php if($selectedLang=='hn') echo 'selected'; ?>>🌐 हिंदी (Hindi)</option>
                <option value="kn" <?php if($selectedLang=='kn') echo 'selected'; ?>>🌐 ಕನ್ನಡ (Kannada)</option>
            </select>
        </form>
        <a href="logindecide.html" class="nav-link-btn">
            <i class="fa-solid fa-shield-halved"></i> <?php echo $lang[$selectedLang]['login_register'] ?? 'Select Portal'; ?>
        </a>
    </div>
</header>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-pill">
        <i class="fa-solid fa-bolt"></i> <?php echo $lang[$selectedLang]['hero_pill']; ?>
    </div>
    <h1 class="hero-title"><?php echo $lang[$selectedLang]['hero_title']; ?></h1>
    <p class="hero-subtitle"><?php echo $lang[$selectedLang]['hero_subtitle']; ?></p>
    
    <div class="hero-actions">
        <a href="peoplelogin/login.php" class="btn-hero-primary">
            <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $lang[$selectedLang]['report_issue']; ?>
        </a>
        <a href="logindecide.html" class="btn-hero-secondary">
            <i class="fa-solid fa-user-gear"></i> <?php echo $lang[$selectedLang]['portal_access']; ?> <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
</section>

<!-- Stats Counters -->
<div class="stats-container">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon icon-blue"><i class="fa-solid fa-clipboard-list"></i></div>
            <div class="stat-number"><?php echo $totalReported; ?></div>
            <div class="stat-label"><?php echo $lang[$selectedLang]['reported_issues']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-amber"><i class="fa-solid fa-person-digging"></i></div>
            <div class="stat-number"><?php echo $totalInProgress; ?></div>
            <div class="stat-label"><?php echo $lang[$selectedLang]['in_progress_issues']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-emerald"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-number"><?php echo $totalResolved; ?></div>
            <div class="stat-label"><?php echo $lang[$selectedLang]['resolved_issues']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-purple"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
            <div class="stat-number"><?php echo $aiScannedCount; ?></div>
            <div class="stat-label"><?php echo $lang[$selectedLang]['ai_scanned_issues']; ?></div>
        </div>
    </div>
</div>

<!-- Features Section -->
<section class="features-section">
    <h2 class="section-title"><?php echo $lang[$selectedLang]['features_title']; ?></h2>
    <p class="section-subtitle"><?php echo $lang[$selectedLang]['features_subtitle']; ?></p>

    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon-box icon-purple"><i class="fa-solid fa-camera-retro"></i></div>
            <h3 class="feature-title"><?php echo $lang[$selectedLang]['feature1_title']; ?></h3>
            <p class="feature-desc"><?php echo $lang[$selectedLang]['feature1_desc']; ?></p>
        </div>

        <div class="feature-card">
            <div class="feature-icon-box icon-blue"><i class="fa-solid fa-map-location-dot"></i></div>
            <h3 class="feature-title"><?php echo $lang[$selectedLang]['feature2_title']; ?></h3>
            <p class="feature-desc"><?php echo $lang[$selectedLang]['feature2_desc']; ?></p>
        </div>

        <div class="feature-card">
            <div class="feature-icon-box icon-emerald"><i class="fa-solid fa-language"></i></div>
            <h3 class="feature-title"><?php echo $lang[$selectedLang]['feature3_title']; ?></h3>
            <p class="feature-desc"><?php echo $lang[$selectedLang]['feature3_desc']; ?></p>
        </div>
    </div>
</section>

<!-- Workflow Infographic -->
<section class="workflow-section">
    <div class="workflow-container">
        <h2 class="section-title"><?php echo $lang[$selectedLang]['workflow_title']; ?></h2>
        <p class="section-subtitle"><?php echo $lang[$selectedLang]['workflow_subtitle']; ?></p>

        <div class="workflow-steps">
            <div class="step-card">
                <div class="step-num">1</div>
                <h4 class="step-title"><?php echo $lang[$selectedLang]['step1_title']; ?></h4>
                <p class="step-desc"><?php echo $lang[$selectedLang]['step1_desc']; ?></p>
            </div>
            <div class="step-card">
                <div class="step-num">2</div>
                <h4 class="step-title"><?php echo $lang[$selectedLang]['step2_title']; ?></h4>
                <p class="step-desc"><?php echo $lang[$selectedLang]['step2_desc']; ?></p>
            </div>
            <div class="step-card">
                <div class="step-num">3</div>
                <h4 class="step-title"><?php echo $lang[$selectedLang]['step3_title']; ?></h4>
                <p class="step-desc"><?php echo $lang[$selectedLang]['step3_desc']; ?></p>
            </div>
            <div class="step-card">
                <div class="step-num">4</div>
                <h4 class="step-title"><?php echo $lang[$selectedLang]['step4_title']; ?></h4>
                <p class="step-desc"><?php echo $lang[$selectedLang]['step4_desc']; ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Carousel Section -->
<section class="carousel-section">
    <h2 class="section-title"><?php echo $lang[$selectedLang]['community_reports_title']; ?></h2>
    <p class="section-subtitle"><?php echo $lang[$selectedLang]['community_reports_subtitle']; ?></p>

    <div class="carousel-wrapper">
        <div class="carousel-track" id="carouselTrack">
            <div class="carousel-slide"><img src="images/istockphoto-502561495-612x612.jpg" alt="Pothole"></div>
            <div class="carousel-slide"><img src="images/istockphoto-1074493878-612x612.jpg" alt="Garbage"></div>
            <div class="carousel-slide"><img src="images/istockphoto-155382228-612x612.jpg" alt="Streetlight"></div>
            <div class="carousel-slide"><img src="images/istockphoto-1437819039-612x612.jpg" alt="Drainage"></div>
            <div class="carousel-slide"><img src="images/istockphoto-1414347687-612x612.jpg" alt="Road Crack"></div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer>
    <p><?php echo $lang[$selectedLang]['footer']; ?></p>
</footer>

<script>
// Auto Carousel Track
const track = document.getElementById('carouselTrack');
let index = 0;
setInterval(() => {
    index++;
    if(index >= track.children.length - 2) index = 0;
    track.style.transform = `translateX(${-index * 300}px)`;
}, 3000);
</script>
<?php include("includes/chatbot_widget.php"); ?>
</body>
</html>
