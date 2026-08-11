<?php
header('Content-Type: application/json');

// Ensure upload directory exists
$uploadDir = __DIR__ . "/../uploads/temp/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 1. Check if image file was uploaded
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'error' => 'No image uploaded or upload error occurred.'
    ]);
    exit();
}

$tmpPath = $_FILES['photo']['tmp_name'];
$fileName = basename($_FILES['photo']['name']);
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Allowed image formats
$allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
if (!in_array($fileExt, $allowedExts)) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid image format. Supported: JPG, PNG, WEBP.'
    ]);
    exit();
}

// 2. Google AI Cloud Vision Engine (PRIMARY RECOMMENDED ENGINE)
function analyzeWithGemini($tmpPath, $apiKey, $fileExt) {
    if (empty($apiKey)) return null;

    $imageData = base64_encode(file_get_contents($tmpPath));
    $mimeType = 'image/jpeg';
    if ($fileExt === 'png') $mimeType = 'image/png';
    elseif ($fileExt === 'webp') $mimeType = 'image/webp';

    $models = ['gemma-4-31b-it', 'gemini-2.0-flash', 'gemini-1.5-flash'];
    
    $prompt = "You are a Municipal Vision AI Engine. Analyze this civic complaint photo.
    Return ONLY a raw JSON object with these exact keys:
    - 'category': Choose ONE from ['Roads & Potholes', 'Sanitation & Garbage', 'Streetlight Failure', 'Drainage & Water Leakage', 'Public Safety & Infrastructure'].
    - 'description': A formal 1-2 sentence description of the civic problem in the photo.
    - 'severity': Choose ONE from ['Low', 'Medium', 'High', 'Critical'].";

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt],
                    ["inline_data" => ["mime_type" => $mimeType, "data" => $imageData]]
                ]
            ]
        ],
        "generationConfig" => [
            "response_mime_type" => "application/json",
            "temperature" => 0.1
        ]
    ];

    foreach ($models as $m) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$m}:generateContent?key=" . $apiKey;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
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
                    if (isset($p['thought']) && $p['thought'] === true) {
                        continue;
                    }
                    if (isset($p['text'])) {
                        $rawText = trim($p['text']);
                        $rawText = preg_replace('/^```json\s*/i', '', $rawText);
                        $rawText = preg_replace('/^```\s*/i', '', $rawText);
                        $rawText = preg_replace('/\s*```$/i', '', $rawText);
                        
                        if (preg_match('/\{[\s\S]*\}/', $rawText, $matches)) {
                            $aiData = json_decode($matches[0], true);
                            if (is_array($aiData) && isset($aiData['category'])) {
                                return [
                                    'success' => true,
                                    'category' => $aiData['category'],
                                    'description' => $aiData['description'] ?? 'Civic issue detected from photo.',
                                    'severity' => $aiData['severity'] ?? 'High',
                                    'source' => 'Google Cloud Vision AI ⭐ (' . $m . ')'
                                ];
                            }
                        }
                    }
                }
            }
        }
    }
    return null;
}

// 3. Direct XAMPP Python Exec Execution (Local Trained ML Backup)
function analyzeWithDirectPythonXAMPP($tmpPath, $fileName = "") {
    $scriptPath = escapeshellarg(__DIR__ . "/../python_ai/ai_cli.py");
    $filePath = escapeshellarg($tmpPath);
    $origArg = escapeshellarg($fileName);
    
    $cmd = "python $scriptPath $filePath $origArg 2>&1";
    $output = shell_exec($cmd);
    
    if ($output) {
        $result = json_decode(trim($output), true);
        if (is_array($result) && isset($result['success']) && $result['success']) {
            return $result;
        }
    }
    return null;
}

// EXECUTION HIERARCHY:
// 1. Google Cloud Vision AI (Primary) -> 2. Local Trained ML Model -> 3. XAMPP Backup Engine
$geminiApiKey = getenv('GEMINI_API_KEY') ?: getenv('GOOGLE_API_KEY');
$aiResult = null;

if (!empty($geminiApiKey)) {
    $aiResult = analyzeWithGemini($tmpPath, $geminiApiKey, $fileExt);
}

if (!$aiResult) {
    $aiResult = analyzeWithDirectPythonXAMPP($tmpPath, $fileName);
}

echo json_encode($aiResult, JSON_UNESCAPED_UNICODE);
