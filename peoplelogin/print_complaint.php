<?php
// peoplelogin/print_complaint.php - Official Municipal Grievance Acknowledgment Slip
session_start();
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../config.php';

$problemId = intval($_GET['id'] ?? 0);

if ($problemId <= 0) {
    die("Invalid Grievance ID.");
}

$query = "SELECT p.*, u.name as citizen_name, u.email as citizen_email, u.mobile as citizen_mobile,
                 w.name as worker_name, w.mobile as worker_mobile
          FROM problems p
          LEFT JOIN people u ON p.user_id = u.id
          LEFT JOIN workers w ON p.worker_id = w.id
          WHERE p.id = '$problemId'";

$res = mysqli_query($conn, $query);
$item = mysqli_fetch_assoc($res);

if (!$item) {
    die("Grievance record not found.");
}

$trackingUrl = "https://civicconnect.up.railway.app/track.php?id=" . $item['id'];
$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($trackingUrl);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grievance Acknowledgment Slip #<?php echo $item['id']; ?> - CivicConnect</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            padding: 40px 20px;
        }

        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1.5px solid #cbd5e1;
            position: relative;
        }

        .receipt-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }

        .gov-title h2 { font-size: 1.4rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .gov-title p { font-size: 0.85rem; color: #64748b; font-weight: 600; }

        .receipt-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        .receipt-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
        }
        .receipt-box h4 {
            font-size: 0.85rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }
        .detail-label { color: #64748b; font-weight: 600; }
        .detail-val { font-weight: 700; color: #0f172a; text-align: right; }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 800;
        }
        .st-Completed { background: #d1fae5; color: #065f46; }
        .st-Pending { background: #fef3c7; color: #92400e; }
        .st-In\ Progress { background: #e0f2fe; color: #0369a1; }

        .photos-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }
        .photo-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px;
            text-align: center;
        }
        .photo-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 8px;
        }

        .print-btn-bar {
            text-align: center;
            margin-top: 24px;
        }
        .print-btn {
            background: #2563eb;
            color: #ffffff;
            border: none;
            padding: 12px 28px;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .print-btn:hover { background: #1d4ed8; }

        @media print {
            body { background: #ffffff; padding: 0; }
            .receipt-container { box-shadow: none; border: none; padding: 0; }
            .print-btn-bar { display: none; }
        }
    </style>
</head>
<body>

<div class="receipt-container">
    <div class="receipt-header">
        <div>
            <div class="gov-title">
                <h2>🏛️ Smart Municipal Corporation</h2>
                <p>Public Grievance Redressal & Smart City Governance Department</p>
            </div>
            <div style="font-size:0.85rem; font-weight:700; margin-top:8px; color:#2563eb;">
                Official Grievance Acknowledgment Receipt
            </div>
        </div>
        <div>
            <img src="<?php echo $qrCodeUrl; ?>" alt="Live Tracking QR Code" style="width:90px; height:90px; border:1px solid #cbd5e1; border-radius:8px; padding:4px;">
            <div style="font-size:0.7rem; text-align:center; color:#64748b; margin-top:2px;">Scan to Track</div>
        </div>
    </div>

    <div class="receipt-grid">
        <div class="receipt-box">
            <h4>📋 Grievance Information</h4>
            <div class="detail-row">
                <span class="detail-label">Complaint ID:</span>
                <span class="detail-val">#<?php echo $item['id']; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Category:</span>
                <span class="detail-val"><?php echo htmlspecialchars($item['category']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date Reported:</span>
                <span class="detail-val"><?php echo date('M d, Y - h:i A', strtotime($item['created_at'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Current Status:</span>
                <span class="detail-val"><span class="status-badge st-<?php echo str_replace(' ', '\\ ', $item['status']); ?>"><?php echo $item['status']; ?></span></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">AI Severity:</span>
                <span class="detail-val"><?php echo htmlspecialchars($item['ai_severity'] ?? 'Medium'); ?></span>
            </div>
        </div>

        <div class="receipt-box">
            <h4>👤 Citizen & Location Details</h4>
            <div class="detail-row">
                <span class="detail-label">Citizen Name:</span>
                <span class="detail-val"><?php echo htmlspecialchars($item['citizen_name'] ?? 'Registered Citizen'); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Mobile Contact:</span>
                <span class="detail-val"><?php echo htmlspecialchars($item['citizen_mobile'] ?? 'N/A'); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Location / Ward:</span>
                <span class="detail-val"><?php echo htmlspecialchars($item['street'] . ', ' . ($item['area'] ?? '') . ', ' . $item['city']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Assigned Officer:</span>
                <span class="detail-val"><?php echo htmlspecialchars($item['worker_name'] ?? 'Allocated to Ward Pool'); ?></span>
            </div>
        </div>
    </div>

    <div class="receipt-box" style="margin-bottom:24px;">
        <h4>📝 Grievance Description</h4>
        <p style="font-size:0.92rem; color:#334155; line-height:1.5;"><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
    </div>

    <div class="photos-section">
        <div class="photo-card">
            <div style="font-size:0.8rem; font-weight:700; color:#64748b; margin-bottom:8px;">📷 Citizen Evidence Photo (Before)</div>
            <?php if (!empty($item['photo'])): ?>
                <img src="../<?php echo htmlspecialchars($item['photo']); ?>" alt="Before Photo" onerror="this.src='../images/pothole.jpg'">
            <?php else: ?>
                <div style="padding:40px; color:#94a3b8; font-size:0.85rem;">No Photo Attached</div>
            <?php endif; ?>
        </div>

        <div class="photo-card">
            <div style="font-size:0.8rem; font-weight:700; color:#059669; margin-bottom:8px;">✅ Field Officer Resolution Proof (After)</div>
            <?php if (!empty($item['after_photo'])): ?>
                <img src="../<?php echo htmlspecialchars($item['after_photo']); ?>" alt="After Photo">
            <?php else: ?>
                <div style="padding:40px; color:#94a3b8; font-size:0.85rem;">Pending Field Resolution</div>
            <?php endif; ?>
        </div>
    </div>

    <div style="border-top:1px dashed #cbd5e1; padding-top:16px; display:flex; justify-content:space-between; align-items:center; font-size:0.78rem; color:#64748b;">
        <span>Generated automatically by CivicConnect Smart City Redressal System</span>
        <span>Authorized Signature & Stamp: ______________________</span>
    </div>

    <div class="print-btn-bar">
        <button type="button" class="print-btn" onclick="window.print()"><i class="fa-solid fa-print"></i> Print Official Receipt</button>
    </div>
</div>

</body>
</html>
