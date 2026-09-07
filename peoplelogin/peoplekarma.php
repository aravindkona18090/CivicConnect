<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../config.php';

session_start();

if (isset($_POST['language'])) {
    $_SESSION['language'] = $_POST['language'];
}
$selectedLang = $_SESSION['language'] ?? 'en';
include("../lang.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['username'] ?? 'Citizen';

// 1. Fetch user data
$uQuery = mysqli_query($conn, "SELECT name, username, email, profile_pic, created_at FROM people WHERE id='$user_id'");
$user = mysqli_fetch_assoc($uQuery);
$displayName = !empty($user['name']) ? $user['name'] : (!empty($user['username']) ? $user['username'] : 'Citizen');
$initial = strtoupper(substr($displayName, 0, 1));

$avatarSrc = '';
if (!empty($user['profile_pic'])) {
    if (strpos($user['profile_pic'], 'http') === 0) {
        $avatarSrc = $user['profile_pic'];
    } elseif (file_exists("../" . $user['profile_pic'])) {
        $avatarSrc = "../" . $user['profile_pic'];
    }
}

// 2. Calculate Karma Metrics
$total_reported = 0;
$total_resolved = 0;
$total_inprogress = 0;

$r1 = mysqli_query($conn, "SELECT status, category FROM problems WHERE user_id='$user_id'");
$category_counts = [];
while ($row = mysqli_fetch_assoc($r1)) {
    $total_reported++;
    if ($row['status'] === 'Completed') $total_resolved++;
    if ($row['status'] === 'In Progress') $total_inprogress++;
    $cat = $row['category'] ?: 'General';
    $category_counts[$cat] = ($category_counts[$cat] ?? 0) + 1;
}

// Points: 50 per report + 150 per resolved + 25 per in-progress
$karmaScore = ($total_reported * 50) + ($total_resolved * 150) + ($total_inprogress * 25);

// Determine Tier & Badge
$tierName = "Active Citizen";
$tierBadgeClass = "badge-bronze";
$tierIcon = "fa-medal";
$nextTierScore = 500;
$progressPercent = min(100, round(($karmaScore / 500) * 100));

if ($karmaScore >= 2500) {
    $tierName = "Smart City Champion";
    $tierBadgeClass = "badge-champion";
    $tierIcon = "fa-crown";
    $nextTierScore = "MAX";
    $progressPercent = 100;
} elseif ($karmaScore >= 1200) {
    $tierName = "Community Hero";
    $tierBadgeClass = "badge-hero";
    $tierIcon = "fa-shield-halved";
    $nextTierScore = 2500;
    $progressPercent = min(100, round((($karmaScore - 1200) / 1300) * 100));
} elseif ($karmaScore >= 500) {
    $tierName = "Civic Guardian";
    $tierBadgeClass = "badge-guardian";
    $tierIcon = "fa-award";
    $nextTierScore = 1200;
    $progressPercent = min(100, round((($karmaScore - 500) / 700) * 100));
}

// 3. Top City Leaderboard
$leaderboardQuery = "SELECT p.name, p.username, p.profile_pic,
                            COUNT(pr.id) as total_reports,
                            SUM(CASE WHEN pr.status='Completed' THEN 1 ELSE 0 END) as resolved_reports
                     FROM people p
                     LEFT JOIN problems pr ON p.id = pr.user_id
                     GROUP BY p.id
                     ORDER BY (COUNT(pr.id)*50 + SUM(CASE WHEN pr.status='Completed' THEN 1 ELSE 0 END)*150) DESC
                     LIMIT 5";
$leaderboardRes = mysqli_query($conn, $leaderboardQuery);
?>
<!DOCTYPE html>
<html lang="<?php echo $selectedLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Civic Karma & Badges - CivicConnect</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-gradient: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            --emerald-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --amber-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --purple-gradient: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
            --bg-slate: #f8fafc;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --card-bg: #ffffff;
            --radius: 18px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-slate);
            color: var(--text-dark);
            min-height: 100vh;
        }

        /* Navbar */
        .portal-nav {
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--text-dark);
            font-size: 1.25rem;
            font-weight: 800;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .nav-links a {
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.92rem;
            transition: color 0.2s;
        }
        .nav-links a:hover, .nav-links a.active {
            color: var(--primary);
        }

        /* Main Container */
        .karma-container {
            max-width: 1100px;
            margin: 32px auto;
            padding: 0 20px;
        }

        /* Hero Banner */
        .karma-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            border-radius: var(--radius);
            padding: 36px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }
        .karma-hero::after {
            content: '';
            position: absolute;
            right: -40px;
            bottom: -40px;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(37,99,235,0.25) 0%, transparent 70%);
            border-radius: 50%;
        }
        .hero-left h1 {
            font-size: 1.85rem;
            font-weight: 800;
            margin-bottom: 8px;
        }
        .hero-left p {
            color: #94a3b8;
            font-size: 0.95rem;
            max-width: 500px;
        }
        .hero-score-badge {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 20px 32px;
            text-align: center;
        }
        .hero-score-badge .score-val {
            font-size: 2.5rem;
            font-weight: 900;
            color: #38bdf8;
            line-height: 1;
        }
        .hero-score-badge .score-lbl {
            font-size: 0.78rem;
            font-weight: 700;
            color: #e2e8f0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 6px;
        }

        /* Grid */
        .karma-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 24px;
        }

        .karma-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }
        .card-header h3 {
            font-size: 1.15rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Tier Status */
        .tier-status-box {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }
        .tier-icon-wrap {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #ffffff;
        }
        .badge-bronze { background: var(--amber-gradient); }
        .badge-guardian { background: var(--primary-gradient); }
        .badge-hero { background: var(--purple-gradient); }
        .badge-champion { background: var(--emerald-gradient); }

        .progress-bar-wrap {
            background: #f1f5f9;
            border-radius: 12px;
            height: 10px;
            overflow: hidden;
            margin: 8px 0;
        }
        .progress-bar-fill {
            background: var(--primary-gradient);
            height: 100%;
            border-radius: 12px;
            transition: width 0.6s ease;
        }

        /* Badges Unlocked Grid */
        .badges-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }
        .badge-item {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 14px;
            text-align: center;
            transition: transform 0.2s;
        }
        .badge-item:hover { transform: translateY(-2px); }
        .badge-item.locked { opacity: 0.45; filter: grayscale(1); }
        .badge-item i { font-size: 1.6rem; margin-bottom: 8px; }
        .badge-item h5 { font-size: 0.82rem; font-weight: 700; }
        .badge-item small { font-size: 0.72rem; color: var(--text-muted); display: block; margin-top: 2px; }

        /* Leaderboard */
        .leaderboard-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .leaderboard-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }
        .lead-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .lead-rank {
            font-size: 0.95rem;
            font-weight: 800;
            width: 24px;
            color: var(--text-muted);
        }
        .lead-rank.top-1 { color: #eab308; }
        .lead-rank.top-2 { color: #94a3b8; }
        .lead-rank.top-3 { color: #b45309; }

        .lead-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 700;
        }
        .lead-info h6 { font-size: 0.88rem; font-weight: 700; }
        .lead-info span { font-size: 0.75rem; color: var(--text-muted); }
        .lead-score { font-size: 0.92rem; font-weight: 800; color: var(--primary); }

        @media (max-width: 768px) {
            .karma-grid { grid-template-columns: 1fr; }
            .karma-hero { flex-direction: column; gap: 20px; text-align: center; }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="portal-nav">
        <a href="peopledashboard.php" class="nav-brand">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M12 2L3 7V12C3 17.52 6.84 22.74 12 24C17.16 22.74 21 17.52 21 12V7L12 2Z" fill="#2563eb"/>
            </svg>
            CivicConnect
        </a>
        <div class="nav-links">
            <a href="peopledashboard.php"><i class="fa-solid fa-house"></i> Home</a>
            <a href="peoplemyproblems.php"><i class="fa-solid fa-list-check"></i> My Complaints</a>
            <a href="peoplekarma.php" class="active"><i class="fa-solid fa-trophy"></i> Civic Karma</a>
            <a href="peopleprofile.php"><i class="fa-solid fa-user"></i> Profile</a>
            <a href="../logout.php" style="color:#ef4444;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </nav>

    <div class="karma-container">
        <!-- Hero Card -->
        <div class="karma-hero">
            <div class="hero-left">
                <h1>Civic Karma & Impact Score 🏆</h1>
                <p>Earn points and level up by reporting local problems, helping municipal field workers, and making your neighborhood cleaner and safer.</p>
            </div>
            <div class="hero-score-badge">
                <div class="score-val"><?php echo number_format($karmaScore); ?></div>
                <div class="score-lbl">Total Karma Points</div>
            </div>
        </div>

        <!-- Grid Details -->
        <div class="karma-grid">
            <!-- Left: Tier & Badges -->
            <div>
                <div class="karma-card" style="margin-bottom:24px;">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-award" style="color:var(--primary);"></i> Current Civic Status</h3>
                        <span style="font-size:0.82rem; font-weight:700; color:var(--text-muted);">Level Status</span>
                    </div>

                    <div class="tier-status-box">
                        <div class="tier-icon-wrap <?php echo $tierBadgeClass; ?>">
                            <i class="fa-solid <?php echo $tierIcon; ?>"></i>
                        </div>
                        <div style="flex:1;">
                            <h4 style="font-size:1.15rem; font-weight:800;"><?php echo $tierName; ?></h4>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill" style="width:<?php echo $progressPercent; ?>%;"></div>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size:0.78rem; color:var(--text-muted); font-weight:600;">
                                <span><?php echo $karmaScore; ?> XP</span>
                                <span><?php echo $nextTierScore === 'MAX' ? 'Max Rank Reached' : "Next: {$nextTierScore} XP"; ?></span>
                            </div>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; margin-top:20px; text-align:center;">
                        <div style="background:#f8fafc; padding:12px; border-radius:12px; border:1px solid var(--border-color);">
                            <div style="font-size:1.3rem; font-weight:800; color:#2563eb;"><?php echo $total_reported; ?></div>
                            <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700;">Reported</div>
                        </div>
                        <div style="background:#f8fafc; padding:12px; border-radius:12px; border:1px solid var(--border-color);">
                            <div style="font-size:1.3rem; font-weight:800; color:#059669;"><?php echo $total_resolved; ?></div>
                            <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700;">Resolved (+150 XP)</div>
                        </div>
                        <div style="background:#f8fafc; padding:12px; border-radius:12px; border:1px solid var(--border-color);">
                            <div style="font-size:1.3rem; font-weight:800; color:#d97706;"><?php echo $total_inprogress; ?></div>
                            <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700;">In Progress</div>
                        </div>
                    </div>
                </div>

                <!-- Badges Showcase -->
                <div class="karma-card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-shield-cat" style="color:#10b981;"></i> Citizen Achievements</h3>
                        <span style="font-size:0.82rem; font-weight:700; color:var(--text-muted);"><?php echo ($total_reported > 0 ? 'Active' : 'Unearned'); ?></span>
                    </div>

                    <div class="badges-grid">
                        <div class="badge-item <?php echo $total_reported >= 1 ? '' : 'locked'; ?>">
                            <i class="fa-solid fa-flag" style="color:#2563eb;"></i>
                            <h5>First Report</h5>
                            <small>Report 1 issue</small>
                        </div>
                        <div class="badge-item <?php echo $total_resolved >= 1 ? '' : 'locked'; ?>">
                            <i class="fa-solid fa-check-double" style="color:#10b981;"></i>
                            <h5>Problem Solver</h5>
                            <small>1 Issue Resolved</small>
                        </div>
                        <div class="badge-item <?php echo $total_reported >= 5 ? '' : 'locked'; ?>">
                            <i class="fa-solid fa-road" style="color:#f59e0b;"></i>
                            <h5>Road Watcher</h5>
                            <small>5 Issues Logged</small>
                        </div>
                        <div class="badge-item <?php echo $total_resolved >= 5 ? '' : 'locked'; ?>">
                            <i class="fa-solid fa-trophy" style="color:#8b5cf6;"></i>
                            <h5>City Hero</h5>
                            <small>5 Resolved</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Leaderboard -->
            <div>
                <div class="karma-card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-ranking-star" style="color:#f59e0b;"></i> Top Citizens</h3>
                        <span style="font-size:0.8rem; font-weight:700; color:var(--primary);">Bengaluru</span>
                    </div>

                    <div class="leaderboard-list">
                        <?php 
                        $rank = 1;
                        while ($lb = mysqli_fetch_assoc($leaderboardRes)): 
                            $lbName = !empty($lb['name']) ? $lb['name'] : (!empty($lb['username']) ? $lb['username'] : 'Citizen');
                            $lbScore = ($lb['total_reports'] * 50) + ($lb['resolved_reports'] * 150);
                            $lbInit = strtoupper(substr($lbName, 0, 1));
                        ?>
                            <div class="leaderboard-item">
                                <div class="lead-left">
                                    <span class="lead-rank top-<?php echo $rank; ?>">#<?php echo $rank; ?></span>
                                    <div class="lead-avatar"><?php echo $lbInit; ?></div>
                                    <div class="lead-info">
                                        <h6><?php echo htmlspecialchars($lbName); ?></h6>
                                        <span><?php echo $lb['resolved_reports']; ?> Fixed • <?php echo $lb['total_reports']; ?> Reports</span>
                                    </div>
                                </div>
                                <div class="lead-score"><?php echo number_format($lbScore); ?> pts</div>
                            </div>
                        <?php 
                            $rank++;
                        endwhile; 
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../includes/chatbot_widget.php"); ?>
</body>
</html>
