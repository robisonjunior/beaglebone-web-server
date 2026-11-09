<?php
// /var/www/html/api_gemini.php
header('Content-Type: application/json');
require_once __DIR__ . '/gemini_api.php';

$input = json_decode(file_get_contents('php://input'), true);
$userPrompt = $input['prompt'] ?? '';

if (empty($userPrompt)) {
    echo json_encode(['status' => 'error', 'message' => 'Prompt vazio.']);
    exit;
}

// 1. Buscar as últimas leituras de telemetria do ESP32 no MariaDB
$conn = @new mysqli('localhost', 'admin', '151805', 'beagle_db');
$esp32DataText = "Sem leituras recentes encontradas no banco de dados.";

if (!$conn->connect_error) {
    $sql = "SELECT dispositivo, temperatura, umidade, data_hora FROM leituras_esp32 ORDER BY id DESC LIMIT 20";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $readings = [];
        while ($row = $result->fetch_assoc()) {
            $readings[] = "Dispositivo: {$row['dispositivo']} | Temp: {$row['temperatura']}°C | Umidade: {$row['umidade']}% | Data/Hora: {$row['data_hora']}";
        }
        $esp32DataText = implode("\n", $readings);
    }
    $conn->close();
}

// 2. Construir o Prompt de Sistema com Contexto Atualizado em Tempo Real
$systemInstruction = "Você é o assistente inteligente da BeagleBone Black. "
    . "Você tem acesso direto aos dados em tempo real da rede de sensores ESP32 gravados no banco MariaDB.\n\n"
    . "=== ÚLTIMAS LEITURAS DO ESP32 (Tabela leituras_esp32) ===\n"
    . $esp32DataText . "\n"
    . "=========================================================\n\n"
    . "Use estritamente os dados acima para responder perguntas sobre médias de temperatura, umidade, tendências, valores mínimos/máximos e status da rede. Responda de forma direta, clara e concisa em português.";

// 3. Consultar a API do Gemini
$resposta = consultarGemini($userPrompt, $systemInstruction);

echo json_encode(['status' => 'success', 'response' => $resposta]);
?>