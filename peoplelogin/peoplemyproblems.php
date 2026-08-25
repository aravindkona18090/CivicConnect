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

$problems_query = "SELECT * FROM problems WHERE user_id='$user_id' ORDER BY id DESC";
$problems_result = mysqli_query($conn, $problems_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $lang[$selectedLang]['my_problems']; ?> - CivicConnect</title>
<!-- Font Awesome & Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
  --primary: #2563eb;
  --primary-hover: #1d4ed8;
  --primary-gradient: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
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
}

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

nav a {
  color: var(--text-muted);
  margin-left: 20px;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.95rem;
  transition: color 0.2s ease;
}

nav a:hover {
  color: var(--primary);
}

main {
  max-width: 950px;
  margin: 40px auto;
  background: var(--card-bg);
  padding: 40px;
  border-radius: 20px;
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-light);
}

h3 {
  font-size: 1.75rem;
  font-weight: 800;
  color: var(--text-dark);
  margin-bottom: 24px;
  letter-spacing: -0.02em;
}

table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  margin-top: 20px;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid var(--border-color);
}

th, td {
  padding: 16px;
  text-align: left;
}

th {
  background: #f1f5f9;
  color: var(--text-dark);
  font-weight: 700;
  border-bottom: 1px solid var(--border-color);
}

td {
  border-bottom: 1px solid var(--border-color);
  color: #334155;
  font-size: 0.95rem;
}

tr:last-child td {
  border-bottom: none;
}

.badge-status {
  padding: 4px 12px;
  border-radius: 20px;
  font-weight: 700;
  font-size: 0.8rem;
  display: inline-block;
}
.status-pending { background: #fef3c7; color: #92400e; }
.status-inprogress { background: #e0f2fe; color: #075985; }
.status-completed { background: #d1fae5; color: #065f46; }
</style>
</head>
<body>

<header>
  <a href="../index.php" class="logo-group">
    <div class="logo-badge" style="background:linear-gradient(135deg, #10b981 0%, #0284c7 100%); box-shadow:0 4px 14px rgba(16, 185, 129, 0.35);"><i class="fa-solid fa-handshake"></i></div>
    <span class="logo-title">Civic<span style="color:#0284c7;">Connect</span></span>
  </a>
  <nav>
    <a href="peopledashboard.php"><i class="fa-solid fa-house"></i> <?php echo $lang[$selectedLang]['dashboard'] ?? 'Dashboard'; ?></a>
    <a href="peoplemyproblems.php"><i class="fa-solid fa-list-check"></i> <?php echo $lang[$selectedLang]['my_problems'] ?? 'My Problems'; ?></a>
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
    <h3><i class="fa-solid fa-clipboard-list" style="color:var(--primary);"></i> <?php echo $lang[$selectedLang]['my_problems']; ?></h3>

    <?php if (mysqli_num_rows($problems_result) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?php echo $lang[$selectedLang]['category']; ?></th>
                    <th><?php echo $lang[$selectedLang]['description']; ?></th>
                    <th><?php echo $lang[$selectedLang]['location']; ?></th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($problems_result)): ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['category']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo htmlspecialchars($row['street']); ?></td>
                        <td>
                            <?php 
                            $st = $row['status'] ?? 'Pending';
                            $stClass = $st==='Completed'?'status-completed':($st==='In Progress'?'status-inprogress':'status-pending');
                            ?>
                            <span class="badge-status <?php echo $stClass; ?>"><?php echo $st; ?></span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="text-align:center; padding:40px; color:var(--text-muted);">
            <i class="fa-solid fa-folder-open" style="font-size:2.5rem; color:#cbd5e1; margin-bottom:10px;"></i>
            <p>No complaints reported yet.</p>
        </div>
    <?php endif; ?>
</main>

</body>
</html>
