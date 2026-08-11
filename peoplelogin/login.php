<?php
session_start();
include("../lang.php");
$error_message = $_GET["error"] ?? "";
$error_message2 = $_GET["error2"] ?? "";
?>
<!DOCTYPE html>
<html lang="<?php echo $selectedLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang[$selectedLang]['people_login']; ?> - CivicConnect</title>
    <!-- Font Awesome & Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary: #2563eb;
            --primary-gradient: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            --bg-slate: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.95);
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --focus-ring: rgba(37, 99, 235, 0.2);
            --radius: 20px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-slate);
            background-image: 
                radial-gradient(at 0% 0%, rgba(37, 99, 235, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(79, 70, 229, 0.1) 0px, transparent 50%);
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
            background: var(--primary-gradient); border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
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
        .input-group input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px var(--focus-ring); }
        .input-icon { position: absolute; left: 16px; color: var(--text-muted); pointer-events: none; }
        .password-toggle { position: absolute; right: 14px; background: none; border: none; color: var(--text-muted); cursor: pointer; }
        .submit-btn {
            width: 100%; padding: 16px; background: var(--primary-gradient); color: #ffffff;
            border: none; border-radius: 12px; font-size: 1rem; font-weight: 700; font-family: inherit;
            cursor: pointer; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35); transition: all 0.25s ease;
            display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 10px;
        }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(37, 99, 235, 0.45); }
        .login-footer { text-align: center; margin-top: 24px; font-size: 0.9rem; color: var(--text-muted); }
        .login-footer a { color: var(--primary); font-weight: 700; text-decoration: none; }
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

        <!-- Sign In Card -->
        <div class="login-card" id="loginCard">
            <div class="login-header">
                <div class="neu-icon">
                    <i class="fa-solid fa-user-group" style="font-size:1.5rem; color:#fff;"></i>
                </div>
                <h2><?php echo $lang[$selectedLang]['people_login']; ?></h2>
                <p><?php echo $lang[$selectedLang]['citizen_welcome']; ?></p>
            </div>
            
            <?php if(!empty($error_message)): ?>
                <div class="alert-error">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form class="login-form" id="loginForm" action="peoplelogin.php" method="POST" autocomplete="off">
                <div class="form-group">
                    <div class="input-group">
                        <div class="input-icon"><i class="fa-solid fa-envelope"></i></div>
                        <input type="email" id="email" name="email" required placeholder="<?php echo $lang[$selectedLang]['email']; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <div class="input-icon"><i class="fa-solid fa-lock"></i></div>
                        <input type="password" id="password" name="password" required placeholder="Password / పాస్‌వర్డ్">
                        <button type="button" class="password-toggle" id="passwordToggle">
                            <i class="fa-solid fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <?php echo $lang[$selectedLang]['login_as_citizen']; ?> <i class="fa-solid fa-arrow-right"></i>
                </button>

                <div class="login-footer">
                    <p><?php echo $lang[$selectedLang]['dont_have_account']; ?> <a href="#" id="showSignup"><?php echo $lang[$selectedLang]['create_account']; ?></a></p>
                    <p style="margin-top: 10px;"><a href="../logindecide.php" style="color:var(--text-muted);"><i class="fa-solid fa-arrow-left"></i> <?php echo $lang[$selectedLang]['change_role']; ?></a></p>
                </div>
            </form>
        </div>

        <!-- Sign Up Card -->
        <div class="login-card" id="signupCard" style="display: none;">
            <div class="login-header">
                <div class="neu-icon">
                    <i class="fa-solid fa-user-plus" style="font-size:1.5rem; color:#fff;"></i>
                </div>
                <h2><?php echo $lang[$selectedLang]['create_account']; ?></h2>
                <p>Join CivicConnect to report issues in your neighborhood</p>
            </div>

            <?php if(!empty($error_message2)): ?>
                <div class="alert-error">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error_message2); ?>
                </div>
            <?php endif; ?>

            <form class="login-form" id="signupForm" action="peoplesignup.php" method="POST">
                <div class="form-group">
                    <div class="input-group">
                        <div class="input-icon"><i class="fa-solid fa-user"></i></div>
                        <input type="text" id="username" name="username" required placeholder="<?php echo $lang[$selectedLang]['name']; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <div class="input-icon"><i class="fa-solid fa-envelope"></i></div>
                        <input type="email" id="emailSign" name="emailSign" required placeholder="<?php echo $lang[$selectedLang]['email']; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <div class="input-icon"><i class="fa-solid fa-phone"></i></div>
                        <input type="tel" id="mobile" name="mobile" required placeholder="<?php echo $lang[$selectedLang]['phone']; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <div class="input-icon"><i class="fa-solid fa-key"></i></div>
                        <input type="password" id="createPass" name="createPass" required placeholder="Create Password">
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <?php echo $lang[$selectedLang]['create_account']; ?> <i class="fa-solid fa-check"></i>
                </button>

                <div class="login-footer">
                    <p>Already have an account? <a href="#" id="showLogin">Sign In</a></p>
                </div>
            </form>
        </div>

    </div>

    <script>
    $(document).ready(function(){
        $('#showSignup').click(function(e){
            e.preventDefault();
            $('#loginCard').hide();
            $('#signupCard').fadeIn();
        });
        $('#showLogin').click(function(e){
            e.preventDefault();
            $('#signupCard').hide();
            $('#loginCard').fadeIn();
        });

        $('#passwordToggle').click(function(){
            var passInput = $('#password');
            var icon = $('#eyeIcon');
            if (passInput.attr('type') === 'password') {
                passInput.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                passInput.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    });
    </script>
</body>
</html>