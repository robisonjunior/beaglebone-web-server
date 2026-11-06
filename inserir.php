<?php
$mensagem = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$sensor = trim($_POST['sensor']);
$valor  = floatval($_POST['valor']);

if (!empty($sensor)) {
    $conn = new mysqli('localhost', 'admin', '151805', 'beagle_db');
    if (!$conn->connect_error) {
        $stmt = $conn->prepare("INSERT INTO leituras (sensor, valor) VALUES (?, ?)");
        $stmt->bind_param("sd", $sensor, $valor);
        if ($stmt->execute()) {
            $mensagem = "<div class='alert success'>Dado inserido com sucesso!</div>";
        } else {
            $mensagem = "<div class='alert error'>Erro ao inserir dado.</div>";
        }
        $stmt->close();
        $conn->close();
    } else {
        $mensagem = "<div class='alert error'>Erro na conexão com o banco de dados.</div>";
    }
}
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Inserir Leitura - MariaDB</title>
<style>
    body { background: #0f172a; color: #f8fafc; font-family: sans-serif; padding: 40px; }
    .box { max-width: 450px; margin: 0 auto; background: #1e293b; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
    h2 { color: #38bdf8; margin-bottom: 20px; font-size: 1.4rem; }
    label { display: block; margin-bottom: 6px; color: #94a3b8; font-size: 0.9rem; }
    input[type="text"], input[type="number"] { width: 100%; padding: 10px; margin-bottom: 18px; border-radius: 6px; border: 1px solid #334155; background: #0f172a; color: #fff; box-sizing: border-box; }
    button { width: 100%; padding: 12px; background: #10b981; border: none; color: #022c22; font-weight: bold; border-radius: 6px; cursor: pointer; font-size: 1rem; }
    button:hover { background: #34d399; }
    .alert { padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; font-weight: bold; }
    .success { background: #065f46; color: #34d399; }
    .error { background: #7f1d1d; color: #f87171; }
    a { display: block; text-align: center; margin-top: 15px; color: #38bdf8; text-decoration: none; font-size: 0.9rem; }
</style>
</head>
<body>
<div class="box">
    <h2>Inserir Nova Leitura</h2>
    <?php echo $mensagem; ?>
    <form method="POST">
        <label for="sensor">Nome do Sensor / Sinal:</label>
        <input type="text" id="sensor" name="sensor" placeholder="Ex: Pressao, Corrente, Bateria" required>

        <label for="valor">Valor Medido:</label>
        <input type="number" step="0.01" id="valor" name="valor" placeholder="Ex: 12.4" required>

        <button type="submit">Gravar no MariaDB</button>
    </form>
    <a href="/">← Voltar ao Dashboard</a>
</div>
</body>
</html>
