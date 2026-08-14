<?php
session_start();
include('../db/connection.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$action_msg = "";
$action_type = "";

// Handle Delete Worker
if (isset($_POST['delete_worker_id'])) {
    $del_id = intval($_POST['delete_worker_id']);
    
    // Check if worker has active problems assigned
    $check_active = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM problems WHERE worker_id = $del_id AND status != 'Completed'");
    $active_cnt = mysqli_fetch_assoc($check_active)['cnt'] ?? 0;
    
    if ($active_cnt > 0) {
        $action_msg = "Cannot remove this officer: They currently have <strong>{$active_cnt} active task(s)</strong> in progress. Please reassign those tasks first.";
        $action_type = "error";
    } else {
        $del_stmt = $conn->prepare("DELETE FROM workers WHERE id = ?");
        $del_stmt->bind_param("i", $del_id);
        if ($del_stmt->execute()) {
            $action_msg = "Field Officer #{$del_id} was successfully removed from the registry.";
            $action_type = "success";
        } else {
            $action_msg = "Failed to remove officer. Please try again.";
            $action_type = "error";
        }
        $del_stmt->close();
    }
}

// Fetch Officers with Live Workload Counts
$query = "
    SELECT 
        w.*,
        COUNT(CASE WHEN p.status = 'In Progress' THEN 1 END) as active_tasks,
        COUNT(CASE WHEN p.status = 'Completed' THEN 1 END) as completed_tasks,
        COUNT(p.id) as total_assigned
    FROM workers w
    LEFT JOIN problems p ON w.id = p.worker_id
    GROUP BY w.id
    ORDER BY w.created_at DESC
";
$result = mysqli_query($conn, $query);

// Summary Stats
$total_officers = 0;
$active_on_duty = 0;
$total_resolved_by_officers = 0;

$workers_list = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $workers_list[] = $row;
        $total_officers++;
        if ($row['active_tasks'] > 0) $active_on_duty++;
        $total_resolved_by_officers += intval($row['completed_tasks']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Field Officers Management - CivicConnect Command Center</title>
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
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s ease;
        }
        .logout-btn:hover { background: #fecaca; }

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
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-header h1 {
            font-size: 1.6rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #0f172a;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .add-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #fff;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .add-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35);
        }

        /* Metrics Bar */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .metric-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-sm);
        }

        .metric-info h4 {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .metric-info p {
            font-size: 1.8rem;
            font-weight: 800;
            color: #0f172a;
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        .icon-blue { background: #e0f2fe; color: #0284c7; }
        .icon-emerald { background: #d1fae5; color: #059669; }
        .icon-amber { background: #fef3c7; color: #d97706; }

        /* Filter Controls */
        .controls-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            box-shadow: var(--shadow-sm);
        }

        .search-box {
            position: relative;
            flex: 1;
            min-width: 280px;
            max-width: 450px;
        }

        .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .search-input {
            width: 100%;
            padding: 10px 14px 10px 38px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.9rem;
            background: #f8fafc;
            color: #0f172a;
            outline: none;
            transition: border-color 0.2s ease, background 0.2s ease;
        }
        .search-input:focus {
            border-color: var(--brand-primary);
            background: #ffffff;
        }

        .dept-tabs {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .dept-tab {
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: #f8fafc;
            color: var(--text-muted);
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .dept-tab:hover {
            color: #0f172a;
            border-color: #cbd5e1;
        }

        .dept-tab.active {
            background: #0284c7;
            color: #ffffff;
            border-color: #0284c7;
        }

        /* Table Styling */
        .table-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
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
            padding: 16px 20px;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            font-size: 0.92rem;
            color: var(--text-main);
            vertical-align: middle;
        }

        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #fbfdff; }

        .officer-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .officer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1rem;
            text-transform: uppercase;
        }

        .officer-name {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .officer-id {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .dept-badge {
            padding: 5px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .dept-road { background: #fef3c7; color: #b45309; }
        .dept-sanitation { background: #dcfce7; color: #15803d; }
        .dept-electricity { background: #e0e7ff; color: #4338ca; }
        .dept-water { background: #e0f2fe; color: #0369a1; }
        .dept-general { background: #f1f5f9; color: #475569; }

        .workload-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .workload-active { background: #fee2e2; color: #dc2626; }
        .workload-idle { background: #ecfdf5; color: #059669; }
        .resolved-count { color: #059669; font-weight: 800; font-size: 0.85rem; }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
            border: none;
            padding: 7px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.8rem;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .btn-delete:hover {
            background: #dc2626;
            color: #ffffff;
        }

        /* Alert Toast */
        .alert-box {
            padding: 14px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
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
            <a href="allproblems.php" class="nav-link"><i class="fa-solid fa-list-check"></i> All Complaints</a>
            <a href="completedproblems.php" class="nav-link"><i class="fa-solid fa-circle-check"></i> Completed Archive</a>
            <a href="workers.php" class="nav-link active"><i class="fa-solid fa-hard-hat"></i> Field Officers</a>
        </nav>

        <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </header>

    <div class="container">
        <?php if (!empty($action_msg)): ?>
            <div class="alert-box <?php echo $action_type == 'success' ? 'alert-success' : 'alert-error'; ?>">
                <i class="fa-solid <?php echo $action_type == 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i>
                <div><?php echo $action_msg; ?></div>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <div>
                <h1><i class="fa-solid fa-hard-hat" style="color:var(--brand-primary);"></i> Field Officers Management</h1>
                <p style="color:var(--text-muted); font-size:0.9rem; margin-top:4px;">Manage municipal workforce, department assignments, and track live operational workloads.</p>
            </div>
            <div class="header-actions">
                <a href="add_worker.php" class="add-btn"><i class="fa-solid fa-user-plus"></i> Add New Field Officer</a>
            </div>
        </div>

        <!-- Metrics Overview -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-info">
                    <h4>Registered Officers</h4>
                    <p><?php echo $total_officers; ?></p>
                </div>
                <div class="metric-icon icon-blue"><i class="fa-solid fa-users-gear"></i></div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <h4>Officers on Active Duty</h4>
                    <p><?php echo $active_on_duty; ?></p>
                </div>
                <div class="metric-icon icon-amber"><i class="fa-solid fa-person-digging"></i></div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <h4>Issues Resolved by Team</h4>
                    <p><?php echo $total_resolved_by_officers; ?></p>
                </div>
                <div class="metric-icon icon-emerald"><i class="fa-solid fa-circle-check"></i></div>
            </div>
        </div>

        <!-- Filter & Search Controls -->
        <div class="controls-card">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="officerSearch" class="search-input" placeholder="Search officer by name, email, mobile, or area..." onkeyup="filterOfficers()">
            </div>

            <div class="dept-tabs">
                <button class="dept-tab active" onclick="filterDepartment('All', this)">All Departments</button>
                <button class="dept-tab" onclick="filterDepartment('Road', this)"><i class="fa-solid fa-road"></i> Roads</button>
                <button class="dept-tab" onclick="filterDepartment('Sanitation', this)"><i class="fa-solid fa-trash-can"></i> Sanitation</button>
                <button class="dept-tab" onclick="filterDepartment('Electricity', this)"><i class="fa-solid fa-bolt"></i> Electricity</button>
                <button class="dept-tab" onclick="filterDepartment('Water', this)"><i class="fa-solid fa-faucet-drip"></i> Water & Drainage</button>
            </div>
        </div>

        <!-- Officers Table -->
        <div class="table-card">
            <table id="officersTable">
                <thead>
                    <tr>
                        <th>Officer Profile</th>
                        <th>Contact Information</th>
                        <th>Department</th>
                        <th>Assigned Area</th>
                        <th>Active Workload</th>
                        <th>Resolved</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($workers_list)): ?>
                        <?php foreach ($workers_list as $w): 
                            $name = htmlspecialchars($w['username'] ?? $w['name'] ?? 'Officer');
                            $initial = strtoupper(substr($name, 0, 1));
                            $cat = $w['category'] ?? $w['department'] ?? 'General';
                            
                            $badge_class = 'dept-general';
                            $cat_icon = 'fa-building';
                            if (stripos($cat, 'road') !== false) { $badge_class = 'dept-road'; $cat_icon = 'fa-road'; }
                            elseif (stripos($cat, 'sanit') !== false || stripos($cat, 'garb') !== false) { $badge_class = 'dept-sanitation'; $cat_icon = 'fa-trash-can'; }
                            elseif (stripos($cat, 'elect') !== false || stripos($cat, 'light') !== false) { $badge_class = 'dept-electricity'; $cat_icon = 'fa-bolt'; }
                            elseif (stripos($cat, 'water') !== false || stripos($cat, 'drain') !== false) { $badge_class = 'dept-water'; $cat_icon = 'fa-faucet-drip'; }
                        ?>
                            <tr class="officer-row" data-dept="<?php echo htmlspecialchars($cat); ?>" data-search="<?php echo strtolower($name . ' ' . $w['email'] . ' ' . ($w['mobile'] ?? '') . ' ' . ($w['area'] ?? '') . ' ' . ($w['city'] ?? '')); ?>">
                                <td>
                                    <div class="officer-profile">
                                        <div class="officer-avatar"><?php echo $initial; ?></div>
                                        <div>
                                            <div class="officer-name"><?php echo $name; ?></div>
                                            <div class="officer-id">Officer #<?php echo $w['id']; ?> <?php if(!empty($w['age'])): ?>• <?php echo $w['age']; ?> yrs<?php endif; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight:600; color:#0f172a; font-size:0.88rem;"><i class="fa-regular fa-envelope" style="color:#94a3b8; width:16px;"></i> <?php echo htmlspecialchars($w['email']); ?></div>
                                    <div style="color:var(--text-muted); font-size:0.82rem; margin-top:3px;"><i class="fa-solid fa-phone" style="color:#94a3b8; width:16px;"></i> <?php echo htmlspecialchars($w['mobile'] ?? $w['phone'] ?? 'N/A'); ?></div>
                                </td>
                                <td>
                                    <span class="dept-badge <?php echo $badge_class; ?>">
                                        <i class="fa-solid <?php echo $cat_icon; ?>"></i>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight:600; color:#0f172a; font-size:0.88rem;"><?php echo htmlspecialchars($w['area'] ?? 'City Central'); ?></div>
                                    <div style="color:var(--text-muted); font-size:0.8rem;"><?php echo htmlspecialchars(($w['city'] ?? 'Municipal') . (!empty($w['pincode']) ? ' - ' . $w['pincode'] : '')); ?></div>
                                </td>
                                <td>
                                    <?php if ($w['active_tasks'] > 0): ?>
                                        <span class="workload-badge workload-active">
                                            <i class="fa-solid fa-spinner fa-spin"></i> <?php echo $w['active_tasks']; ?> Active Task<?php echo $w['active_tasks'] > 1 ? 's' : ''; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="workload-badge workload-idle">
                                            <i class="fa-solid fa-circle-check"></i> Available (Idle)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="resolved-count"><i class="fa-solid fa-check-double"></i> <?php echo intval($w['completed_tasks']); ?> Fixed</span>
                                </td>
                                <td style="text-align: right;">
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to remove Officer <?php echo addslashes($name); ?>?');" style="display:inline;">
                                        <input type="hidden" name="delete_worker_id" value="<?php echo $w['id']; ?>">
                                        <button type="submit" class="btn-delete" title="Remove Officer">
                                            <i class="fa-solid fa-trash-can"></i> Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="emptyRow">
                            <td colspan="7" style="text-align:center; padding:40px; color:#64748b;">
                                <i class="fa-solid fa-user-slash" style="font-size:2rem; color:#cbd5e1; margin-bottom:10px; display:block;"></i>
                                No field officers registered yet. Click <b>"Add New Field Officer"</b> to onboard your municipal staff.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        let currentDept = 'All';

        function filterDepartment(dept, btn) {
            currentDept = dept;
            document.querySelectorAll('.dept-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            applyFilters();
        }

        function filterOfficers() {
            applyFilters();
        }

        function applyFilters() {
            const query = document.getElementById('officerSearch').value.toLowerCase().trim();
            const rows = document.querySelectorAll('.officer-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const searchData = row.getAttribute('data-search') || '';
                const deptData = row.getAttribute('data-dept') || '';

                const matchesSearch = query === '' || searchData.includes(query);
                const matchesDept = currentDept === 'All' || deptData.toLowerCase().includes(currentDept.toLowerCase());

                if (matchesSearch && matchesDept) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
