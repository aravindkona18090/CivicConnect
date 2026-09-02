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

$task_id = intval($_GET['id'] ?? 0);
if (!$task_id) {
    header("Location: workerdashboard.php");
    exit();
}

$msg = "";
$msg_type = "";

// Handle after photo upload and completion submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_completion'])) {
    $status = $_POST['status'] ?? 'Completed';

    if (isset($_FILES['after_photo']) && $_FILES['after_photo']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['after_photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (!in_array($ext, $allowed)) {
            $msg = "❌ Invalid photo format. Please upload JPG, PNG, or WEBP.";
            $msg_type = "error";
        } else {
            $filename = uniqid('after_') . '.' . $ext;
            $target_dir = "../uploads/after_photos/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $target_file = $target_dir . $filename;

            if (move_uploaded_file($_FILES['after_photo']['tmp_name'], $target_file)) {
                $update_sql = "UPDATE problems SET after_photo = '$target_file', status = '$status', completed_at = NOW() WHERE id = '$task_id' AND worker_id = '$worker_id'";
                if (mysqli_query($conn, $update_sql)) {
                    $msg = "✅ Resolution photo submitted successfully & task marked as Completed!";
                    $msg_type = "success";
                } else {
                    $msg = "❌ Error updating database: " . mysqli_error($conn);
                    $msg_type = "error";
                }
            } else {
                $msg = "❌ Failed to save uploaded photo.";
                $msg_type = "error";
            }
        }
    } else {
        $msg = "❌ Please select a valid resolution photo.";
        $msg_type = "error";
    }
}

// Fetch Task Details with Citizen Reporter Info
$query = "
    SELECT 
        p.*, 
        u.username as citizen_username, 
        u.name as citizen_name, 
        u.mobile as citizen_mobile, 
        u.email as citizen_email
    FROM problems p
    LEFT JOIN people u ON p.user_id = u.id
    WHERE p.id = '$task_id' AND p.worker_id = '$worker_id'
";
$res = mysqli_query($conn, $query);
$task = ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;

if (!$task) {
    header("Location: workerdashboard.php");
    exit();
}

$isCompleted = ($task['status'] === 'Completed');

// Google Maps URL
if (!empty($task['lat']) && !empty($task['lng'])) {
    $maps_url = "https://www.google.com/maps/dir/?api=1&destination=" . urlencode($task['lat'] . ',' . $task['lng']);
} else {
    $maps_url = "https://www.google.com/maps/search/?api=1&query=" . urlencode($task['street'] . ', ' . ($task['area'] ?? '') . ', ' . ($task['city'] ?? ''));
}
?>
<!DOCTYPE html>
<html lang="<?php echo $selectedLang; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Work Order #<?php echo $task['id']; ?> Inspection - CivicConnect</title>
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

.container {
    max-width: 1180px;
    margin: 28px auto;
    padding: 0 20px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--text-muted);
    font-weight: 700;
    font-size: 0.9rem;
    text-decoration: none;
    margin-bottom: 20px;
    transition: color 0.2s ease;
}
.back-link:hover { color: var(--primary); }

.detail-grid {
    display: grid;
    grid-template-columns: 1fr 420px;
    gap: 24px;
}

@media (max-width: 900px) {
    .detail-grid { grid-template-columns: 1fr; }
}

.card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    padding: 26px;
    box-shadow: var(--shadow);
    margin-bottom: 24px;
}

.card-title {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--text-dark);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 12px;
}

.badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-inprogress { background: #e0f2fe; color: #0369a1; }
.badge-completed { background: #d1fae5; color: #065f46; }

.description-text {
    font-size: 1.05rem;
    line-height: 1.6;
    color: #1e293b;
    background: #f8fafc;
    padding: 16px 20px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    margin-bottom: 14px;
}

.translate-btn {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: 0.2s ease;
}
.translate-btn:hover { background: #2563eb; color: white; }

.info-list {
    display: grid;
    gap: 12px;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.info-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    font-size: 1rem;
    flex-shrink: 0;
}

.info-content label {
    display: block;
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}

.info-content p {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-dark);
}

.nav-maps-btn {
    background: #0284c7;
    color: #ffffff;
    padding: 12px 18px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.9rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
    margin-top: 10px;
    transition: all 0.2s ease;
}
.nav-maps-btn:hover {
    background: #0369a1;
    transform: translateY(-2px);
}

.photo-preview-card {
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--border-color);
    margin-bottom: 16px;
}

.photo-preview-card img {
    width: 100%;
    max-height: 260px;
    object-fit: cover;
    display: block;
    cursor: pointer;
    transition: transform 0.2s ease;
}
.photo-preview-card img:hover { transform: scale(1.02); }

.photo-preview-card .photo-label {
    padding: 8px 14px;
    font-size: 0.8rem;
    font-weight: 700;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.msg-banner {
    padding: 14px 20px;
    border-radius: 12px;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.msg-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
.msg-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

.submit-btn {
    width: 100%;
    padding: 14px;
    background: var(--primary-gradient);
    color: #ffffff;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
    transition: all 0.25s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-top: 10px;
}
.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
}

.preview-thumb-box {
    display: none;
    margin-top: 12px;
    text-align: center;
    border: 1.5px dashed #10b981;
    padding: 12px;
    border-radius: 12px;
    background: #f0fdf4;
}
.preview-thumb-box img {
    max-height: 180px;
    border-radius: 8px;
    object-fit: cover;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0; top: 0; width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.modal-content {
    background: #ffffff;
    border-radius: 16px;
    max-width: 650px;
    width: 100%;
    padding: 20px;
    position: relative;
    text-align: center;
}
.modal-close {
    position: absolute;
    right: 14px; top: 14px;
    background: #fee2e2;
    color: #dc2626;
    border: none;
    width: 32px; height: 32px;
    border-radius: 50%;
    cursor: pointer;
    font-weight: bold;
}
</style>
</head>
<body>

<header>
    <a href="workerdashboard.php" class="logo-group">
        <div class="logo-badge"><i class="fa-solid fa-handshake"></i></div>
        <span class="logo-title">Civic<span style="color:#059669;">Connect</span> Officer Workspace</span>
    </a>
    <div style="display:flex; align-items:center; gap:12px;">
        <span style="background:rgba(16,185,129,0.1); color:#059669; padding:6px 14px; border-radius:20px; font-size:0.85rem; font-weight:700;">
            <i class="fa-solid fa-user-shield"></i> Officer <?php echo htmlspecialchars($worker_name); ?>
        </span>
        <a href="../logout.php" style="color:#ef4444; background:#fee2e2; padding:6px 12px; border-radius:8px; text-decoration:none; font-weight:700; font-size:0.82rem;">Logout</a>
    </div>
</header>

<div class="container">
    <a href="workerdashboard.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Work Orders Dashboard</a>

    <?php if (!empty($msg)): ?>
        <div class="msg-banner <?php echo $msg_type==='success' ? 'msg-success':'msg-error'; ?>">
            <i class="fa-solid <?php echo $msg_type==='success' ? 'fa-circle-check':'fa-triangle-exclamation'; ?>"></i>
            <div><?php echo htmlspecialchars($msg); ?></div>
        </div>
    <?php endif; ?>

    <div class="detail-grid">
        <!-- Left Column: Details, Location, Citizen -->
        <div>
            <!-- Problem Details Card -->
            <div class="card">
                <div class="card-title">
                    <div style="display:flex; align-items:center; justify-content:space-between; width:100%; flex-wrap:wrap; gap:10px;">
                        <span><i class="fa-solid fa-hashtag" style="color:#059669;"></i> Work Order #<?php echo $task['id']; ?></span>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <?php if(!empty($task['ai_severity'])): ?>
                                <?php 
                                    $sevColor = $task['ai_severity'] === 'Critical' ? '#dc2626' : ($task['ai_severity'] === 'High' ? '#ea580c' : '#16a34a');
                                ?>
                                <span class="badge" style="background:<?php echo $sevColor; ?>; color:#fff;">
                                    <i class="fa-solid fa-shield-halved"></i> <?php echo htmlspecialchars($task['ai_severity']); ?> Priority
                                </span>
                            <?php endif; ?>
                            <span class="badge <?php echo $isCompleted ? 'badge-completed' : 'badge-inprogress'; ?>">
                                <i class="fa-solid <?php echo $isCompleted ? 'fa-circle-check':'fa-spinner fa-spin'; ?>"></i>
                                <?php echo htmlspecialchars($task['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom:12px;">
                    <span style="background:#e0f2fe; color:#0369a1; padding:4px 12px; border-radius:6px; font-weight:700; font-size:0.85rem;">
                        <i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($task['category']); ?>
                    </span>
                </div>

                <div class="description-text">
                    <span id="task_desc"><?php echo htmlspecialchars($task['description']); ?></span>
                </div>

                <button type="button" class="translate-btn" onclick="translateTaskDesc('<?php echo $task['id']; ?>', '<?php echo $selectedLang; ?>')">
                    <i class="fa-solid fa-language"></i> Translate Description to <?php echo strtoupper($selectedLang); ?>
                </button>
            </div>

            <!-- Location & Navigation Card -->
            <div class="card">
                <div class="card-title">
                    <i class="fa-solid fa-location-dot" style="color:#ef4444;"></i> Site Location & Navigation
                </div>

                <div class="info-list">
                    <div class="info-item">
                        <div class="info-icon" style="background:#fee2e2; color:#ef4444;"><i class="fa-solid fa-map-pin"></i></div>
                        <div class="info-content">
                            <label>Street Address</label>
                            <p><?php echo htmlspecialchars($task['street']); ?></p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon" style="background:#e0f2fe; color:#0284c7;"><i class="fa-solid fa-city"></i></div>
                        <div class="info-content">
                            <label>Area & City</label>
                            <p><?php echo htmlspecialchars(($task['area'] ?? 'City Central') . ', ' . ($task['city'] ?? 'Municipal') . (!empty($task['pincode']) ? ' - ' . $task['pincode'] : '')); ?></p>
                        </div>
                    </div>
                </div>

                <div style="margin-top:16px;">
                    <a href="<?php echo $maps_url; ?>" target="_blank" class="nav-maps-btn">
                        <i class="fa-solid fa-map-location-dot"></i> Open Turn-by-Turn GPS in Google Maps
                    </a>
                </div>
            </div>

            <!-- Citizen Reporter Card -->
            <div class="card">
                <div class="card-title">
                    <i class="fa-solid fa-user-check" style="color:#0284c7;"></i> Reporting Citizen Information
                </div>

                <div class="info-list">
                    <div class="info-item">
                        <div class="info-icon"><i class="fa-regular fa-user"></i></div>
                        <div class="info-content">
                            <label>Resident Name</label>
                            <p><?php echo htmlspecialchars($task['citizen_name'] ?? $task['citizen_username'] ?? 'Local Resident'); ?></p>
                        </div>
                    </div>

                    <?php if(!empty($task['citizen_mobile'])): ?>
                        <div class="info-item">
                            <div class="info-icon" style="background:#d1fae5; color:#059669;"><i class="fa-solid fa-phone"></i></div>
                            <div class="info-content">
                                <label>Contact Phone</label>
                                <p><a href="tel:<?php echo htmlspecialchars($task['citizen_mobile']); ?>" style="color:#0284c7; text-decoration:none; font-weight:bold;"><?php echo htmlspecialchars($task['citizen_mobile']); ?></a> (Click to Call)</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="info-item">
                        <div class="info-icon"><i class="fa-regular fa-clock"></i></div>
                        <div class="info-content">
                            <label>Reported On</label>
                            <p><?php echo date('M d, Y • h:i A', strtotime($task['created_at'])); ?></p>
                        </div>
                    </div>

                    <?php if(!empty($task['completed_at'])): ?>
                        <div class="info-item">
                            <div class="info-icon" style="background:#d1fae5; color:#059669;"><i class="fa-solid fa-circle-check"></i></div>
                            <div class="info-content">
                                <label>Resolved On</label>
                                <p style="color:#059669; font-weight:bold;"><?php echo date('M d, Y • h:i A', strtotime($task['completed_at'])); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Before Photo & Resolution Upload -->
        <div>
            <!-- Before Photo Card -->
            <div class="card">
                <div class="card-title">
                    <i class="fa-solid fa-camera" style="color:#0284c7;"></i> Citizen Report Photo (Before)
                </div>

                <?php if(!empty($task['photo'])): ?>
                    <div class="photo-preview-card">
                        <div class="photo-label">
                            <span>📷 Initial Complaint Evidence</span>
                            <small>Click to zoom</small>
                        </div>
                        <img src="<?php echo htmlspecialchars($task['photo']); ?>" alt="Before Photo" onclick="zoomPhoto('<?php echo htmlspecialchars($task['photo']); ?>', '📷 Citizen Complaint Photo #<?php echo $task['id']; ?>')">
                    </div>
                <?php else: ?>
                    <p style="color:var(--text-muted); font-size:0.9rem; font-style:italic;">No photo attached with this complaint.</p>
                <?php endif; ?>
            </div>

            <!-- Resolution / After Photo Card -->
            <div class="card">
                <div class="card-title">
                    <i class="fa-solid fa-circle-check" style="color:#059669;"></i> Resolution Proof (After)
                </div>

                <?php if ($isCompleted && !empty($task['after_photo'])): ?>
                    <div class="photo-preview-card" style="border-color:#10b981;">
                        <div class="photo-label" style="background:#ecfdf5; color:#065f46;">
                            <span>✅ Verified Fixed Resolution Photo</span>
                            <small>Click to zoom</small>
                        </div>
                        <img src="<?php echo htmlspecialchars($task['after_photo']); ?>" alt="After Photo" onclick="zoomPhoto('<?php echo htmlspecialchars($task['after_photo']); ?>', '✅ Officer Fixed Proof #<?php echo $task['id']; ?>')">
                    </div>
                    <div style="background:#f0fdf4; border:1px solid #a7f3d0; padding:14px; border-radius:12px; text-align:center; color:#065f46; font-weight:700; font-size:0.9rem;">
                        <i class="fa-solid fa-circle-check"></i> Work Order Successfully Completed & Archived
                    </div>
                <?php else: ?>
                    <p style="color:var(--text-muted); font-size:0.88rem; margin-bottom:16px;">
                        Once the field repair work is completed on site, take or upload a photo showing the resolved issue.
                    </p>

                    <form method="POST" action="task_details.php?id=<?php echo $task['id']; ?>" enctype="multipart/form-data">
                        <div style="margin-bottom:14px;">
                            <label style="display:block; font-weight:700; font-size:0.85rem; margin-bottom:6px;">Upload Fixed Proof Photo</label>
                            <input type="file" name="after_photo" id="afterPhotoInput" accept="image/*" required onchange="previewProofImage(this)" style="width:100%; padding:10px; border:1.5px solid var(--border-color); border-radius:10px; font-family:inherit;">
                            <div id="proofPreviewBox" class="preview-thumb-box">
                                <img id="proofPreviewImg" src="" alt="Proof Preview">
                                <div style="font-size:0.75rem; color:#059669; font-weight:bold; margin-top:4px;">Photo selected & ready to submit!</div>
                            </div>
                        </div>

                        <div style="margin-bottom:14px;">
                            <label style="display:block; font-weight:700; font-size:0.85rem; margin-bottom:6px;">Update Task Status</label>
                            <select name="status" required style="width:100%; padding:10px; border:1.5px solid var(--border-color); border-radius:10px; font-family:inherit; font-weight:bold;">
                                <option value="Completed" selected>Mark as Completed ✅</option>
                                <option value="In Progress">In Progress (Work Ongoing)</option>
                            </select>
                        </div>

                        <button type="submit" name="submit_completion" class="submit-btn">
                            <i class="fa-solid fa-paper-plane"></i> Submit Resolution Evidence
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Photo Zoom Modal -->
<div id="photoZoomModal" class="modal" onclick="closeZoom(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="closeZoomModal()">&times;</button>
        <h4 id="zoomTitle" style="margin-bottom:12px; color:#0f172a; font-size:1.05rem;">Inspection Photo</h4>
        <img id="zoomImg" src="" style="width:100%; max-height:450px; object-fit:contain; border-radius:10px;">
    </div>
</div>

<script>
function previewProofImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#proofPreviewImg').attr('src', e.target.result);
            $('#proofPreviewBox').slideDown();
        };
        reader.readAsDataURL(input.files[0]);
    }
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
    if (e.key === 'Escape') closeZoomModal();
});

function translateTaskDesc(id, targetLang) {
    var $elem = $('#task_desc');
    var text = $elem.text();
    
    $elem.html('<i class="fa-solid fa-spinner fa-spin" style="color:#2563eb;"></i> Translating description...');
    
    $.ajax({
        url: '../api/ai_translate.php',
        type: 'POST',
        data: { text: text, target: targetLang },
        dataType: 'json',
        success: function(res) {
            if(res && res.success) {
                $elem.html(res.translated + ' <br><small style="color:#059669; font-weight:bold;">(AI Translated from ' + res.source_language + ')</small>');
            } else {
                $elem.text(text);
            }
        },
        error: function() {
            $elem.text(text);
        }
    });
}
</script>

</body>
</html>
