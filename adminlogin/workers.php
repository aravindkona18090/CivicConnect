<?php
session_start();
include('../db/connection.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$result = mysqli_query($conn, "SELECT * FROM workers ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Field Officers Management - Admin Command Center</title>
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
        }

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
        }

        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .add-btn {
            background: #10b981;
            color: #fff;
            padding: 10px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

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
        }

        th {
            background: #f8fafc;
            padding: 16px 20px;
            text-align: left;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            font-size: 0.95rem;
            color: var(--text-main);
        }

        tr:last-child td { border-bottom: none; }
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
        <div class="page-header">
            <h1><i class="fa-solid fa-hard-hat" style="color:var(--brand-primary);"></i> Municipal Field Officers Registry</h1>
            <a href="add_worker.php" class="add-btn"><i class="fa-solid fa-user-plus"></i> Add New Field Officer</a>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Registered Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($w = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><strong>#<?php echo $w['id']; ?></strong></td>
                                <td><strong><?php echo htmlspecialchars($w['username'] ?? $w['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($w['age'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($w['email']); ?></td>
                                <td><?php echo htmlspecialchars($w['mobile'] ?? $w['phone'] ?? 'N/A'); ?></td>
                                <td><span style="background:#e0f2fe; color:#0369a1; padding:4px 10px; border-radius:6px; font-weight:700; font-size:0.8rem;"><?php echo htmlspecialchars($w['category'] ?? $w['department'] ?? 'General Municipal'); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($w['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; padding:30px; color:#64748b;">No field officers registered yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
