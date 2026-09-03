<?php
session_start();
include("../db/connection.php");
include("../lang.php"); 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$msg = "";
$msg_type = "";
$active_tab = "personal";
$start_in_edit = false;

// Ensure uploads directory exists
$upload_dir = "../uploads/profile_pics/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// 1. Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A. Update Personal Info & Profile Picture
    if (isset($_POST['update_personal'])) {
        $name = trim($_POST['name'] ?? '');
        $dob = trim($_POST['dob'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');

        if (empty($name) || empty($mobile)) {
            $msg = "Please fill in both your full name and mobile number.";
            $msg_type = "error";
            $start_in_edit = true;
        } else {
            $pic_sql = "";
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
                $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($ext, $allowed)) {
                    $filename = "avatar_" . $user_id . "_" . time() . "." . $ext;
                    $target_path = $upload_dir . $filename;
                    if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_path)) {
                        $db_path = "uploads/profile_pics/" . $filename;
                        $pic_sql = ", profile_pic='$db_path'";
                    }
                }
            }

            $up_stmt = $conn->prepare("UPDATE people SET name = ?, dob = ?, gender = ?, mobile = ? $pic_sql WHERE id = ?");
            $up_stmt->bind_param("ssssi", $name, $dob, $gender, $mobile, $user_id);
            if ($up_stmt->execute()) {
                $_SESSION['username'] = $name;
                $msg = "Personal profile details updated successfully!";
                $msg_type = "success";
            } else {
                $msg = "Failed to update profile: " . $conn->error;
                $msg_type = "error";
                $start_in_edit = true;
            }
            $up_stmt->close();
        }
    }

    // B. Direct Quick Avatar Upload from Banner
    elseif (isset($_POST['quick_avatar_upload'])) {
        if (isset($_FILES['quick_profile_pic']) && $_FILES['quick_profile_pic']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['quick_profile_pic']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($ext, $allowed)) {
                $filename = "avatar_" . $user_id . "_" . time() . "." . $ext;
                $target_path = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['quick_profile_pic']['tmp_name'], $target_path)) {
                    $db_path = "uploads/profile_pics/" . $filename;
                    $up_pic = $conn->prepare("UPDATE people SET profile_pic = ? WHERE id = ?");
                    $up_pic->bind_param("si", $db_path, $user_id);
                    $up_pic->execute();
                    $up_pic->close();
                    $msg = "Profile photo updated successfully!";
                    $msg_type = "success";
                } else {
                    $msg = "Failed to save uploaded photo.";
                    $msg_type = "error";
                }
            } else {
                $msg = "Invalid image format. Supported: JPG, PNG, WEBP.";
                $msg_type = "error";
            }
        }
    }

    // C. Update Address
    elseif (isset($_POST['update_address'])) {
        $door = trim($_POST['door'] ?? '');
        $street = trim($_POST['street'] ?? '');
        $area = trim($_POST['area'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $pincode = trim($_POST['pincode'] ?? '');

        $addr_stmt = $conn->prepare("UPDATE people SET door = ?, street = ?, area = ?, city = ?, state = ?, pincode = ? WHERE id = ?");
        $addr_stmt->bind_param("ssssssi", $door, $street, $area, $city, $state, $pincode, $user_id);
        if ($addr_stmt->execute()) {
            $msg = "Residential address saved successfully!";
            $msg_type = "success";
        } else {
            $msg = "Failed to update address: " . $conn->error;
            $msg_type = "error";
            $start_in_edit = true;
        }
        $addr_stmt->close();
    }

    // D. Change Password
    elseif (isset($_POST['update_password'])) {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        $p_stmt = $conn->prepare("SELECT password FROM people WHERE id = ?");
        $p_stmt->bind_param("i", $user_id);
        $p_stmt->execute();
        $user_auth = $p_stmt->get_result()->fetch_assoc();
        $p_stmt->close();

        $saved_hash = $user_auth['password'] ?? '';

        if (!password_verify($current_pass, $saved_hash) && $current_pass !== $saved_hash) {
            $msg = "The current password entered is incorrect.";
            $msg_type = "error";
            $start_in_edit = true;
            $active_tab = "security";
        } elseif (strlen($new_pass) < 6) {
            $msg = "New password must be at least 6 characters long.";
            $msg_type = "error";
            $start_in_edit = true;
            $active_tab = "security";
        } elseif ($new_pass !== $confirm_pass) {
            $msg = "New passwords do not match.";
            $msg_type = "error";
            $start_in_edit = true;
            $active_tab = "security";
        } else {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $up_pass = $conn->prepare("UPDATE people SET password = ? WHERE id = ?");
            $up_pass->bind_param("si", $new_hash, $user_id);
            if ($up_pass->execute()) {
                $msg = "Your password has been changed successfully!";
                $msg_type = "success";
            } else {
                $msg = "Database error updating password.";
                $msg_type = "error";
                $start_in_edit = true;
                $active_tab = "security";
            }
            $up_pass->close();
        }
    }
}

// Fetch Fresh User Record
$user_stmt = $conn->prepare("SELECT * FROM people WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

$selectedLang = $_SESSION['language'] ?? ($user['language'] ?? 'en');

$displayName = !empty($user['name']) ? $user['name'] : (!empty($user['username']) ? $user['username'] : 'Citizen');
$initial = strtoupper(substr($displayName, 0, 1));
$joinDate = !empty($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'Aug 14, 2026';

$avatarSrc = '';
if (!empty($user['profile_pic'])) {
    if (strpos($user['profile_pic'], 'http') === 0) {
        $avatarSrc = $user['profile_pic'];
    } elseif (file_exists("../" . $user['profile_pic'])) {
        $avatarSrc = "../" . $user['profile_pic'];
    }
}

// Count total reported problems
$cnt_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM problems WHERE user_id='$user_id'");
$reported_count = mysqli_fetch_assoc($cnt_res)['cnt'] ?? 0;
?>
<!DOCTYPE html>
<html lang="<?php echo $selectedLang; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $lang[$selectedLang]['profile'] ?? 'Citizen Profile'; ?> - CivicConnect</title>
<!-- Google Fonts & Font Awesome -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
:root {
    --brand-primary: #0284c7;
    --brand-emerald: #10b981;
    --brand-gradient: linear-gradient(135deg, #10b981 0%, #0284c7 100%);
    --bg-canvas: #f8fafc;
    --card-bg: #ffffff;
    --text-main: #0f172a;
    --text-muted: #64748b;
    --border-color: #e2e8f0;
    --radius-lg: 20px;
    --radius: 16px;
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
    --shadow-md: 0 12px 28px -5px rgba(15, 23, 42, 0.08);
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: var(--bg-canvas);
    color: var(--text-main);
    min-height: 100vh;
    padding-bottom: 80px;
}

/* Modern Header Navbar */
header {
    background: #ffffff;
    border-bottom: 1px solid var(--border-color);
    padding: 14px 36px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: var(--shadow-sm);
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
    background: var(--brand-gradient);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
}

.logo-title {
    font-size: 1.3rem;
    font-weight: 800;
    color: var(--text-main);
}
.logo-title span { color: var(--brand-primary); }

nav {
    display: flex;
    align-items: center;
    gap: 20px;
}

nav a {
    color: var(--text-muted);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.92rem;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: color 0.2s ease;
}
nav a:hover { color: var(--brand-primary); }
nav a.active { color: var(--brand-primary); font-weight: 800; }

.logout-btn {
    background: #fee2e2;
    color: #dc2626 !important;
    padding: 7px 14px;
    border-radius: 8px;
    font-weight: 700 !important;
    font-size: 0.85rem !important;
}
.logout-btn:hover { background: #fecaca; }

/* Main Container */
main {
    max-width: 1040px;
    margin: 18px auto 0;
    padding: 0 20px;
}

/* Alert Notification Banner */
.alert-banner {
    padding: 12px 18px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.92rem;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
.alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

/* ========================================================
   LARGE HERO COVER BANNER & FLOATING AVATAR (LAYOUT A)
   ======================================================== */
.profile-hero-card {
    background: #ffffff;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    margin-bottom: 22px;
    position: relative;
}

/* High-Contrast Civic Cover Banner */
.hero-cover {
    height: 150px;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0369a1 100%);
    position: relative;
}
.hero-cover::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: radial-gradient(circle at 80% 20%, rgba(16, 185, 129, 0.25) 0%, transparent 60%);
}

/* Floating Avatar & Details Body */
.hero-body {
    padding: 0 36px 26px;
    position: relative;
}

.hero-profile-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-top: -70px;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 20px;
}

/* Large 140px Floating Avatar */
.avatar-floating-wrap {
    position: relative;
    width: 140px;
    height: 140px;
    flex-shrink: 0;
}

.avatar-large-img, .avatar-large-initial {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    object-fit: cover;
    border: 5px solid #ffffff;
    box-shadow: 0 8px 25px rgba(0,0,0,0.18);
    display: block;
    background: #ffffff;
}

.avatar-large-initial {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3.6rem;
    font-weight: 800;
}

/* Large Interactive Camera Icon */
.avatar-camera-btn-large {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 42px;
    height: 42px;
    background: var(--brand-gradient);
    color: #ffffff;
    border: 3.5px solid #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.05rem;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(0,0,0,0.25);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.avatar-camera-btn-large:hover {
    transform: scale(1.15);
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.45);
}

.hero-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.btn-edit-main {
    background: var(--brand-gradient);
    color: #ffffff;
    border: none;
    padding: 12px 24px;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
    transition: all 0.2s ease;
}
.btn-edit-main:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
}

.btn-view-main {
    background: #f1f5f9;
    color: #0f172a;
    border: 1px solid #cbd5e1;
    padding: 12px 22px;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}
.btn-view-main:hover { background: #e2e8f0; }

/* Large Bold Title & Meta */
.hero-user-details h1 {
    font-size: 1.85rem;
    font-weight: 800;
    color: var(--text-main);
    margin-bottom: 6px;
    letter-spacing: -0.5px;
}

.hero-user-meta {
    display: flex;
    align-items: center;
    gap: 14px;
    color: var(--text-muted);
    font-size: 0.95rem;
    flex-wrap: wrap;
}

.meta-pill {
    background: #e0f2fe;
    color: #0369a1;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* ========================================================
   READ-ONLY DISPLAY CARDS
   ======================================================== */
.content-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    padding: 28px 32px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 24px;
}

.section-head {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--text-main);
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 12px;
}

.big-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 18px;
}

.big-tile {
    background: #f8fafc;
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 16px 20px;
    transition: transform 0.15s ease;
}
.big-tile:hover { transform: translateY(-1px); }

.big-tile label {
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    display: block;
    margin-bottom: 6px;
}

.big-tile p {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--text-main);
}

/* Emergency Helplines */
.helpline-grid-large {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.helpline-box {
    background: #f8fafc;
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
}

.help-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.help-number {
    font-weight: 800;
    font-size: 1.2rem;
    color: #0f172a;
    text-decoration: none;
    display: block;
    margin-top: 2px;
}

/* ========================================================
   SPACIOUS EDIT DASHBOARD
   ======================================================== */
.edit-tabs-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f1f5f9;
    padding: 8px;
    border-radius: 14px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.tab-btn-spacious {
    flex: 1;
    min-width: 170px;
    padding: 12px 20px;
    border: none;
    background: transparent;
    border-radius: 10px;
    font-family: inherit;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.tab-btn-spacious:hover { color: var(--text-main); }
.tab-btn-spacious.active {
    background: #ffffff;
    color: var(--brand-primary);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.form-grid-wide {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

@media (max-width: 680px) {
    .form-grid-wide { grid-template-columns: 1fr; }
}

.form-group-wide {
    margin-bottom: 20px;
}

.form-group-wide label {
    display: block;
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--text-main);
    margin-bottom: 8px;
}

.form-input-wide, .form-select-wide {
    width: 100%;
    padding: 13px 16px;
    border: 1.5px solid var(--border-color);
    border-radius: 12px;
    font-family: inherit;
    font-size: 0.96rem;
    color: var(--text-main);
    background: #f8fafc;
    outline: none;
    transition: all 0.2s ease;
}

.form-input-wide:focus, .form-select-wide:focus {
    border-color: var(--brand-primary);
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.15);
}

/* Big Avatar Uploader in Form */
.avatar-form-uploader {
    display: flex;
    align-items: center;
    gap: 24px;
    background: #f8fafc;
    border: 2px dashed #cbd5e1;
    border-radius: 16px;
    padding: 20px 24px;
    margin-bottom: 24px;
}

@media (max-width: 600px) {
    .avatar-form-uploader { flex-direction: column; text-align: center; }
}

.save-btn-large {
    background: var(--brand-gradient);
    color: #ffffff;
    border: none;
    padding: 14px 28px;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
    transition: all 0.2s ease;
}
.save-btn-large:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(16, 185, 129, 0.4);
}
</style>
</head>
<body>

<header>
  <a href="peopledashboard.php" class="logo-group">
    <div class="logo-badge">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 22S20 18 20 12V5L12 2L4 5V12C4 18 12 22 12 22Z" fill="rgba(255,255,255,0.25)" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9 12L11 14L15 10" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>
    <span class="logo-title">Civic<span>Connect</span></span>
  </a>

  <nav>
    <a href="peopledashboard.php"><i class="fa-solid fa-house"></i> <?php echo $lang[$selectedLang]['dashboard'] ?? 'Dashboard'; ?></a>
    <a href="peoplemyproblems.php"><i class="fa-solid fa-list-check"></i> <?php echo $lang[$selectedLang]['my_problems'] ?? 'My Complaints'; ?></a>
    <a href="peopleprofile.php" class="active"><i class="fa-solid fa-user"></i> <?php echo $lang[$selectedLang]['profile'] ?? 'Profile'; ?></a>
    <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> <?php echo $lang[$selectedLang]['logout'] ?? 'Logout'; ?></a>
  </nav>

  <form method="POST" style="display:inline-flex; align-items:center; gap:6px;">
    <select name="language" onchange="this.form.submit()" style="padding:8px 12px; border-radius:8px; border:1px solid #cbd5e1; font-weight:700; font-family:inherit; cursor:pointer;" title="Select Language">
      <option value="en" <?php if ($selectedLang=='en') echo 'selected'; ?>>🌐 English</option>
      <option value="te" <?php if ($selectedLang=='te') echo 'selected'; ?>>🌐 తెలుగు (Telugu)</option>
      <option value="hn" <?php if ($selectedLang=='hn') echo 'selected'; ?>>🌐 हिंदी (Hindi)</option>
      <option value="kn" <?php if ($selectedLang=='kn') echo 'selected'; ?>>🌐 ಕನ್ನಡ (Kannada)</option>
    </select>
  </form>
</header>

<main>
    <?php if (!empty($msg)): ?>
        <div class="alert-banner <?php echo $msg_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
            <i class="fa-solid <?php echo $msg_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
            <div><?php echo htmlspecialchars($msg); ?></div>
        </div>
    <?php endif; ?>

    <!-- ========================================================
         HERO COVER BANNER WITH 130PX FLOATING AVATAR
         ======================================================== -->
    <div class="profile-hero-card">
        <!-- Top Cover Banner -->
        <div class="hero-cover"></div>

        <!-- Body with Floating Avatar & Actions -->
        <div class="hero-body">
            <div class="hero-profile-row">
                <!-- 130px Big Avatar with Interactive Camera Button -->
                <form id="quickAvatarForm" method="POST" action="peopleprofile.php" enctype="multipart/form-data" style="display:inline;">
                    <input type="file" id="quickAvatarInput" name="quick_profile_pic" accept="image/*" style="display:none;" onchange="document.getElementById('quickAvatarForm').submit();">
                    <input type="hidden" name="quick_avatar_upload" value="1">
                    
                    <div class="avatar-floating-wrap">
                        <?php if (!empty($avatarSrc)): ?>
                            <img src="<?php echo htmlspecialchars($avatarSrc); ?>" alt="Profile Avatar" class="avatar-large-img">
                        <?php else: ?>
                            <div class="avatar-large-initial"><?php echo $initial; ?></div>
                        <?php endif; ?>
                        
                        <label for="quickAvatarInput" class="avatar-camera-btn-large" title="Click to Change Photo">
                            <i class="fa-solid fa-camera"></i>
                        </label>
                    </div>
                </form>

                <!-- Action Controls: Edit vs View Mode -->
                <div class="hero-actions">
                    <button type="button" id="btnOpenEdit" class="btn-edit-main" onclick="toggleEditMode(true)" style="<?php echo $start_in_edit ? 'display:none;' : ''; ?>">
                        <i class="fa-solid fa-pen-to-square"></i> Edit Profile & Settings
                    </button>
                    <button type="button" id="btnCloseEdit" class="btn-view-main" onclick="toggleEditMode(false)" style="<?php echo $start_in_edit ? '' : 'display:none;'; ?>">
                        <i class="fa-solid fa-arrow-left"></i> Back to Profile View
                    </button>
                </div>
            </div>

            <!-- Big Bold User Info -->
            <div class="hero-user-details">
                <h1><?php echo htmlspecialchars($displayName); ?></h1>
                <div class="hero-user-meta">
                    <span><i class="fa-regular fa-envelope" style="color:#0284c7;"></i> <?php echo htmlspecialchars($user['email'] ?? 'No email set'); ?></span>
                    <span>•</span>
                    <span class="meta-pill"><i class="fa-solid fa-shield-halved"></i> Citizen ID #<?php echo $user_id; ?></span>
                    <span>•</span>
                    <span><i class="fa-regular fa-calendar-check" style="color:#10b981;"></i> Member since <?php echo $joinDate; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================
         1. NORMAL DISPLAY VIEW (Default)
         ======================================================== -->
    <div id="viewMode" style="<?php echo $start_in_edit ? 'display:none;' : ''; ?>">
        <!-- Personal Details Card -->
        <div class="content-card">
            <div class="section-head">
                <i class="fa-solid fa-user" style="color:var(--brand-primary); font-size:1.25rem;"></i>
                Personal Information
            </div>
            <div class="big-info-grid">
                <div class="big-tile">
                    <label>Full Legal Name</label>
                    <p><?php echo htmlspecialchars($user['name'] ?? $user['username'] ?? 'Not specified'); ?></p>
                </div>
                <div class="big-tile">
                    <label>Phone / Mobile</label>
                    <p><?php echo htmlspecialchars(!empty($user['mobile']) ? $user['mobile'] : 'Not specified'); ?></p>
                </div>
                <div class="big-tile">
                    <label>Date of Birth</label>
                    <p><?php echo htmlspecialchars(!empty($user['dob']) ? date('M d, Y', strtotime($user['dob'])) : 'Not specified'); ?></p>
                </div>
                <div class="big-tile">
                    <label>Gender</label>
                    <p><?php echo htmlspecialchars(!empty($user['gender']) ? $user['gender'] : 'Not specified'); ?></p>
                </div>
            </div>
        </div>

        <!-- Residential Address Card -->
        <div class="content-card">
            <div class="section-head">
                <i class="fa-solid fa-location-dot" style="color:#ef4444; font-size:1.25rem;"></i>
                Residential Address & Ward
            </div>
            <div class="big-info-grid">
                <div class="big-tile">
                    <label>Door / House Number</label>
                    <p><?php echo htmlspecialchars(!empty($user['door']) ? $user['door'] : 'Not specified'); ?></p>
                </div>
                <div class="big-tile">
                    <label>Street Address</label>
                    <p><?php echo htmlspecialchars(!empty($user['street']) ? $user['street'] : 'Not specified'); ?></p>
                </div>
                <div class="big-tile">
                    <label>Area / Locality</label>
                    <p><?php echo htmlspecialchars(!empty($user['area']) ? $user['area'] : 'Not specified'); ?></p>
                </div>
                <div class="big-tile">
                    <label>City & State</label>
                    <p><?php echo htmlspecialchars((!empty($user['city']) ? $user['city'] : 'City') . (!empty($user['state']) ? ', ' . $user['state'] : '')); ?></p>
                </div>
                <div class="big-tile">
                    <label>PIN Code</label>
                    <p><?php echo htmlspecialchars(!empty($user['pincode']) ? $user['pincode'] : 'Not specified'); ?></p>
                </div>
            </div>
        </div>

        <!-- Account Security & Activity Card -->
        <div class="content-card">
            <div class="section-head">
                <i class="fa-solid fa-shield-halved" style="color:#059669; font-size:1.25rem;"></i>
                Account Security & Community Activity
            </div>
            <div class="big-info-grid">
                <div class="big-tile">
                    <label>Login Email</label>
                    <p><?php echo htmlspecialchars($user['email'] ?? 'Not set'); ?></p>
                </div>
                <div class="big-tile">
                    <label>Account Password</label>
                    <p>•••••••••••• <button type="button" onclick="openEditTab('security')" style="background:none; border:none; color:var(--brand-primary); font-weight:800; cursor:pointer; font-family:inherit; margin-left:6px;">(Change)</button></p>
                </div>
                <div class="big-tile">
                    <label>Total Complaints Lodged</label>
                    <p style="color:#0284c7;"><strong><?php echo $reported_count; ?></strong> Reported Issues</p>
                </div>
            </div>
        </div>

        <!-- Emergency Municipal Helplines -->
        <div class="content-card">
            <div class="section-head">
                <i class="fa-solid fa-phone-volume" style="color:#d97706; font-size:1.25rem;"></i>
                Emergency Municipal Helplines
            </div>
            <div class="helpline-grid-large">
                <div class="helpline-box">
                    <div class="help-icon" style="background:#fef3c7; color:#d97706;"><i class="fa-solid fa-bolt"></i></div>
                    <div>
                        <small style="color:var(--text-muted); font-size:0.75rem; font-weight:bold; text-transform:uppercase;">Electricity</small>
                        <a href="tel:1912" class="help-number">1912</a>
                    </div>
                </div>
                <div class="helpline-box">
                    <div class="help-icon" style="background:#dcfce7; color:#15803d;"><i class="fa-solid fa-trash-can"></i></div>
                    <div>
                        <small style="color:var(--text-muted); font-size:0.75rem; font-weight:bold; text-transform:uppercase;">Sanitation</small>
                        <a href="tel:1969" class="help-number">1969</a>
                    </div>
                </div>
                <div class="helpline-box">
                    <div class="help-icon" style="background:#e0f2fe; color:#0369a1;"><i class="fa-solid fa-faucet-drip"></i></div>
                    <div>
                        <small style="color:var(--text-muted); font-size:0.75rem; font-weight:bold; text-transform:uppercase;">Water Board</small>
                        <a href="tel:1916" class="help-number">1916</a>
                    </div>
                </div>
                <div class="helpline-box">
                    <div class="help-icon" style="background:#fee2e2; color:#dc2626;"><i class="fa-solid fa-truck-medical"></i></div>
                    <div>
                        <small style="color:var(--text-muted); font-size:0.75rem; font-weight:bold; text-transform:uppercase;">Emergency</small>
                        <a href="tel:112" class="help-number">112</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================
         2. SPATIOUS EDIT DASHBOARD (On Click)
         ======================================================== -->
    <div id="editMode" style="<?php echo $start_in_edit ? '' : 'display:none;'; ?>">
        <div class="edit-tabs-bar">
            <button class="tab-btn-spacious <?php echo $active_tab==='personal' ? 'active' : ''; ?>" onclick="openTab('personal', this)">
                <i class="fa-solid fa-user-pen"></i> 1. Personal Information
            </button>
            <button class="tab-btn-spacious <?php echo $active_tab==='address' ? 'active' : ''; ?>" onclick="openTab('address', this)">
                <i class="fa-solid fa-location-dot"></i> 2. Residential Address
            </button>
            <button class="tab-btn-spacious <?php echo $active_tab==='security' ? 'active' : ''; ?>" onclick="openTab('security', this)">
                <i class="fa-solid fa-lock"></i> 3. Change Password
            </button>
        </div>

        <!-- Tab 1: Personal Info Form -->
        <div id="tab-personal" class="content-card" style="<?php echo $active_tab!=='personal' ? 'display:none;' : ''; ?>">
            <form method="POST" action="peopleprofile.php" enctype="multipart/form-data">
                <!-- Large Avatar Form Uploader -->
                <div class="avatar-form-uploader">
                    <div class="avatar-floating-wrap">
                        <?php if (!empty($avatarSrc)): ?>
                            <img id="formAvatarPreview" src="<?php echo htmlspecialchars($avatarSrc); ?>" alt="Profile Preview" class="avatar-large-img">
                        <?php else: ?>
                            <div id="formAvatarInit" class="avatar-large-initial"><?php echo $initial; ?></div>
                            <img id="formAvatarPreview" src="" alt="Profile Preview" class="avatar-large-img" style="display:none;">
                        <?php endif; ?>
                    </div>
                    <div>
                        <h3 style="font-size:1.1rem; font-weight:800; color:#0f172a; margin-bottom:4px;">Upload Profile Photo</h3>
                        <p style="font-size:0.9rem; color:var(--text-muted); margin-bottom:10px;">Select a new portrait image. Supported: JPG, PNG, WEBP (Max 5MB).</p>
                        <input type="file" id="formAvatarInput" name="profile_pic" accept="image/*" style="font-size:0.9rem;" onchange="previewAvatar(this)">
                    </div>
                </div>

                <div class="form-grid-wide">
                    <div class="form-group-wide">
                        <label>Full Legal Name *</label>
                        <input type="text" name="name" class="form-input-wide" value="<?php echo htmlspecialchars($user['name'] ?? $user['username'] ?? ''); ?>" required placeholder="Your full name">
                    </div>

                    <div class="form-group-wide">
                        <label>Phone / Mobile Number *</label>
                        <input type="text" name="mobile" class="form-input-wide" value="<?php echo htmlspecialchars($user['mobile'] ?? ''); ?>" required placeholder="e.g. 9876543210">
                    </div>
                </div>

                <div class="form-grid-wide">
                    <div class="form-group-wide">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" class="form-input-wide" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>">
                    </div>

                    <div class="form-group-wide">
                        <label>Gender</label>
                        <select name="gender" class="form-select-wide">
                            <option value="">-- Select Gender --</option>
                            <option value="Male" <?php if(($user['gender'] ?? '') === 'Male') echo 'selected'; ?>>Male</option>
                            <option value="Female" <?php if(($user['gender'] ?? '') === 'Female') echo 'selected'; ?>>Female</option>
                            <option value="Other" <?php if(($user['gender'] ?? '') === 'Other') echo 'selected'; ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div style="display:flex; align-items:center; gap:14px; margin-top:10px;">
                    <button type="submit" name="update_personal" class="save-btn-large">
                        <i class="fa-solid fa-floppy-disk"></i> Save Personal Details
                    </button>
                    <button type="button" class="btn-view-main" onclick="toggleEditMode(false)">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Tab 2: Residential Address Form -->
        <div id="tab-address" class="content-card" style="<?php echo $active_tab!=='address' ? 'display:none;' : ''; ?>">
            <form method="POST" action="peopleprofile.php">
                <div class="form-grid-wide">
                    <div class="form-group-wide">
                        <label>Door / House Number</label>
                        <input type="text" name="door" class="form-input-wide" value="<?php echo htmlspecialchars($user['door'] ?? ''); ?>" placeholder="e.g. Flat 302, Block B">
                    </div>

                    <div class="form-group-wide">
                        <label>Street Address</label>
                        <input type="text" name="street" class="form-input-wide" value="<?php echo htmlspecialchars($user['street'] ?? ''); ?>" placeholder="e.g. 5th Main Road">
                    </div>
                </div>

                <div class="form-grid-wide">
                    <div class="form-group-wide">
                        <label>Area / Locality</label>
                        <input type="text" name="area" class="form-input-wide" value="<?php echo htmlspecialchars($user['area'] ?? ''); ?>" placeholder="e.g. Indiranagar">
                    </div>

                    <div class="form-group-wide">
                        <label>City / Municipality</label>
                        <input type="text" name="city" class="form-input-wide" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" placeholder="e.g. Bengaluru">
                    </div>
                </div>

                <div class="form-grid-wide">
                    <div class="form-group-wide">
                        <label>State</label>
                        <input type="text" name="state" class="form-input-wide" value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>" placeholder="e.g. Karnataka">
                    </div>

                    <div class="form-group-wide">
                        <label>PIN Code</label>
                        <input type="text" name="pincode" class="form-input-wide" value="<?php echo htmlspecialchars($user['pincode'] ?? ''); ?>" placeholder="e.g. 560038">
                    </div>
                </div>

                <div style="display:flex; align-items:center; gap:14px; margin-top:10px;">
                    <button type="submit" name="update_address" class="save-btn-large">
                        <i class="fa-solid fa-map-pin"></i> Save Address
                    </button>
                    <button type="button" class="btn-view-main" onclick="toggleEditMode(false)">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Tab 3: Security & Password Form -->
        <div id="tab-security" class="content-card" style="<?php echo $active_tab!=='security' ? 'display:none;' : ''; ?>">
            <form method="POST" action="peopleprofile.php" autocomplete="off">
                <div class="form-group-wide">
                    <label>Registered Email</label>
                    <input type="text" class="form-input-wide" value="<?php echo htmlspecialchars($user['email'] ?? 'Not set'); ?>" disabled style="background:#f1f5f9; color:#64748b; cursor:not-allowed;">
                </div>

                <div class="form-group-wide">
                    <label>Current Password *</label>
                    <input type="password" name="current_password" required placeholder="Enter current password" class="form-input-wide">
                </div>

                <div class="form-grid-wide">
                    <div class="form-group-wide">
                        <label>New Password (Min. 6 characters) *</label>
                        <input type="password" name="new_password" required minlength="6" placeholder="Enter new password" class="form-input-wide">
                    </div>

                    <div class="form-group-wide">
                        <label>Confirm New Password *</label>
                        <input type="password" name="confirm_password" required minlength="6" placeholder="Confirm new password" class="form-input-wide">
                    </div>
                </div>

                <div style="display:flex; align-items:center; gap:14px; margin-top:10px;">
                    <button type="submit" name="update_password" class="save-btn-large">
                        <i class="fa-solid fa-key"></i> Update Password
                    </button>
                    <button type="button" class="btn-view-main" onclick="toggleEditMode(false)">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
function toggleEditMode(isEdit) {
    if (isEdit) {
        document.getElementById('viewMode').style.display = 'none';
        document.getElementById('editMode').style.display = 'block';
        document.getElementById('btnOpenEdit').style.display = 'none';
        document.getElementById('btnCloseEdit').style.display = 'inline-flex';
    } else {
        document.getElementById('viewMode').style.display = 'block';
        document.getElementById('editMode').style.display = 'none';
        document.getElementById('btnOpenEdit').style.display = 'inline-flex';
        document.getElementById('btnCloseEdit').style.display = 'none';
    }
}

function openEditTab(tabName) {
    toggleEditMode(true);
    openTab(tabName);
}

function openTab(tabName, btn) {
    document.querySelectorAll('.content-card').forEach(card => {
        if (card.parentElement.id === 'editMode') card.style.display = 'none';
    });
    document.querySelectorAll('.tab-btn-spacious').forEach(b => b.classList.remove('active'));
    
    document.getElementById('tab-' + tabName).style.display = 'block';
    if (btn) {
        btn.classList.add('active');
    } else {
        const matchingBtn = Array.from(document.querySelectorAll('.tab-btn-spacious')).find(b => b.getAttribute('onclick').includes(tabName));
        if (matchingBtn) matchingBtn.classList.add('active');
    }
}

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var $img = $('#formAvatarPreview');
            $img.attr('src', e.target.result).show();
            $('#formAvatarInit').hide();
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
<?php include("../includes/chatbot_widget.php"); ?>
</body>
</html>
