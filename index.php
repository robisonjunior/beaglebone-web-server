<?php
if (isset($_GET['api'])) {
    error_reporting(0);
    ini_set('display_errors', 0);
    header('Content-Type: application/json');

    $load = sys_getloadavg();
    $cpu_load = round($load[0] * 100, 1);

    $free_mem = shell_exec("free -m");
    preg_match_all('/(\d+)/', explode("\n", $free_mem)[1], $matches);
    $ram_total = isset($matches[0][0]) ? $matches[0][0] : 1;
    $ram_used  = isset($matches[0][1]) ? $matches[0][1] : 0;
    $ram_percent = round(($ram_used / $ram_total) * 100, 1);

    $disk_total = disk_total_space("/");
    $disk_free  = disk_free_space("/");
    $disk_used  = $disk_total - $disk_free;
    $disk_percent = round(($disk_used / $disk_total) * 100, 1);
    $disk_used_gb = round($disk_used / (1024 * 1024 * 1024), 2);
    $disk_total_gb = round($disk_total / (1024 * 1024 * 1024), 2);

    $uptime_str = shell_exec("uptime -p");
    $uptime_str = trim(str_replace('up ', '', $uptime_str));

    $temp_c = 'N/A';
    $temp_paths = [
        '/sys/class/thermal/thermal_zone0/temp',
        '/sys/class/hwmon/hwmon0/temp1_input',
        '/sys/class/hwmon/hwmon1/temp1_input'
    ];
    foreach ($temp_paths as $path) {
        $val = @file_get_contents($path);
        if ($val !== false && is_numeric(trim($val))) {
            $temp_c = round(trim($val) / 1000, 1);
            break;
        }
    }

    $ps_output = shell_exec("ps -eo pid,user,ni,pcpu,pmem,comm --sort=-pcpu | head -n 11");
    $lines = explode("\n", trim($ps_output));
    $processes = [];
    for ($i = 1; $i < count($lines); $i++) {
        $cols = preg_split('/\s+/', trim($lines[$i]));
        if (count($cols) >= 6) {
            $cpu_val = floatval($cols[3]);
            if ($cpu_val > 100) $cpu_val = round($cpu_val / 10, 1);
            $processes[] = [
                'pid' => $cols[0],
                'user' => $cols[1],
                'nice' => $cols[2],
                'cpu' => number_format($cpu_val, 1),
                'mem' => $cols[4],
                'command' => implode(' ', array_slice($cols, 5))
            ];
        }
    }

    $db_readings = [];
    $esp32_readings = [];
    $db_status = "Desconectado";
    $conn = @new mysqli('localhost', 'admin', '151805', 'beagle_db');

    if (!$conn->connect_error) {
        $db_status = "Conectado";
        
        // Leituras da BeagleBone
        $sql = "SELECT id, sensor, valor, data_registro FROM leituras ORDER BY id DESC LIMIT 10";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $db_readings[] = $row;
            }
        }

        // Leituras da tabela leituras_esp32
        $sql_esp = "SELECT id, dispositivo, temperatura, umidade, data_hora FROM leituras_esp32 ORDER BY id DESC LIMIT 10";
        $result_esp = $conn->query($sql_esp);
        if ($result_esp) {
            while ($row = $result_esp->fetch_assoc()) {
                $esp32_readings[] = $row;
            }
        }

        $conn->close();
    } else {
        $db_status = "Erro de Autenticação";
    }

    echo json_encode([
        'cpu' => $cpu_load,
        'ram_used' => $ram_used,
        'ram_total' => $ram_total,
        'ram_percent' => $ram_percent,
        'disk_used' => $disk_used_gb,
        'disk_total' => $disk_total_gb,
        'disk_percent' => $disk_percent,
        'uptime' => $uptime_str,
        'temp' => $temp_c,
        'processes' => $processes,
        'db_status' => $db_status,
        'db_readings' => array_reverse($db_readings),
        'esp32_readings' => $esp32_readings
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard BeagleBone Black</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    body { background-color: #0f172a; color: #f8fafc; padding: 20px; }
    .container { max-width: 1100px; margin: 0 auto; }
    header { text-align: center; margin-bottom: 25px; padding: 20px; background: #1e293b; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3); }
    header h1 { color: #38bdf8; font-size: 1.8rem; margin-bottom: 8px; }
    header p { color: #94a3b8; font-size: 1rem; }
    .status-badge { display: inline-block; background: #10b981; color: #022c22; font-weight: bold; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; margin-top: 10px; }
    
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 25px; }
    .card { background: #1e293b; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2); }
    .card h3 { color: #94a3b8; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 0.5px; }
    .card .value { font-size: 1.7rem; font-weight: bold; color: #f8fafc; margin-bottom: 10px; }
    .progress-bar { background: #334155; height: 10px; border-radius: 5px; overflow: hidden; }
    .progress-fill { background: #38bdf8; height: 100%; width: 0%; transition: width 0.5s ease-in-out; }
    
    .section-card { background: #090d16; border: 1px solid #1e293b; border-radius: 12px; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.5); margin-bottom: 25px; font-family: 'Courier New', Courier, monospace; }
    .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #1e293b; padding-bottom: 10px; }
    .section-title { color: #4ade80; font-size: 1rem; font-weight: bold; display: flex; align-items: center; gap: 8px; }
    .section-title::before { content: '>'; color: #38bdf8; }
    
    table.custom-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; color: #cbd5e1; }
    table.custom-table th { text-align: left; padding: 8px; color: #64748b; border-bottom: 1px solid #334155; text-transform: uppercase; }
    table.custom-table td { padding: 6px 8px; border-bottom: 1px solid #0f172a; }
    table.custom-table tr:hover { background: #1e293b; }
    .badge-nice { background: #334155; color: #38bdf8; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; }
    .badge-db { background: #065f46; color: #34d399; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; }

    .btn-panel { display: flex; gap: 12px; margin-top: 10px; }
    .btn-action { padding: 8px 16px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 0.85rem; transition: background 0.2s; }
    .btn-danger { background: #ef4444; color: #fff; }
    .btn-danger:hover { background: #dc2626; }
    .btn-primary { background: #38bdf8; color: #0f172a; text-decoration: none; display: inline-block; }
    .btn-primary:hover { background: #0284c7; }

    .chart-container { position: relative; height: 260px; width: 100%; margin-top: 15px; }
    .footer { text-align: center; margin-top: 30px; color: #64748b; font-size: 0.85rem; }

    .esp32-form { margin-bottom: 15px; display: flex; gap: 10px; align-items: center; }
    .esp32-input { padding: 8px; background: #1e293b; border: 1px solid #334155; color: #f8fafc; border-radius: 6px; font-family: sans-serif; }
    .esp32-btn { padding: 8px 16px; background: #10b981; color: #022c22; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
    .esp32-btn:hover { background: #059669; color: #fff; }
</style>
</head>
<body>
<div class="container">
    <header>
        <h1>Servidor BeagleBone Ativo com Sucesso!</h1>
        <p>Monitorização e Gestão de Processos | MariaDB 10.11</p>
        <span class="status-badge">● ONLINE — 192.168.10.173</span>
    </header>

    <div class="grid">
        <div class="card">
            <h3>Carga da CPU</h3>
            <div class="value" id="cpu-val">0%</div>
            <div class="progress-bar"><div class="progress-fill" id="cpu-bar"></div></div>
        </div>
        <div class="card">
            <h3>Memória RAM</h3>
            <div class="value" id="ram-val">0 MB</div>
            <div class="progress-bar"><div class="progress-fill" id="ram-bar"></div></div>
        </div>
        <div class="card">
            <h3>Armazenamento</h3>
            <div class="value" id="disk-val">0 GB</div>
            <div class="progress-bar"><div class="progress-fill" id="disk-bar"></div></div>
        </div>
        <div class="card">
            <h3>Temperatura</h3>
            <div class="value" id="temp-val">-- °C</div>
        </div>
    </div>

    <!-- Painel de Controlo / Ações Rápidas -->
    <div class="section-card">
        <div class="section-header">
            <div class="section-title" style="color: #ec4899;">control_panel --server_actions</div>
        </div>
        <div class="btn-panel">
            <a href="/inserir.php" class="btn-action btn-primary">+ Inserir Leitura Manual</a>
            <button onclick="executarAcao('limpar_banco')" class="btn-action btn-danger">Resetar Dados MariaDB</button>
        </div>
    </div>

    <!-- Gráfico BeagleBone -->
    <div class="section-card">
        <div class="section-header">
            <div class="section-title" style="color: #38bdf8;">realtime_telemetry_chart --MariaDB</div>
        </div>
        <div class="chart-container">
            <canvas id="telemetryChart"></canvas>
        </div>
    </div>

    <!-- Tabela BeagleBone -->
    <div class="section-card">
        <div class="section-header">
            <div class="section-title" style="color: #facc15;">mariadb_feed --beagle_db.leituras</div>
            <div id="db-status-badge"><span class="badge-db">A ligar...</span></div>
        </div>
        <table class="custom-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Sensor / Dispositivo</th>
                    <th>Valor Medido</th>
                    <th>Data / Hora do Registo</th>
                </tr>
            </thead>
            <tbody id="db-readings-list">
                <tr><td colspan="4" style="text-align: center; color: #64748b;">A carregar dados do MariaDB...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Gráfico ESP32 -->
    <div class="section-card">
        <div class="section-header">
            <div class="section-title" style="color: #a855f7;">esp32_telemetry --leituras_esp32</div>
        </div>
        
        <form id="formManualESP32" class="esp32-form">
            <input type="number" step="0.1" id="tempInput" placeholder="Temp (°C)" required class="esp32-input">
            <input type="number" step="0.1" id="umidInput" placeholder="Umidade (%)" required class="esp32-input">
            <button type="submit" class="esp32-btn">+ Inserir ESP32 Manual</button>
        </form>

        <div class="chart-container">
            <canvas id="graficoESP32"></canvas>
        </div>
    </div>

    <!-- TABELA DO ESP32 (TEMPERATURA & UMIDADE) -->
    <div class="section-card">
        <div class="section-header">
            <div class="section-title" style="color: #38bdf8;">mariadb_feed --beagle_db.leituras_esp32</div>
        </div>
        <table class="custom-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Dispositivo</th>
                    <th>Temperatura (°C)</th>
                    <th>Umidade (%)</th>
                    <th>Data / Hora do Registo</th>
                </tr>
            </thead>
            <tbody id="esp32-readings-list">
                <tr><td colspan="5" style="text-align: center; color: #64748b;">A carregar dados do ESP32...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Terminal Shell -->
    <div class="section-card">
        <div class="section-header">
            <div class="section-title">task_monitor --live (top 10 processes)</div>
            <div style="color: #64748b; font-size: 0.8rem;" id="uptime-val">Uptime: --</div>
        </div>
        <table class="custom-table">
            <thead>
                <tr>
                    <th>PID</th>
                    <th>Usuário</th>
                    <th>PRI (Nice)</th>
                    <th>%CPU</th>
                    <th>%MEM</th>
                    <th>Comando / Tarefa</th>
                </tr>
            </thead>
            <tbody id="process-list">
                <tr><td colspan="6" style="text-align: center; color: #64748b;">A carregar tabela de processos...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="footer">
        Pilha LAMP | Apache2 & PHP 8.2 & MariaDB 10.11
    </div>
</div>

<script>
    // 1. Gráfico BeagleBone
    const ctx = document.getElementById('telemetryChart').getContext('2d');
    const telemetryChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Valor Medido',
                data: [],
                borderColor: '#38bdf8',
                backgroundColor: 'rgba(56, 189, 248, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#facc15',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { ticks: { color: '#64748b' }, grid: { color: '#1e293b' } },
                y: { ticks: { color: '#64748b' }, grid: { color: '#1e293b' } }
            },
            plugins: { legend: { labels: { color: '#94a3b8' } } }
        }
    });

    // 2. Gráfico ESP32
    const ctxESP32 = document.getElementById('graficoESP32').getContext('2d');
    const chartESP32 = new Chart(ctxESP32, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Temperatura (°C)',
                    borderColor: '#f43f5e',
                    backgroundColor: 'rgba(244, 63, 94, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    data: [],
                    yAxisID: 'y'
                },
                {
                    label: 'Umidade (%)',
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    data: [],
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { ticks: { color: '#64748b' }, grid: { color: '#1e293b' } },
                y: { type: 'linear', display: true, position: 'left', ticks: { color: '#f43f5e' }, grid: { color: '#1e293b' }, title: { display: true, text: 'Temp °C', color: '#f43f5e' } },
                y1: { type: 'linear', display: true, position: 'right', ticks: { color: '#3b82f6' }, grid: { drawOnChartArea: false }, title: { display: true, text: 'Umidade %', color: '#3b82f6' } }
            },
            plugins: { legend: { labels: { color: '#94a3b8' } } }
        }
    });

    // 3. Atualização das Métricas e Tabelas
    async function updateMetrics() {
        try {
            const response = await fetch('/index.php?api=1');
            const data = await response.json();

            document.getElementById('cpu-val').innerText = data.cpu + '%';
            document.getElementById('cpu-bar').style.width = Math.min(data.cpu, 100) + '%';

            document.getElementById('ram-val').innerText = data.ram_used + ' / ' + data.ram_total + ' MB';
            document.getElementById('ram-bar').style.width = data.ram_percent + '%';

            document.getElementById('disk-val').innerText = data.disk_used + ' / ' + data.disk_total + ' GB';
            document.getElementById('disk-bar').style.width = data.disk_percent + '%';

            document.getElementById('temp-val').innerText = data.temp !== 'N/A' ? data.temp + ' °C' : 'N/A';
            document.getElementById('uptime-val').innerText = 'Uptime: ' + data.uptime;

            document.getElementById('db-status-badge').innerHTML = `<span class="badge-db">${data.db_status}</span>`;
            
            // Tabela BeagleBone
            const dbTbody = document.getElementById('db-readings-list');
            let dbHtml = '';

            if (data.db_readings && data.db_readings.length > 0) {
                const labels = [];
                const chartValues = [];

                [...data.db_readings].reverse().forEach(row => {
                    dbHtml += `
                        <tr>
                            <td style="color: #facc15; font-weight: bold;">#${row.id}</td>
                            <td style="color: #e2e8f0;">${row.sensor}</td>
                            <td style="color: #38bdf8; font-weight: bold;">${row.valor}</td>
                            <td style="color: #94a3b8;">${row.data_registro}</td>
                        </tr>
                    `;
                });

                data.db_readings.forEach(row => {
                    const hora = row.data_registro.split(' ')[1] || row.data_registro;
                    labels.push(`${row.sensor} (${hora})`);
                    chartValues.push(row.valor);
                });

                telemetryChart.data.labels = labels;
                telemetryChart.data.datasets[0].data = chartValues;
                telemetryChart.update();
            } else {
                dbHtml = '<tr><td colspan="4" style="text-align: center; color: #64748b;">Nenhum registo encontrado.</td></tr>';
            }
            dbTbody.innerHTML = dbHtml;

            // Nova Tabela ESP32
            const espTbody = document.getElementById('esp32-readings-list');
            let espHtml = '';
            if (data.esp32_readings && data.esp32_readings.length > 0) {
                data.esp32_readings.forEach(row => {
                    espHtml += `
                        <tr>
                            <td style="color: #facc15; font-weight: bold;">#${row.id}</td>
                            <td style="color: #e2e8f0;">${row.dispositivo}</td>
                            <td style="color: #f43f5e; font-weight: bold;">${row.temperatura} °C</td>
                            <td style="color: #3b82f6; font-weight: bold;">${row.umidade} %</td>
                            <td style="color: #94a3b8;">${row.data_hora}</td>
                        </tr>
                    `;
                });
            } else {
                espHtml = '<tr><td colspan="5" style="text-align: center; color: #64748b;">Nenhum registo do ESP32 encontrado.</td></tr>';
            }
            espTbody.innerHTML = espHtml;

            // Lista de Processos
            const procTbody = document.getElementById('process-list');
            let rowsHtml = '';
            data.processes.forEach(proc => {
                rowsHtml += `
                    <tr>
                        <td style="color: #38bdf8;">${proc.pid}</td>
                        <td>${proc.user}</td>
                        <td><span class="badge-nice">${proc.nice}</span></td>
                        <td style="color: ${parseFloat(proc.cpu) > 10 ? '#f87171' : '#4ade80'}; font-weight: bold;">${proc.cpu}%</td>
                        <td>${proc.mem}%</td>
                        <td style="color: #e2e8f0;">${proc.command}</td>
                    </tr>
                `;
            });
            procTbody.innerHTML = rowsHtml;

        } catch (err) {
            console.error("Erro ao atualizar métricas:", err);
        }
    }

    // 4. Atualiza Gráfico ESP32
    function atualizarGraficoESP32() {
        fetch('dados_esp32.php')
            .then(res => res.json())
            .then(data => {
                chartESP32.data.labels = data.map(item => item.hora);
                chartESP32.data.datasets[0].data = data.map(item => item.temperatura);
                chartESP32.data.datasets[1].data = data.map(item => item.umidade);
                chartESP32.update();
            });
    }

    // Form ESP32
    document.getElementById('formManualESP32').addEventListener('submit', function(e) {
        e.preventDefault();
        const temp = document.getElementById('tempInput').value;
        const umid = document.getElementById('umidInput').value;

        fetch('inserir_esp32.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `temperatura=${temp}&umidade=${umid}&dispositivo=Manual_Web`
        })
        .then(res => res.json())
        .then(res => {
            if(res.status === 'success') {
                document.getElementById('tempInput').value = '';
                document.getElementById('umidInput').value = '';
                atualizarGraficoESP32();
                updateMetrics();
            }
        });
    });

    async function executarAcao(acaoNome) {
        if (confirm("Tem a certeza que deseja executar esta ação?")) {
            const formData = new FormData();
            formData.append('acao', acaoNome);

            const res = await fetch('/acoes.php', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            alert(result.message);
            updateMetrics();
            atualizarGraficoESP32();
        }
    }

    setInterval(updateMetrics, 2000);
    setInterval(atualizarGraficoESP32, 5000);

    updateMetrics();
    atualizarGraficoESP32();
</script>
</body>
</html>
