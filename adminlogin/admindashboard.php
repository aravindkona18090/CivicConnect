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

// Fetch All Geotagged Problems for the City-Wide GIS Command Map
$map_query = "SELECT p.*, w.name as worker_name, w.mobile as worker_mobile 
              FROM problems p 
              LEFT JOIN workers w ON p.worker_id = w.id 
              WHERE p.lat IS NOT NULL AND p.lat != '' AND p.lat != '0' 
              ORDER BY p.id DESC";
$map_res = mysqli_query($conn, $map_query);
$map_problems = [];
while ($row = mysqli_fetch_assoc($map_res)) {
    $map_problems[] = [
        'id' => $row['id'],
        'category' => $row['category'] ?: 'General',
        'description' => $row['description'] ?: '',
        'street' => $row['street'] ?: '',
        'area' => $row['area'] ?: '',
        'city' => $row['city'] ?: '',
        'pincode' => $row['pincode'] ?: '',
        'lat' => floatval($row['lat']),
        'lng' => floatval($row['lng']),
        'photo' => $row['photo'] ?: '',
        'after_photo' => $row['after_photo'] ?: '',
        'status' => $row['status'] ?: 'Pending',
        'severity' => $row['ai_severity'] ?? 'Medium',
        'worker_name' => $row['worker_name'] ?: 'Unassigned',
        'worker_mobile' => $row['worker_mobile'] ?: '',
        'created_at' => date('M d, Y', strtotime($row['created_at']))
    ];
}
$mapProblemsJson = json_encode($map_problems, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CivicConnect Command Center</title>
    <!-- Google Fonts, Font Awesome, & Leaflet GIS Map -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

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
            padding-bottom: 70px;
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
            z-index: 1000;
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
            color: white;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
        }

        .brand-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-main);
        }
        .brand-title span { color: var(--brand-primary); }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--brand-primary);
            font-weight: 700;
        }

        .logout-btn {
            background: #fee2e2;
            color: #dc2626;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .logout-btn:hover { background: #fecaca; }

        /* CONTAINER */
        .container {
            max-width: 1240px;
            margin: 24px auto 0;
            padding: 0 24px;
        }

        /* WELCOME BAR */
        .welcome-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .welcome-text h1 {
            font-size: 1.65rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 4px;
        }

        .welcome-text p {
            font-size: 0.9rem;
            color: var(--text-muted);
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

        /* METRICS CARDS */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }

        .metric-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .icon-blue { background: rgba(2, 132, 199, 0.1); color: #0284c7; }
        .icon-amber { background: rgba(217, 119, 6, 0.1); color: #d97706; }
        .icon-purple { background: rgba(124, 58, 237, 0.1); color: #7c3aed; }
        .icon-emerald { background: rgba(5, 150, 105, 0.1); color: #059669; }

        .metric-val {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.1;
        }

        .metric-lbl {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* ========================================================
           LIVE CITY-WIDE GIS COMMAND MAP SECTION
           ======================================================== */
        .gis-command-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 28px;
        }

        .gis-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 16px;
        }

        .gis-title-group h2 {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .gis-title-group p {
            font-size: 0.86rem;
            color: var(--text-muted);
            margin-top: 3px;
        }

        .gis-filter-toolbar {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .map-filter-btn {
            background: #f8fafc;
            border: 1px solid var(--border);
            padding: 7px 14px;
            border-radius: 20px;
            font-family: inherit;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-muted);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .map-filter-btn:hover { color: var(--text-main); border-color: #cbd5e1; }
        .map-filter-btn.active {
            background: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.2);
        }

        #gisCommandMap {
            height: 480px;
            width: 100%;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            z-index: 10;
        }

        /* Map Legend Bar */
        .gis-legend-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 14px;
            padding: 10px 14px;
            background: #f8fafc;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        .legend-items {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        /* Custom Leaflet Pin Markers */
        .custom-gis-pin {
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: white;
            font-size: 13px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            border: 2.5px solid #ffffff;
            transition: transform 0.2s ease;
        }
        .custom-gis-pin:hover { transform: scale(1.2); }
        .pin-pending { background: #d97706; }
        .pin-inprogress { background: #0284c7; }
        .pin-completed { background: #059669; }

        /* Custom Leaflet Popup */
        .leaflet-popup-content-wrapper {
            border-radius: 14px;
            padding: 4px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .gis-popup-card {
            min-width: 240px;
            max-width: 280px;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .gis-popup-img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        .gis-popup-cat {
            font-size: 0.78rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .gis-popup-desc {
            font-size: 0.85rem;
            color: #334155;
            line-height: 1.35;
            margin-bottom: 8px;
        }
        .gis-popup-loc {
            font-size: 0.8rem;
            color: #64748b;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .gis-popup-btn {
            display: block;
            text-align: center;
            background: #0284c7;
            color: #ffffff;
            padding: 7px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.82rem;
            transition: background 0.2s;
        }
        .gis-popup-btn:hover { background: #0369a1; }

        /* TOOLBAR: FILTER TABS & SEARCH */
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .filter-tabs {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #ffffff;
            padding: 4px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .tab-btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-weight: 700;
            font-size: 0.85rem;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .tab-btn.active {
            background: var(--brand-primary);
            color: #ffffff;
        }

        .search-box {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 8px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: var(--shadow-sm);
            width: 300px;
        }

        .search-box input {
            border: none;
            outline: none;
            width: 100%;
            font-family: inherit;
            font-size: 0.9rem;
            color: var(--text-main);
        }

        /* TABLE CARD */
        .table-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background: #f8fafc;
            padding: 14px 20px;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
            color: var(--text-main);
            vertical-align: middle;
        }

        tr:last-child td { border-bottom: none; }

        .complaint-row {
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .complaint-row:hover {
            background: #f1f5f9;
        }

        .category-badge {
            font-weight: 800;
            font-size: 0.85rem;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }

        .complaint-id {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .location-text {
            font-size: 0.82rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 4px;
        }

        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.78rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-inprogress { background: #e0f2fe; color: #0369a1; }
        .status-completed { background: #d1fae5; color: #065f46; }

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
            <div class="brand-badge">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 22S20 18 20 12V5L12 2L4 5V12C4 18 12 22 12 22Z" fill="rgba(255,255,255,0.25)" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9 12L11 14L15 10" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
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
                <p>Monitor real-time city infrastructure issues, dispatch field officers, and inspect verified resolutions.</p>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <a href="export_reports.php" class="btn-inspect" style="background:#059669; box-shadow:0 2px 8px rgba(5,150,105,0.25);" title="Download CSV/Excel Report">
                    <i class="fa-solid fa-file-excel"></i> Export Reports
                </a>
                <div class="admin-badge-chip">
                    <i class="fa-solid fa-circle-check" style="color:var(--brand-emerald);"></i> Logged in as: <?php echo htmlspecialchars($adminName); ?>
                </div>
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
                    <div class="metric-lbl">Pending Allocation</div>
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

        <!-- MUNICIPAL EFFICIENCY KPI SUMMARY BAR -->
        <div style="background:#ffffff; border:1px solid var(--border); border-radius:var(--radius-md); padding:16px 24px; margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px; box-shadow:var(--shadow-sm);">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:42px; height:42px; border-radius:10px; background:#eff6ff; color:#2563eb; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                    <i class="fa-solid fa-gauge-high"></i>
                </div>
                <div>
                    <div style="font-size:0.8rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Resolution Efficiency</div>
                    <div style="font-size:1.15rem; font-weight:800; color:#0f172a;"><?php echo $total_cnt > 0 ? round(($completed_cnt / $total_cnt) * 100, 1) : 0; ?>% Overall Solved</div>
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:42px; height:42px; border-radius:10px; background:#f0fdf4; color:#059669; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <div style="font-size:0.8rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Avg Response Target</div>
                    <div style="font-size:1.15rem; font-weight:800; color:#0f172a;">< 24 Hours</div>
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:42px; height:42px; border-radius:10px; background:#faf5ff; color:#7c3aed; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <div style="font-size:0.8rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Active City Ward</div>
                    <div style="font-size:1.15rem; font-weight:800; color:#0f172a;">Bengaluru Central</div>
                </div>
            </div>
        </div>

        <!-- ========================================================
             LIVE CITY-WIDE GIS COMMAND MAP SECTION
             ======================================================== -->
        <div class="gis-command-card">
            <div class="gis-header-row">
                <div class="gis-title-group">
                    <h2><i class="fa-solid fa-map-location-dot" style="color:var(--brand-primary);"></i> Live City-Wide GIS Command Map</h2>
                    <p>Interactive geospatial monitoring of reported municipal issues, active field officer tasks, and resolved tickets.</p>
                </div>
                <div class="admin-badge-chip">
                    <i class="fa-solid fa-location-crosshairs" style="color:var(--brand-primary);"></i> <span id="mapCountPill"><?php echo count($map_problems); ?> Plotted Coordinates</span>
                </div>
            </div>

            <!-- Map Filter Toolbar -->
            <div class="gis-filter-toolbar">
                <span style="font-size:0.8rem; font-weight:700; color:var(--text-muted); margin-right:4px;">Filter Map:</span>
                <button type="button" class="map-filter-btn active" onclick="filterMapMarkers('all', this)">🌐 All Statuses</button>
                <button type="button" class="map-filter-btn" onclick="filterMapMarkers('Pending', this)">🟡 Pending Only</button>
                <button type="button" class="map-filter-btn" onclick="filterMapMarkers('In Progress', this)">🔵 In Progress</button>
                <button type="button" class="map-filter-btn" onclick="filterMapMarkers('Completed', this)">🟢 Resolved</button>
                <span style="color:#cbd5e1;">|</span>
                <button type="button" class="map-filter-btn" onclick="filterMapCategory('Road', this)">🛣️ Roads</button>
                <button type="button" class="map-filter-btn" onclick="filterMapCategory('Sanitation', this)">🚯 Sanitation</button>
                <button type="button" class="map-filter-btn" onclick="filterMapCategory('Electricity', this)">⚡ Electricity</button>
                <button type="button" class="map-filter-btn" onclick="filterMapCategory('Drainage', this)">💧 Drainage</button>
            </div>

            <!-- Leaflet Container -->
            <div id="gisCommandMap"></div>

            <!-- Legend Bar -->
            <div class="gis-legend-bar">
                <div class="legend-items">
                    <span><span class="legend-dot" style="background:#d97706;"></span> <strong>Pending</strong> (Waiting for Officer)</span>
                    <span><span class="legend-dot" style="background:#0284c7;"></span> <strong>In Progress</strong> (Officer on Site)</span>
                    <span><span class="legend-dot" style="background:#059669;"></span> <strong>Completed</strong> (Fixed with Proof)</span>
                </div>
                <div>
                    <span><i class="fa-solid fa-circle-info" style="color:var(--brand-primary);"></i> Click any pin to view photo proof and dispatch options.</span>
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
                        <th>Severity</th>
                        <th>Assigned Officer</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($problems_result) > 0): ?>
                        <?php while ($p = mysqli_fetch_assoc($problems_result)): 
                            $cat = $p['category'] ?? 'General';
                            $icon = 'fa-triangle-exclamation';
                            $iconColor = '#64748b';
                            if (stripos($cat, 'road') !== false || stripos($cat, 'pothole') !== false) { $icon = 'fa-road'; $iconColor = '#0284c7'; }
                            elseif (stripos($cat, 'sanitation') !== false || stripos($cat, 'garbage') !== false) { $icon = 'fa-trash-can'; $iconColor = '#059669'; }
                            elseif (stripos($cat, 'elect') !== false || stripos($cat, 'light') !== false) { $icon = 'fa-bolt'; $iconColor = '#d97706'; }
                            elseif (stripos($cat, 'water') !== false || stripos($cat, 'drain') !== false) { $icon = 'fa-faucet-drip'; $iconColor = '#0891b2'; }

                            $status = $p['status'] ?? 'Pending';
                            $statusClass = 'status-pending';
                            $statusIcon = 'fa-hourglass-half';
                            if ($status === 'In Progress') { $statusClass = 'status-inprogress'; $statusIcon = 'fa-spinner fa-spin'; }
                            elseif ($status === 'Completed') { $statusClass = 'status-completed'; $statusIcon = 'fa-circle-check'; }

                            // Fetch assigned worker name if any
                            $wName = 'Unassigned';
                            if (!empty($p['worker_id'])) {
                                $w_res = mysqli_query($conn, "SELECT name FROM workers WHERE id='{$p['worker_id']}'");
                                if ($w_row = mysqli_fetch_assoc($w_res)) {
                                    $wName = htmlspecialchars($w_row['name']);
                                }
                            }
                        ?>
                            <tr class="complaint-row" data-status="<?php echo $status; ?>" onclick="window.location.href='problem_details.php?id=<?php echo $p['id']; ?>'">
                                <td>
                                    <div class="category-badge">
                                        <i class="fa-solid <?php echo $icon; ?>" style="color:<?php echo $iconColor; ?>;"></i>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </div>
                                    <div class="complaint-id">Ticket #<?php echo $p['id']; ?> • <?php echo date('M d, Y', strtotime($p['created_at'])); ?></div>
                                </td>
                                <td>
                                    <div style="font-weight:600; color:#1e293b; max-width:340px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        <?php echo htmlspecialchars($p['description']); ?>
                                    </div>
                                    <div class="location-text">
                                        <i class="fa-solid fa-location-dot" style="color:#ef4444;"></i>
                                        <?php echo htmlspecialchars(($p['street'] ? $p['street'].', ' : '') . ($p['area'] ? $p['area'].', ' : '') . $p['city']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                        $sev = $p['ai_severity'] ?? 'Medium';
                                        $sevColor = '#d97706';
                                        if ($sev === 'High' || $sev === 'Critical') $sevColor = '#dc2626';
                                        elseif ($sev === 'Low') $sevColor = '#059669';
                                    ?>
                                    <span style="font-weight:700; color:<?php echo $sevColor; ?>; font-size:0.85rem;">
                                        <i class="fa-solid fa-shield-halved"></i> <?php echo htmlspecialchars($sev); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight:700; font-size:0.88rem; color:#0f172a;">
                                        <?php if ($wName !== 'Unassigned'): ?>
                                            <i class="fa-solid fa-hard-hat" style="color:var(--brand-emerald);"></i> <?php echo $wName; ?>
                                        <?php else: ?>
                                            <span style="color:#94a3b8; font-weight:600;"><i class="fa-regular fa-clock"></i> Unassigned</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-status <?php echo $statusClass; ?>">
                                        <i class="fa-solid <?php echo $statusIcon; ?>"></i>
                                        <?php echo $status; ?>
                                    </span>
                                </td>
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

    <!-- ========================================================
         LEAFLET GIS COMMAND MAP SCRIPT
         ======================================================== -->
    <script>
        // Parse Geotagged Problems
        const allMapProblems = <?php echo $mapProblemsJson; ?>;
        let gisMap = null;
        let markersLayer = null;

        document.addEventListener('DOMContentLoaded', function() {
            initGisMap();
        });

        function initGisMap() {
            // Default center on India / first problem
            let defaultLat = 12.9716;
            let defaultLng = 77.5946;

            if (allMapProblems.length > 0) {
                defaultLat = allMapProblems[0].lat;
                defaultLng = allMapProblems[0].lng;
            }

            // Initialize Map with Smooth Zoom
            gisMap = L.map('gisCommandMap', {
                center: [defaultLat, defaultLng],
                zoom: 13,
                zoomControl: true
            });

            // Modern CartoDB Voyager Clean Tiles
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>',
                subdomains: 'abcd',
                maxZoom: 19
            }).addTo(gisMap);

            markersLayer = L.layerGroup().addTo(gisMap);

            renderMarkers(allMapProblems);
        }

        function renderMarkers(problemsList) {
            markersLayer.clearLayers();
            const bounds = [];

            problemsList.forEach(p => {
                if (!p.lat || !p.lng) return;

                // Determine Pin Color by Status
                let pinClass = 'pin-pending';
                let statusBadge = '<span style="background:#fef3c7; color:#92400e; padding:3px 8px; border-radius:12px; font-weight:700; font-size:0.75rem;">🟡 Pending</span>';
                if (p.status === 'In Progress') {
                    pinClass = 'pin-inprogress';
                    statusBadge = '<span style="background:#e0f2fe; color:#0369a1; padding:3px 8px; border-radius:12px; font-weight:700; font-size:0.75rem;">🔵 In Progress</span>';
                } else if (p.status === 'Completed') {
                    pinClass = 'pin-completed';
                    statusBadge = '<span style="background:#d1fae5; color:#065f46; padding:3px 8px; border-radius:12px; font-weight:700; font-size:0.75rem;">🟢 Resolved</span>';
                }

                // Determine Icon by Category
                let iconHtml = '<i class="fa-solid fa-triangle-exclamation"></i>';
                const catLower = p.category.toLowerCase();
                if (catLower.includes('road') || catLower.includes('pothole')) iconHtml = '<i class="fa-solid fa-road"></i>';
                else if (catLower.includes('sanitation') || catLower.includes('garbage')) iconHtml = '<i class="fa-solid fa-trash-can"></i>';
                else if (catLower.includes('elect') || catLower.includes('light')) iconHtml = '<i class="fa-solid fa-bolt"></i>';
                else if (catLower.includes('water') || catLower.includes('drain')) iconHtml = '<i class="fa-solid fa-faucet-drip"></i>';

                // Create Custom Leaflet DivIcon
                const customIcon = L.divIcon({
                    className: 'custom-gis-pin-container',
                    html: `<div class="custom-gis-pin ${pinClass}" style="width:34px; height:34px;">${iconHtml}</div>`,
                    iconSize: [34, 34],
                    iconAnchor: [17, 34],
                    popupAnchor: [0, -32]
                });

                // Build Image HTML
                let imgHtml = '';
                if (p.photo) {
                    imgHtml = `<img src="../${p.photo}" class="gis-popup-img" alt="Complaint Photo" onerror="this.style.display='none'">`;
                }

                // Popup Content Card
                const popupContent = `
                    <div class="gis-popup-card">
                        ${imgHtml}
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                            <span class="gis-popup-cat" style="color:#0284c7;">${p.category}</span>
                            ${statusBadge}
                        </div>
                        <div class="gis-popup-desc"><strong>#${p.id}</strong>: ${escapeHtml(p.description.substring(0, 75))}${p.description.length > 75 ? '...' : ''}</div>
                        <div class="gis-popup-loc">
                            <i class="fa-solid fa-location-dot" style="color:#ef4444;"></i>
                            <span>${escapeHtml(p.street || p.area || p.city)}</span>
                        </div>
                        <div style="font-size:0.78rem; color:#475569; margin-bottom:10px;">
                            <strong>Officer:</strong> ${escapeHtml(p.worker_name)}
                        </div>
                        <a href="problem_details.php?id=${p.id}" class="gis-popup-btn">
                            <i class="fa-solid fa-magnifying-glass"></i> Inspect & Allocate Ticket ➔
                        </a>
                    </div>
                `;

                const marker = L.marker([p.lat, p.lng], { icon: customIcon }).bindPopup(popupContent);
                markersLayer.addLayer(marker);
                bounds.push([p.lat, p.lng]);
            });

            // Update Plotted Count Pill
            document.getElementById('mapCountPill').innerText = `${problemsList.length} Plotted Coordinates`;

            // Auto-fit Bounds
            if (bounds.length > 0) {
                gisMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
            }
        }

        // Status Filter
        function filterMapMarkers(status, btn) {
            document.querySelectorAll('.map-filter-btn').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');

            if (status === 'all') {
                renderMarkers(allMapProblems);
            } else {
                const filtered = allMapProblems.filter(p => p.status === status);
                renderMarkers(filtered);
            }
        }

        // Category Filter
        function filterMapCategory(catKeyword, btn) {
            document.querySelectorAll('.map-filter-btn').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');

            const filtered = allMapProblems.filter(p => p.category.toLowerCase().includes(catKeyword.toLowerCase()));
            renderMarkers(filtered);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Table Filter Functions
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
