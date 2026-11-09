<?php
// /var/www/html/teste_gemini.php

require_once __DIR__ . '/gemini_api.php';

echo "=== Testando Agente Gemini na BeagleBone Black ===\n";

$prompt = "Olá! Você está rodando como um agente embarcado em um servidor Apache em uma BeagleBone Black. Apresente-se resumidamente em 2 frases.";
$systemPrompt = "Você é um assistente técnico especializado em sistemas embarcados e IoT.";

$resposta = consultarGemini($prompt, $systemPrompt);

echo "Resposta do Gemini:\n\n";
echo $resposta . "\n";
?>