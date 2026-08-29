<?php
// includes/mailer.php - Universal Email Dispatcher for CivicConnect (Brevo API, Brevo SMTP, PHPMailer, Resend)

require_once __DIR__ . '/../config.php';
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

/**
 * Universal function to send transactional emails in CivicConnect
 * @param string $toEmail - Recipient email address
 * @param string $subject - Email subject line
 * @param string $htmlContent - HTML body content
 * @param string $toName - Recipient name
 * @return bool - True if sent successfully, False otherwise
 */
function sendCivicEmail($toEmail, $subject, $htmlContent, $toName = 'Citizen') {
    $brevoApiKey = civic_config('BREVO_API_KEY') ?: getenv('BREVO_API_KEY');
    $brevoSenderEmail = civic_config('BREVO_SENDER_EMAIL') ?: getenv('BREVO_SENDER_EMAIL') ?: 'no-reply@civicconnect.gov';
    $brevoSenderName = civic_config('BREVO_SENDER_NAME') ?: 'CivicConnect';

    // 1. Primary Engine: Brevo REST API v3 (Fastest & Works on all servers/XAMPP without port blocking)
    if (!empty($brevoApiKey)) {
        $url = 'https://api.brevo.com/v3/smtp/email';
        $payload = [
            'sender' => [
                'name'  => $brevoSenderName,
                'email' => $brevoSenderEmail
            ],
            'to' => [
                [
                    'name'  => $toName,
                    'email' => $toEmail
                ]
            ],
            'subject'     => $subject,
            'htmlContent' => $htmlContent
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'api-key: ' . $brevoApiKey,
            'content-type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        } else {
            error_log("Brevo API Error ({$httpCode}): " . $response);
        }
    }

    // 2. Engine 2: Brevo / Generic SMTP via PHPMailer
    $smtpHost = civic_config('SMTP_HOST') ?: getenv('SMTP_HOST') ?: 'smtp-relay.brevo.com';
    $smtpUser = civic_config('SMTP_USER') ?: getenv('SMTP_USER');
    $smtpPass = civic_config('SMTP_PASS') ?: getenv('SMTP_PASS');
    $smtpPort = intval(civic_config('SMTP_PORT') ?: getenv('SMTP_PORT') ?: 587);

    if (!empty($smtpUser) && !empty($smtpPass) && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            $mail->SMTPSecure = ($smtpPort == 465) ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $smtpPort;

            $mail->setFrom($smtpUser, $brevoSenderName);
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlContent;

            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log("PHPMailer SMTP Error: " . $e->getMessage());
        }
    }

    // 3. Engine 3: Resend API (Fallback)
    $resendKey = civic_config('RESEND_API_KEY') ?: getenv('RESEND_API_KEY');
    if (!empty($resendKey) && class_exists('Resend')) {
        try {
            $resend = Resend::client($resendKey);
            $resend->emails->send([
                'from' => 'CivicConnect <onboarding@resend.dev>',
                'to' => [$toEmail],
                'subject' => $subject,
                'html' => $htmlContent
            ]);
            return true;
        } catch (\Exception $e) {
            error_log("Resend API Error: " . $e->getMessage());
        }
    }

    return false;
}
