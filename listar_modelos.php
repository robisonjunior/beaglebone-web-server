<?php
// /var/www/html/listar_modelos.php

require_once __DIR__ . '/config.php';

$apiKey = GEMINI_API_KEY;
$url = "https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$json = json_decode($response, true);

echo "=== MODELOS DISPONÍVEIS NA SUA CHAVE API ===\n\n";

if (isset($json['models'])) {
    foreach ($json['models'] as $m) {
        // Exibe apenas modelos que suportam generateContent
        if (in_array("generateContent", $m['supportedGenerationMethods'] ?? [])) {
            echo "- " . str_replace("models/", "", $m['name']) . "\n";
        }
    }
} else {
    echo "Erro ao buscar modelos:\n";
    print_r($json);
}
?>