<?php
session_start();
include("../db/connection.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$completed_query = "SELECT * FROM problems WHERE status='Completed' ORDER BY completed_at DESC";
$completed_result = mysqli_query($conn, $completed_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completed Complaints - Admin Command Center</title>
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

        .complaint-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            padding: 20px;
            margin-bottom: 18px;
            display: grid;
            grid-template-columns: 240px 1fr 180px;
            gap: 20px;
            align-items: center;
        }

        .photo-column {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .photo-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .thumb-img {
            width: 70px;
            height: 70px;
            border-radius: 10px;
            object-fit: cover;
            cursor: pointer;
            border: 1px solid var(--border);
            transition: transform 0.2s ease;
        }
        .thumb-img:hover { transform: scale(1.08); }

        .photo-tag {
            font-size: 0.65rem;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .tag-before { background: #f1f5f9; color: #475569; }
        .tag-after { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }

        .badge-completed {
            background: #d1fae5;
            color: #065f46;
            padding: 6px 14px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            display: inline-block;
        }

        .empty-state {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 50px;
            text-align: center;
            border: 1px dashed var(--border);
            color: var(--text-muted);
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
            padding: 20px;
        }
        .modal-bg.active { display: flex; }
        .modal-content {
            position: relative;
            max-width: 90vw;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .modal-content img {
            max-width: 90vw;
            max-height: 80vh;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.7);
            border: 2px solid rgba(255,255,255,0.2);
        }
        .modal-title {
            color: #ffffff;
            margin-bottom: 12px;
            font-size: 1.1rem;
            font-weight: 700;
        }
        .modal-close-btn {
            position: absolute;
            top: -18px;
            right: -18px;
            width: 44px;
            height: 44px;
            background: #ef4444;
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
            border: 2px solid #ffffff;
            z-index: 1000;
        }
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
            <a href="completedproblems.php" class="nav-link active"><i class="fa-solid fa-circle-check"></i> Completed Archive</a>
            <a href="workers.php" class="nav-link"><i class="fa-solid fa-hard-hat"></i> Field Officers</a>
        </nav>

        <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </header>

    <div class="container">
        <div class="page-header">
            <h1><i class="fa-solid fa-circle-check" style="color:var(--brand-emerald);"></i> Resolved & Completed Complaints Archive</h1>
        </div>

        <?php if ($completed_result && mysqli_num_rows($completed_result) > 0): ?>
            <?php while ($p = mysqli_fetch_assoc($completed_result)): ?>
                <div class="complaint-card">
                    <!-- BEFORE & AFTER PROOF PHOTOS -->
                    <div class="photo-column">
                        <div class="photo-box">
                            <?php if (!empty($p['photo'])): ?>
                                <img src="<?php echo htmlspecialchars($p['photo']); ?>" alt="Before Photo" class="thumb-img" onclick="zoomPhoto('<?php echo htmlspecialchars($p['photo']); ?>', 'Reported Problem Photo (Before)')">
                                <span class="photo-tag tag-before">Before</span>
                            <?php else: ?>
                                <div style="width:70px; height:70px; background:#f1f5f9; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:0.65rem;">No Before</div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($p['after_photo'])): ?>
                            <div class="photo-box">
                                <img src="<?php echo htmlspecialchars($p['after_photo']); ?>" alt="After Photo Proof" class="thumb-img" style="border:2px solid #10b981;" onclick="zoomPhoto('<?php echo htmlspecialchars($p['after_photo']); ?>', '✅ Work Completion Proof Photo (Field Officer)')">
                                <span class="photo-tag tag-after">✅ Fixed Proof</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <div style="display:flex; gap:10px; margin-bottom:8px;">
                            <span style="font-weight:800; color:#475569;">ID #<?php echo $p['id']; ?></span>
                            <span style="font-weight:700; color:var(--brand-primary);"><?php echo htmlspecialchars($p['category']); ?></span>
                        </div>
                        <div style="font-weight:700; font-size:1.05rem; margin-bottom:6px;"><?php echo htmlspecialchars($p['description']); ?></div>
                        <div style="font-size:0.85rem; color:#64748b;"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($p['street'] . ', ' . $p['city']); ?></div>
                        <div style="font-size:0.8rem; color:#94a3b8; margin-top:4px;"><i class="fa-solid fa-check"></i> Completed: <?php echo date('M d, Y • h:i A', strtotime($p['completed_at'] ?? $p['created_at'])); ?></div>
                    </div>

                    <div style="text-align:right;">
                        <span class="badge-completed"><i class="fa-solid fa-check-double"></i> Completed</span>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-box-archive" style="font-size:3rem; color:#cbd5e1; margin-bottom:12px;"></i>
                <h3>No Completed Archive Entries</h3>
                <p>Completed complaints will appear here.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- PHOTO LIGHTBOX MODAL -->
    <div class="modal-bg" id="photoModal" onclick="closePhotoOnOverlay(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <span class="modal-close-btn" onclick="closePhoto()" title="Close (Esc)">&times;</span>
            <div class="modal-title" id="modalTitle">Photo Preview</div>
            <img id="modalImg" src="" alt="Full Preview">
        </div>
    </div>

    <script>
        function zoomPhoto(src, title) {
            document.getElementById('modalImg').src = src;
            document.getElementById('modalTitle').innerText = title || 'Photo Preview';
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
