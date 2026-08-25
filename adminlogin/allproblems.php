<?php
session_start();
include('../db/connection.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$query = "SELECT * FROM problems ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
$adminName = $_SESSION['adminname'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Complaints - Admin Command Center</title>
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
            padding: 14px 32px;
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
            gap: 10px;
            text-decoration: none;
        }

        .brand-badge {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #10b981 0%, #0284c7 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.1rem;
        }

        .brand-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .brand-title span { color: var(--brand-primary); }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 12px;
        }

        .nav-link {
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 700;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link:hover { color: var(--text-main); }

        .nav-link.active {
            background: #ffffff;
            color: var(--brand-primary);
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }

        .logout-btn {
            background: #fee2e2;
            color: #dc2626;
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.85rem;
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
            font-size: 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .complaints-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px;
        }

        .complaint-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            padding: 20px;
            display: grid;
            grid-template-columns: 160px 1fr 180px;
            gap: 20px;
            align-items: center;
        }

        .photo-wrapper {
            width: 100%;
            height: 120px;
            border-radius: 10px;
            overflow: hidden;
            background: #f1f5f9;
        }
        .photo-wrapper img { width: 100%; height: 100%; object-fit: cover; }

        .badge-status {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
            text-align: center;
            display: inline-block;
        }
        .status-Pending { background: #fef3c7; color: #b45309; }
        .status-InProgress { background: #e0f2fe; color: #0369a1; }
        .status-Completed { background: #d1fae5; color: #065f46; }
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
            <a href="allproblems.php" class="nav-link active"><i class="fa-solid fa-list-check"></i> All Complaints</a>
            <a href="completedproblems.php" class="nav-link"><i class="fa-solid fa-circle-check"></i> Completed Archive</a>
            <a href="workers.php" class="nav-link"><i class="fa-solid fa-hard-hat"></i> Field Officers</a>
        </nav>

        <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </header>

    <div class="container">
        <div class="page-header">
            <h1><i class="fa-solid fa-list-check" style="color:var(--brand-primary);"></i> Master Registry of All Civic Complaints</h1>
        </div>

        <div class="complaints-list">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($p = mysqli_fetch_assoc($result)): 
                    $st = str_replace(' ', '', $p['status']);
                ?>
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
                                <span style="font-weight:800; color:#475569;">#<?php echo $p['id']; ?></span>
                                <span style="font-weight:700; color:var(--brand-primary);"><?php echo htmlspecialchars($p['category']); ?></span>
                            </div>
                            <div style="font-weight:700; font-size:1.05rem; margin-bottom:6px;"><?php echo htmlspecialchars($p['description']); ?></div>
                            <div style="font-size:0.85rem; color:#64748b;"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($p['street'] . ', ' . $p['city']); ?></div>
                            <div style="font-size:0.8rem; color:#94a3b8; margin-top:4px;"><i class="fa-solid fa-clock"></i> Reported: <?php echo $p['created_at']; ?></div>
                        </div>

                        <div style="text-align:right;">
                            <span class="badge-status status-<?php echo $st; ?>"><?php echo htmlspecialchars($p['status']); ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align:center; padding:40px; color:#64748b;">No complaints found in system database.</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
