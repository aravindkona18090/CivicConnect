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
  padding-bottom: 80px;
}

main {
  max-width: 1100px;
  margin: 32px auto;
  padding: 0 20px;
}

.page-title {
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--text-dark);
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.page-subtitle {
  color: var(--text-muted);
  font-size: 0.92rem;
  margin-bottom: 20px;
}

/* Filter Bar Controls */
.filter-bar-wrap {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 20px;
}

.filter-tabs-group {
  display: flex;
  gap: 6px;
  background: #ffffff;
  padding: 4px;
  border-radius: 12px;
  border: 1px solid var(--border-color);
  box-shadow: 0 2px 6px rgba(0,0,0,0.02);
}

.filter-tab {
  padding: 7px 16px;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.82rem;
  cursor: pointer;
  background: transparent;
  color: var(--text-muted);
  transition: all 0.2s ease;
  font-family: inherit;
}

.filter-tab.active {
  background: #0284c7;
  color: #ffffff;
  box-shadow: 0 2px 8px rgba(2, 132, 199, 0.25);
}

.filter-search-box {
  position: relative;
  width: 280px;
}

.filter-search-box input {
  width: 100%;
  padding: 8px 12px 8px 36px;
  border: 1.5px solid var(--border-color);
  border-radius: 10px;
  font-size: 0.86rem;
  font-family: inherit;
  outline: none;
  background: #ffffff;
  box-sizing: border-box;
  transition: border-color 0.2s;
}

.filter-search-box input:focus {
  border-color: #0284c7;
}

.filter-search-box i {
  position: absolute;
  left: 12px;
  top: 11px;
  color: var(--text-muted);
  font-size: 0.85rem;
}

/* ========================================================
   TABLE & DESKTOP STYLES
   ======================================================== */
.table-card {
  background: var(--card-bg);
  border-radius: 16px;
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-light);
  overflow: hidden;
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

/* Shared Elements */
.badge-status {
  padding: 5px 12px;
  border-radius: 20px;
  font-weight: 700;
  font-size: 0.8rem;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}
.status-pending { background: #fef3c7; color: #92400e; }
.status-inprogress { background: #e0f2fe; color: #0369a1; }
.status-completed { background: #d1fae5; color: #065f46; }

.category-pill {
  background: #e0f2fe;
  color: #0369a1;
  padding: 3px 9px;
  border-radius: 6px;
  font-weight: 700;
  font-size: 0.78rem;
  display: inline-block;
  margin-top: 3px;
}

.complaint-desc-text {
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 4px;
  max-width: 320px;
  word-break: break-word;
}

.complaint-loc-text {
  color: var(--text-muted);
  font-size: 0.82rem;
}

.thumb-preview {
  width: 52px;
  height: 52px;
  border-radius: 8px;
  object-fit: cover;
  cursor: pointer;
  border: 1.5px solid var(--border-color);
  transition: transform 0.15s ease;
}
.thumb-preview:hover {
  transform: scale(1.08);
}

.print-slip-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.76rem;
  font-weight: 700;
  color: #2563eb;
  text-decoration: none;
  padding: 5px 10px;
  border-radius: 6px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  transition: all 0.15s;
}
.print-slip-btn:hover {
  background: #dbeafe;
}

.mobile-only-header {
  display: none;
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

/* ========================================================
   MOBILE CARD LAYOUT (@media max-width: 768px)
   ELIMINATES SQUEEZED TABLE AND AWKWARD VERTICAL WATERFALL
   ======================================================== */
@media (max-width: 768px) {
  main {
    margin: 16px auto 30px;
    padding: 0 14px;
  }
  .page-title {
    font-size: 1.35rem;
  }
  .page-subtitle {
    font-size: 0.85rem;
    margin-bottom: 14px;
  }

  /* Responsive Filter Controls */
  .filter-bar-wrap {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
    margin-bottom: 16px;
  }
  .filter-tabs-group {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    box-sizing: border-box;
    justify-content: space-between;
  }
  .filter-tab {
    flex: 1;
    padding: 7px 10px;
    text-align: center;
    font-size: 0.78rem;
  }
  .filter-search-box {
    width: 100%;
  }

  /* Hide the HTML table header */
  #citizenComplaintsTable thead {
    display: none !important;
  }

  /* Convert table container into fluid list */
  .table-card {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    overflow: visible !important;
    margin-top: 12px;
  }

  #citizenComplaintsTable,
  #citizenComplaintsTable tbody {
    display: block !important;
    width: 100% !important;
  }

  /* Transform each row into a distinct, high-end Mobile Card */
  .problem-record-row {
    display: block !important;
    background: #ffffff !important;
    border-radius: 16px !important;
    border: 1px solid var(--border-color) !important;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05) !important;
    margin-bottom: 16px !important;
    padding: 16px !important;
    box-sizing: border-box !important;
    position: relative !important;
  }

  /* td cells become clean stacked block segments */
  .problem-record-row td {
    display: block !important;
    width: 100% !important;
    padding: 0 !important;
    border: none !important;
    margin-bottom: 10px !important;
    box-sizing: border-box !important;
  }

  /* Desktop-only elements hide on mobile */
  .desktop-only-cell {
    display: none !important;
  }

  /* Mobile card header with ID, category, and status */
  .mobile-only-header {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
  }

  .desktop-id-meta {
    display: none !important;
  }

  /* THE AWKWARD DESCRIPTION FIX: 100% FULL WIDTH WITH NATURAL WRAPPING */
  .complaint-desc-text {
    max-width: 100% !important;
    font-size: 0.98rem !important;
    line-height: 1.55 !important;
    color: #0f172a !important;
    word-break: break-word !important;
    white-space: normal !important;
    margin-top: 4px !important;
    margin-bottom: 6px !important;
  }

  .complaint-loc-text {
    font-size: 0.84rem !important;
  }

  /* Photos section on mobile card */
  .photos-cell-wrap {
    background: #f8fafc;
    border-radius: 10px;
    padding: 8px 12px;
    margin-top: 4px;
  }

  /* Officer box on mobile */
  .officer-cell-wrap {
    background: #f8fafc;
    border-radius: 10px;
    padding: 8px 12px;
  }

  /* Card footer: print slip link aligned right */
  .problem-record-row td:last-child {
    margin-bottom: 0 !important;
    padding-top: 8px !important;
    border-top: 1px dashed #e2e8f0 !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
  }
}
</style>
</head>
<body>

<?php include("../includes/citizen_header.php"); ?>

<main>
    <div class="page-title">
        <i class="fa-solid fa-clipboard-list" style="color:var(--primary);"></i> 
        <?php echo $lang[$selectedLang]['my_problems'] ?? 'My Tracked Complaints'; ?>
    </div>
    <p class="page-subtitle">Track the real-time status of your reported municipal issues, assigned officers, and resolution proof photos.</p>

    <!-- Status Filter & Search Controls -->
    <div class="filter-bar-wrap">
        <!-- Status Filter Tabs -->
        <div class="filter-tabs-group">
            <button type="button" class="filter-tab active" onclick="filterCitizenRows('all', this)">All</button>
            <button type="button" class="filter-tab" onclick="filterCitizenRows('Pending', this)">Pending</button>
            <button type="button" class="filter-tab" onclick="filterCitizenRows('In Progress', this)">In Progress</button>
            <button type="button" class="filter-tab" onclick="filterCitizenRows('Completed', this)">Resolved</button>
        </div>

        <!-- Search Input -->
        <div class="filter-search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="citizenSearch" placeholder="Search by ID, area, category..." onkeyup="filterCitizenSearch()">
        </div>
    </div>

    <div class="table-card">
        <?php if ($problems_result && mysqli_num_rows($problems_result) > 0): ?>
            <table id="citizenComplaintsTable">
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
                        $searchBlob = strtolower($row['id'] . ' ' . $row['category'] . ' ' . $row['description'] . ' ' . $row['street'] . ' ' . ($row['area'] ?? '') . ' ' . ($row['worker_name'] ?? ''));
                    ?>
                        <tr class="problem-record-row" data-status="<?php echo $st; ?>" data-search="<?php echo htmlspecialchars($searchBlob); ?>">
                            <!-- 1. Header segment: ID + Category on Desktop; on Mobile shows ID + Category on left & Status on right -->
                            <td class="col-id-category">
                                <!-- Mobile Card Top Bar -->
                                <div class="mobile-only-header">
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <strong style="font-size:1.02rem; color:#0f172a;">#<?php echo $row['id']; ?></strong>
                                        <span class="category-pill">
                                            <?php echo htmlspecialchars($row['category']); ?>
                                        </span>
                                    </div>
                                    <span class="badge-status <?php echo $stClass; ?>">
                                        <i class="fa-solid <?php echo $stIcon; ?>"></i> <?php echo $st; ?>
                                    </span>
                                </div>

                                <!-- Desktop ID & Category -->
                                <div class="desktop-id-meta">
                                    <strong style="font-size:0.95rem; color:#0f172a;">#<?php echo $row['id']; ?></strong><br>
                                    <span class="category-pill">
                                        <?php echo htmlspecialchars($row['category']); ?>
                                    </span>
                                </div>
                            </td>

                            <!-- 2. Complaint Details: Full width on mobile, no awkward squeeze -->
                            <td class="col-details">
                                <div class="complaint-desc-text"><?php echo htmlspecialchars($row['description']); ?></div>
                                <div class="complaint-loc-text">
                                    <i class="fa-solid fa-location-dot" style="color:#ef4444; margin-right:3px;"></i>
                                    <?php echo htmlspecialchars($row['street'] . ($row['area'] ? ', ' . $row['area'] : '')); ?>
                                </div>
                            </td>

                            <!-- 3. Photos -->
                            <td class="col-photos">
                                <div class="photos-cell-wrap">
                                    <?php if (!empty($row['photo']) || !empty($row['after_photo'])): ?>
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <?php if (!empty($row['photo'])): ?>
                                                <div title="Your Report Photo" style="text-align:center;">
                                                    <img src="<?php echo htmlspecialchars($row['photo']); ?>" class="thumb-preview" onclick="zoomPhoto('<?php echo htmlspecialchars($row['photo']); ?>', '📷 Your Original Report Photo')">
                                                    <div style="font-size:0.7rem; color:var(--text-muted); margin-top:2px;">Before</div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($row['after_photo'])): ?>
                                                <div title="Field Officer Resolution Proof Photo" style="text-align:center;">
                                                    <img src="<?php echo htmlspecialchars($row['after_photo']); ?>" class="thumb-preview" style="border:2px solid #10b981;" onclick="zoomPhoto('<?php echo htmlspecialchars($row['after_photo']); ?>', '✅ Field Officer Fixed Proof Photo')">
                                                    <div style="font-size:0.7rem; color:#059669; font-weight:700; margin-top:2px;">After</div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:#94a3b8; font-size:0.8rem; font-style:italic;"><i class="fa-regular fa-image"></i> No photos attached</span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- 4. Assigned Officer -->
                            <td class="col-officer">
                                <div class="officer-cell-wrap">
                                    <?php if (!empty($row['worker_name'])): ?>
                                        <div style="font-weight:700; color:#0f172a; font-size:0.88rem;">
                                            <i class="fa-solid fa-hard-hat" style="color:#0284c7;"></i> <?php echo htmlspecialchars($row['worker_name']); ?>
                                        </div>
                                        <?php if (!empty($row['worker_phone'])): ?>
                                            <div style="font-size:0.82rem; margin-top:3px;">
                                                <a href="tel:<?php echo htmlspecialchars($row['worker_phone']); ?>" style="color:#0284c7; text-decoration:none; font-weight:600; display:inline-flex; align-items:center; gap:4px;">
                                                    <i class="fa-solid fa-phone" style="font-size:0.75rem; color:#10b981;"></i> <?php echo htmlspecialchars($row['worker_phone']); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:#94a3b8; font-size:0.82rem; font-style:italic;">
                                            <i class="fa-solid fa-user-clock"></i> Awaiting Officer Allocation
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- 5. Actions / Status (on Desktop shows status + slip; on Mobile shows print slip) -->
                            <td class="col-status-action">
                                <div class="desktop-only-cell" style="margin-bottom:6px;">
                                    <span class="badge-status <?php echo $stClass; ?>">
                                        <i class="fa-solid <?php echo $stIcon; ?>"></i> <?php echo $st; ?>
                                    </span>
                                </div>
                                <a href="print_complaint.php?id=<?php echo $row['id']; ?>" target="_blank" class="print-slip-btn">
                                    <i class="fa-solid fa-print"></i> <span>Download Slip</span>
                                </a>
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
let activeStatusFilter = 'all';

function filterCitizenRows(status, btn) {
    activeStatusFilter = status;
    document.querySelectorAll('.filter-tab').forEach(b => {
        b.classList.remove('active');
    });
    if (btn) {
        btn.classList.add('active');
    }
    applyCitizenFilters();
}

function filterCitizenSearch() {
    applyCitizenFilters();
}

function applyCitizenFilters() {
    const term = (document.getElementById('citizenSearch')?.value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.problem-record-row');

    rows.forEach(row => {
        const rowStatus = row.getAttribute('data-status') || '';
        const rowSearch = row.getAttribute('data-search') || '';

        const matchesStatus = (activeStatusFilter === 'all' || rowStatus === activeStatusFilter);
        const matchesTerm = (!term || rowSearch.includes(term));

        if (matchesStatus && matchesTerm) {
            row.style.setProperty('display', '', '');
        } else {
            row.style.setProperty('display', 'none', 'important');
        }
    });
}

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

<?php include("../includes/chatbot_widget.php"); ?>
<?php include("../includes/citizen_bottom_nav.php"); ?>
</body>
</html>
