<?php
session_start();
header('Content-Type: application/json');

include("../db/connection.php");

// 1. Parse incoming user message
$inputData = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($inputData['message'] ?? '');
$history = $inputData['history'] ?? [];

if (empty($userMessage)) {
    echo json_encode([
        'success' => false,
        'reply' => 'Please type a message to start chatting with CivicBot!'
    ]);
    exit();
}

// 2. Fetch logged in citizen context if available
$userContext = "";
if (isset($_SESSION['user_id'])) {
    $userId = intval($_SESSION['user_id']);
    $uStmt = $conn->prepare("SELECT name, username, email, mobile, city, area FROM people WHERE id = ?");
    $uStmt->bind_param("i", $userId);
    $uStmt->execute();
    $uData = $uStmt->get_result()->fetch_assoc();
    $uStmt->close();

    if ($uData) {
        $name = $uData['name'] ?: $uData['username'];
        $userContext .= "\n[CURRENT LOGGED-IN CITIZEN CONTEXT: Name: {$name}, Citizen ID: #{$userId}, Area: {$uData['area']}, City: {$uData['city']}]\n";
    }

    // Check recent complaints
    $pQuery = "SELECT id, category, description, status, created_at FROM problems WHERE user_id = '$userId' ORDER BY id DESC LIMIT 5";
    $pRes = mysqli_query($conn, $pQuery);
    if ($pRes && mysqli_num_rows($pRes) > 0) {
        $userContext .= "Recent Complaints by this citizen:\n";
        while ($row = mysqli_fetch_assoc($pRes)) {
            $userContext .= "- Complaint #{$row['id']}: {$row['category']} - Status: {$row['status']} (Reported: {$row['created_at']})\n";
        }
    }
}

// 3. Prepare System Prompt for CivicBot
$systemPrompt = "You are CivicBot, the official 24/7 intelligent municipal AI assistant for the 'CivicConnect' Smart City Platform.
Your purpose is to provide friendly, polite, concise, and highly accurate guidance to citizens, field officers, and city residents.

KEY CAPABILITIES & GUIDELINES:
1. Multilingual Excellence: Always reply in the exact language the user talks in (supports English, Telugu 'తెలుగు', Hindi 'हिंदी', Kannada 'ಕನ್ನಡ', Tamil 'தமிழ்', Marathi, etc.).
2. Reporting Guidance: Explain how to lodge complaints on CivicConnect (select category, upload photo with AI auto-detection, mark GPS location on map).
3. Complaint Categories Supported:
   - Roads & Potholes (broken asphalt, hazardous potholes)
   - Sanitation & Garbage (overflowing dumpsters, uncollected waste)
   - Electricity & Streetlights (dark streets, damaged lamp posts, wire hazards)
   - Drainage & Water Leakage (pipeline leaks, clogged storm drains, sewage overflows)
4. Emergency Municipal Helplines:
   - ⚡ Electricity Board: 1912
   - 🚯 Sanitation & Solid Waste: 1969
   - 💧 Water Supply & Drainage Board: 1916
   - 🚓 Police / Ambulance / Civic Emergency: 112
5. If the citizen asks about their complaints, refer to the logged-in citizen context provided if available.
6. Tone: Empathetic, respectful, professional, with clean markdown formatting and helpful emojis. Keep answers concise (under 3-4 short paragraphs).
$userContext";

// 4. Build Conversation Payload for Gemini API
$apiKey = getenv('GEMINI_API_KEY') ?: getenv('GOOGLE_API_KEY') ?: '';
$models = ['gemini-3.6-flash', 'gemini-3.7-flash', 'gemini-flash-latest', 'gemini-3.5-flash'];

$formattedContents = [];

// Append conversation history
if (is_array($history)) {
    foreach (array_slice($history, -6) as $msg) {
        $role = ($msg['sender'] === 'user') ? 'user' : 'model';
        $text = trim($msg['text'] ?? '');
        if (!empty($text)) {
            $formattedContents[] = [
                "role" => $role,
                "parts" => [["text" => $text]]
            ];
        }
    }
}

// Append current message
$formattedContents[] = [
    "role" => "user",
    "parts" => [["text" => $userMessage]]
];

$payload = [
    "systemInstruction" => [
        "parts" => [["text" => $systemPrompt]]
    ],
    "contents" => $formattedContents,
    "generationConfig" => [
        "temperature" => 0.6,
        "maxOutputTokens" => 700
    ]
];

$aiReply = null;

foreach ($models as $m) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$m}:generateContent?key=" . $apiKey;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        if (isset($result['candidates'][0]['content']['parts'])) {
            $parts = $result['candidates'][0]['content']['parts'];
            for ($i = count($parts) - 1; $i >= 0; $i--) {
                $p = $parts[$i];
                if (isset($p['thought']) && $p['thought'] === true) continue;
                if (isset($p['text']) && !empty(trim($p['text']))) {
                    $aiReply = trim($p['text']);
                    break 2;
                }
            }
        }
    }
}

// Fallback intelligent responder if offline or network issue
if (empty($aiReply)) {
    $lower = strtolower($userMessage);
    if (strpos($lower, 'pothole') !== false || strpos($lower, 'road') !== false) {
        $aiReply = "🛣️ **To report a Pothole or Road Damage:**\n1. Go to your **Citizen Dashboard**.\n2. Upload a photo of the pothole (our AI will automatically verify severity).\n3. Pin the location on the interactive map.\n4. Click **Submit**! A field officer will be assigned immediately.";
    } elseif (strpos($lower, 'garbage') !== false || strpos($lower, 'waste') !== false || strpos($lower, 'trash') !== false) {
        $aiReply = "🚯 **To report Sanitation & Garbage Issues:**\n1. Go to your **Citizen Dashboard**.\n2. Select category **Sanitation & Garbage**.\n3. Take a photo of the uncollected garbage.\n4. Submit the report. You can also call the National Sanitation Helpline at **1969**.";
    } elseif (strpos($lower, 'help') !== false || strpos($lower, 'emergency') !== false || strpos($lower, 'number') !== false) {
        $aiReply = "📞 **Official Emergency Municipal Helplines:**\n- ⚡ **Electricity Board**: `1912`\n- 🚯 **Sanitation & Waste**: `1969`\n- 💧 **Water & Sewage Board**: `1916`\n- 🚓 **Civic Emergency / Police**: `112`";
    } else {
        $aiReply = "👋 Hello! I am **CivicBot**, your 24/7 Smart City Assistant. You can ask me how to report potholes, track your complaint status, or get emergency municipal contact numbers in English, Telugu, Hindi, or Kannada!";
    }
}

echo json_encode([
    'success' => true,
    'reply' => $aiReply,
    'timestamp' => date('H:i')
], JSON_UNESCAPED_UNICODE);
