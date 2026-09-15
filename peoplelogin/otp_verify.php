<?php
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../config.php';
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

session_start();

// Language handling
if (isset($_POST['language'])) {
    $_SESSION['language'] = $_POST['language'];
}
$selectedLang = $_SESSION['language'] ?? 'en';
include("../lang.php");

// 1. Resolve Target Email & Registration Data
$pendingData = $_SESSION['pending_signup'] ?? null;
$email = $pendingData['email'] ?? ($_GET['email'] ?? '');
$username = $pendingData['username'] ?? ($_GET['name'] ?? 'Citizen');
$password = $pendingData['password'] ?? ($_GET['password'] ?? '');
$mobile = $pendingData['mobile'] ?? ($_GET['phone'] ?? '');

if (empty($email)) {
    header("Location: login.php");
    exit();
}

$errorMessage = "";
$successMessage = "";
$isVerified = false;

// 2. Email Sender Function (Resend API / PHPMailer / Dev Fallback)
function sendCivicOtp($toEmail, $otpCode) {
    $resendKey = civic_config('RESEND_API_KEY') ?: getenv('RESEND_API_KEY');
    
    // Attempt 1: Resend API
    if (!empty($resendKey) && class_exists('Resend')) {
        try {
            $resend = Resend::client($resendKey);
            $resend->emails->send([
                'from' => 'CivicConnect <onboarding@resend.dev>',
                'to' => [$toEmail],
                'subject' => 'CivicConnect - Your 4-Digit Verification Code: ' . $otpCode,
                'html' => "
                    <div style='font-family:Arial,sans-serif; max-width:520px; margin:0 auto; padding:24px; border:1px solid #e2e8f0; border-radius:12px;'>
                        <div style='text-align:center; margin-bottom:20px;'>
                            <h2 style='color:#0f172a; margin:0;'>🏛️ CivicConnect</h2>
                            <p style='color:#64748b; font-size:14px; margin-top:4px;'>Smart City Grievance Redressal Portal</p>
                        </div>
                        <p style='font-size:15px; color:#334155;'>Hello,</p>
                        <p style='font-size:15px; color:#334155;'>Your one-time verification code for citizen registration is:</p>
                        <div style='text-align:center; margin:24px 0;'>
                            <span style='display:inline-block; font-size:32px; font-weight:800; letter-spacing:8px; color:#2563eb; background:#eff6ff; padding:12px 28px; border-radius:10px; border:1.5px dashed #3b82f6;'>{$otpCode}</span>
                        </div>
                        <p style='font-size:13px; color:#64748b; text-align:center;'>This code is valid for <strong>5 minutes</strong>. Please do not share it with anyone.</p>
                        <hr style='border:none; border-top:1px solid #e2e8f0; margin:20px 0;'>
                        <p style='font-size:12px; color:#94a3b8; text-align:center;'>CivicConnect Smart Governance System</p>
                    </div>
                "
            ]);
            return true;
        } catch (\Exception $e) {
            error_log("Resend API Error: " . $e->getMessage());
        }
    }

    // Attempt 2: PHPMailer / Mail function
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = civic_config('SMTP_HOST', 'smtp.gmail.com');
            $mail->SMTPAuth   = true;
            $mail->Username   = civic_config('SMTP_USER', '');
            $mail->Password   = civic_config('SMTP_PASS', '');
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            if (!empty($mail->Username)) {
                $mail->setFrom($mail->Username, 'CivicConnect');
                $mail->addAddress($toEmail);
                $mail->isHTML(true);
                $mail->Subject = 'Your CivicConnect OTP: ' . $otpCode;
                $mail->Body    = "Your OTP verification code is <strong>{$otpCode}</strong> (valid for 5 minutes).";
                $mail->send();
                return true;
            }
        } catch (\Exception $e) {
            error_log("PHPMailer Error: " . $e->getMessage());
        }
    }

    return false;
}

// 3. Auto-send OTP on first load
if (!isset($_SESSION['otp'])) {
    $newOtp = rand(1000, 9999);
    $_SESSION['otp'] = $newOtp;
    $_SESSION['otp_timestamp'] = time();
    sendCivicOtp($email, $newOtp);
    $successMessage = "A 4-digit verification code has been sent to your email.";
}

// 4. Handle POST Submissions (Verify or Resend)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'resend') {
        $newOtp = rand(1000, 9999);
        $_SESSION['otp'] = $newOtp;
        $_SESSION['otp_timestamp'] = time();
        sendCivicOtp($email, $newOtp);
        $successMessage = "A new verification code has been sent to your email.";
    } elseif (isset($_POST['verify_otp'])) {
        // Collect 4 digits
        $d1 = trim($_POST['d1'] ?? '');
        $d2 = trim($_POST['d2'] ?? '');
        $d3 = trim($_POST['d3'] ?? '');
        $d4 = trim($_POST['d4'] ?? '');
        $enteredOtp = $d1 . $d2 . $d3 . $d4;

        if (empty($enteredOtp)) {
            $enteredOtp = trim($_POST['otp'] ?? '');
        }

        if (isset($_SESSION['otp']) && $enteredOtp == $_SESSION['otp']) {
            if (time() - $_SESSION['otp_timestamp'] > 300) {
                $errorMessage = "Verification code has expired. Please request a new one.";
                unset($_SESSION['otp'], $_SESSION['otp_timestamp']);
            } else {
                // OTP is Valid -> Create Citizen Account
                $stmt = $conn->prepare("INSERT INTO people (username, email, password, mobile, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("ssss", $username, $email, $password, $mobile);
                
                if ($stmt->execute()) {
                    $newUserId = $stmt->insert_id;
                    $stmt->close();

                    // Clear OTP & pending data
                    unset($_SESSION['otp'], $_SESSION['otp_timestamp'], $_SESSION['pending_signup']);

                    // Automatically log citizen into dashboard
                    $_SESSION['user_id'] = $newUserId;
                    $_SESSION['username'] = $username;
                    $_SESSION['user_email'] = $email;

                    $isVerified = true;
                    header("Location: peopledashboard.php?registered=1");
                    exit();
                } else {
                    $errorMessage = "Error creating account: " . $conn->error;
                }
            }
        } else {
            $errorMessage = "Incorrect verification code. Please check and try again.";
        }
    }
}

// Mask Email for UI Privacy (e.g. j***e@gmail.com)
$parts = explode('@', $email);
$maskedEmail = substr($parts[0], 0, 2) . str_repeat('*', max(3, strlen($parts[0]) - 3)) . '@' . ($parts[1] ?? '');
?>
<!DOCTYPE html>
<html lang="<?php echo $selectedLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Citizen Account - CivicConnect</title>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-gradient: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            --bg-slate: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.95);
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
                radial-gradient(at 0% 0%, rgba(37, 99, 235, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(79, 70, 229, 0.1) 0px, transparent 50%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: var(--text-dark);
        }

        .otp-container {
            width: 100%;
            max-width: 440px;
        }

        .otp-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: var(--radius);
            padding: 38px 32px;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.08);
            text-align: center;
            position: relative;
        }

        .otp-icon-wrap {
            width: 64px;
            height: 64px;
            margin: 0 auto 18px;
            background: var(--primary-gradient);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
            color: #ffffff;
            font-size: 1.5rem;
        }

        .otp-card h2 {
            font-size: 1.55rem;
            font-weight: 800;
            margin-bottom: 6px;
            color: var(--text-dark);
        }

        .otp-card p {
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.45;
            margin-bottom: 20px;
        }

        .email-pill {
            font-weight: 700;
            color: var(--primary);
            background: #eff6ff;
            padding: 2px 8px;
            border-radius: 6px;
            display: inline-block;
        }

        /* 4 Digit Inputs */
        .digit-inputs-row {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin: 24px 0 20px;
        }

        .digit-box {
            width: 58px;
            height: 64px;
            border: 2px solid var(--border-color);
            border-radius: 14px;
            text-align: center;
            font-size: 1.7rem;
            font-weight: 800;
            color: var(--text-dark);
            background: #ffffff;
            outline: none;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .digit-box:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.18);
            transform: translateY(-2px);
        }

        .digit-box.filled {
            border-color: #2563eb;
            background: #f8fafc;
        }

        /* Submit Button */
        .verify-btn {
            width: 100%;
            padding: 15px;
            background: var(--primary-gradient);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 0.98rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .verify-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.45);
        }

        /* Timer & Resend */
        .resend-section {
            margin-top: 22px;
            font-size: 0.88rem;
            color: var(--text-muted);
        }

        .resend-btn {
            background: none;
            border: none;
            color: var(--primary);
            font-weight: 700;
            font-family: inherit;
            font-size: 0.88rem;
            cursor: pointer;
            text-decoration: none;
            padding: 0;
        }
        .resend-btn:disabled {
            color: #94a3b8;
            cursor: not-allowed;
            text-decoration: none;
        }

        /* Alerts */
        .alert-box {
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-align: left;
        }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

        .back-link {
            display: inline-block;
            margin-top: 18px;
            font-size: 0.85rem;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        .back-link:hover { color: var(--primary); }
    </style>
</head>
<body>

    <div class="otp-container">
        <div class="otp-card">
            
            <div class="otp-icon-wrap">
                <i class="fa-solid fa-shield-halved"></i>
            </div>

            <h2>Verify Your Email</h2>
            <p>We've sent a 4-digit verification code to<br><span class="email-pill"><?php echo htmlspecialchars($maskedEmail); ?></span></p>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert-box alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div><?php echo htmlspecialchars($errorMessage); ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($successMessage)): ?>
                <div class="alert-box alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <div><?php echo $successMessage; ?></div>
                </div>
            <?php endif; ?>

            <!-- OTP Form -->
            <form method="POST" id="otpForm">
                <input type="hidden" name="verify_otp" value="1">
                
                <div class="digit-inputs-row">
                    <input type="text" inputmode="numeric" pattern="[0-9]*" class="digit-box" name="d1" id="d1" maxlength="1" autofocus autocomplete="off" required>
                    <input type="text" inputmode="numeric" pattern="[0-9]*" class="digit-box" name="d2" id="d2" maxlength="1" autocomplete="off" required>
                    <input type="text" inputmode="numeric" pattern="[0-9]*" class="digit-box" name="d3" id="d3" maxlength="1" autocomplete="off" required>
                    <input type="text" inputmode="numeric" pattern="[0-9]*" class="digit-box" name="d4" id="d4" maxlength="1" autocomplete="off" required>
                </div>

                <button type="submit" class="verify-btn" id="verifyBtn">
                    <span>Verify & Continue</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <!-- Resend Section with Live Countdown -->
            <div class="resend-section">
                <span id="timerText">Resend code in <strong id="countdown" style="color:#0f172a;">00:60</strong></span>
                <form method="POST" id="resendForm" style="display:inline;">
                    <input type="hidden" name="action" value="resend">
                    <button type="submit" id="resendBtn" class="resend-btn" style="display:none;">
                        <i class="fa-solid fa-rotate-right"></i> Resend OTP
                    </button>
                </form>
            </div>

            <div>
                <a href="login.php" class="back-link">
                    <i class="fa-solid fa-arrow-left"></i> Change Email or Log In
                </a>
            </div>

        </div>
    </div>

    <script>
        // Auto-Advancing & Auto-Submitting 4-Digit Inputs
        const digits = [document.getElementById('d1'), document.getElementById('d2'), document.getElementById('d3'), document.getElementById('d4')];
        const form = document.getElementById('otpForm');

        digits.forEach((input, idx) => {
            input.addEventListener('input', function(e) {
                // Allow only digits
                this.value = this.value.replace(/[^0-9]/g, '');
                
                if (this.value.length >= 1) {
                    this.classList.add('filled');
                    if (idx < 3) {
                        digits[idx + 1].focus();
                    } else {
                        // Auto-submit on 4th digit
                        if (digits.every(d => d.value.length === 1)) {
                            form.submit();
                        }
                    }
                } else {
                    this.classList.remove('filled');
                }
            });

            // Backspace Navigation
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value === '' && idx > 0) {
                    digits[idx - 1].focus();
                    digits[idx - 1].value = '';
                    digits[idx - 1].classList.remove('filled');
                }
            });

            // Paste Full 4-Digit Code
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim();
                const cleanData = pasteData.replace(/[^0-9]/g, '').slice(0, 4);

                if (cleanData.length === 4) {
                    for (let i = 0; i < 4; i++) {
                        digits[i].value = cleanData[i];
                        digits[i].classList.add('filled');
                    }
                    digits[3].focus();
                    form.submit();
                }
            });
        });

        // 60-Second Resend Countdown
        let timeLeft = 60;
        const countdownElem = document.getElementById('countdown');
        const timerText = document.getElementById('timerText');
        const resendBtn = document.getElementById('resendBtn');

        const timer = setInterval(() => {
            timeLeft--;
            const secs = timeLeft < 10 ? '0' + timeLeft : timeLeft;
            countdownElem.innerText = `00:${secs}`;

            if (timeLeft <= 0) {
                clearInterval(timer);
                timerText.style.display = 'none';
                resendBtn.style.display = 'inline-flex';
            }
        }, 1000);
    </script>
</body>
</html>