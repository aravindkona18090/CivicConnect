<?php
session_start();
include('../db/connection.php');
include('../lang.php');

// Check if worker is logged in
if (!isset($_SESSION['worker_id'])) {
    header("Location: login.php");
    exit();
}

$worker_id = $_SESSION['worker_id'];
$worker_name = $_SESSION['workername'] ?? 'Officer';

$msg = "";
$msg_type = "";

// Handle Direct Password Change from Dashboard Modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    $stmt = $conn->prepare("SELECT password FROM workers WHERE id = ?");
    $stmt->bind_param("i", $worker_id);
    $stmt->execute();
    $curr_hash = $stmt->get_result()->fetch_assoc()['password'] ?? '';
    $stmt->close();

    if (!password_verify($current_pass, $curr_hash) && $current_pass !== $curr_hash) {
        $msg = "❌ Current password entered is incorrect.";
        $msg_type = "error";
    } elseif (strlen($new_pass) < 6) {
        $msg = "❌ New password must be at least 6 characters long.";
        $msg_type = "error";
    } elseif ($new_pass !== $confirm_pass) {
        $msg = "❌ New passwords do not match.";
        $msg_type = "error";
    } else {
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $up_stmt = $conn->prepare("UPDATE workers SET password = ? WHERE id = ?");
        $up_stmt->bind_param("si", $new_hash, $worker_id);
        if ($up_stmt->execute()) {
            $msg = "✅ Your password has been changed successfully!";
            $msg_type = "success";
        } else {
            $msg = "❌ Error updating password. Please try again.";
            $msg_type = "error";
        }
        $up_stmt->close();
    }
}

// Fetch all problems assigned to this worker with citizen reporter info
$problems_query = "
    SELECT 
        p.*, 
        u.username as citizen_username, 
        u.name as citizen_name, 
        u.mobile as citizen_mobile, 
        u.email as citizen_email
    FROM problems p
    LEFT JOIN people u ON p.user_id = u.id
    WHERE p.worker_id = '$worker_id'
    ORDER BY CASE WHEN p.status != 'Completed' THEN 1 ELSE 2 END, p.created_at DESC
";
$problems_result = mysqli_query($conn, $problems_query);

// Compute Summary Metrics
$total_assigned = 0;
$active_tasks = 0;
$completed_tasks = 0;

$task_list = [];
if ($problems_result && mysqli_num_rows($problems_result) > 0) {
    while ($row = mysqli_fetch_assoc($problems_result)) {
        $task_list[] = $row;
        $total_assigned++;
        if ($row['status'] === 'Completed') {
            $completed_tasks++;
        } else {
            $active_tasks++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $selectedLang; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Field Officer Command Center - CivicConnect</title>
<!-- Google Fonts & Font Awesome Icons -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
:root {
    --primary: #059669;
    --primary-hover: #047857;
    --primary-gradient: linear-gradient(135deg, #059669 0%, #10b981 100%);
    --bg-slate: #f8fafc;
    --card-bg: #ffffff;
    --text-dark: #0f172a;
    --text-muted: #64748b;
    --border-color: #e2e8f0;
    --radius: 16px;
    --shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.06), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: var(--bg-slate);
    color: var(--text-dark);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    padding-bottom: 60px;
}

header {
    background: #ffffff;
    border-bottom: 1px solid var(--border-color);
    padding: 14px 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}

.logo-group {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
}

.logo-badge {
    width: 40px;
    height: 40px;
    background: var(--primary-gradient);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.1rem;
}

.logo-title {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--text-dark);
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 12px;
}

.officer-tag {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.change-pass-btn {
    background: #f1f5f9;
    color: #0f172a;
    border: 1px solid #cbd5e1;
    padding: 6px 12px;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    transition: all 0.2s ease;
}
.change-pass-btn:hover { background: #e2e8f0; }

.logout-btn {
    color: #ef4444;
    background: #fee2e2;
    padding: 6px 12px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.82rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background 0.2s ease;
}
.logout-btn:hover { background: #fecaca; }

main {
    max-width: 1240px;
    margin: 28px auto;
    padding: 0 20px;
    width: 100%;
    flex: 1;
}

.dashboard-header {
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
}

.dashboard-header h2 {
    font-size: 1.6rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Metric Cards */
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.metric-card {
    background: #ffffff;
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 18px 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
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
    font-size: 1.7rem;
    font-weight: 800;
    color: #0f172a;
}

.metric-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}
.icon-blue { background: #e0f2fe; color: #0284c7; }
.icon-amber { background: #fef3c7; color: #d97706; }
.icon-emerald { background: #d1fae5; color: #059669; }

/* Filter Controls */
.controls-card {
    background: #ffffff;
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    padding: 14px 18px;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
}

.search-box {
    position: relative;
    flex: 1;
    min-width: 260px;
    max-width: 400px;
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
    border: 1px solid var(--border-color);
    border-radius: 10px;
    font-family: inherit;
    font-size: 0.88rem;
    background: #f8fafc;
    color: #0f172a;
    outline: none;
    transition: all 0.2s ease;
}
.search-input:focus {
    border-color: var(--primary);
    background: #ffffff;
}

.status-tabs {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.status-tab {
    padding: 7px 14px;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    background: #f8fafc;
    color: var(--text-muted);
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}
.status-tab:hover { color: #0f172a; border-color: #cbd5e1; }
.status-tab.active {
    background: var(--primary);
    color: #ffffff;
    border-color: var(--primary);
}

.msg-banner {
    padding: 12px 18px;
    border-radius: 12px;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.msg-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
.msg-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

/* Clean Table */
.table-card {
    background: var(--card-bg);
    border-radius: var(--radius);
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow);
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
    border-bottom: 1px solid var(--border-color);
}

td {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color);
    font-size: 0.92rem;
    color: var(--text-dark);
    vertical-align: middle;
}

tr:last-child td { border-bottom: none; }
tbody tr:hover { background: #fbfdff; }

.badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-inprogress { background: #e0f2fe; color: #0369a1; }
.badge-completed { background: #d1fae5; color: #065f46; }

.thumb-preview {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    object-fit: cover;
    cursor: pointer;
    border: 1.5px solid var(--border-color);
    transition: transform 0.15s ease;
}
.thumb-preview:hover { transform: scale(1.08); }

.btn-inspect {
    background: var(--primary-gradient);
    color: #ffffff;
    padding: 8px 16px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.25);
    transition: all 0.2s ease;
}
.btn-inspect:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
}

/* Modals */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0; top: 0; width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-content {
    background: #ffffff;
    border-radius: 20px;
    padding: 30px;
    width: 100%;
    max-width: 480px;
    position: relative;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
}

.modal-close {
    position: absolute;
    right: 18px;
    top: 18px;
    font-size: 1.3rem;
    color: var(--text-muted);
    cursor: pointer;
    background: #f1f5f9;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.form-group-modal {
    margin-bottom: 16px;
}

.form-group-modal label {
    display: block;
    font-weight: 700;
    font-size: 0.85rem;
    margin-bottom: 6px;
}

.form-group-modal input {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid var(--border-color);
    border-radius: 10px;
    font-family: inherit;
    font-size: 0.9rem;
    outline: none;
}
.form-group-modal input:focus { border-color: var(--primary); }

.action-btn {
    background: var(--primary-gradient);
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.85rem;
    font-family: inherit;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
    transition: all 0.2s ease;
}
.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35);
}
</style>
</head>
<body>

<header>
    <a href="workerdashboard.php" class="logo-group">
        <div class="logo-badge"><i class="fa-solid fa-handshake"></i></div>
        <span class="logo-title">Civic<span style="color:#059669;">Connect</span> Officer Portal</span>
    </a>
    <div class="user-profile">
        <span class="officer-tag"><i class="fa-solid fa-user-shield"></i> Officer <?php echo htmlspecialchars($worker_name); ?></span>
        <button type="button" class="change-pass-btn" onclick="$('#changePassModal').css('display', 'flex');">
            <i class="fa-solid fa-key" style="color:#059669;"></i> Change Password
        </button>
        <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        <form method="POST" style="display:inline-flex; align-items:center;">
            <select name="language" onchange="this.form.submit()" style="padding:6px 10px; border-radius:8px; border:1px solid #cbd5e1; font-weight:600; font-family:inherit; cursor:pointer;" title="Select Language">
                <option value="en" <?php if ($selectedLang=='en') echo 'selected'; ?>>🌐 English</option>
                <option value="te" <?php if ($selectedLang=='te') echo 'selected'; ?>>🌐 తెలుగు (Telugu)</option>
                <option value="hn" <?php if ($selectedLang=='hn') echo 'selected'; ?>>🌐 हिंदी (Hindi)</option>
                <option value="kn" <?php if ($selectedLang=='kn') echo 'selected'; ?>>🌐 ಕನ್ನಡ (Kannada)</option>
            </select>
        </form>
    </div>
</header>

<main>
    <div class="dashboard-header">
        <div>
            <h2><i class="fa-solid fa-person-digging" style="color:#059669;"></i> Assigned Municipal Work Orders</h2>
            <p style="color:var(--text-muted); font-size:0.9rem; margin-top:4px;">Manage assigned field tasks, view location maps, and submit verified resolution photos.</p>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-info">
                <h4>Total Assigned</h4>
                <p><?php echo $total_assigned; ?></p>
            </div>
            <div class="metric-icon icon-blue"><i class="fa-solid fa-clipboard-list"></i></div>
        </div>

        <div class="metric-card">
            <div class="metric-info">
                <h4>Active Action Required</h4>
                <p><?php echo $active_tasks; ?></p>
            </div>
            <div class="metric-icon icon-amber"><i class="fa-solid fa-triangle-exclamation"></i></div>
        </div>

        <div class="metric-card">
            <div class="metric-info">
                <h4>Tasks Resolved by You</h4>
                <p><?php echo $completed_tasks; ?></p>
            </div>
            <div class="metric-icon icon-emerald"><i class="fa-solid fa-circle-check"></i></div>
        </div>
    </div>

    <!-- Filter & Search Controls -->
    <div class="controls-card">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="taskSearch" class="search-input" placeholder="Search tasks by ID, category, street, or citizen..." onkeyup="filterTasks()">
        </div>

        <div class="status-tabs">
            <button class="status-tab active" onclick="filterStatus('All', this)">All Tasks (<?php echo $total_assigned; ?>)</button>
            <button class="status-tab" onclick="filterStatus('Active', this)"><i class="fa-solid fa-clock"></i> Active Tasks (<?php echo $active_tasks; ?>)</button>
            <button class="status-tab" onclick="filterStatus('Completed', this)"><i class="fa-solid fa-circle-check"></i> Resolved Archive (<?php echo $completed_tasks; ?>)</button>
        </div>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="msg-banner <?php echo $msg_type==='success' ? 'msg-success':'msg-error'; ?>">
            <i class="fa-solid <?php echo $msg_type==='success' ? 'fa-circle-check':'fa-triangle-exclamation'; ?>"></i>
            <div><?php echo htmlspecialchars($msg); ?></div>
        </div>
    <?php endif; ?>

    <!-- Tasks Summary Table -->
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Work Order</th>
                    <th>Location & Area</th>
                    <th>Citizen Reporter</th>
                    <th>Photo Evidence</th>
                    <th>Priority & Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($task_list)): ?>
                    <?php foreach ($task_list as $problem): 
                        $isCompleted = ($problem['status'] === 'Completed');
                        $statusCategory = $isCompleted ? 'Completed' : 'Active';
                        $badgeClass = $isCompleted ? 'badge-completed' : ($problem['status'] === 'In Progress' ? 'badge-inprogress' : 'badge-pending');
                        $searchData = strtolower(
                            $problem['id'] . ' ' . 
                            $problem['category'] . ' ' . 
                            $problem['street'] . ' ' . 
                            ($problem['area'] ?? '') . ' ' . 
                            ($problem['citizen_name'] ?? $problem['citizen_username'] ?? '')
                        );
                    ?>
                        <tr class="task-row" data-status="<?php echo $statusCategory; ?>" data-search="<?php echo htmlspecialchars($searchData); ?>">
                            <td>
                                <strong>#<?php echo $problem['id']; ?></strong><br>
                                <span style="background:#e0f2fe; color:#0369a1; padding:3px 8px; border-radius:6px; font-weight:700; font-size:0.8rem; display:inline-block; margin-top:4px;">
                                    <?php echo htmlspecialchars($problem['category']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-weight:600; color:#0f172a;"><?php echo htmlspecialchars($problem['street']); ?></div>
                                <div style="color:var(--text-muted); font-size:0.82rem; margin-top:2px;">
                                    <i class="fa-solid fa-location-dot" style="color:#ef4444;"></i> <?php echo htmlspecialchars(($problem['area'] ?? 'Central') . ', ' . ($problem['city'] ?? 'Municipal')); ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:600; color:#0f172a; font-size:0.88rem;"><?php echo htmlspecialchars($problem['citizen_name'] ?? $problem['citizen_username'] ?? 'Local Resident'); ?></div>
                                <?php if (!empty($problem['citizen_mobile'])): ?>
                                    <div style="font-size:0.8rem; color:var(--text-muted); margin-top:2px;">
                                        <i class="fa-solid fa-phone" style="font-size:0.75rem;"></i> <?php echo htmlspecialchars($problem['citizen_mobile']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <?php if(!empty($problem['photo'])): ?>
                                        <img src="<?php echo htmlspecialchars($problem['photo']); ?>" class="thumb-preview" title="Citizen Complaint Photo (Before)" onclick="zoomPhoto('<?php echo htmlspecialchars($problem['photo']); ?>', '📷 Citizen Complaint Photo #<?php echo $problem['id']; ?>')">
                                    <?php endif; ?>

                                    <?php if(!empty($problem['after_photo'])): ?>
                                        <img src="<?php echo htmlspecialchars($problem['after_photo']); ?>" class="thumb-preview" style="border:2px solid #10b981;" title="Resolution Proof (After)" onclick="zoomPhoto('<?php echo htmlspecialchars($problem['after_photo']); ?>', '✅ Resolution Proof #<?php echo $problem['id']; ?>')">
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if(!empty($problem['ai_severity'])): ?>
                                    <?php 
                                        $sevColor = $problem['ai_severity'] === 'Critical' ? '#dc2626' : ($problem['ai_severity'] === 'High' ? '#ea580c' : '#16a34a');
                                    ?>
                                    <span class="badge" style="background:<?php echo $sevColor; ?>; color:#fff; margin-bottom:4px; display:inline-block;">
                                        <i class="fa-solid fa-shield-halved"></i> <?php echo htmlspecialchars($problem['ai_severity']); ?>
                                    </span><br>
                                <?php endif; ?>
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <i class="fa-solid <?php echo $isCompleted ? 'fa-circle-check':'fa-spinner fa-spin'; ?>"></i>
                                    <?php echo htmlspecialchars($problem['status']); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="task_details.php?id=<?php echo $problem['id']; ?>" class="btn-inspect">
                                    <span><?php echo $isCompleted ? 'View Archive' : 'Inspect & Resolve'; ?></span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:50px 20px; color:var(--text-muted);">
                            <i class="fa-solid fa-clipboard-check" style="font-size:2.5rem; color:#cbd5e1; margin-bottom:12px; display:block;"></i>
                            <h4 style="color:#0f172a; font-weight:800; margin-bottom:4px;">No Work Orders Assigned</h4>
                            <p>You currently have no tasks in your queue.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Change Password Modal -->
<div id="changePassModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="$('#changePassModal').hide();">&times;</span>
        <h3><i class="fa-solid fa-key" style="color:#059669;"></i> Change Officer Password</h3>
        <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:18px;">Update your auto-generated temporary password to a secure personal password.</p>
        <form method="POST" action="workerdashboard.php" autocomplete="off">
            <div class="form-group-modal">
                <label>Current Password</label>
                <input type="password" name="current_password" required placeholder="Enter current / default password">
            </div>
            <div class="form-group-modal">
                <label>New Password (Min. 6 characters)</label>
                <input type="password" name="new_password" required minlength="6" placeholder="Enter new password">
            </div>
            <div class="form-group-modal">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required minlength="6" placeholder="Confirm new password">
            </div>
            <button type="submit" name="change_password" class="action-btn" style="width:100%; justify-content:center; margin-top:8px;">
                <i class="fa-solid fa-check"></i> Update Password
            </button>
        </form>
    </div>
</div>

<!-- Photo Zoom Modal -->
<div id="photoZoomModal" class="modal" onclick="closeZoom(event)">
    <div class="modal-content" style="max-width:600px; text-align:center;" onclick="event.stopPropagation()">
        <span class="modal-close" onclick="closeZoomModal()">&times;</span>
        <h4 id="zoomTitle" style="margin-bottom:12px; color:#0f172a; font-size:1.05rem;">Inspection Photo</h4>
        <img id="zoomImg" src="" style="width:100%; max-height:420px; object-fit:contain; border-radius:10px;">
    </div>
</div>

<script>
$(window).click(function(e){
    if($(e.target).hasClass("modal")){
        $(".modal").hide();
    }
});

let currentStatus = 'All';

function filterStatus(status, btn) {
    currentStatus = status;
    document.querySelectorAll('.status-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyTaskFilters();
}

function filterTasks() {
    applyTaskFilters();
}

function applyTaskFilters() {
    const query = document.getElementById('taskSearch').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.task-row');

    rows.forEach(row => {
        const searchData = row.getAttribute('data-search') || '';
        const statusData = row.getAttribute('data-status') || '';

        const matchesSearch = query === '' || searchData.includes(query);
        const matchesStatus = currentStatus === 'All' || statusData === currentStatus;

        if (matchesSearch && matchesStatus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function zoomPhoto(src, title) {
    document.getElementById('zoomImg').src = src;
    document.getElementById('zoomTitle').innerText = title || 'Inspection Photo';
    document.getElementById('photoZoomModal').style.display = 'flex';
}
function closeZoomModal() {
    document.getElementById('photoZoomModal').style.display = 'none';
}
function closeZoom(e) {
    if (e.target.id === 'photoZoomModal') closeZoomModal();
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        $('.modal').hide();
    }
});
</script>

</body>
</html>
