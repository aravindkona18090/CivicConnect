<?php
session_start();
include("../db/connection.php");
require __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch Metrics Counts
$total_cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM problems"))['c'] ?? 0;
$pending_cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM problems WHERE status='Pending'"))['c'] ?? 0;
$in_prog_cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM problems WHERE status='In Progress'"))['c'] ?? 0;
$completed_cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM problems WHERE status='Completed'"))['c'] ?? 0;
$workers_cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM workers"))['c'] ?? 0;

// Fetch Active Management Queue Items
$combined_query = "SELECT * FROM problems WHERE status IN ('Pending', 'In Progress') ORDER BY created_at DESC";
$problems_result = mysqli_query($conn, $combined_query);
$adminName = $_SESSION['adminname'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CivicConnect Command Center</title>
    <!-- Google Fonts & Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --brand-primary: #0284c7;
            --brand-emerald: #10b981;
            --brand-gradient: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            --bg-canvas: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --shadow-sm: 0 2px 6px rgba(0,0,0,0.03);
            --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.06);
            --radius-lg: 16px;
            --radius-md: 12px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-canvas);
            color: var(--text-main);
            min-height: 100vh;
            padding-bottom: 60px;
        }

        /* SLEEK NAVBAR */
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
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.25);
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
            transition: all 0.2s ease;
        }
        .logout-btn:hover { background: #fca5a5; }

        .container {
            max-width: 1280px;
            margin: 28px auto;
            padding: 0 24px;
        }

        .welcome-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .welcome-text h1 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .welcome-text p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 4px;
        }

        .admin-badge-chip {
            background: #ffffff;
            border: 1px solid var(--border);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow-sm);
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .metric-card {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            padding: 18px 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--brand-primary);
        }

        .metric-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }
        .icon-blue { background: #e0f2fe; color: #0284c7; }
        .icon-amber { background: #fef3c7; color: #d97706; }
        .icon-purple { background: #f3e8ff; color: #9333ea; }
        .icon-emerald { background: #d1fae5; color: #059669; }

        .metric-val { font-size: 1.5rem; font-weight: 800; line-height: 1.1; }
        .metric-lbl { font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-top: 2px; }

        .toolbar {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }

        .filter-tabs {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .tab-btn {
            padding: 8px 14px;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .tab-btn:hover { color: var(--text-main); }
        .tab-btn.active {
            background: #e0f2fe;
            color: #0369a1;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 6px 12px;
            width: 280px;
        }

        .search-box input {
            border: none;
            background: transparent;
            outline: none;
            font-family: inherit;
            font-size: 0.85rem;
            width: 100%;
        }

        .table-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8fafc;
            padding: 14px 18px;
            text-align: left;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 16px 18px;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
            vertical-align: middle;
        }

        tr:last-child td { border-bottom: none; }
        tr.complaint-row { cursor: pointer; transition: background 0.15s ease; }
        tr.complaint-row:hover { background: #f0f9ff; }

        .badge-status-Pending { background: #fef3c7; color: #b45309; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; display: inline-block; }
        .badge-status-InProgress { background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; display: inline-block; }

        .badge-severity { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; display: inline-block; }
        .sev-Critical { background: #fee2e2; color: #991b1b; }
        .sev-High { background: #ffedd5; color: #c2410c; }
        .sev-Medium { background: #fef3c7; color: #b45309; }
        .sev-Low { background: #d1fae5; color: #065f46; }

        .btn-inspect {
            background: #0284c7;
            color: #ffffff;
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.2);
        }
        .btn-inspect:hover { background: #0369a1; transform: translateY(-1px); }

        .empty-state {
            padding: 50px;
            text-align: center;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="navbar">
        <a href="admindashboard.php" class="nav-brand">
            <div class="brand-badge"><i class="fa-solid fa-handshake-angle"></i></div>
            <div class="brand-title">Civic<span>Connect</span></div>
        </a>

        <nav class="nav-links">
            <a href="admindashboard.php" class="nav-link active"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
            <a href="allproblems.php" class="nav-link"><i class="fa-solid fa-list-check"></i> All Complaints</a>
            <a href="completedproblems.php" class="nav-link"><i class="fa-solid fa-circle-check"></i> Completed Archive</a>
            <a href="workers.php" class="nav-link"><i class="fa-solid fa-hard-hat"></i> Field Officers</a>
        </nav>

        <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </header>

    <div class="container">

        <!-- WELCOME BAR -->
        <div class="welcome-bar">
            <div class="welcome-text">
                <h1><i class="fa-solid fa-user-shield" style="color:var(--brand-primary);"></i> Municipal Command Center</h1>
                <p>Click any problem entry to open full inspection details, proof comparison, and location maps.</p>
            </div>
            <div class="admin-badge-chip">
                <i class="fa-solid fa-circle-check" style="color:var(--brand-emerald);"></i> Logged in as: <?php echo htmlspecialchars($adminName); ?>
            </div>
        </div>

        <!-- CLICKABLE METRICS GRID -->
        <div class="metrics-grid">
            <div class="metric-card" onclick="filterQueue('all')">
                <div class="metric-icon icon-blue"><i class="fa-solid fa-folder-open"></i></div>
                <div>
                    <div class="metric-val"><?php echo $total_cnt; ?></div>
                    <div class="metric-lbl">Total Complaints</div>
                </div>
            </div>

            <div class="metric-card" onclick="filterQueue('Pending')">
                <div class="metric-icon icon-amber"><i class="fa-solid fa-hourglass-half"></i></div>
                <div>
                    <div class="metric-val"><?php echo $pending_cnt; ?></div>
                    <div class="metric-lbl">Pending Verification</div>
                </div>
            </div>

            <div class="metric-card" onclick="filterQueue('In Progress')">
                <div class="metric-icon icon-purple"><i class="fa-solid fa-gears"></i></div>
                <div>
                    <div class="metric-val"><?php echo $in_prog_cnt; ?></div>
                    <div class="metric-lbl">In Progress Work</div>
                </div>
            </div>

            <div class="metric-card" onclick="window.location.href='workers.php'">
                <div class="metric-icon icon-emerald"><i class="fa-solid fa-hard-hat"></i></div>
                <div>
                    <div class="metric-val"><?php echo $workers_cnt; ?></div>
                    <div class="metric-lbl">Field Officers</div>
                </div>
            </div>
        </div>

        <!-- TOOLBAR: FILTER TABS & SEARCH -->
        <div class="toolbar">
            <div class="filter-tabs">
                <button class="tab-btn active" id="tab-all" onclick="filterQueue('all')">
                    <i class="fa-solid fa-layer-group"></i> Active Queue (<?php echo mysqli_num_rows($problems_result); ?>)
                </button>
                <button class="tab-btn" id="tab-pending" onclick="filterQueue('Pending')">
                    <i class="fa-solid fa-clock"></i> Pending Only (<?php echo $pending_cnt; ?>)
                </button>
                <button class="tab-btn" id="tab-inprogress" onclick="filterQueue('In Progress')">
                    <i class="fa-solid fa-spinner"></i> In Progress Only (<?php echo $in_prog_cnt; ?>)
                </button>
            </div>

            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass" style="color:var(--text-muted);"></i>
                <input type="text" id="searchInput" placeholder="Search by ID, Category, Street..." onkeyup="searchTable()">
            </div>
        </div>

        <!-- CLEAN SUMMARY TABLE -->
        <div class="table-card">
            <table id="complaintsTable">
                <thead>
                    <tr>
                        <th>ID & Category</th>
                        <th>Complaint Overview & Location</th>
                        <th>AI Severity</th>
                        <th>Status</th>
                        <th>Reported Date</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($problems_result) > 0): ?>
                        <?php while ($p = mysqli_fetch_assoc($problems_result)): 
                            $sev = $p['ai_severity'] ?? 'Medium';
                            $st = $p['status'];
                            $st_badge = str_replace(' ', '', $st);
                        ?>
                            <tr class="complaint-row" data-status="<?php echo htmlspecialchars($st); ?>" onclick="window.location.href='problem_details.php?id=<?php echo $p['id']; ?>'">
                                <!-- ID & CATEGORY -->
                                <td>
                                    <div style="font-weight:800; color:#475569; font-size:0.85rem;">#<?php echo $p['id']; ?></div>
                                    <div style="font-weight:700; color:var(--brand-primary); font-size:0.85rem; margin-top:2px;">
                                        <?php echo htmlspecialchars($p['category']); ?>
                                    </div>
                                </td>

                                <!-- OVERVIEW & LOCATION -->
                                <td>
                                    <div style="font-weight:700; color:var(--text-main); margin-bottom:4px;">
                                        <?php echo htmlspecialchars($p['description']); ?>
                                    </div>
                                    <div style="font-size:0.8rem; color:var(--text-muted);">
                                        <i class="fa-solid fa-location-dot" style="color:var(--brand-primary);"></i>
                                        <?php echo htmlspecialchars($p['street'] . ($p['city'] ? ', ' . $p['city'] : '')); ?>
                                    </div>
                                </td>

                                <!-- SEVERITY -->
                                <td>
                                    <span class="badge-severity sev-<?php echo $sev; ?>"><?php echo htmlspecialchars($sev); ?></span>
                                </td>

                                <!-- STATUS -->
                                <td>
                                    <span class="badge-status-<?php echo $st_badge; ?>"><?php echo htmlspecialchars($st); ?></span>
                                </td>

                                <!-- REPORTED DATE -->
                                <td style="font-size:0.8rem; color:var(--text-muted);">
                                    <?php echo date('M d, Y', strtotime($p['created_at'])); ?>
                                </td>

                                <!-- ACTION LINK -->
                                <td style="text-align:right;">
                                    <a href="problem_details.php?id=<?php echo $p['id']; ?>" class="btn-inspect" onclick="event.stopPropagation()">
                                        <i class="fa-solid fa-magnifying-glass"></i> Inspect & Review
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <i class="fa-solid fa-clipboard-check" style="font-size:2.5rem; color:#cbd5e1; margin-bottom:8px;"></i>
                                <h3>No Active Complaints Found</h3>
                                <p style="font-size:0.85rem; margin-top:4px;">All submitted complaints have been completed or reviewed.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <script>
        function filterQueue(status) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            
            if (status === 'all') {
                document.getElementById('tab-all').classList.add('active');
            } else if (status === 'Pending') {
                document.getElementById('tab-pending').classList.add('active');
            } else if (status === 'In Progress') {
                document.getElementById('tab-inprogress').classList.add('active');
            }

            const rows = document.querySelectorAll('.complaint-row');
            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                if (status === 'all' || rowStatus === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function searchTable() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('.complaint-row');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(input)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
