<?php
session_start();
include('../db/connection.php');
include('../lang.php');

// Check if worker is logged in
if (!isset($_SESSION['worker_id'])) {
    header("Location: workerlogin.php");
    exit();
}

$worker_id = $_SESSION['worker_id'];
$worker_name = $_SESSION['workername'] ?? 'Officer';

// Handle after photo upload and submission
$msg = "";
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_completion'])){
    $problem_id = intval($_POST['problem_id']);
    $status = $_POST['status'];

    if(isset($_FILES['after_photo']) && $_FILES['after_photo']['error'] === 0){
        $ext = pathinfo($_FILES['after_photo']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('after_').'.'.$ext;
        $target_dir = "../uploads/after_photos/";
        if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $target_file = $target_dir.$filename;

        if(move_uploaded_file($_FILES['after_photo']['tmp_name'], $target_file)){
            $update_sql = "UPDATE problems SET after_photo='$target_file', status='$status', completed_at=NOW() WHERE id='$problem_id' AND worker_id='$worker_id'";
            if(mysqli_query($conn, $update_sql)){
                $msg = "✅ Resolution photo submitted successfully!";
            } else {
                $msg = "❌ Error updating database: " . mysqli_error($conn);
            }
        }
    } else {
        $msg = "❌ Please select a valid resolution photo.";
    }
}

// Fetch all problems assigned to this worker
$problems_query = "
    SELECT * FROM problems
    WHERE worker_id='$worker_id'
    ORDER BY created_at DESC
";
$problems_result = mysqli_query($conn, $problems_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Field Officer Dashboard - CivicConnect</title>
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
}

header {
    background: #ffffff;
    border-bottom: 1px solid var(--border-color);
    padding: 16px 36px;
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
    font-size: 1.3rem;
    font-weight: 800;
    color: var(--text-dark);
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 16px;
}

.officer-tag {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 700;
}

.logout-btn {
    color: #ef4444;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 6px;
}

main {
    max-width: 1100px;
    margin: 32px auto;
    padding: 0 20px;
    width: 100%;
    flex: 1;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
}

.dashboard-header h2 {
    font-size: 1.75rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.msg-banner {
    padding: 12px 18px;
    border-radius: 12px;
    font-weight: 600;
    margin-bottom: 24px;
}
.msg-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
.msg-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

.task-grid {
    display: grid;
    gap: 24px;
}

.task-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    padding: 24px;
    box-shadow: var(--shadow);
    transition: transform 0.2s ease;
}

.task-card:hover {
    transform: translateY(-2px);
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 14px;
    margin-bottom: 16px;
}

.task-id {
    font-weight: 800;
    color: var(--text-dark);
    font-size: 1.1rem;
}

.badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
}

.badge-pending { background: #fef3c7; color: #92400e; }
.badge-inprogress { background: #e0f2fe; color: #075985; }
.badge-completed { background: #d1fae5; color: #065f46; }

.task-body {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 24px;
}

@media (max-width: 850px) {
    .task-body { grid-template-columns: 1fr; }
}

.task-info p {
    margin-bottom: 10px;
    line-height: 1.5;
    color: #334155;
}

.task-info strong {
    color: var(--text-dark);
}

.photo-gallery {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.photo-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px;
    text-align: center;
}

.photo-box span {
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--text-muted);
    display: block;
    margin-bottom: 6px;
}

.photo-box img {
    max-width: 100%;
    height: 160px;
    object-fit: cover;
    border-radius: 8px;
}

.translate-btn {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    margin-left: 8px;
    transition: 0.2s ease;
}

.translate-btn:hover {
    background: #2563eb;
    color: white;
}

.action-btn {
    background: var(--primary-gradient);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 10px;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 14px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    transition: all 0.2s ease;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: #ffffff;
    border-radius: 20px;
    padding: 32px;
    width: 90%;
    max-width: 480px;
    position: relative;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
}

.modal-close {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 1.4rem;
    color: var(--text-muted);
    cursor: pointer;
}

.modal h3 {
    margin-bottom: 20px;
    font-weight: 800;
    font-size: 1.3rem;
}

.form-group-modal {
    margin-bottom: 18px;
}

.form-group-modal label {
    display: block;
    font-weight: 700;
    font-size: 0.9rem;
    margin-bottom: 6px;
}

.form-group-modal input[type="file"],
.form-group-modal select {
    width: 100%;
    padding: 12px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    font-family: inherit;
}
</style>
</head>
<body>

<header>
    <a href="../index.php" class="logo-group" style="text-decoration:none;">
        <div class="logo-badge" style="background:linear-gradient(135deg, #10b981 0%, #0284c7 100%); box-shadow:0 4px 14px rgba(16, 185, 129, 0.35);"><i class="fa-solid fa-handshake"></i></div>
        <span class="logo-title">Civic<span style="color:#0284c7;">Connect</span> Officer Portal</span>
    </a>
    <div class="user-profile">
        <span class="officer-tag"><i class="fa-solid fa-user-shield"></i> Officer <?php echo htmlspecialchars($worker_name); ?></span>
        <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        <form method="POST" style="display:inline-flex; align-items:center; margin-left:10px;">
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
        <h2>Assigned Municipal Complaints</h2>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="msg-banner <?php echo strpos($msg, '✅')!==false ? 'msg-success':'msg-error'; ?>">
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <?php if(mysqli_num_rows($problems_result) > 0): ?>
        <div class="task-grid">
            <?php while($problem = mysqli_fetch_assoc($problems_result)): ?>
                <div class="task-card">
                    <div class="task-header">
                        <span class="task-id"><i class="fa-solid fa-hashtag"></i> Issue #<?php echo $problem['id']; ?></span>
                        <div>
                            <?php if(!empty($problem['ai_severity'])): ?>
                                <?php 
                                    $sev = $problem['ai_severity'];
                                    $sevColor = $sev==='Critical'?'#dc2626':($sev==='High'?'#d97706':'#059669');
                                ?>
                                <span style="background:<?php echo $sevColor; ?>; color:#fff; padding:4px 10px; border-radius:12px; font-size:0.75rem; font-weight:800; margin-right:6px;">
                                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $sev; ?> Risk
                                </span>
                            <?php endif; ?>

                            <span class="badge 
                                <?php 
                                    echo $problem['status']=='Pending' ? 'badge-pending' : 
                                        ($problem['status']=='In Progress' ? 'badge-inprogress' : 'badge-completed'); 
                                ?>">
                                <?php echo $problem['status']; ?>
                            </span>
                        </div>
                    </div>

                    <div class="task-body">
                        <div class="task-info">
                            <p>
                                <strong><i class="fa-solid fa-folder"></i> Category:</strong> 
                                <?php echo htmlspecialchars($problem['category']); ?>
                            </p>
                            <p>
                                <strong><i class="fa-solid fa-location-dot"></i> Location:</strong> 
                                <?php echo htmlspecialchars($problem['street']); ?>
                            </p>
                            <p>
                                <strong><i class="fa-solid fa-align-left"></i> Description:</strong> 
                                <span id="desc_<?php echo $problem['id']; ?>"><?php echo htmlspecialchars($problem['description']); ?></span>
                                <button type="button" class="translate-btn" onclick="translateText(<?php echo $problem['id']; ?>, 'en')">
                                    <i class="fa-solid fa-language"></i> 🌐 Translate
                                </button>
                            </p>
                            
                            <?php if($problem['status'] != 'Completed'): ?>
                                <button class="action-btn openModalBtn" data-id="<?php echo $problem['id']; ?>">
                                    <i class="fa-solid fa-camera"></i> Submit Resolution Evidence
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="photo-gallery">
                            <?php if(!empty($problem['photo'])): ?>
                                <div class="photo-box">
                                    <span>📸 Before Photo (Citizen)</span>
                                    <img src="<?php echo htmlspecialchars($problem['photo']); ?>" alt="Before Photo">
                                </div>
                            <?php endif; ?>

                            <?php if(!empty($problem['after_photo'])): ?>
                                <div class="photo-box" style="border-color:#10b981;">
                                    <span style="color:#059669;">✅ After Photo (Resolution)</span>
                                    <img src="<?php echo htmlspecialchars($problem['after_photo']); ?>" alt="After Photo">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div style="background:#fff; padding:40px; text-align:center; border-radius:16px; border:1px solid #e2e8f0;">
            <i class="fa-solid fa-clipboard-check" style="font-size:3rem; color:#cbd5e1; margin-bottom:12px;"></i>
            <h3 style="color:#64748b;">No Assigned Complaints</h3>
            <p style="color:#9499b7; font-size:0.9rem;">You have no pending tasks assigned in your queue.</p>
        </div>
    <?php endif; ?>
</main>

<!-- Resolution Upload Modal -->
<div id="completionModal" class="modal">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <h3><i class="fa-solid fa-circle-check" style="color:#059669;"></i> Submit Work Proof</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" id="modal_problem_id" name="problem_id" value="">
            
            <div class="form-group-modal">
                <label><i class="fa-solid fa-image"></i> Upload Resolution Photo (After Photo):</label>
                <input type="file" name="after_photo" accept="image/*" required>
            </div>

            <div class="form-group-modal">
                <label><i class="fa-solid fa-list-check"></i> Update Task Status:</label>
                <select name="status" required>
                    <option value="In Progress">In Progress</option>
                    <option value="Completed" selected>Completed</option>
                </select>
            </div>

            <button type="submit" name="submit_completion" class="action-btn" style="width:100%; justify-content:center;">
                <i class="fa-solid fa-paper-plane"></i> Submit Resolution Evidence
            </button>
        </form>
    </div>
</div>

<script>
$(document).ready(function(){
    $(".openModalBtn").click(function(){
        var problemId = $(this).data("id");
        $("#modal_problem_id").val(problemId);
        $("#completionModal").css("display", "flex");
    });

    $(".modal-close").click(function(){
        $("#completionModal").hide();
    });

    $(window).click(function(e){
        if($(e.target).hasClass("modal")){
            $("#completionModal").hide();
        }
    });
});

/**
 * Free AI Dynamic Translation Handler
 */
function translateText(id, targetLang) {
    var $elem = $('#desc_' + id);
    var text = $elem.text();
    
    $elem.html('<i class="fa-solid fa-spinner fa-spin" style="color:#2563eb;"></i> Translating...');
    
    $.ajax({
        url: '../api/ai_translate.php',
        type: 'POST',
        data: { text: text, target: targetLang },
        dataType: 'json',
        success: function(res) {
            if(res && res.success) {
                $elem.html(res.translated + ' <small style="color:#059669; font-weight:bold;">(AI Translated from ' + res.source_language + ')</small>');
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
