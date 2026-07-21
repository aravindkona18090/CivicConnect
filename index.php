<?php
session_start();
include("db/connection.php");
include("lang.php");

if (isset($_POST['language'])) {
    $_SESSION['language'] = $_POST['language'];
}
$selectedLang = $_SESSION['language'] ?? 'en';

$totalReported = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM problems"));
$totalResolved = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM problems WHERE status='Completed'"));
$totalInProgress = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM problems WHERE status='In Progress'"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

<style>
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    background: #e8f0fe;
    color: #1a1a1a;
}

/* Header */
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    padding: 15px 40px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    position: sticky;
    top: 0;
    z-index: 1000;
}

header .logo {
    font-size: 24px;
    font-weight: 700;
    color: #0056b3;
}

header .top-right {
    display: flex;
    align-items: center;
    gap: 15px;
}

header select,
header a {
    background: #0056b3;
    border: none;
    color: #fff;
    padding: 8px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
}

header select:hover,
header a:hover {
    background: #00408a;
}

/* Unique Report Button */
.report-btn-container {
    text-align: center;
    margin: 30px 0;
}

.report-btn {
    background: #0056b3;
    color: #fff;
    padding: 20px 40px;
    border-radius: 12px;
    font-size: 22px;
    font-weight: 700;
    text-decoration: none;
    box-shadow: 0 6px 18px rgba(0,0,0,0.2);
    transition: 0.3s;
    display: inline-block;
}

.report-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
}

.report-btn:active {
    transform: scale(0.95);
}

/* Info Section */
.info-section {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 30px;
    margin: 40px 50px;
}

.info-card {
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    flex: 1 1 300px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
}

.info-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.info-card h3 {
    margin-bottom: 15px;
    color: #0056b3;
    font-weight: 700;
}

.info-card ul,
.info-card ol {
    margin: 0;
    padding-left: 20px;
    line-height: 1.8;
}

/* Stats Section */
.stats-section {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 40px;
    margin: 50px 20px;
}

.stat {
    background: #fff;
    color: #000;
    padding: 25px 40px;
    border-radius: 12px;
    text-align: center;
    width: 200px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
}

.stat:hover {
    transform: translateY(-8px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.stat h2 {
    font-size: 32px;
    margin: 0;
    color: #0056b3;
    font-weight: 700;
}

.stat p {
    margin-top: 10px;
    font-size: 15px;
}

/* Carousel */
.carousel-container {
    position: relative;
    width: 90%;
    margin: 40px auto;
    overflow: hidden;
}

.carousel-track {
    display: flex;
    gap: 20px;
    transition: transform 0.5s ease-in-out;
}

.card {
    min-width: 220px;
    height: 220px;
    background: #fff;
    flex-shrink: 0;
    border-radius: 12px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    overflow: hidden;
}

.card:hover {
    transform: scale(1.05);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
}

.card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Arrows */
.carousel-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    font-size: 36px;
    color: #0056b3;
    background: rgba(0,0,0,0.1);
    padding: 8px;
    border-radius: 50%;
    cursor: pointer;
    user-select: none;
    z-index: 10;
}
.carousel-arrow.left { left: 10px; }
.carousel-arrow.right { right: 10px; }

/* Footer */
footer {
    text-align: center;
    padding: 20px;
    background: #f0f0f0;
    font-size: 14px;
    color: #555;
}
</style>
</head>
<body>

<!-- Header -->
<header>
    <div class="logo">Civic Connect</div>
    <div class="top-right">
        <form method="POST" style="display:inline;">
            <select name="language" onchange="this.form.submit()">
                <option value="en" <?php if($selectedLang=='en') echo 'selected'; ?>>English</option>
                <option value="te" <?php if($selectedLang=='te') echo 'selected'; ?>>తెలుగు</option>
                <option value="hn" <?php if($selectedLang=='hn') echo 'selected'; ?>>हिंदी</option>
                <option value="kn" <?php if($selectedLang=='kn') echo 'selected'; ?>>ಕನ್ನಡ</option>
            </select>
        </form>
        <a href="logindecide.html"><?php echo $lang[$selectedLang]['login_register']; ?></a>
    </div>
</header>

<!-- Report Button -->
<div class="report-btn-container">
    <a href="peoplelogin/login.php" class="report-btn" id="reportBtn">
        ⚠️ <?php echo $lang[$selectedLang]['report_issue']; ?>
    </a>
</div>

<!-- Info Section -->
<section class="info-section">
    <div class="info-card">
        <h3>👥 For Citizens:</h3>
        <ul>
            <li>✅ Just take a photo – We handle the rest</li>
            <li>✅ Automatic location detection from GPS or image metadata</li>
            <li>✅ Track your issues with unique IDs</li>
            <li>✅ Smart duplicate prevention – similar issues get grouped</li>
        </ul>
    </div>
    <div class="info-card">
        <h3>⚙️ Workflow:</h3>
        <ol>
            <li>Upload Photo → Citizen takes photo</li>
            <li>AI Processing → Extract location, find authority</li>
            <li>Admin Review → Verify details, approve actions</li>
            <li>Auto-Contact → Email sent to civic authority</li>
            <li>Track Progress → Monitor until resolution</li>
        </ol>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="stat">
        <h2><?php echo $totalReported; ?></h2>
        <p><?php echo $lang[$selectedLang]['reported_issues']; ?></p>
    </div>
    <div class="stat">
        <h2><?php echo $totalResolved; ?></h2>
        <p><?php echo $lang[$selectedLang]['resolved_issues']; ?></p>
    </div>
    <div class="stat">
        <h2><?php echo $totalInProgress; ?></h2>
        <p><?php echo $lang[$selectedLang]['in_progress_issues']; ?></p>
    </div>
</section>

<!-- Carousel Section -->
<div class="carousel-container">
    <div class="carousel-arrow left" id="prev">&#10094;</div>
    <div class="carousel-arrow right" id="next">&#10095;</div>
    <div class="carousel-track" id="carouselTrack">
        <div class="card"><img src="images/istockphoto-502561495-612x612.jpg" alt="1"></div>
        <div class="card"><img src="images/istockphoto-1074493878-612x612.jpg" alt="2"></div>
        <div class="card"><img src="images/istockphoto-155382228-612x612.jpg" alt="3"></div>
        <div class="card"><img src="images/istockphoto-1437819039-612x612.jpg" alt="4"></div>
        <div class="card"><img src="images/istockphoto-1414347687-612x612.jpg" alt="5"></div>
        <div class="card"><img src="images/istockphoto-1489051648-612x612.jpg" alt="6"></div>
    </div>
</div>

<!-- Footer -->
<footer>
    <p><?php echo $lang[$selectedLang]['footer']; ?></p>
</footer>

<script>
// Carousel Logic
const track = document.getElementById('carouselTrack');
const cards = Array.from(track.children);
const prev = document.getElementById('prev');
const next = document.getElementById('next');

let index = 0;
const cardWidth = cards[0].offsetWidth + 20;

function updateCarousel() {
    track.style.transform = `translateX(${-index * cardWidth}px)`;
}

next.addEventListener('click', () => {
    index++;
    if(index >= cards.length) index = 0;
    updateCarousel();
});

prev.addEventListener('click', () => {
    index--;
    if(index < 0) index = cards.length - 1;
    updateCarousel();
});

setInterval(() => {
    index++;
    if(index >= cards.length) index = 0;
    updateCarousel();
}, 3000);
</script>
</body>
</html>
