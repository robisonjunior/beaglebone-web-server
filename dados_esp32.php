<?php
header("Content-Type: application/json");

$host = "localhost";
$user = "admin";
$pass = "151805";
$db   = "beagle_db";

$conn = @new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT temperatura, umidade, DATE_FORMAT(data_hora, '%H:%i:%s') as hora FROM leituras_esp32 ORDER BY id DESC LIMIT 20";
$result = $conn->query($sql);

$data = array();
if ($result) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode(array_reverse($data));
$conn->close();
?>
