<?php
session_start();
include("../db/connection.php");
include("../lang.php");

require __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle completion toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_completed'])) {
    $problem_id = intval($_POST['problem_id']);
    $completed = $_POST['value'] === '1' ? 1 : 0;

    if ($completed) {
        mysqli_query($conn, "UPDATE problems SET status='Completed', completed_at=NOW() WHERE id='$problem_id'");

        $user_query = mysqli_query($conn, "
            SELECT p.description, p.category, p.street, u.username, u.email
            FROM problems p
            JOIN people u ON p.user_id = u.id
            WHERE p.id='$problem_id'
        ");

        if ($user_query && mysqli_num_rows($user_query) > 0) {
            $row = mysqli_fetch_assoc($user_query);
            try {
                if (class_exists('Resend')) {
                    $resend = Resend::client(getenv('RESEND_API_KEY'));
                    $resend->emails->send([
                        'from' => 'CivicConnect <onboarding@resend.dev>',
                        'to' => [$row['email']],
                        'subject' => '✅ CivicConnect - Problem Completed',
                        'html' => "<h2>Dear {$row['username']},</h2><p>Your reported problem ({$row['description']}) has been <strong>Marked as Completed</strong>.</p><p>Thank you for helping improve your community! 🎉</p>"
                    ]);
                }
            } catch (\Exception $e) {
                error_log("Resend Error: " . $e->getMessage());
            }
        }
    } else {
        mysqli_query($conn, "UPDATE problems SET status='In Progress', completed_at=NULL WHERE id='$problem_id'");
    }

    header("Location: pendingcompletions.php");
    exit();
}

// Fetch In Progress complaints
$query = "SELECT * FROM problems WHERE status='In Progress' ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
$adminName = $_SESSION['adminname'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="<?php echo $selectedLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Verification - Admin Command Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --brand-primary: #0284c7;
            --brand-emerald: #10b981;
            --bg-canvas: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
            --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.08);
            --radius: 16px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-canvas);
            color: var(--text-main);
            min-height: 100vh;
            padding-bottom: 60px;
        }

        .navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-sm);
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand-badge {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #10b981 0%, #0284c7 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.25rem;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        }

        .brand-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .brand-title span { color: var(--brand-primary); }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link {
            padding: 8px 16px;
            border-radius: 10px;
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link:hover, .nav-link.active {
            background: #f0f9ff;
            color: var(--brand-primary);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .lang-select {
            padding: 8px 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
            font-weight: 600;
            font-family: inherit;
            background: #fff;
            color: var(--text-main);
            cursor: pointer;
            outline: none;
        }

        .logout-btn {
            background: #fee2e2;
            color: #dc2626;
            padding: 8px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .container {
            max-width: 1280px;
            margin: 32px auto;
            padding: 0 24px;
        }

        .page-header {
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .page-header h1 {
            font-size: 1.6rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .complaint-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            padding: 24px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 160px 1fr 200px;
            gap: 24px;
            align-items: center;
        }

        .photo-wrapper {
            width: 100%;
            height: 120px;
            border-radius: 12px;
            overflow: hidden;
            background: #f1f5f9;
        }
        .photo-wrapper img { width: 100%; height: 100%; object-fit: cover; }

        .btn-toggle {
            padding: 10px 18px;
            border-radius: 10px;
            border: none;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-mark-done { background: #10b981; color: #fff; }
        .btn-mark-done:hover { background: #059669; }
        .btn-reopen { background: #fef3c7; color: #b45309; }

        .empty-state {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 60px;
            text-align: center;
            border: 1px dashed var(--border);
            color: var(--text-muted);
        }
    </style>
</head>
<body>

    <header class="navbar">
        <a href="admindashboard.php" class="nav-brand">
            <div class="brand-badge"><i class="fa-solid fa-handshake-angle"></i></div>
            <div class="brand-title">Civic<span>Connect</span></div>
        </a>

        <nav class="nav-links">
            <a href="admindashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
            <a href="pendingcompletions.php" class="nav-link active"><i class="fa-solid fa-clock"></i> In Progress Work</a>
            <a href="allproblems.php" class="nav-link"><i class="fa-solid fa-list-check"></i> All Complaints</a>
            <a href="completedproblems.php" class="nav-link"><i class="fa-solid fa-circle-check"></i> Completed</a>
        </nav>

        <div class="nav-actions">
            <form method="POST" style="margin:0;">
                <select name="language" onchange="this.form.submit()" class="lang-select" title="Select Language">
                    <option value="en" <?php if ($selectedLang=='en') echo 'selected'; ?>>🌐 English</option>
                    <option value="te" <?php if ($selectedLang=='te') echo 'selected'; ?>>🌐 తెలుగు (Telugu)</option>
                    <option value="hn" <?php if ($selectedLang=='hn') echo 'selected'; ?>>🌐 हिंदी (Hindi)</option>
                    <option value="kn" <?php if ($selectedLang=='kn') echo 'selected'; ?>>🌐 ಕನ್ನಡ (Kannada)</option>
                </select>
            </form>
            <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h1><i class="fa-solid fa-gears" style="color:var(--brand-primary);"></i> Active In Progress Work Orders</h1>
        </div>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($p = mysqli_fetch_assoc($result)): ?>
                <div class="complaint-card">
                    <div class="photo-wrapper">
                        <?php if (!empty($p['photo'])): ?>
                            <img src="<?php echo htmlspecialchars($p['photo']); ?>" alt="Photo">
                        <?php else: ?>
                            <div style="display:flex; height:100%; align-items:center; justify-content:center; color:#94a3b8; font-size:0.8rem;">No Photo</div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <div style="display:flex; gap:10px; margin-bottom:8px;">
                            <span style="font-weight:800; color:#475569;">ID #<?php echo $p['id']; ?></span>
                            <span style="font-weight:700; color:var(--brand-primary);"><?php echo htmlspecialchars($p['category']); ?></span>
                        </div>
                        <div style="font-weight:700; font-size:1.05rem; margin-bottom:6px;"><?php echo htmlspecialchars($p['description']); ?></div>
                        <div style="font-size:0.85rem; color:#64748b;"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($p['street'] . ', ' . $p['city']); ?></div>
                    </div>

                    <div>
                        <form method="POST">
                            <input type="hidden" name="problem_id" value="<?php echo $p['id']; ?>">
                            <input type="hidden" name="toggle_completed" value="1">
                            <input type="hidden" name="value" value="1">
                            <button type="submit" class="btn-toggle btn-mark-done">
                                <i class="fa-solid fa-check-double"></i> Mark Completed
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-circle-check" style="font-size:3rem; color:#cbd5e1; margin-bottom:12px;"></i>
                <h3>No Active In Progress Work Orders</h3>
                <p>All allocated issues are currently completed.</p>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
