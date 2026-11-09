<?php
// /var/www/html/gemini_api.php

require_once __DIR__ . '/config.php';

function requisitarApi($model, $prompt, $systemInstruction) {
    $apiKey = GEMINI_API_KEY;
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

    $data = [
        "contents" => [
            ["parts" => [["text" => $prompt]]]
        ]
    ];

    if ($systemInstruction) {
        $data["systemInstruction"] = [
            "parts" => [["text" => $systemInstruction]]
        ];
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Evita falha por certificados locais ausentes

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return ['code' => 0, 'response' => "Erro cURL: " . $curlErr];
    }

    return ['code' => $httpCode, 'response' => $response];
}

function consultarGemini($prompt, $systemInstruction = null) {
    $res = requisitarApi(GEMINI_MODEL_PRIMARY, $prompt, $systemInstruction);

    if ($res['code'] === 0 || $res['code'] === 503 || $res['code'] === 404) {
        $res = requisitarApi(GEMINI_MODEL_FALLBACK, $prompt, $systemInstruction);
    }

    if ($res['code'] !== 200) {
        return "Erro (Código {$res['code']}): " . $res['response'];
    }

    $json = json_decode($res['response'], true);
    if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
        return $json['candidates'][0]['content']['parts'][0]['text'];
    }

    return "Não foi possível extrair a resposta do Gemini.";
}
?>