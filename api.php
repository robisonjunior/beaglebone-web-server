<?php
header('Content-Type: application/json');

// Permitir apenas requisições HTTP POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
http_response_code(405);
echo json_encode(["status" => "error", "message" => "Método não permitido. Utilize POST."]);
exit;
}

// Receber dados enviados via JSON Raw ou Form-Data
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);

$sensor = isset($inputData['sensor']) ? trim($inputData['sensor']) : (isset($_POST['sensor']) ? trim($_POST['sensor']) : null);
$valor  = isset($inputData['valor']) ? floatval($inputData['valor']) : (isset($_POST['valor']) ? floatval($_POST['valor']) : null);

if ($sensor !== null && $valor !== null) {
$conn = @new mysqli('localhost', 'admin', '151805', 'beagle_db');
if (!$conn->connect_error) {
    $stmt = $conn->prepare("INSERT INTO leituras (sensor, valor) VALUES (?, ?)");
    $stmt->bind_param("sd", $sensor, $valor);
    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            "status" => "success",
            "message" => "Leitura gravada no MariaDB com sucesso",
            "data" => ["sensor" => $sensor, "valor" => $valor]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Falha ao executar query no MariaDB"]);
    }
    $stmt->close();
    $conn->close();
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro de conexão com o banco de dados"]);
}
} else {
http_response_code(400);
echo json_encode(["status" => "error", "message" => "Parâmetros 'sensor' e 'valor' são obrigatórios."]);
}
?>
