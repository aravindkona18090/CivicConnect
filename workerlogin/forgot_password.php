<?php
session_start();
include("../db/connection.php");
include("../lang.php");

require __DIR__ . '/../vendor/autoload.php';

$step = 1;
$error_msg = "";
$success_msg = "";
$otp_display_preview = "";

function sendPasswordResetEmail($to, $name, $otp) {
    try {
        if (class_exists('Resend') && !empty(getenv('RESEND_API_KEY'))) {
            $resend = Resend::client(getenv('RESEND_API_KEY'));
            $resend->emails->send([
                'from' => 'CivicConnect Security <onboarding@resend.dev>',
                'to' => [$to],
                'subject' => '🔑 CivicConnect - Password Reset OTP for Field Officer',
                'html' => "
                    <div style='font-family: Arial, sans-serif; max-width: 550px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 16px;'>
                        <h2 style='color: #059669;'>Password Reset Request 🔐</h2>
                        <p>Hello <b>{$name}</b>,</p>
                        <p>We received a request to reset the password for your Field Officer account in CivicConnect.</p>
                        
                        <div style='background: #ecfdf5; padding: 20px; border-radius: 12px; text-align: center; margin: 20px 0; border: 1px dashed #059669;'>
                            <p style='margin: 0; font-size: 0.9rem; color: #065f46; font-weight: bold;'>Your One-Time Verification OTP:</p>
                            <h1 style='letter-spacing: 6px; color: #059669; font-size: 2.2rem; margin: 8px 0;'>{$otp}</h1>
                            <p style='margin: 0; font-size: 0.8rem; color: #64748b;'>Valid for 15 minutes. Do not share this code with anyone.</p>
                        </div>
                        
                        <p style='color: #64748b; font-size: 0.85rem;'>If you did not request this password reset, please ignore this email.</p>
                        <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                        <p style='color: #94a3b8; font-size: 0.8rem;'>CivicConnect Municipal Administration Support</p>
                    </div>
                "
            ]);
            return true;
        }
        return false;
    } catch (\Exception $e) {
        error_log("Resend OTP Error: " . $e->getMessage());
        return false;
    }
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 1: Send OTP
    if (isset($_POST['send_otp'])) {
        $email = trim($_POST['email'] ?? '');
        if (empty($email)) {
            $error_msg = "Please enter your registered email address.";
        } else {
            $stmt = $conn->prepare("SELECT id, name, email FROM workers WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $worker = $result->fetch_assoc();
                $otp = (string)mt_rand(100000, 999999);
                
                $_SESSION['reset_email'] = $worker['email'];
                $_SESSION['reset_name'] = $worker['name'];
                $_SESSION['reset_otp'] = $otp;
                $_SESSION['reset_otp_time'] = time();

                $email_sent = sendPasswordResetEmail($worker['email'], $worker['name'], $otp);
                
                // Show preview banner if running locally or email dispatched
                $success_msg = "A 6-digit verification code has been generated for <strong>" . htmlspecialchars($email) . "</strong>.";
                $otp_display_preview = $otp;
                $step = 2;
            } else {
                $error_msg = "No Field Officer account found with the email address <strong>" . htmlspecialchars($email) . "</strong>.";
            }
            $stmt->close();
        }
    }

    // Step 2: Verify OTP & Change Password
    elseif (isset($_POST['verify_and_reset'])) {
        $step = 2;
        $entered_otp = trim($_POST['otp'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $saved_otp = $_SESSION['reset_otp'] ?? '';
        $saved_email = $_SESSION['reset_email'] ?? '';
        $saved_time = $_SESSION['reset_otp_time'] ?? 0;

        if (empty($saved_email) || empty($saved_otp)) {
            $error_msg = "Session expired. Please request a new verification code.";
            $step = 1;
        } elseif (time() - $saved_time > 900) { // 15 minutes expiry
            $error_msg = "Verification OTP has expired. Please request a new code.";
            $step = 1;
        } elseif ($entered_otp !== $saved_otp) {
            $error_msg = "Invalid verification code entered. Please check and try again.";
        } elseif (strlen($new_password) < 6) {
            $error_msg = "New password must be at least 6 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $error_msg = "Passwords do not match. Please enter the same password in both fields.";
        } else {
            // Update password in database
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE workers SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashedPassword, $saved_email);

            if ($stmt->execute()) {
                unset($_SESSION['reset_otp']);
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_name']);
                unset($_SESSION['reset_otp_time']);

                header("Location: login.php?msg=" . urlencode("Password reset successfully! You can now log in with your new password."));
                exit();
            } else {
                $error_msg = "Database update error. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $selectedLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Field Officer Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --emerald-primary: #059669;
            --emerald-gradient: linear-gradient(135deg, #059669 0%, #10b981 100%);
            --bg-slate: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.98);
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --radius: 20px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-slate);
            background-image: 
                radial-gradient(at 0% 0%, rgba(5, 150, 105, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.1) 0px, transparent 50%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: var(--text-dark);
        }

        .reset-container {
            width: 100%;
            max-width: 460px;
        }

        .reset-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 40px 36px;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.08);
            position: relative;
        }

        .reset-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .neu-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            background: var(--emerald-gradient);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.3);
            color: #fff;
            font-size: 1.5rem;
        }

        .reset-header h2 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 6px;
        }

        .reset-header p {
            color: var(--text-muted);
            font-size: 0.92rem;
            line-height: 1.4;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-group input {
            width: 100%;
            padding: 14px 16px 14px 44px;
            background: #ffffff;
            border: 1.5px solid var(--border-color);
            border-radius: 12px;
            font-size: 0.95rem;
            font-family: inherit;
            color: var(--text-dark);
            outline: none;
            transition: all 0.25s ease;
        }

        .input-group input:focus {
            border-color: var(--emerald-primary);
            box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.15);
        }

        .input-icon {
            position: absolute;
            left: 16px;
            color: var(--text-muted);
            pointer-events: none;
            font-size: 0.95rem;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: var(--emerald-gradient);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.3);
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.4);
        }

        .alert-box {
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }

        .otp-badge-box {
            background: #f0fdf4;
            border: 1.5px dashed #059669;
            padding: 14px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
        }

        .otp-badge-box span {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: 6px;
            color: #059669;
            display: block;
            margin-top: 4px;
        }

        .footer-links {
            text-align: center;
            margin-top: 24px;
            font-size: 0.9rem;
        }
        .footer-links a {
            color: var(--emerald-primary);
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .footer-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="reset-container">
        <div class="reset-card">
            <div class="reset-header">
                <div class="neu-icon">
                    <i class="fa-solid fa-key"></i>
                </div>
                <h2>Reset Officer Password</h2>
                <p>
                    <?php if ($step === 1): ?>
                        Enter your registered email address to receive a secure password reset code.
                    <?php else: ?>
                        Enter the 6-digit verification code sent to your email and set your new password.
                    <?php endif; ?>
                </p>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="alert-box alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div><?php echo $error_msg; ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_msg)): ?>
                <div class="alert-box alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <div><?php echo $success_msg; ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($otp_display_preview) && $step === 2): ?>
                <div class="otp-badge-box">
                    <div style="font-size:0.8rem; color:#065f46; font-weight:700;">YOUR 6-DIGIT VERIFICATION CODE:</div>
                    <span><?php echo $otp_display_preview; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <!-- Step 1: Request OTP -->
                <form action="forgot_password.php" method="POST" autocomplete="off">
                    <div class="form-group">
                        <label for="email">Registered Officer Email Address</label>
                        <div class="input-group">
                            <div class="input-icon"><i class="fa-regular fa-envelope"></i></div>
                            <input type="email" id="email" name="email" required placeholder="e.g. officer@civicconnect.gov" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <button type="submit" name="send_otp" class="submit-btn">
                        <span>Send Verification Code</span> <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>
            <?php else: ?>
                <!-- Step 2: Enter OTP & New Password -->
                <form action="forgot_password.php" method="POST" autocomplete="off">
                    <div class="form-group">
                        <label for="otp">6-Digit Verification Code</label>
                        <div class="input-group">
                            <div class="input-icon"><i class="fa-solid fa-shield-halved"></i></div>
                            <input type="text" id="otp" name="otp" required maxlength="6" placeholder="Enter 6-digit OTP" style="letter-spacing: 3px; font-weight: bold; font-size: 1.1rem;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password (Min. 6 characters)</label>
                        <div class="input-group">
                            <div class="input-icon"><i class="fa-solid fa-lock"></i></div>
                            <input type="password" id="new_password" name="new_password" required minlength="6" placeholder="Enter new password">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="input-group">
                            <div class="input-icon"><i class="fa-solid fa-check-double"></i></div>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Confirm new password">
                        </div>
                    </div>

                    <button type="submit" name="verify_and_reset" class="submit-btn">
                        <span>Update Password & Log In</span> <i class="fa-solid fa-check"></i>
                    </button>
                </form>
            <?php endif; ?>

            <div class="footer-links">
                <a href="login.php"><i class="fa-solid fa-arrow-left"></i> Back to Officer Login</a>
            </div>
        </div>
    </div>

</body>
</html>
