<?php
session_start();
include("../db/connection.php");
include("../lang.php"); 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$selectedLang = $_SESSION['language'] ?? 'en';
$user_id = $_SESSION['user_id'];

$problems_query = "
    SELECT p.*, w.name as worker_name, w.mobile as worker_phone
    FROM problems p
    LEFT JOIN workers w ON p.worker_id = w.id
    WHERE p.user_id = '$user_id'
    ORDER BY p.id DESC
";
$problems_result = mysqli_query($conn, $problems_query);
?>
<!DOCTYPE html>
<html lang="<?php echo $selectedLang; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $lang[$selectedLang]['my_problems'] ?? 'My Complaints'; ?> - CivicConnect</title>
<!-- Font Awesome & Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
  --primary: #0284c7;
  --primary-hover: #0369a1;
  --primary-gradient: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
  --bg-slate: #f8fafc;
  --card-bg: #ffffff;
  --text-dark: #0f172a;
  --text-muted: #64748b;
  --border-color: #e2e8f0;
  --shadow-light: 0 10px 25px -5px rgba(15, 23, 42, 0.06);
}

body {
  margin: 0;
  font-family: 'Plus Jakarta Sans', sans-serif;
  background: var(--bg-slate);
  color: var(--text-dark);
  min-height: 100vh;
  padding-bottom: 60px;
}

header {
  background: #ffffff;
  padding: 16px 36px;
  border-bottom: 1px solid var(--border-color);
  box-shadow: 0 4px 12px rgba(0,0,0,0.03);
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: sticky;
  top: 0;
  z-index: 100;
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
  background: linear-gradient(135deg, #10b981 0%, #0284c7 100%);
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

nav a {
  color: var(--text-muted);
  margin-left: 20px;
  text-decoration: none;
  font-weight: 700;
  font-size: 0.9rem;
  transition: color 0.2s ease;
}

nav a:hover {
  color: var(--primary);
}

main {
  max-width: 1100px;
  margin: 36px auto;
  padding: 0 20px;
}

.page-title {
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--text-dark);
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.table-card {
  background: var(--card-bg);
  border-radius: 16px;
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-light);
  overflow: hidden;
  margin-top: 20px;
}

table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

th, td {
  padding: 16px 20px;
  vertical-align: middle;
}

th {
  background: #f8fafc;
  color: var(--text-muted);
  font-weight: 700;
  font-size: 0.82rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 1px solid var(--border-color);
}

td {
  border-bottom: 1px solid var(--border-color);
  color: #334155;
  font-size: 0.92rem;
}

tr:last-child td {
  border-bottom: none;
}

tbody tr:hover {
  background: #fbfdff;
}

.badge-status {
  padding: 5px 12px;
  border-radius: 20px;
  font-weight: 700;
  font-size: 0.8rem;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.status-pending { background: #fef3c7; color: #92400e; }
.status-inprogress { background: #e0f2fe; color: #0369a1; }
.status-completed { background: #d1fae5; color: #065f46; }

.thumb-preview {
  width: 54px;
  height: 54px;
  border-radius: 8px;
  object-fit: cover;
  cursor: pointer;
  border: 1.5px solid var(--border-color);
  transition: transform 0.15s ease;
}
.thumb-preview:hover {
  transform: scale(1.08);
}

/* Modal Lightbox */
.modal-overlay {
  display: none;
  position: fixed;
  top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(15, 23, 42, 0.8);
  backdrop-filter: blur(4px);
  z-index: 2000;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.modal-content {
  background: #fff;
  border-radius: 16px;
  max-width: 600px;
  width: 100%;
  padding: 20px;
  position: relative;
  text-align: center;
}
.modal-close {
  position: absolute;
  top: 12px; right: 14px;
  background: #fee2e2;
  color: #dc2626;
  border: none;
  width: 32px; height: 32px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1rem;
  font-weight: bold;
}
</style>
</head>
<body>

<header>
  <a href="peopledashboard.php" class="logo-group">
    <div class="logo-badge"><i class="fa-solid fa-handshake"></i></div>
    <span class="logo-title">Civic<span style="color:#0284c7;">Connect</span></span>
  </a>
  <nav>
    <a href="peopledashboard.php"><i class="fa-solid fa-house"></i> <?php echo $lang[$selectedLang]['dashboard'] ?? 'Dashboard'; ?></a>
    <a href="peoplemyproblems.php" style="color:var(--primary);"><i class="fa-solid fa-list-check"></i> <?php echo $lang[$selectedLang]['my_problems'] ?? 'My Complaints'; ?></a>
    <a href="peopleprofile.php"><i class="fa-solid fa-user"></i> <?php echo $lang[$selectedLang]['profile'] ?? 'Profile'; ?></a>
    <a href="../logout.php" style="color:#ef4444;"><i class="fa-solid fa-right-from-bracket"></i> <?php echo $lang[$selectedLang]['logout'] ?? 'Logout'; ?></a>
  </nav>
  <form method="POST" style="display:inline-flex; align-items:center; gap:6px;">
    <select name="language" onchange="this.form.submit()" style="padding:8px 12px; border-radius:8px; border:1px solid #cbd5e1; font-weight:600; font-family:inherit; cursor:pointer;" title="Select Language">
      <option value="en" <?php if ($selectedLang=='en') echo 'selected'; ?>>🌐 English</option>
      <option value="te" <?php if ($selectedLang=='te') echo 'selected'; ?>>🌐 తెలుగు (Telugu)</option>
      <option value="hn" <?php if ($selectedLang=='hn') echo 'selected'; ?>>🌐 हिंदी (Hindi)</option>
      <option value="kn" <?php if ($selectedLang=='kn') echo 'selected'; ?>>🌐 ಕನ್ನಡ (Kannada)</option>
    </select>
  </form>
</header>

<main>
    <div class="page-title">
        <i class="fa-solid fa-clipboard-list" style="color:var(--primary);"></i> 
        <?php echo $lang[$selectedLang]['my_problems'] ?? 'My Tracked Complaints'; ?>
    </div>
    <p style="color:var(--text-muted); font-size:0.92rem; margin-bottom:20px;">Track the real-time status of your reported municipal issues, assigned officers, and resolution proof photos.</p>

    <div class="table-card">
        <?php if ($problems_result && mysqli_num_rows($problems_result) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID & Category</th>
                        <th>Complaint Details</th>
                        <th>Photos</th>
                        <th>Assigned Officer</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($problems_result)): 
                        $st = $row['status'] ?? 'Pending';
                        $stClass = $st==='Completed'?'status-completed':($st==='In Progress'?'status-inprogress':'status-pending');
                        $stIcon = $st==='Completed'?'fa-circle-check':($st==='In Progress'?'fa-spinner fa-spin':'fa-clock');
                    ?>
                        <tr>
                            <td>
                                <strong>#<?php echo $row['id']; ?></strong><br>
                                <span style="background:#e0f2fe; color:#0369a1; padding:3px 8px; border-radius:6px; font-weight:700; font-size:0.8rem; display:inline-block; margin-top:4px;">
                                    <?php echo htmlspecialchars($row['category']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-weight:600; color:#0f172a; margin-bottom:4px; max-width:320px;"><?php echo htmlspecialchars($row['description']); ?></div>
                                <div style="color:var(--text-muted); font-size:0.82rem;"><i class="fa-solid fa-location-dot" style="color:#ef4444;"></i> <?php echo htmlspecialchars($row['street'] . ($row['area'] ? ', ' . $row['area'] : '')); ?></div>
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <?php if (!empty($row['photo'])): ?>
                                        <div title="Your Report Photo">
                                            <img src="<?php echo htmlspecialchars($row['photo']); ?>" class="thumb-preview" onclick="zoomPhoto('<?php echo htmlspecialchars($row['photo']); ?>', '📷 Your Original Report Photo')">
                                            <div style="font-size:0.7rem; color:var(--text-muted); text-align:center;">Before</div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($row['after_photo'])): ?>
                                        <div title="Field Officer Resolution Proof Photo">
                                            <img src="<?php echo htmlspecialchars($row['after_photo']); ?>" class="thumb-preview" style="border:2px solid #10b981;" onclick="zoomPhoto('<?php echo htmlspecialchars($row['after_photo']); ?>', '✅ Field Officer Fixed Proof Photo')">
                                            <div style="font-size:0.7rem; color:#059669; font-weight:bold; text-align:center;">After</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($row['worker_name'])): ?>
                                    <div style="font-weight:700; color:#0f172a; font-size:0.88rem;"><i class="fa-solid fa-hard-hat" style="color:#0284c7;"></i> <?php echo htmlspecialchars($row['worker_name']); ?></div>
                                    <?php if (!empty($row['worker_phone'])): ?>
                                        <div style="font-size:0.8rem; color:var(--text-muted); margin-top:2px;"><i class="fa-solid fa-phone" style="font-size:0.75rem;"></i> <?php echo htmlspecialchars($row['worker_phone']); ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-size:0.82rem; font-style:italic;">Awaiting Officer Allocation</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-status <?php echo $stClass; ?>">
                                    <i class="fa-solid <?php echo $stIcon; ?>"></i> <?php echo $st; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align:center; padding:50px 20px; color:var(--text-muted);">
                <i class="fa-solid fa-folder-open" style="font-size:2.5rem; color:#cbd5e1; margin-bottom:12px; display:block;"></i>
                <p style="font-weight:600;">No complaints reported yet.</p>
                <a href="peopledashboard.php" style="display:inline-block; margin-top:10px; background:#0284c7; color:#fff; padding:8px 16px; border-radius:8px; text-decoration:none; font-weight:bold; font-size:0.85rem;">+ Report a Problem</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Photo Zoom Modal -->
<div id="photoModal" class="modal-overlay" onclick="closeZoom(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="closeZoomModal()">&times;</button>
        <h4 id="modalTitle" style="margin-bottom:12px; color:#0f172a;">Inspection Photo</h4>
        <img id="modalImg" src="" style="width:100%; max-height:420px; object-fit:contain; border-radius:10px;">
    </div>
</div>

<script>
function zoomPhoto(src, title) {
    document.getElementById('modalImg').src = src;
    document.getElementById('modalTitle').innerText = title || 'Inspection Photo';
    document.getElementById('photoModal').style.display = 'flex';
}
function closeZoomModal() {
    document.getElementById('photoModal').style.display = 'none';
}
function closeZoom(e) {
    if (e.target.id === 'photoModal') closeZoomModal();
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeZoomModal();
});
</script>

</body>
</html>
