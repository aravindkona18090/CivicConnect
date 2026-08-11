<?php
session_start();
include("lang.php");
?>
<!DOCTYPE html>
<html lang="<?php echo $selectedLang; ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CivicConnect - <?php echo $lang[$selectedLang]['login_register']; ?></title>
  <!-- Google Fonts & Font Awesome Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <style>
    :root {
      --primary: #2563eb;
      --primary-hover: #1d4ed8;
      --primary-gradient: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
      --emerald-gradient: linear-gradient(135deg, #059669 0%, #10b981 100%);
      --amber-gradient: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
      --bg-slate: #f8fafc;
      --text-dark: #0f172a;
      --text-muted: #64748b;
      --card-bg: rgba(255, 255, 255, 0.85);
      --card-border: rgba(226, 232, 240, 0.8);
      --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
      --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--bg-slate);
      color: var(--text-dark);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      position: relative;
      overflow-x: hidden;
    }

    .ambient-glow-1 {
      position: absolute; top: -100px; left: -100px; width: 400px; height: 400px;
      background: radial-gradient(circle, rgba(37, 99, 235, 0.15) 0%, rgba(255, 255, 255, 0) 70%);
      border-radius: 50%; z-index: 0; pointer-events: none;
    }
    .ambient-glow-2 {
      position: absolute; bottom: -100px; right: -100px; width: 500px; height: 500px;
      background: radial-gradient(circle, rgba(79, 70, 229, 0.12) 0%, rgba(255, 255, 255, 0) 70%);
      border-radius: 50%; z-index: 0; pointer-events: none;
    }

    header {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(12px);
      padding: 18px 40px;
      border-bottom: 1px solid var(--card-border);
      display: flex; justify-content: space-between; align-items: center;
      position: sticky; top: 0; z-index: 100;
    }

    .logo {
      display: flex; align-items: center; gap: 12px; text-decoration: none;
    }

    .logo-icon {
      width: 42px; height: 42px; background: var(--primary-gradient);
      border-radius: 12px; display: flex; align-items: center; justify-content: center;
      color: white; font-size: 1.2rem; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    .logo-text {
      font-size: 1.4rem; font-weight: 800;
      background: linear-gradient(135deg, #0f172a 0%, #2563eb 100%);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      letter-spacing: -0.02em;
    }

    main {
      flex: 1; max-width: 1100px; margin: 0 auto; padding: 50px 24px;
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      position: relative; z-index: 1;
    }

    .hero-badge {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(37, 99, 235, 0.08); color: var(--primary);
      padding: 8px 18px; border-radius: 30px; font-size: 0.9rem; font-weight: 700;
      margin-bottom: 20px; border: 1px solid rgba(37, 99, 235, 0.2);
    }

    h1 {
      font-size: 2.6rem; font-weight: 800; text-align: center; color: var(--text-dark);
      letter-spacing: -0.03em; line-height: 1.25; margin-bottom: 16px;
    }

    .subtitle {
      font-size: 1.1rem; color: var(--text-muted); text-align: center;
      max-width: 650px; line-height: 1.6; margin-bottom: 50px;
    }

    .role-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 28px; width: 100%; max-width: 1050px;
    }

    .role-card {
      background: var(--card-bg); backdrop-filter: blur(16px);
      border: 1px solid var(--card-border); border-radius: 24px; padding: 36px 28px;
      box-shadow: var(--shadow-sm); display: flex; flex-direction: column;
      align-items: flex-start; transition: all 0.35s ease; position: relative; overflow: hidden;
    }

    .role-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-xl); border-color: rgba(37, 99, 235, 0.3); }

    .icon-wrapper {
      width: 64px; height: 64px; border-radius: 18px; display: flex;
      align-items: center; justify-content: center; font-size: 1.6rem; margin-bottom: 24px;
    }

    .citizen-icon { background: rgba(37, 99, 235, 0.1); color: #2563eb; }
    .worker-icon { background: rgba(16, 185, 129, 0.1); color: #059669; }
    .admin-icon { background: rgba(217, 119, 6, 0.1); color: #d97706; }

    .role-title { font-size: 1.4rem; font-weight: 700; color: var(--text-dark); margin-bottom: 10px; }
    .role-desc { font-size: 0.95rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 30px; flex-grow: 1; }

    .role-btn {
      width: 100%; padding: 14px 20px; border-radius: 14px; font-size: 1rem;
      font-weight: 700; text-decoration: none; display: flex; align-items: center;
      justify-content: center; gap: 10px; transition: all 0.3s ease;
    }

    .btn-citizen { background: var(--primary-gradient); color: white; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3); }
    .btn-worker { background: var(--emerald-gradient); color: white; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3); }
    .btn-admin { background: var(--amber-gradient); color: white; box-shadow: 0 4px 14px rgba(217, 119, 6, 0.3); }

    footer { text-align: center; padding: 24px; color: var(--text-muted); font-size: 0.9rem; border-top: 1px solid var(--card-border); background: rgba(255, 255, 255, 0.5); }
  </style>
</head>
<body>

  <div class="ambient-glow-1"></div>
  <div class="ambient-glow-2"></div>

  <header>
    <a href="index.php" class="logo">
      <div class="logo-icon" style="background:linear-gradient(135deg, #10b981 0%, #0284c7 100%); box-shadow:0 4px 14px rgba(16, 185, 129, 0.35);"><i class="fa-solid fa-handshake"></i></div>
      <span class="logo-text">Civic<span style="color:#0284c7;">Connect</span></span>
    </a>
    <div style="display:flex; align-items:center; gap:14px;">
      <a href="index.php" style="text-decoration:none; background:#ffffff; color:#2563eb; padding:8px 16px; border-radius:12px; font-weight:700; font-size:0.9rem; border:1px solid #bfdbfe; box-shadow:0 2px 6px rgba(0,0,0,0.05);">
        <i class="fa-solid fa-arrow-left"></i> <?php echo $lang[$selectedLang]['back_to_home']; ?>
      </a>
      <form method="POST" style="display:inline-flex; align-items:center;">
        <select name="language" onchange="this.form.submit()" style="padding:8px 12px; border-radius:10px; border:1px solid #bfdbfe; font-weight:700; font-family:inherit; cursor:pointer;" title="Select Language">
          <option value="en" <?php if ($selectedLang=='en') echo 'selected'; ?>>🌐 English</option>
          <option value="te" <?php if ($selectedLang=='te') echo 'selected'; ?>>🌐 తెలుగు (Telugu)</option>
          <option value="hn" <?php if ($selectedLang=='hn') echo 'selected'; ?>>🌐 हिंदी (Hindi)</option>
          <option value="kn" <?php if ($selectedLang=='kn') echo 'selected'; ?>>🌐 ಕನ್ನಡ (Kannada)</option>
        </select>
      </form>
    </div>
  </header>

  <main>
    <div style="margin-bottom: 16px;">
      <a href="index.php" style="text-decoration:none; color:#64748b; font-size:0.9rem; font-weight:600;">
        <i class="fa-solid fa-house"></i> Home / <span style="color:#2563eb;"><?php echo $lang[$selectedLang]['login_register']; ?></span>
      </a>
    </div>
    <div class="hero-badge">
      <i class="fa-solid fa-shield-halved"></i> <?php echo $lang[$selectedLang]['portal_tagline']; ?>
    </div>
    <h1><?php echo $lang[$selectedLang]['role_selection_title']; ?></h1>
    <p class="subtitle"><?php echo $lang[$selectedLang]['role_selection_desc']; ?></p>

    <div class="role-grid">
      <!-- Citizen Portal -->
      <div class="role-card">
        <div class="icon-wrapper citizen-icon"><i class="fa-solid fa-user-group"></i></div>
        <h2 class="role-title"><?php echo $lang[$selectedLang]['people_login']; ?></h2>
        <p class="role-desc"><?php echo $lang[$selectedLang]['citizen_desc']; ?></p>
        <a href="peoplelogin/login.php" class="role-btn btn-citizen">
          <?php echo $lang[$selectedLang]['login_as_citizen']; ?> <i class="fa-solid fa-arrow-right"></i>
        </a>
      </div>

      <!-- Field Officer / Worker Portal -->
      <div class="role-card worker-card">
        <div class="icon-wrapper worker-icon"><i class="fa-solid fa-person-digging"></i></div>
        <h2 class="role-title"><?php echo $lang[$selectedLang]['worker_login']; ?></h2>
        <p class="role-desc"><?php echo $lang[$selectedLang]['worker_desc']; ?></p>
        <a href="workerlogin/login.php" class="role-btn btn-worker">
          <?php echo $lang[$selectedLang]['login_as_worker']; ?> <i class="fa-solid fa-arrow-right"></i>
        </a>
      </div>

      <!-- Admin Portal -->
      <div class="role-card admin-card">
        <div class="icon-wrapper admin-icon"><i class="fa-solid fa-user-shield"></i></div>
        <h2 class="role-title"><?php echo $lang[$selectedLang]['admin_login']; ?></h2>
        <p class="role-desc"><?php echo $lang[$selectedLang]['admin_desc']; ?></p>
        <a href="adminlogin/login.php" class="role-btn btn-admin">
          <?php echo $lang[$selectedLang]['login_as_admin']; ?> <i class="fa-solid fa-arrow-right"></i>
        </a>
      </div>
    </div>
  </main>

  <footer>
    <p><?php echo $lang[$selectedLang]['footer']; ?></p>
  </footer>

</body>
</html>
