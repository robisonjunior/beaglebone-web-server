# BeagleBone Black - Web Server & ESP32 Data Collector

Este repositório contém o servidor web (Apache + PHP) hospedado na BeagleBone Black (Debian 12) responsável por receber, processar e exibir dados enviados por nós sensores ESP32.

## 🛠️ Tecnologias Utilizadas
- **Hardware:** BeagleBone Black Rev C
- **OS:** Debian 12 (Bookworm)
- **Web Server:** Apache2
- **Linguagem Backend:** PHP
- **IDE:** VS Code com SSH FS

## 📁 Estrutura de Arquivos
- `index.php`: Painel de controle e monitoramento principal.
- `inserir_esp32.php`: Endpoint para recebimento das requisições HTTP dos ESP32.
- `dados_esp32.php`: Leitura e processamento das medições.

## 🔄 Atualização de Código
Para enviar atualizações da BeagleBone para o GitHub: