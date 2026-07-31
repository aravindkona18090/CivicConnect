<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
include("../db/connection.php");
require __DIR__ . '/../vendor/autoload.php';
// Include PHPMailer via Composer autoload

session_start();

// Function to send OTP using PHPMailer
function sendOtp($email, $otp)
{
  $mail = new PHPMailer(true);

  try {
    // SMTP server configuration
    $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = getenv('MAIL_USERNAME');
        $mail->Password = getenv('MAIL_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->Timeout = 10;
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = 'html';

    $mail->setFrom('civicconnect24@gmail.com', 'CivicConnect');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Your OTP for Verification';
    $mail->Body = "Hello,<br>Your OTP for verification is: <b>$otp</b><br>Please do not share this OTP.";

    $mail->send();
    return true;
  } catch (Exception $e) {
    error_log("PHPMailer Error: " . $mail->ErrorInfo);
    return false;
  }
}
$home = false;
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['send_otp'])) {
    // Generate a 4-digit OTP
    $otp = rand(1000, 9999);

    // Save OTP in session
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_timestamp'] = time();

    // Use the default email for testing
    if (isset($_GET['email'])) {
      $email = $_GET['email'];
      if (sendOtp($email, $otp)) {
        $successMessage = "OTP sent successfully.";
      } else {
        $errorMessage = "Failed to send OTP. Please try again.";
      }
    }

    // Send OTP

  } elseif (isset($_POST['verify_otp'])) {
    // Combine individual input fields into a single OTP value
    $enteredOtp = $_POST['otp'] ?? '';

    // Check if OTP matches and has not expired
    if (isset($_SESSION['otp']) && $enteredOtp == $_SESSION['otp']) {
      if (time() - $_SESSION['otp_timestamp'] > 300) { // 5 minutes expiration
        $errorMessage = "OTP expired. Please request a new one.";
        $home = false;
        unset($_SESSION['otp'], $_SESSION['otp_timestamp']);
      } else {
        $successMessage = "OTP verified successfully!";
        unset($_SESSION['otp'], $_SESSION['otp_timestamp']);
        $homeLink = '<a href="login.php">Go to Home</a>';
        $home = true;
        if (isset($_GET['email'])) {
          $email = $_GET['email'];
          $username = $_GET['name'];
          $hashed_password = $_GET['password'];
          $phone = $_GET['phone'];
          try {
            $mail = new PHPMailer(true);
            // SMTP s$mail = new PHPMailer(true);
            $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = getenv('MAIL_USERNAME');
        $mail->Password = getenv('MAIL_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->Timeout = 10;
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = 'html';

            $mail->setFrom('civicconnect24@gmail.com', 'CivicConnect');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "CivicConnect Registration Successful - Start Making a Difference!";
            $mail->Body = "
    <h2>Dear User,</h2>
    <p>You have successfully registered on <strong>CivicConnect</strong>. Welcome to our community!</p>
    <h3>Account Details:</h3>
    <ul>
        <li><strong>Registered Email:</strong> $email</li>
    </ul>
    <p>Thank you for joining <strong>CivicConnect</strong>, your platform to report and track government-related issues, raise concerns, and contribute to community improvement.</p>
    <p>With CivicConnect, you can easily submit complaints, monitor their progress, and work together with others for a better society.</p>
    <p>If you have any questions, feel free to reach out to us at civicconnect24@gmail.com.</p>
    <p>Let's build a better community together!</p>
    <p>Regards,<br>CivicConnect Team</p>";


            $mail->send();
          } catch (Exception $e) {
            $errorMessage = "Error sending email: {$mail->ErrorInfo}";
          }
          $sql = "INSERT INTO people (username, email, password, mobile) VALUES ('$username', '$email', '$hashed_password', '$phone')";
          $conn->query($sql);
        }


      }
    } else {
      $errorMessage = "Invalid OTP. Please try again.";
      $home = false;
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OTP Verification</title>
  <style>
    /* Your existing CSS */
    .otp {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      gap: 10px;
      background: #00000000;
      border-radius: 16px;
      box-shadow: 0 8px 40px rgba(0, 0, 0, 0.2);
      backdrop-filter: blur(8.2px);
      -webkit-backdrop-filter: blur(8.2px);
      border: 1px solid #369eff66;
      width: 20em;
      height: 20em;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }

    .otp .content {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-top: auto;
      margin-bottom: auto;
    }

    .otp p {
      color: #000000;
      font-weight: bolder;
      size: 10px;
    }

    .otp .path {
      fill: #369eff;
    }

    .otp .svg {
      filter: blur(20px);
      z-index: -1;
      position: absolute;
      opacity: 50%;
      animation: anim 3s infinite linear;
    }

    .otp .inp {
      margin-left: auto;
      margin-right: auto;
      white-space: 4px;
    }

    .otp .input+.input {
      margin-left: 0.3em;
    }

    .otp .input {
      color: black;
      height: 2.5em;
      width: 2.5em;
      float: left;
      text-align: center;
      background: #00000000;
      outline: none;
      border: 1px #369eff solid;
      border-radius: 10px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    }

    .otp .input:focus {
      outline: none;
      border: 1px #fff solid;
    }

    .otp .input:not(:placeholder-shown) {
      opacity: 100%;
    }

    .otp button {
      margin-left: auto;
      margin-right: auto;
      background-color: #00000000;
      color: #000000;
      width: 10em;
      height: 3em;
      border: #369eff 0.2em solid;
      border-radius: 11px;
      transition: all 0.5s ease;
      font-size: 1em;
    }

    .otp button:hover {
      background-color: #369eff;
    }

    @keyframes anim {
      0% {
        transform: rotate(0deg);
      }

      50% {
        transform: rotate(180deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }
  </style>
</head>

<body>
  <form class="otp" method="POST">
    <div class="content">
      <?php if (!isset($_SESSION['otp']) && $home == false): ?>
        <!-- Show Send OTP button if no OTP is set in session -->
        <button type="submit" name="send_otp">Send OTP</button>
      <?php elseif (isset($_SESSION['otp'])): ?>
        <!-- Show OTP Verification form if OTP is set but not yet verified -->
        <p align="center">Verify OTP</p>
        <div class="inp">
          <input type="text" class="input" maxlength="1" required>
          <input type="text" class="input" maxlength="1" required>
          <input type="text" class="input" maxlength="1" required>
          <input type="text" class="input" maxlength="1" required>
        </div>
        <button type="submit" name="verify_otp">Verify OTP</button>
      <?php endif; ?>
      <svg class="svg" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
        <path fill="#4073ff"
          d="M56.8,-23.9C61.7,-3.2,45.7,18.8,26.5,31.7C7.2,44.6,-15.2,48.2,-35.5,36.5C-55.8,24.7,-73.9,-2.6,-67.6,-25.2C-61.3,-47.7,-30.6,-65.6,-2.4,-64.8C25.9,-64.1,51.8,-44.7,56.8,-23.9Z"
          transform="translate(100 100)" class="path"></path>
      </svg>
      <!-- Display Messages -->
      <?php if (isset($successMessage)): ?>
        <div class="message">
          <?php echo $successMessage; ?>
          <?php if (isset($homeLink)): ?>
            <p><?php echo $homeLink; ?></p>
          <?php endif; ?>
        </div>
      <?php elseif (isset($errorMessage)): ?>
        <div class="message"><?php echo $errorMessage; ?></div>
      <?php endif; ?>
    </div>
  </form>
</body>
<script>
  // Handle OTP input field focus and combination
  const otpInputs = document.querySelectorAll('.otp .input');
  const form = document.querySelector('.otp');

  form.addEventListener('submit', (e) => {
    let otp = '';
    otpInputs.forEach(input => otp += input.value);
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'otp';
    hiddenInput.value = otp;
    form.appendChild(hiddenInput);
  });

  otpInputs.forEach((input, index) => {
    input.addEventListener('input', () => {
      if (input.value.length === 1 && otpInputs[index + 1]) {
        otpInputs[index + 1].focus();
      }
    });
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && input.value === '' && otpInputs[index - 1]) {
        otpInputs[index - 1].focus();
      }
    });
  });
</script>

</html>