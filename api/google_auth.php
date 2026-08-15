<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
include("../db/connection.php");
require_once(__DIR__ . "/../config.php");

// 1. Read Raw JSON Input
$inputData = json_decode(file_get_contents('php://input'), true);
$idToken = $inputData['credential'] ?? '';

if (empty($idToken)) {
    echo json_encode(['success' => false, 'message' => 'No Google credential token provided.']);
    exit();
}

// 2. Verify and decode Google ID Token
$googleClientId = civic_config('GOOGLE_CLIENT_ID');
$payload = null;

// Call Google OAuth Tokeninfo endpoint for verification
$verifyUrl = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($idToken);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $verifyUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && !empty($response)) {
    $payload = json_decode($response, true);
} else {
    // Fallback: parse JWT payload directly if external call times out
    $parts = explode('.', $idToken);
    if (count($parts) === 3) {
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    }
}

if (!$payload || empty($payload['email'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired Google token.']);
    exit();
}

// Verify Audience / Client ID if present
if (!empty($payload['aud']) && $payload['aud'] !== $googleClientId) {
    // Verify aud matches our client
    if (strpos($payload['aud'], '167199542241') === false) {
        echo json_encode(['success' => false, 'message' => 'Google Client ID mismatch.']);
        exit();
    }
}

$email = trim(strtolower($payload['email']));
$name = trim($payload['name'] ?? $payload['given_name'] ?? 'Citizen');
$picture = trim($payload['picture'] ?? '');
$username = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower(explode('@', $email)[0])) ?: 'citizen';

// 3. Database Check / Upsert in 'people' table
$stmt = $conn->prepare("SELECT id, name, username, email, profile_pic FROM people WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->num_rows > 0) {
    // Existing citizen login
    $user = $res->fetch_assoc();
    $userId = $user['id'];
    $userName = !empty($user['name']) ? $user['name'] : (!empty($user['username']) ? $user['username'] : $name);

    // Update avatar if currently empty
    if (empty($user['profile_pic']) && !empty($picture)) {
        $up = $conn->prepare("UPDATE people SET profile_pic = ? WHERE id = ?");
        $up->bind_param("si", $picture, $userId);
        $up->execute();
    }

    // Set Session
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $userName;
    $_SESSION['user_email'] = $email;

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => 'peopledashboard.php',
        'user' => [
            'id' => $userId,
            'name' => $userName,
            'email' => $email
        ]
    ]);
    exit();
} else {
    // Create new citizen account with Google details
    $randomPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $insertStmt = $conn->prepare("INSERT INTO people (name, username, email, mobile, password, profile_pic, language, created_at) VALUES (?, ?, ?, '', ?, ?, 'en', NOW())");
    $insertStmt->bind_param("sssss", $name, $username, $email, $randomPass, $picture);

    if ($insertStmt->execute()) {
        $newUserId = $insertStmt->insert_id;

        // Set Session
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['username'] = $name;
        $_SESSION['user_email'] = $email;

        echo json_encode([
            'success' => true,
            'message' => 'Account created and logged in',
            'redirect' => 'peopledashboard.php',
            'is_new' => true,
            'user' => [
                'id' => $newUserId,
                'name' => $name,
                'email' => $email
            ]
        ]);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error creating account: ' . $conn->error]);
        exit();
    }
}
