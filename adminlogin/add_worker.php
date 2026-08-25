<?php
session_start();
include('../db/connection.php');

require __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

function generateRandomPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    return substr(str_shuffle(str_repeat($chars, 3)), 0, $length);
}

function sendWorkerCredentialsEmail($to, $name, $randomPassword)
{
    try {
        if (class_exists('Resend')) {
            $resend = Resend::client(getenv('RESEND_API_KEY'));
            $resend->emails->send([
                'from' => 'CivicConnect Admin <onboarding@resend.dev>',
                'to' => [$to],
                'subject' => '🔐 Welcome to CivicConnect - Your Field Officer Account Credentials',
                'html' => "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 16px;'>
                        <h2 style='color: #0284c7;'>Welcome to CivicConnect, {$name}! 👋</h2>
                        <p>Your Field Officer account has been manually verified and created by the Municipal Administrator.</p>
                        
                        <div style='background: #f0f9ff; padding: 20px; border-radius: 12px; border-left: 4px solid #0284c7; margin: 20px 0;'>
                            <h4 style='margin-top: 0; color: #0369a1;'>Your Field Officer Login Credentials:</h4>
                            <p style='margin: 8px 0;'><b>Login Portal:</b> <a href='http://localhost/CivicConnect/workerlogin/login.php'>http://localhost/CivicConnect/workerlogin/login.php</a></p>
                            <p style='margin: 8px 0;'><b>Email / Login ID:</b> <code>{$to}</code></p>
                            <p style='margin: 8px 0;'><b>Auto-Generated Password:</b> <code style='background: #e0f2fe; padding: 4px 8px; border-radius: 4px; font-weight: bold;'>{$randomPassword}</code></p>
                        </div>
                        
                        <p style='color: #64748b;'>Please log in to your Field Officer dashboard and update your password after your first login.</p>
                        <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                        <p style='color: #94a3b8; font-size: 0.85rem;'>Regards,<br><strong>CivicConnect Municipal Administration</strong></p>
                    </div>
                "
            ]);
            return true;
        }
        return false;
    } catch (\Exception $e) {
        error_log("Resend Error: " . $e->getMessage());
        return false;
    }
}

$check_age_col = mysqli_query($conn, "SHOW COLUMNS FROM workers LIKE 'age'");
if ($check_age_col && mysqli_num_rows($check_age_col) == 0) {
    mysqli_query($conn, "ALTER TABLE workers ADD COLUMN age INT DEFAULT NULL");
}

$success_msg = "";
$error_msg = "";
$created_credentials = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_worker'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $mobile = trim($_POST['mobile'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');

    if (empty($name) || empty($email) || empty($mobile) || empty($category)) {
        $error_msg = "Please fill in all required fields (Name, Email, Mobile, Department).";
    } else {
        $check_stmt = $conn->prepare("SELECT id FROM workers WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $error_msg = "A Field Officer with this email address (<strong>{$email}</strong>) is already registered.";
        } else {
            $randomPassword = generateRandomPassword(10);
            $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO workers (name, email, age, mobile, area, city, pincode, category, password, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssissssss", $name, $email, $age, $mobile, $area, $city, $pincode, $category, $hashedPassword);
            
            if ($stmt->execute()) {
                $worker_id = $stmt->insert_id;
                $stmt->close();

                sendWorkerCredentialsEmail($email, $name, $randomPassword);

                $created_credentials = [
                    'id' => $worker_id,
                    'name' => $name,
                    'email' => $email,
                    'password' => $randomPassword,
                    'category' => $category
                ];

                $success_msg = "Field Officer <strong>{$name}</strong> added successfully! Login credentials have been emailed to <strong>{$email}</strong>.";
            } else {
                $error_msg = "Database error: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Field Officer - Admin Command Center</title>
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
            max-width: 800px;
            margin: 32px auto;
            padding: 0 24px;
        }

        .form-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            padding: 32px;
        }

        .form-header {
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }

        .form-header h1 {
            font-size: 1.4rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.full-width { grid-column: span 2; }

        label {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-main);
        }

        input, select {
            width: 100%;
            padding: 11px 14px;
            border-radius: 8px;
            border: 1px solid var(--border);
            font-family: inherit;
            font-size: 0.9rem;
            background: #fff;
            outline: none;
        }

        input:focus, select:focus {
            border-color: var(--brand-primary);
        }

        .submit-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            padding: 13px 20px;
            border-radius: 10px;
            border: none;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            padding: 18px;
            border-radius: 12px;
            margin-bottom: 24px;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 24px;
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
            <a href="completedproblems.php" class="nav-link"><i class="fa-solid fa-circle-check"></i> Completed Archive</a>
            <a href="workers.php" class="nav-link active"><i class="fa-solid fa-hard-hat"></i> Field Officers</a>
        </nav>

        <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </header>

    <div class="container">

        <?php if (!empty($success_msg)): ?>
            <div class="alert-success">
                <div style="display:flex; align-items:center; gap:10px; font-weight:800; font-size:1.05rem; margin-bottom:6px;">
                    <i class="fa-solid fa-circle-check" style="font-size:1.3rem;"></i> Field Officer Registered Successfully!
                </div>
                <p><?php echo $success_msg; ?></p>

                <?php if ($created_credentials): ?>
                    <div style="background:#f0f9ff; border:1px solid #bae6fd; padding:14px; border-radius:8px; margin-top:10px;">
                        <h4 style="color:#0369a1; margin-bottom:6px;"><i class="fa-solid fa-key"></i> Auto-Generated Login Credentials (Emailed to Worker):</h4>
                        <p style="margin:3px 0;"><strong>Worker ID:</strong> #<?php echo $created_credentials['id']; ?></p>
                        <p style="margin:3px 0;"><strong>Name:</strong> <?php echo htmlspecialchars($created_credentials['name']); ?></p>
                        <p style="margin:3px 0;"><strong>Login Email:</strong> <code><?php echo htmlspecialchars($created_credentials['email']); ?></code></p>
                        <p style="margin:3px 0;"><strong>Auto-Generated Password:</strong> <code style="background:#e0f2fe; padding:3px 8px; border-radius:4px; font-weight:800; color:#0369a1;"><?php echo htmlspecialchars($created_credentials['password']); ?></code></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-xmark"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <div class="form-header">
                <h1><i class="fa-solid fa-user-plus" style="color:var(--brand-emerald);"></i> Add & Verify Field Officer</h1>
                <p style="color:var(--text-muted); font-size:0.85rem; margin-top:4px;">Admin verification page. Registering a field officer auto-generates a secure password and emails account details.</p>
            </div>

            <form method="POST" autocomplete="off">
                <div class="form-grid">

                    <div class="form-group">
                        <label for="name">Full Name <span style="color:#dc2626;">*</span></label>
                        <input type="text" id="name" name="name" required placeholder="e.g. Ramesh Kumar">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address (Login ID) <span style="color:#dc2626;">*</span></label>
                        <input type="email" id="email" name="email" required placeholder="e.g. ramesh.officer@gmail.com">
                    </div>

                    <div class="form-group">
                        <label for="age">Age <span style="color:#dc2626;">*</span></label>
                        <input type="number" id="age" name="age" required min="18" max="75" placeholder="e.g. 32">
                    </div>

                    <div class="form-group">
                        <label for="mobile">Mobile Number <span style="color:#dc2626;">*</span></label>
                        <input type="tel" id="mobile" name="mobile" required placeholder="e.g. 9876543210">
                    </div>

                    <div class="form-group full-width">
                        <label for="category">Assigned Department / Category <span style="color:#dc2626;">*</span></label>
                        <select id="category" name="category" required>
                            <option value="">-- Select Municipal Department --</option>
                            <option value="Roads & Potholes">🛣️ Roads & Potholes Department</option>
                            <option value="Sanitation & Garbage">🗑️ Sanitation & Waste Management</option>
                            <option value="Electricity & Streetlights">⚡ Electricity & Streetlights</option>
                            <option value="Drainage & Water Leakage">🚰 Drainage & Water Supply</option>
                            <option value="General Municipal">🏛️ General Municipal Infrastructure</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="area">Assigned Area / Division</label>
                        <input type="text" id="area" name="area" placeholder="e.g. Ward 12, Jubilee Hills">
                    </div>

                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" placeholder="e.g. Hyderabad">
                    </div>

                    <div class="form-group full-width">
                        <label for="pincode">Pincode</label>
                        <input type="text" id="pincode" name="pincode" placeholder="e.g. 500033">
                    </div>

                </div>

                <button type="submit" name="add_worker" class="submit-btn">
                    <i class="fa-solid fa-paper-plane"></i> Verify, Create Account & Email Password
                </button>
            </form>
        </div>

    </div>

</body>
</html>