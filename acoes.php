<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = isset($_POST['acao']) ? $_POST['acao'] : '';

    if ($acao === 'limpar_banco') {
        $conn = @new mysqli('localhost', 'admin', '151805', 'beagle_db');
        
        if ($conn->connect_error) {
            echo json_encode(["status" => "error", "message" => "Erro ao ligar ao MariaDB: " . $conn->connect_error]);
            exit;
        }

        // Limpa e reseta a tabela antiga da BeagleBone
        $conn->query("TRUNCATE TABLE leituras");
        $conn->query("INSERT INTO leituras (sensor, valor) VALUES ('Sistema_Reset', 0.0)");

        // Limpa e reseta a nova tabela do ESP32
        $conn->query("TRUNCATE TABLE leituras_esp32");
        $conn->query("INSERT INTO leituras_esp32 (dispositivo, temperatura, umidade) VALUES ('ESP32_Reset', 0.0, 0.0)");

        $conn->close();
        echo json_encode(["status" => "success", "message" => "Todas as tabelas foram reiniciadas com sucesso!"]);
        exit;
    }
}

echo json_encode(["status" => "error", "message" => "Acao invalida"]);
?>
