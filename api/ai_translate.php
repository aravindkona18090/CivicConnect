<?php
header('Content-Type: application/json');

$text = isset($_REQUEST['text']) ? trim($_REQUEST['text']) : '';
$targetLang = isset($_REQUEST['target']) ? trim($_REQUEST['target']) : 'en';

if (empty($text)) {
    echo json_encode([
        'success' => false,
        'error' => 'Empty text provided'
    ]);
    exit();
}

// 1. Python AI Engine Translation Proxy
function translateWithPythonAI($text, $target) {
    $url = "http://127.0.0.1:8000/translate";
    $postData = [
        'text' => $text,
        'target_lang' => $target,
        'source_lang' => 'auto'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 4);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['success']) && $data['success']) {
            return [
                'success' => true,
                'original' => $text,
                'translated' => $data['translated_text'],
                'target_language' => $target,
                'engine' => 'Python Deep Translator API'
            ];
        }
    }
    return null;
}

// 2. Direct Fallback Translation
function translateFallback($text, $target) {
    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=" . urlencode($target) . "&dt=t&q=" . urlencode($text);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        if (isset($result[0]) && is_array($result[0])) {
            $translatedText = '';
            foreach ($result[0] as $segment) {
                if (isset($segment[0])) {
                    $translatedText .= $segment[0];
                }
            }
            if (!empty($translatedText)) {
                return [
                    'success' => true,
                    'original' => $text,
                    'translated' => $translatedText,
                    'target_language' => $target,
                    'engine' => 'Google Translate Fallback'
                ];
            }
        }
    }
    
    return [
        'success' => true,
        'original' => $text,
        'translated' => $text,
        'engine' => 'Original Text'
    ];
}

$res = translateWithPythonAI($text, $targetLang);
if (!$res) {
    $res = translateFallback($text, $targetLang);
}

echo json_encode($res, JSON_UNESCAPED_UNICODE);
