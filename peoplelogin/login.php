<?php
session_start();
include("../lang.php");
require_once(__DIR__ . "/../config.php");
$error_message = $_GET["error"] ?? "";
$error_message2 = $_GET["error2"] ?? "";
$googleClientId = civic_config('GOOGLE_CLIENT_ID');
?>
<!DOCTYPE html>
<html lang="<?php echo $selectedLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang[$selectedLang]['people_login']; ?> - CivicConnect</title>
    <!-- Font Awesome, Google Fonts, & Google Identity Services -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
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
            padding: 40px 36px; box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.08);
            position: relative; transition: transform 0.3s ease;
        }
        .login-card:hover { transform: translateY(-4px); }
        .login-header { text-align: center; margin-bottom: 24px; }
        .neu-icon {
            width: 64px; height: 64px; margin: 0 auto 16px;
            background: var(--primary-gradient); border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
        }
        .login-header h2 { color: var(--text-dark); font-size: 1.65rem; font-weight: 800; margin-bottom: 6px; }
        .login-header p { color: var(--text-muted); font-size: 0.92rem; }
        .form-group { margin-bottom: 20px; position: relative; }
        .input-group { position: relative; display: flex; align-items: center; }
        .input-group input {
            width: 100%; padding: 15px 16px 15px 48px; background: #ffffff;
            border: 1.5px solid var(--border-color); border-radius: 12px;
            font-size: 0.95rem; font-family: inherit; color: var(--text-dark); outline: none;
            transition: all 0.25s ease;
        }
        .input-group input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px var(--focus-ring); }
        .input-icon { position: absolute; left: 16px; color: var(--text-muted); pointer-events: none; }
        .password-toggle { position: absolute; right: 14px; background: none; border: none; color: var(--text-muted); cursor: pointer; }
        .submit-btn {
            width: 100%; padding: 15px; background: var(--primary-gradient); color: #ffffff;
            border: none; border-radius: 12px; font-size: 0.98rem; font-weight: 700; font-family: inherit;
            cursor: pointer; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35); transition: all 0.25s ease;
            display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 8px;
        }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(37, 99, 235, 0.45); }
        
        /* OAUTH DIVIDER & GOOGLE BUTTON */
        .oauth-divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0 16px;
            color: var(--text-muted);
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .oauth-divider::before, .oauth-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--border-color);
        }
        .oauth-divider span {
            padding: 0 12px;
        }
        .google-btn-wrap {
            display: flex;
            justify-content: center;
            width: 100%;
            margin-bottom: 8px;
            min-height: 44px;
        }

        .auth-status-msg {
            display: none !important;
            padding: 10px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 14px;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .auth-status-msg.active {
            display: flex !important;
        }
        .auth-status-loading {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .login-footer { text-align: center; margin-top: 20px; font-size: 0.88rem; color: var(--text-muted); }
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

        <!-- Google OAuth Credentials & Initialization -->
        <div id="g_id_onload"
             data-client_id="<?php echo htmlspecialchars($googleClientId); ?>"
             data-callback="handleGoogleCredentialResponse"
             data-auto_prompt="false">
        </div>

        <!-- Sign In Card -->
        <div class="login-card" id="loginCard">
            <div class="login-header">
                <div class="neu-icon">
                    <i class="fa-solid fa-user-group" style="font-size:1.4rem; color:#fff;"></i>
                </div>
                <h2><?php echo $lang[$selectedLang]['people_login']; ?></h2>
                <p><?php echo $lang[$selectedLang]['citizen_welcome']; ?></p>
            </div>
            
            <div id="googleAuthStatus" class="auth-status-msg auth-status-loading">
                <i class="fa-solid fa-circle-notch fa-spin"></i> Authenticating with Google...
            </div>

            <?php if(!empty($error_message)): ?>
                <div class="alert-error">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Google Sign-In Button -->
            <div class="google-btn-wrap">
                <div class="g_id_signin"
                     data-type="standard"
                     data-shape="rectangular"
                     data-theme="outline"
                     data-text="signin_with"
                     data-size="large"
                     data-logo_alignment="left"
                     data-width="368">
                </div>
            </div>

            <div class="oauth-divider">
                <span>or sign in with password</span>
            </div>

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
                    <i class="fa-solid fa-user-plus" style="font-size:1.4rem; color:#fff;"></i>
                </div>
                <h2><?php echo $lang[$selectedLang]['create_account']; ?></h2>
                <p>Join CivicConnect to report issues in your neighborhood</p>
            </div>

            <div id="googleAuthStatusSignup" class="auth-status-msg auth-status-loading">
                <i class="fa-solid fa-circle-notch fa-spin"></i> Setting up your Google account...
            </div>

            <?php if(!empty($error_message2)): ?>
                <div class="alert-error">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error_message2); ?>
                </div>
            <?php endif; ?>

            <!-- Google Sign-Up Button -->
            <div class="google-btn-wrap">
                <div class="g_id_signin"
                     data-type="standard"
                     data-shape="rectangular"
                     data-theme="outline"
                     data-text="signup_with"
                     data-size="large"
                     data-logo_alignment="left"
                     data-width="368">
                </div>
            </div>

            <div class="oauth-divider">
                <span>or register with email</span>
            </div>

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

    <!-- Google Identity Services Callback Handler -->
    <script>
    function handleGoogleCredentialResponse(response) {
        if (!response || !response.credential) {
            alert("Google Sign-In failed to return credentials.");
            return;
        }

        $('#googleAuthStatus, #googleAuthStatusSignup').addClass('active');
        $('.login-form, .google-btn-wrap').css('opacity', '0.5').css('pointer-events', 'none');

        $.ajax({
            url: '../api/google_auth.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ credential: response.credential }),
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    window.location.href = res.redirect || 'peopledashboard.php';
                } else {
                    $('#googleAuthStatus, #googleAuthStatusSignup').removeClass('active');
                    $('.login-form, .google-btn-wrap').css('opacity', '1').css('pointer-events', 'auto');
                    alert(res.message || "Google Sign-In failed.");
                }
            },
            error: function(xhr) {
                $('#googleAuthStatus, #googleAuthStatusSignup').removeClass('active');
                $('.login-form, .google-btn-wrap').css('opacity', '1').css('pointer-events', 'auto');
                console.error("Google Auth error:", xhr.responseText);
                alert("An error occurred during Google authentication. Please try again.");
            }
        });
    }

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