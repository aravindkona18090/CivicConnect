<?php
session_start();
include("../db/connection.php");
require __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$problem_id = intval($_GET['id'] ?? 0);
if (!$problem_id) {
    header("Location: admindashboard.php");
    exit();
}

function sendEmailNotification($to, $subject, $body)
{
    try {
        if (class_exists('Resend')) {
            $resend = Resend::client(getenv('RESEND_API_KEY'));
            $resend->emails->send([
                'from' => 'CivicConnect <onboarding@resend.dev>',
                'to' => [$to],
                'subject' => $subject,
                'html' => $body,
            ]);
            return true;
        }
        return false;
    } catch (\Exception $e) {
        error_log("Resend Error: " . $e->getMessage());
        return false;
    }
}

$action_msg = "";

// Handle admin actions (Assign Worker / Change Status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch reporter details
    $u_stmt = $conn->prepare("SELECT p.description, p.category, u.username, u.email FROM problems p JOIN people u ON p.user_id = u.id WHERE p.id = ?");
    $u_stmt->bind_param("i", $problem_id);
    $u_stmt->execute();
    $user = $u_stmt->get_result()->fetch_assoc();

    // ASSIGN OR REASSIGN WORKER
    if (isset($_POST['assign_worker'])) {
        $worker_id = intval($_POST['worker_id'] ?? 0);
        if ($worker_id > 0) {
            $upd = $conn->prepare("UPDATE problems SET status='In Progress', worker_id=?, allocated_at=NOW() WHERE id=?");
            $upd->bind_param("ii", $worker_id, $problem_id);
            $upd->execute();

            // Fetch Worker Details for email notification
            $w_stmt = $conn->prepare("SELECT name, email FROM workers WHERE id=?");
            $w_stmt->bind_param("i", $worker_id);
            $w_stmt->execute();
            $worker = $w_stmt->get_result()->fetch_assoc();

            if ($worker && !empty($worker['email'])) {
                $w_subject = "🚨 New Civic Work Order Assigned: Complaint #{$problem_id}";
                $w_body = "
                    <div style='font-family:Arial,sans-serif; padding:20px; border:1px solid #e2e8f0; border-radius:12px;'>
                        <h3 style='color:#0284c7;'>Hello {$worker['name']},</h3>
                        <p>You have been assigned a new municipal task by the Administrator.</p>
                        <p><b>Complaint ID:</b> #{$problem_id}</p>
                        <p><b>Description:</b> {$user['description']}</p>
                        <p>Please log in to your Field Officer portal to view complete location details and upload completion proof photo once resolved.</p>
                    </div>
                ";
                sendEmailNotification($worker['email'], $w_subject, $w_body);
            }

            if ($user && !empty($user['email'])) {
                $u_subject = "CivicConnect - Field Officer Assigned (ID: #{$problem_id})";
                $u_body = "<p>Dear <strong>{$user['username']}</strong>,</p><p>Your reported problem <em>({$user['description']})</em> has been approved and assigned to Field Officer <strong>{$worker['name']}</strong>. Work is now <b>In Progress</b>.</p>";
                sendEmailNotification($user['email'], $u_subject, $u_body);
            }

            $action_msg = "Field Officer <strong>" . htmlspecialchars($worker['name']) . "</strong> assigned successfully!";
        }
    }

    if (isset($_POST['complete_problem'])) {
        mysqli_query($conn, "UPDATE problems SET status='Completed', completed_at=NOW() WHERE id='$problem_id'");
        if ($user && !empty($user['email'])) {
            $subject = "CivicConnect - Problem Completed (ID: #{$problem_id})";
            $body = "<p>Dear <strong>{$user['username']}</strong>,</p><p>Your reported problem <em>({$user['description']})</em> has been <b>Marked as Completed</b>. Thank you!</p>";
            sendEmailNotification($user['email'], $subject, $body);
        }
        $action_msg = "Complaint #{$problem_id} marked as Completed!";
    }

    if (isset($_POST['delete_problem'])) {
        mysqli_query($conn, "DELETE FROM problems WHERE id='$problem_id'");
        if ($user && !empty($user['email'])) {
            $subject = "CivicConnect - Problem Rejected (ID: #{$problem_id})";
            $body = "<p>Dear <strong>{$user['username']}</strong>,</p><p>Your reported problem <em>({$user['description']})</em> was <b>Rejected/Removed</b>.</p>";
            sendEmailNotification($user['email'], $subject, $body);
        }
        header("Location: admindashboard.php");
        exit();
    }
}

// Fetch complete problem details with citizen & worker info
$query = "
    SELECT p.*, 
           u.username as reporter_name, u.email as reporter_email, u.mobile as reporter_mobile,
           w.name as worker_name, w.email as worker_email, w.mobile as worker_mobile, w.category as worker_dept
    FROM problems p
    LEFT JOIN people u ON p.user_id = u.id
    LEFT JOIN workers w ON p.worker_id = w.id
    WHERE p.id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $problem_id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();

if (!$p) {
    header("Location: admindashboard.php");
    exit();
}

// Fetch all field workers for assignment dropdown
$workers_result = mysqli_query($conn, "SELECT * FROM workers ORDER BY name ASC");

$adminName = $_SESSION['adminname'] ?? 'Administrator';
$sev = $p['ai_severity'] ?? 'Medium';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection #<?php echo $p['id']; ?> - CivicConnect Admin</title>
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
            --shadow-md: 0 12px 30px -5px rgba(0,0,0,0.08);
            --radius-lg: 20px;
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
        }
        .brand-title span { color: var(--brand-primary); }

        .back-btn {
            background: #f1f5f9;
            color: #475569;
            padding: 8px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        .back-btn:hover { background: #e2e8f0; color: #0f172a; }

        .container {
            max-width: 1200px;
            margin: 32px auto;
            padding: 0 24px;
        }

        .header-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }

        .badge-id { background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.8rem; }
        .badge-cat { background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem; }
        .badge-sev { padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem; }
        .sev-Critical { background: #fee2e2; color: #991b1b; }
        .sev-High { background: #ffedd5; color: #c2410c; }
        .sev-Medium { background: #fef3c7; color: #b45309; }
        .sev-Low { background: #d1fae5; color: #065f46; }

        .problem-desc {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-main);
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 24px;
        }

        .card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 800;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .photos-comparison {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .photo-pane {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .photo-pane h4 {
            font-size: 0.85rem;
            font-weight: 800;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .photo-pane img {
            width: 100%;
            height: 260px;
            object-fit: cover;
            border-radius: 10px;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
        }

        .no-photo {
            height: 260px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 0.85rem;
            gap: 8px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: 700; color: var(--text-muted); }
        .info-val { font-weight: 700; color: var(--text-main); }

        .worker-select {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            background: #ffffff;
            margin-bottom: 12px;
            outline: none;
        }
        .worker-select:focus { border-color: var(--brand-primary); }

        .btn-action {
            width: 100%;
            padding: 12px 18px;
            border-radius: 10px;
            border: none;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 10px;
        }
        .btn-assign { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); }
        .btn-complete { background: #0284c7; color: #fff; }
        .btn-delete { background: #fee2e2; color: #dc2626; }
        .btn-delete:hover { background: #fca5a5; }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 700;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-bg {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(8px);
            z-index: 999;
            align-items: center;
            justify-content: center;
        }
        .modal-bg.active { display: flex; }
        .modal-content img { max-width: 90vw; max-height: 85vh; border-radius: 16px; }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header class="navbar">
        <a href="admindashboard.php" class="nav-brand">
            <div class="brand-badge"><i class="fa-solid fa-handshake-angle"></i></div>
            <div class="brand-title">Civic<span>Connect</span></div>
        </a>

        <a href="admindashboard.php" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i> Back to Command Center
        </a>
    </header>

    <div class="container">

        <?php if (!empty($action_msg)): ?>
            <div class="alert-success">
                <i class="fa-solid fa-circle-check"></i> <?php echo $action_msg; ?>
            </div>
        <?php endif; ?>

        <!-- HEADER SUMMARY CARD -->
        <div class="header-card">
            <div>
                <div class="header-info">
                    <div class="header-meta">
                        <span class="badge-id">Complaint #<?php echo $p['id']; ?></span>
                        <span class="badge-cat"><i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($p['category']); ?></span>
                        <span class="badge-sev sev-<?php echo $sev; ?>"><i class="fa-solid fa-bolt"></i> AI Severity: <?php echo htmlspecialchars($sev); ?></span>
                        <span style="background:#f1f5f9; color:#475569; padding:4px 10px; border-radius:6px; font-weight:700; font-size:0.8rem;">Status: <?php echo htmlspecialchars($p['status']); ?></span>
                    </div>
                    <div class="problem-desc"><?php echo htmlspecialchars($p['description']); ?></div>
                </div>
            </div>
        </div>

        <!-- 2-COLUMN INSPECTION LAYOUT -->
        <div class="details-grid">

            <!-- LEFT COLUMN: PHOTOS & MAP LOCATION -->
            <div>
                <!-- SIDE-BY-SIDE PHOTO COMPARISON -->
                <div class="card">
                    <div class="card-title">
                        <i class="fa-solid fa-camera-retro" style="color:var(--brand-primary);"></i> Photo Inspection & Proof Comparison
                    </div>

                    <div class="photos-comparison">
                        <!-- BEFORE PHOTO -->
                        <div class="photo-pane">
                            <h4 style="color:#475569;"><i class="fa-solid fa-user"></i> Citizen Report Photo (Before)</h4>
                            <?php if (!empty($p['photo'])): ?>
                                <img src="<?php echo htmlspecialchars($p['photo']); ?>" alt="Before Photo" onclick="zoomPhoto('<?php echo htmlspecialchars($p['photo']); ?>')">
                            <?php else: ?>
                                <div class="no-photo"><i class="fa-solid fa-image" style="font-size:2rem;"></i><span>No Photo Provided</span></div>
                            <?php endif; ?>
                        </div>

                        <!-- AFTER PHOTO (Worker Proof) -->
                        <div class="photo-pane">
                            <h4 style="color:#059669;"><i class="fa-solid fa-circle-check"></i> Officer Completion Proof (After)</h4>
                            <?php if (!empty($p['after_photo'])): ?>
                                <img src="<?php echo htmlspecialchars($p['after_photo']); ?>" alt="After Photo Proof" style="border: 2px solid #10b981;" onclick="zoomPhoto('<?php echo htmlspecialchars($p['after_photo']); ?>')">
                            <?php else: ?>
                                <div class="no-photo"><i class="fa-solid fa-hourglass-half" style="font-size:2rem;"></i><span>Awaiting Field Officer Proof</span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- LOCATION & GIS DETAILS -->
                <div class="card">
                    <div class="card-title">
                        <i class="fa-solid fa-location-dot" style="color:var(--brand-primary);"></i> Location & GPS Coordinates
                    </div>

                    <div class="info-row">
                        <span class="info-label">Street Address</span>
                        <span class="info-val"><?php echo htmlspecialchars($p['street']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Area / Division</span>
                        <span class="info-val"><?php echo htmlspecialchars($p['area'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">City & Pincode</span>
                        <span class="info-val"><?php echo htmlspecialchars(($p['city'] ?: 'N/A') . ' - ' . ($p['pincode'] ?: 'N/A')); ?></span>
                    </div>
                    <?php if ($p['lat'] && $p['lng']): ?>
                        <div class="info-row">
                            <span class="info-label">GPS Pin</span>
                            <span class="info-val">
                                <a href="https://maps.google.com/?q=<?php echo $p['lat']; ?>,<?php echo $p['lng']; ?>" target="_blank" style="color:var(--brand-primary); font-weight:800; text-decoration:none;">
                                    <?php echo $p['lat']; ?>, <?php echo $p['lng']; ?> <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT COLUMN: ASSIGNMENT FORM, CITIZEN INFO & ACTIONS -->
            <div>
                <!-- INTERACTIVE FIELD WORKER ASSIGNMENT PANEL -->
                <div class="card" style="border-top: 4px solid var(--brand-emerald);">
                    <div class="card-title">
                        <i class="fa-solid fa-hard-hat" style="color:var(--brand-emerald);"></i> Assign Field Officer
                    </div>

                    <form method="POST">
                        <label style="font-size:0.85rem; font-weight:700; color:var(--text-main); display:block; margin-bottom:6px;">Select Municipal Field Officer:</label>
                        <select name="worker_id" class="worker-select" required>
                            <option value="">-- Choose Officer --</option>
                            <?php if ($workers_result && mysqli_num_rows($workers_result) > 0): ?>
                                <?php mysqli_data_seek($workers_result, 0); ?>
                                <?php while ($w = mysqli_fetch_assoc($workers_result)): 
                                    $is_selected = ($p['worker_id'] == $w['id']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $w['id']; ?>" <?php echo $is_selected; ?>>
                                        👷 <?php echo htmlspecialchars($w['username'] ?? $w['name']); ?> (<?php echo htmlspecialchars($w['category'] ?? 'General'); ?>)
                                    </option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="" disabled>No Field Officers Registered</option>
                            <?php endif; ?>
                        </select>

                        <button type="submit" name="assign_worker" class="btn-action btn-assign">
                            <i class="fa-solid fa-user-check"></i> <?php echo ($p['worker_id']) ? 'Reassign Field Officer' : 'Assign Officer & Start Work'; ?>
                        </button>
                    </form>

                    <hr style="border:none; border-top:1px solid var(--border); margin:16px 0;">

                    <?php if ($p['status'] === 'In Progress' || $p['status'] === 'Pending'): ?>
                        <form method="POST">
                            <button type="submit" name="complete_problem" class="btn-action btn-complete">
                                <i class="fa-solid fa-check-double"></i> Mark Completed
                            </button>
                        </form>
                    <?php endif; ?>

                    <form method="POST">
                        <button type="submit" name="delete_problem" class="btn-action btn-delete" onclick="return confirm('Reject and remove complaint #<?php echo $p['id']; ?>?');">
                            <i class="fa-solid fa-trash"></i> Reject / Delete Report
                        </button>
                    </form>
                </div>

                <!-- CURRENTLY ASSIGNED WORKER DETAILS -->
                <div class="card">
                    <div class="card-title">
                        <i class="fa-solid fa-clipboard-user" style="color:var(--brand-primary);"></i> Assigned Officer Details
                    </div>
                    <?php if ($p['worker_name']): ?>
                        <div class="info-row">
                            <span class="info-label">Officer Name</span>
                            <span class="info-val"><?php echo htmlspecialchars($p['worker_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Department</span>
                            <span class="info-val"><?php echo htmlspecialchars($p['worker_dept'] ?? $p['category']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Officer Email</span>
                            <span class="info-val"><code><?php echo htmlspecialchars($p['worker_email']); ?></code></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Contact Phone</span>
                            <span class="info-val"><?php echo htmlspecialchars($p['worker_mobile'] ?? 'N/A'); ?></span>
                        </div>
                    <?php else: ?>
                        <p style="font-size:0.85rem; color:var(--text-muted); text-align:center; padding:10px 0;">No Field Officer assigned yet. Select an officer above to assign.</p>
                    <?php endif; ?>
                </div>

                <!-- CITIZEN REPORTER INFO -->
                <div class="card">
                    <div class="card-title">
                        <i class="fa-solid fa-user-tie" style="color:var(--brand-primary);"></i> Citizen Reporter Details
                    </div>
                    <div class="info-row">
                        <span class="info-label">Reporter Name</span>
                        <span class="info-val"><?php echo htmlspecialchars($p['reporter_name'] ?? 'Citizen User'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-val"><?php echo htmlspecialchars($p['reporter_email'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Reported On</span>
                        <span class="info-val"><?php echo date('M d, Y • h:i A', strtotime($p['created_at'])); ?></span>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- PHOTO LIGHTBOX MODAL -->
    <div class="modal-bg" id="photoModal" onclick="closePhotoOnOverlay(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <span style="position:absolute; top:-18px; right:-18px; width:44px; height:44px; background:#ef4444; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.5rem; font-weight:800; cursor:pointer;" onclick="closePhoto()">&times;</span>
            <img id="modalImg" src="" alt="Full Preview">
        </div>
    </div>

    <script>
        function zoomPhoto(src) {
            document.getElementById('modalImg').src = src;
            document.getElementById('photoModal').classList.add('active');
        }

        function closePhoto() {
            document.getElementById('photoModal').classList.remove('active');
        }

        function closePhotoOnOverlay(event) {
            if (event.target.classList.contains('modal-bg')) {
                closePhoto();
            }
        }

        window.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closePhoto();
            }
        });
    </script>
</body>
</html>
