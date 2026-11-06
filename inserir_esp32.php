<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$host = "localhost";
$user = "admin";    // Utilizador configurado no MariaDB
$pass = "151805";   // Palavra-passe do MariaDB
$db   = "beagle_db";

$conn = @new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Erro na ligação: " . $conn->connect_error]);
    exit;
}

// Aceita tanto formulário normal (POST) quanto payload JSON (ESP32)
$inputJSON = file_get_contents('php://input');
$dataJSON  = json_decode($inputJSON, true);

$temp = $_POST['temperatura'] ?? $dataJSON['temperatura'] ?? null;
$umid = $_POST['umidade']     ?? $dataJSON['umidade']     ?? null;
$disp = $_POST['dispositivo'] ?? $dataJSON['dispositivo'] ?? 'ESP32_Bancada';

if ($temp !== null && $umid !== null) {
    $stmt = $conn->prepare("INSERT INTO leituras_esp32 (dispositivo, temperatura, umidade) VALUES (?, ?, ?)");
    $stmt->bind_param("sdd", $disp, $temp, $umid);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Leitura gravada com sucesso"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Erro SQL: " . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Parametros temperatura e umidade ausentes"]);
}

$conn->close();
?>
