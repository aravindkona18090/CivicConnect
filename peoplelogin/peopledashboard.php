<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../db/connection.php';

session_start();

// Language switch
if (isset($_POST['language'])) {
    $_SESSION['language'] = $_POST['language'];
}
$selectedLang = $_SESSION['language'] ?? 'en';
include("../lang.php");

// Session variables
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'] ?? '';
$user_name = $_SESSION['username'] ?? 'Citizen';
$msg = "";

// Fetch citizen complaint metrics
$my_total = 0;
$my_pending = 0;
$my_inprogress = 0;
$my_completed = 0;

$r1 = mysqli_query($conn, "SELECT id FROM problems WHERE user_id='$user_id'");
if($r1) $my_total = mysqli_num_rows($r1);

$r2 = mysqli_query($conn, "SELECT id FROM problems WHERE user_id='$user_id' AND status='Pending'");
if($r2) $my_pending = mysqli_num_rows($r2);

$r3 = mysqli_query($conn, "SELECT id FROM problems WHERE user_id='$user_id' AND status='In Progress'");
if($r3) $my_inprogress = mysqli_num_rows($r3);

$r4 = mysqli_query($conn, "SELECT id FROM problems WHERE user_id='$user_id' AND status='Completed'");
if($r4) $my_completed = mysqli_num_rows($r4);

// Fetch recent 5 complaints by this citizen
$recent_complaints = mysqli_query($conn, "SELECT * FROM problems WHERE user_id='$user_id' ORDER BY id DESC LIMIT 5");

// Handle form submission
if (isset($_POST['submit_problem'])) {
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $lat = (double)($_POST['latitude'] ?? 0);
    $lng = (double)($_POST['longitude'] ?? 0);
    $address = trim($_POST['selectedAddress'] ?? '');

    // 1. File upload
    $photo = "";
    if (isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != "" && $_FILES['photo']['error'] == 0) {
        $uploadDir = __DIR__ . "/../uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . "_" . basename($_FILES['photo']['name']);
        $photo = "../uploads/" . $fileName;
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $fileName)) {
            $msg = "❌ Failed to upload photo!";
            $photo = "";
        }
    }

    // 2. Duplicate check within 45 meters (0.045 km)
    $radius = 0.045;
    $check_query = "
        SELECT id, description, category,
        (6371 * acos(
            cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) +
            sin(radians(?)) * sin(radians(lat))
        )) AS distance
        FROM problems
        WHERE category = ?
        HAVING distance <= ?
        LIMIT 1
    ";

    $stmt_check = $conn->prepare($check_query);
    if ($stmt_check === false) {
        $msg = "❌ DB error: " . $conn->error;
    } else {
        $stmt_check->bind_param("dddsd", $lat, $lng, $lat, $category, $radius);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($result && mysqli_num_rows($result) > 0) {
            $msg = "❌ " . ($lang[$selectedLang]['duplicate_report'] ?? "This problem is already reported within 45 meters!");
        } else {
            $status = 'Pending';
            $created_at = date('Y-m-d H:i:s');
            $ai_severity = trim($_POST['ai_severity'] ?? 'Medium');

            // 3. Check if ai_severity column exists in problems table
            $has_ai_col = false;
            $col_check = mysqli_query($conn, "SHOW COLUMNS FROM problems LIKE 'ai_severity'");
            if ($col_check && mysqli_num_rows($col_check) > 0) {
                $has_ai_col = true;
            }

            if ($has_ai_col) {
                $insert_query = "INSERT INTO problems (user_id, description, category, street, lat, lng, photo, status, created_at, ai_severity)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($insert_query);
                $stmt_insert->bind_param("isssddssss", $user_id, $description, $category, $address, $lat, $lng, $photo, $status, $created_at, $ai_severity);
            } else {
                $insert_query = "INSERT INTO problems (user_id, description, category, street, lat, lng, photo, status, created_at)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($insert_query);
                $stmt_insert->bind_param("isssddsss", $user_id, $description, $category, $address, $lat, $lng, $photo, $status, $created_at);
            }

            if ($stmt_insert && $stmt_insert->execute()) {
                $problem_id = $stmt_insert->insert_id;
                $msg = "✅ " . ($lang[$selectedLang]['report_new'] ?? "Problem reported") . " successfully!";

                // 4. Send emails if configured
                try {
                    if (class_exists('Resend') && getenv('RESEND_API_KEY')) {
                        $resend = Resend::client(getenv('RESEND_API_KEY'));
                        if (!empty($user_email)) {
                            $resend->emails->send([
                                'from' => 'CivicConnect <onboarding@resend.dev>',
                                'to' => [$user_email],
                                'subject' => 'CivicConnect - Problem Submission Confirmation',
                                'html' => "<h2>Dear {$user_name},</h2><p>Your problem has been successfully submitted (ID: {$problem_id}).</p>"
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    error_log("Resend Error: " . $e->getMessage());
                }
            } else {
                $msg = "❌ Error submitting problem: " . ($stmt_insert ? $stmt_insert->error : $conn->error);
            }
            if ($stmt_insert) $stmt_insert->close();
        }
        if ($result instanceof mysqli_result) $result->free();
        $stmt_check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $selectedLang; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $lang[$selectedLang]['dashboard'] ?? 'Citizen Dashboard'; ?> - CivicConnect</title>

<!-- Fonts, Icons, Leaflet CSS & JS -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
:root {
  --primary: #0284c7;
  --primary-hover: #0369a1;
  --emerald: #059669;
  --gradient-main: linear-gradient(135deg, #10b981 0%, #0284c7 100%);
  --bg-slate: #f8fafc;
  --card-bg: #ffffff;
  --text-dark: #0f172a;
  --text-muted: #64748b;
  --border-color: #e2e8f0;
  --shadow-light: 0 10px 25px -5px rgba(15, 23, 42, 0.06);
  --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
  font-family: 'Plus Jakarta Sans', sans-serif;
  background: var(--bg-slate);
  color: var(--text-dark);
  min-height: 100vh;
  line-height: 1.6;
}

/* Header */
header {
  background: #ffffff;
  padding: 16px 40px;
  border-bottom: 1px solid var(--border-color);
  box-shadow: 0 4px 12px rgba(0,0,0,0.03);
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: sticky;
  top: 0;
  z-index: 1000;
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
  background: var(--gradient-main);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.15rem;
  box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
}

.logo-title {
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--text-dark);
}

nav { display: flex; align-items: center; gap: 20px; }

nav a {
  color: var(--text-muted);
  text-decoration: none;
  font-weight: 600;
  font-size: 0.95rem;
  transition: color 0.2s ease;
  display: flex;
  align-items: center;
  gap: 6px;
}

nav a:hover { color: var(--primary); }

/* Main Wrapper */
main {
  max-width: 1200px;
  margin: 36px auto 80px;
  padding: 0 24px;
}

/* Citizen Banner */
.citizen-welcome-card {
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0369a1 100%);
  color: white;
  border-radius: 20px;
  padding: 32px 36px;
  margin-bottom: 32px;
  box-shadow: var(--shadow-xl);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 20px;
}

.welcome-title { font-size: 1.8rem; font-weight: 800; margin-bottom: 6px; }
.welcome-desc { color: #94a3b8; font-size: 1rem; max-width: 600px; }

.citizen-stats-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}

.stat-pill-card {
  background: white;
  border: 1px solid var(--border-color);
  border-radius: 16px;
  padding: 20px;
  box-shadow: var(--shadow-light);
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-pill-icon {
  width: 46px;
  height: 46px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.icon-blue { background: rgba(2, 132, 199, 0.1); color: #0284c7; }
.icon-amber { background: rgba(217, 119, 6, 0.1); color: #d97706; }
.icon-emerald { background: rgba(5, 150, 105, 0.1); color: #059669; }

.stat-val { font-size: 1.6rem; font-weight: 800; color: var(--text-dark); line-height: 1; }
.stat-lbl { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); }

/* Layout Grid */
.report-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 32px;
}

@media (max-width: 900px) {
  .report-grid { grid-template-columns: 1fr; }
}

.form-panel, .map-panel {
  background: var(--card-bg);
  border-radius: 20px;
  border: 1px solid var(--border-color);
  padding: 32px;
  box-shadow: var(--shadow-light);
}

.panel-title {
  font-size: 1.3rem;
  font-weight: 800;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.form-group { margin-bottom: 20px; }

label {
  display: block;
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--text-dark);
  margin-bottom: 8px;
}

select, input[type="text"], textarea, input[type="file"] {
  width: 100%;
  padding: 14px 16px;
  border: 1.5px solid var(--border-color);
  border-radius: 12px;
  font-family: inherit;
  font-size: 0.95rem;
  color: var(--text-dark);
  background: #ffffff;
  outline: none;
  transition: border-color 0.2s ease;
}

select:focus, input[type="text"]:focus, textarea:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.15);
}

.voice-btn {
  background: #eff6ff;
  color: #0284c7;
  border: 1px solid #bfdbfe;
  padding: 8px 14px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-top: 6px;
  transition: all 0.2s ease;
}

.voice-btn:hover { background: #0284c7; color: white; }

.submit-btn {
  width: 100%;
  padding: 16px;
  background: var(--gradient-main);
  color: white;
  border: none;
  border-radius: 14px;
  font-size: 1.05rem;
  font-weight: 800;
  cursor: pointer;
  box-shadow: 0 6px 18px rgba(16, 185, 129, 0.35);
  transition: all 0.25s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.submit-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 22px rgba(16, 185, 129, 0.45);
}

/* Map Panel */
#map {
  height: 380px;
  border-radius: 14px;
  border: 1px solid var(--border-color);
  margin-top: 14px;
}

.search-bar-wrap {
  display: flex;
  gap: 10px;
}

.btn-locate {
  background: #ffffff;
  color: #0284c7;
  border: 1px solid #cbd5e1;
  padding: 12px 16px;
  border-radius: 12px;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
}

.btn-locate:hover { background: #f1f5f9; }

/* AI Analysis Box */
.ai-status-card {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 12px;
  padding: 14px 16px;
  margin-top: 16px;
  font-size: 0.9rem;
}

/* Recent Tracker Table */
.tracker-section {
  margin-top: 40px;
  background: white;
  border-radius: 20px;
  border: 1px solid var(--border-color);
  padding: 32px;
  box-shadow: var(--shadow-light);
}

.complaint-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 16px;
}

.complaint-table th, .complaint-table td {
  padding: 14px 16px;
  text-align: left;
  border-bottom: 1px solid var(--border-color);
}

.complaint-table th { background: #f8fafc; font-weight: 700; font-size: 0.85rem; color: var(--text-muted); }

.status-badge {
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 700;
  display: inline-block;
}
.st-pending { background: #fef3c7; color: #92400e; }
.st-inprogress { background: #e0f2fe; color: #075985; }
.st-completed { background: #d1fae5; color: #065f46; }

.alert-box {
  padding: 14px 20px;
  border-radius: 12px;
  font-weight: 700;
  margin-bottom: 24px;
}
.alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
.alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
</style>
</head>
<body>

<!-- Header -->
<header>
  <a href="peopledashboard.php" class="logo-group">
    <div class="logo-badge">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 22S20 18 20 12V5L12 2L4 5V12C4 18 12 22 12 22Z" fill="rgba(255,255,255,0.25)" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9 12L11 14L15 10" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>
    <span class="logo-title">Civic<span style="color:#0284c7;">Connect</span></span>
  </a>
  
  <nav>
    <a href="peopledashboard.php"><i class="fa-solid fa-house"></i> <?php echo $lang[$selectedLang]['dashboard'] ?? 'Dashboard'; ?></a>
    <a href="peoplemyproblems.php"><i class="fa-solid fa-list-check"></i> <?php echo $lang[$selectedLang]['my_problems'] ?? 'My Complaints'; ?></a>
    <a href="peoplekarma.php"><i class="fa-solid fa-trophy"></i> Civic Karma</a>
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

  <!-- Welcome Card -->
  <div class="citizen-welcome-card">
    <div>
      <h1 class="welcome-title"><?php echo $lang[$selectedLang]['welcome'] ?? 'Welcome'; ?>, <?php echo htmlspecialchars($user_name); ?>! 👋</h1>
      <p class="welcome-desc">Report civic problems in your ward in seconds. Instant Vision AI photo scanning auto-detects problem categories & sets risk severity ratings.</p>
    </div>
    <a href="#reportSection" style="background:#ffffff; color:#0284c7; padding:12px 24px; border-radius:12px; font-weight:800; text-decoration:none; box-shadow:0 4px 12px rgba(0,0,0,0.15);">
      <i class="fa-solid fa-plus"></i> <?php echo $lang[$selectedLang]['report_new'] ?? 'Report an Issue'; ?>
    </a>
  </div>

  <!-- Citizen Stats Metrics -->
  <div class="citizen-stats-row">
    <div class="stat-pill-card">
      <div class="stat-pill-icon icon-blue"><i class="fa-solid fa-clipboard-list"></i></div>
      <div>
        <div class="stat-val"><?php echo $my_total; ?></div>
        <div class="stat-lbl"><?php echo $lang[$selectedLang]['total_reported'] ?? 'Total Complaints'; ?></div>
      </div>
    </div>

    <div class="stat-pill-card">
      <div class="stat-pill-icon icon-amber"><i class="fa-solid fa-clock"></i></div>
      <div>
        <div class="stat-val"><?php echo $my_pending; ?></div>
        <div class="stat-lbl"><?php echo $lang[$selectedLang]['in_progress'] ?? 'Pending Review'; ?></div>
      </div>
    </div>

    <div class="stat-pill-card">
      <div class="stat-pill-icon icon-emerald"><i class="fa-solid fa-circle-check"></i></div>
      <div>
        <div class="stat-val"><?php echo $my_completed; ?></div>
        <div class="stat-lbl"><?php echo $lang[$selectedLang]['completed'] ?? 'Resolved Issues'; ?></div>
      </div>
    </div>
  </div>

  <!-- Submission Message Alert -->
  <?php if(!empty($msg)): ?>
    <div class="alert-box <?php echo (strpos($msg, '✅') !== false)?'alert-success':'alert-error'; ?>">
      <?php echo htmlspecialchars($msg); ?>
    </div>
  <?php endif; ?>

  <!-- Report Form & Map Grid -->
  <div class="report-grid" id="reportSection">
    
    <!-- Left Column: Complaint Form -->
    <div class="form-panel">
      <div class="panel-title">
        <i class="fa-solid fa-camera-retro" style="color:var(--emerald);"></i>
        <span><?php echo $lang[$selectedLang]['report_new'] ?? 'Report a Problem'; ?></span>
      </div>

      <form method="POST" enctype="multipart/form-data" id="complaintForm">
        
        <!-- Photo Upload & AI Vision Box -->
        <div class="form-group">
          <label for="photo"><i class="fa-solid fa-camera"></i> <?php echo $lang[$selectedLang]['upload_photo'] ?? 'Upload Photo (AI Auto-Categorization Enabled)'; ?>:</label>
          <input type="file" name="photo" id="photo" accept="image/*">
          <div id="imagePreviewWrap" style="display:none; margin-top:10px; text-align:center;">
            <img id="imgPreviewThumb" style="max-height:160px; border-radius:12px; border:2px solid #0284c7; box-shadow:0 4px 12px rgba(0,0,0,0.1);" alt="Preview">
          </div>
          <div id="aiAnalysisStatus" class="ai-status-card" style="display:none;"></div>
        </div>

        <!-- Category Selector -->
        <div class="form-group">
          <label for="category"><i class="fa-solid fa-layer-group"></i> <?php echo $lang[$selectedLang]['category'] ?? 'Category'; ?>:</label>
          <select name="category" id="category" required>
            <option value="">-- <?php echo $lang[$selectedLang]['select_location'] ?? 'Select Category'; ?> --</option>
            <option value="Road">🛣️ Road & Potholes</option>
            <option value="Water">🚰 Water & Drainage</option>
            <option value="Electricity">⚡ Electricity & Streetlights</option>
            <option value="Sanitation">🧹 Sanitation & Garbage</option>
            <option value="Other">⚠️ Other Public Issue</option>
          </select>
        </div>

        <!-- Description + Voice Input -->
        <div class="form-group">
          <label for="description"><i class="fa-solid fa-align-left"></i> <?php echo $lang[$selectedLang]['description'] ?? 'Problem Description'; ?>:</label>
          <textarea name="description" id="description" rows="4" required placeholder="Describe the problem or click 'Speak Description' below..."></textarea>
          <button type="button" class="voice-btn" id="startVoiceBtn">
            <i class="fa-solid fa-microphone"></i> 🎙️ Speak Description (Voice Input)
          </button>
        </div>

        <input type="hidden" name="latitude" id="latitude" required>
        <input type="hidden" name="longitude" id="longitude" required>
        <input type="hidden" name="selectedAddress" id="selectedAddress" required>
        <input type="hidden" name="ai_severity" id="ai_severity" value="Medium">

        <button type="submit" name="submit_problem" class="submit-btn">
          <i class="fa-solid fa-paper-plane"></i> <?php echo $lang[$selectedLang]['submit'] ?? 'Submit Problem Report'; ?>
        </button>

      </form>
    </div>

    <!-- Right Column: Interactive GIS Map -->
    <div class="map-panel">
      <div class="panel-title">
        <i class="fa-solid fa-map-location-dot" style="color:var(--primary);"></i>
        <span><?php echo $lang[$selectedLang]['location'] ?? 'Problem Location'; ?></span>
      </div>

      <div class="form-group">
        <div class="search-bar-wrap">
          <input type="text" id="searchBox" placeholder="Search landmark, street or area...">
          <button type="button" class="btn-locate" id="searchBtn"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
          <button type="button" class="btn-locate" id="locateMeBtn" title="Locate Me GPS"><i class="fa-solid fa-crosshairs"></i> GPS</button>
        </div>
      </div>

      <label style="font-size:0.85rem; color:var(--text-muted); margin-bottom:6px;">
        <i class="fa-solid fa-circle-info"></i> Click pin anywhere on map to select problem spot:
      </label>

      <div id="map"></div>
    </div>

  </div>

  <!-- Recent Complaints Tracker Table -->
  <div class="tracker-section">
    <div class="panel-title">
      <i class="fa-solid fa-clock-rotate-left" style="color:#d97706;"></i>
      <span>Recent Submitted Complaints</span>
    </div>

    <?php if(mysqli_num_rows($recent_complaints) > 0): ?>
      <table class="complaint-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Category</th>
            <th>Description</th>
            <th>Location</th>
            <th>Reported Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php while($c = mysqli_fetch_assoc($recent_complaints)): ?>
            <tr>
              <td><strong>#<?php echo $c['id']; ?></strong></td>
              <td><span style="font-weight:700; color:#0284c7;"><?php echo htmlspecialchars($c['category']); ?></span></td>
              <td><?php echo htmlspecialchars($c['description']); ?></td>
              <td><small style="color:var(--text-muted);"><?php echo htmlspecialchars($c['street']); ?></small></td>
              <td><small><?php echo date('M d, Y', strtotime($c['created_at'])); ?></small></td>
              <td>
                <?php 
                $st = $c['status'] ?? 'Pending';
                $stCls = $st === 'Completed' ? 'st-completed' : ($st === 'In Progress' ? 'st-inprogress' : 'st-pending');
                ?>
                <span class="status-badge <?php echo $stCls; ?>"><?php echo $st; ?></span>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p style="text-align:center; color:var(--text-muted); padding:20px;">You haven't reported any problems yet. Use the form above to file your first complaint!</p>
    <?php endif; ?>
  </div>

</main>

<script>
$(document).ready(function(){
    var map = L.map('map').setView([13.28510073, 77.59980869], 13);
    var marker;

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Initial GPS Autodetect
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position){
            var lat = position.coords.latitude;
            var lng = position.coords.longitude;
            setMapPin(lat, lng);
        });
    }

    function setMapPin(lat, lng) {
        map.setView([lat, lng], 16);
        if(marker) map.removeLayer(marker);
        marker = L.marker([lat, lng]).addTo(map);

        $('#latitude').val(lat);
        $('#longitude').val(lng);

        // Reverse Geocode
        $.getJSON(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`, function(data){
            if(data && data.display_name) {
                $('#selectedAddress').val(data.display_name);
            }
        });
    }

    // Map Click Pinning
    map.on('click', function(e){
        setMapPin(e.latlng.lat, e.latlng.lng);
    });

    // Locate Me GPS Button
    $('#locateMeBtn').click(function(){
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position){
                setMapPin(position.coords.latitude, position.coords.longitude);
            });
        }
    });

    // Search Address Button
    $('#searchBtn').click(function(){
        var query = $('#searchBox').val();
        if(query){
            $.getJSON(`https://nominatim.openstreetmap.org/search?format=json&q=${query}`, function(data){
                if(data && data.length > 0){
                    setMapPin(data[0].lat, data[0].lon);
                } else {
                    alert('Location not found. Please click directly on the map pin.');
                }
            });
        }
    });

    // 🤖 Real-Time Vision AI Image Categorization & Thumbnail Preview
    $('#photo').on('change', function(){
        var file = this.files[0];
        if(!file) return;

        // Show image thumbnail preview
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#imgPreviewThumb').attr('src', e.target.result);
            $('#imagePreviewWrap').slideDown();
        };
        reader.readAsDataURL(file);

        var formData = new FormData();
        formData.append('photo', file);

        $('#aiAnalysisStatus').html('<i class="fa-solid fa-spinner fa-spin"></i> 🤖 <b>Vision AI is scanning your photo & categorizing...</b>').slideDown();

        $.ajax({
            url: '../api/ai_analyze.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res){
                if(res && res.success){
                    var cat = res.category;
                    if(cat.includes('Road')) $('#category').val('Road');
                    else if(cat.includes('Water') || cat.includes('Drainage')) $('#category').val('Water');
                    else if(cat.includes('Light') || cat.includes('Electric')) $('#category').val('Electricity');
                    else if(cat.includes('Garbage') || cat.includes('Sanitation')) $('#category').val('Sanitation');
                    else $('#category').val('Other');

                    if(res.description) $('#description').val(res.description);
                    if(res.severity) $('#ai_severity').val(res.severity);

                    var badgeColor = res.severity === 'Critical' ? '#dc2626' : (res.severity === 'High' ? '#ea580c' : '#16a34a');
                    $('#aiAnalysisStatus').html(`
                        <span style="color:#059669; font-weight:800;"><i class="fa-solid fa-circle-check"></i> Vision AI Categorization Complete!</span><br>
                        <b>Auto-Categorized As:</b> <span style="color:#0284c7; font-weight:800;">${res.category}</span><br>
                        <b>Risk Severity:</b> <span style="background:${badgeColor}; color:#fff; padding:3px 10px; border-radius:12px; font-weight:bold; font-size:0.85rem;">${res.severity}</span><br>
                        <small style="color:#64748b; display:block; margin-top:6px;"><i>"${res.description}"</i></small>
                    `);
                } else {
                    $('#aiAnalysisStatus').html('ℹ️ Photo attached successfully.');
                }
            },
            error: function(){
                $('#aiAnalysisStatus').html('ℹ️ Photo attached. Category auto-selected.');
            }
        });
    });

    // 🎙️ Speech-to-Text Voice Input
    $('#startVoiceBtn').click(function(){
        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            var recognition = new SpeechRecognition();
            recognition.lang = 'en-IN'; // Default to Indian English / Multilingual recognition
            recognition.interimResults = false;

            $('#startVoiceBtn').html('<i class="fa-solid fa-microphone" style="color:red; animation:pulse 1s infinite;"></i> 🎙️ Listening... Speak now!').css('background', '#fee2e2');

            recognition.onresult = function(event) {
                var transcript = event.results[0][0].transcript;
                var currentText = $('#description').val();
                $('#description').val((currentText ? currentText + ' ' : '') + transcript);
                $('#startVoiceBtn').html('<i class="fa-solid fa-microphone"></i> 🎙️ Speak Description (Voice Input)').css('background', '#eff6ff');
            };

            recognition.onerror = function() {
                alert('Voice recognition error or quiet environment. Please type description.');
                $('#startVoiceBtn').html('<i class="fa-solid fa-microphone"></i> 🎙️ Speak Description (Voice Input)').css('background', '#eff6ff');
            };

            recognition.start();
        } else {
            alert('Voice input is supported on Chrome, Edge, and Android browsers.');
        }
    });
});
</script>

<?php include("../includes/chatbot_widget.php"); ?>
</body>
</html>