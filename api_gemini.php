<?php
// /var/www/html/api_gemini.php
header('Content-Type: application/json');
require_once __DIR__ . '/gemini_api.php';

$input = json_decode(file_get_contents('php://input'), true);
$prompt = $input['prompt'] ?? '';

if (empty($prompt)) {
    echo json_encode(['status' => 'error', 'message' => 'Prompt vazio.']);
    exit;
}

$systemPrompt = "Você é o agente inteligente embarcado na BeagleBone Black (Debian 12, Apache, PHP). Responda de forma concisa e técnica sobre monitoramento de dados IoT e estado do servidor.";
$resposta = consultarGemini($prompt, $systemPrompt);

echo json_encode(['status' => 'success', 'response' => $resposta]);
?>