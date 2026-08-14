<?php
session_start();
include("../lang.php");
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo $selectedLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang[$selectedLang]['worker_login']; ?> - CivicConnect</title>
    <!-- Font Awesome & Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --emerald-primary: #059669;
            --emerald-gradient: linear-gradient(135deg, #059669 0%, #10b981 100%);
            --bg-slate: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.95);
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --focus-ring: rgba(5, 150, 105, 0.2);
            --radius: 20px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-slate);
            background-image: 
                radial-gradient(at 0% 0%, rgba(5, 150, 105, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.1) 0px, transparent 50%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 24px; color: var(--text-dark);
        }
        .login-container { width: 100%; max-width: 440px; }
        .login-card {
            background: var(--card-bg); backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.8); border-radius: var(--radius);
            padding: 44px 36px; box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.08);
            position: relative; transition: transform 0.3s ease;
        }
        .login-card:hover { transform: translateY(-4px); }
        .login-header { text-align: center; margin-bottom: 28px; }
        .neu-icon {
            width: 68px; height: 68px; margin: 0 auto 20px;
            background: var(--emerald-gradient); border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.3);
        }
        .login-header h2 { color: var(--text-dark); font-size: 1.75rem; font-weight: 800; margin-bottom: 6px; }
        .login-header p { color: var(--text-muted); font-size: 0.95rem; }
        .form-group { margin-bottom: 22px; position: relative; }
        .input-group { position: relative; display: flex; align-items: center; }
        .input-group input {
            width: 100%; padding: 16px 16px 16px 48px; background: #ffffff;
            border: 1.5px solid var(--border-color); border-radius: 12px;
            font-size: 0.95rem; font-family: inherit; color: var(--text-dark); outline: none;
            transition: all 0.25s ease;
        }
        .input-group input:focus { border-color: var(--emerald-primary); box-shadow: 0 0 0 4px var(--focus-ring); }
        .input-icon { position: absolute; left: 16px; color: var(--text-muted); pointer-events: none; }
        .submit-btn {
            width: 100%; padding: 16px; background: var(--emerald-gradient); color: #ffffff;
            border: none; border-radius: 12px; font-size: 1rem; font-weight: 700; font-family: inherit;
            cursor: pointer; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35); transition: all 0.25s ease;
            display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 10px;
        }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(5, 150, 105, 0.45); }
        .login-footer { text-align: center; margin-top: 24px; font-size: 0.9rem; color: var(--text-muted); }
        .login-footer a { color: var(--emerald-primary); font-weight: 700; text-decoration: none; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 12px; border-radius: 10px; font-size: 0.9rem; font-weight: 600; margin-bottom: 20px; text-align: center; }
        .top-lang-bar { text-align: right; margin-bottom: 12px; }
        .top-lang-bar select { padding: 6px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 700; font-family: inherit; cursor: pointer; }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="top-lang-bar">
            <form method="POST" style="display:inline-flex; align-items:center;">
                <select name="language" onchange="this.form.submit()" title="Select Language">
                    <option value="en" <?php if ($selectedLang=='en') echo 'selected'; ?>>🌐 English</option>
                    <option value="te" <?php if ($selectedLang=='te') echo 'selected'; ?>>🌐 తెలుగు (Telugu)</option>
                    <option value="hn" <?php if ($selectedLang=='hn') echo 'selected'; ?>>🌐 हिंदी (Hindi)</option>
                    <option value="kn" <?php if ($selectedLang=='kn') echo 'selected'; ?>>🌐 ಕನ್ನಡ (Kannada)</option>
                </select>
            </form>
        </div>

        <div class="login-card">
            <div class="login-header">
                <div class="neu-icon">
                    <i class="fa-solid fa-person-digging" style="font-size:1.5rem; color:#fff;"></i>
                </div>
                <h2><?php echo $lang[$selectedLang]['worker_login']; ?></h2>
                <p><?php echo $lang[$selectedLang]['worker_welcome']; ?></p>
            </div>
            
            <?php 
            $msg = $_GET['msg'] ?? '';
            if(!empty($msg)): ?>
                <div style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 12px; border-radius: 10px; font-size: 0.9rem; font-weight: 600; margin-bottom: 20px; text-align: center;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <?php if(!empty($error)): ?>
                <div class="alert-error">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="workerlogin.php" method="POST" autocomplete="off">
                <div class="form-group">
                    <div class="input-group">
                        <div class="input-icon"><i class="fa-solid fa-user-shield"></i></div>
                        <input type="text" id="login_id" name="login_id" required placeholder="Officer Email, Username, or ID">
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <div class="input-icon"><i class="fa-solid fa-lock"></i></div>
                        <input type="password" id="password" name="password" required placeholder="Password">
                    </div>
                    <div style="text-align: right; margin-top: 8px;">
                        <a href="forgot_password.php" style="color: var(--emerald-primary); font-weight: 700; font-size: 0.85rem; text-decoration: none;">
                            <i class="fa-solid fa-key" style="font-size: 0.75rem;"></i> Forgot / Change Password?
                        </a>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <?php echo $lang[$selectedLang]['login_as_worker']; ?> <i class="fa-solid fa-arrow-right"></i>
                </button>

                <div class="login-footer">
                    <p><a href="../logindecide.php" style="color:var(--text-muted);"><i class="fa-solid fa-arrow-left"></i> <?php echo $lang[$selectedLang]['change_role']; ?></a></p>
                </div>
            </form>
        </div>
    </div>

</body>
</html>