# BeagleBone Black - Web Server & ESP32 Data Collector

![Arquitetura do Projeto BeagleBone](./image_dbabb58a.jpg)


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

# 🚀 BeagleBone Black - ESP32 IoT Web Server & Data Monitoring

[![Debian 12](https://img.shields.io/badge/OS-Debian%2012%20Bookworm-blue.svg)](https://www.debian.org/)
[![Apache Server](https://img.shields.io/badge/Web%20Server-Apache%202.4-red.svg)](https://httpd.apache.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.2-purple.svg)](https://www.php.net/)
[![Cloudflare](https://img.shields.io/badge/Tunnel-Cloudflare-orange.svg)](https://www.cloudflare.com/)
[![Hardware](https://img.shields.io/badge/Hardware-BeagleBone%20Black%20Rev%20C-orange.svg)](https://beagleboard.org/black)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Servidor de aplicação embarcado desenvolvido para rodar na **BeagleBone Black (Rev C)**. A plataforma atua como hub central de ingestão de dados HTTP POST/REST transmitidos por nós sensores baseados em microcontroladores **ESP32**, fornecendo processamento em tempo real, persistência local e dashboards de monitoramento.

O acesso ao servidor é protegido e exposto para a internet através do **Cloudflare Tunnel (cloudflared)**, dispensando a abertura de portas no roteador (`port forwarding`), garantindo criptografia TLS/HTTPS fim a fim e proteção contra ataques DDoS.

---

## 📐 Arquitetura do Sistema e Conectividade

```text
                                     [ INTERNET / ACESSO EXTERNO ]
                                                   |
                                                   v
                                       [ Cloudflare Edge Network ]
                                                   |
                                       (Cloudflare Tunnel / HTTPS)
                                                   |
                                                   v
[ ESP32 Node 1 ] --- (HTTP POST Local) ---> | [ BeagleBone Black ]
[ ESP32 Node 2 ] --- (HTTP POST Local) ---> |   - IP Local: 192.168.10.173
[ ESP32 Node N ] --- (HTTP POST Local) ---> |   - Service: cloudflared
                                            |   - Path: /var/www/html
                                                   |
                                                   +---> [ Apache 2.4 + PHP 8.2 ]
                                                   |
                                                   +---> [ GitHub Remote Mirror ]

        🛠️ Stack Tecnológica & Especificações
Hardware & Rede
Placa: BeagleBone Black Rev C (Processor AM3358 ARM Cortex-A8)

Armazenamento: Cartão MicroSD Class 10 (16 GB)

Rede Local: Endereço IP Fixo reservado via DHCP (192.168.10.173)

Acesso Remoto Seguro: Cloudflare Tunnel (cloudflared) integrado ao domínio próprio

Software & Ambientes
Sistema Operacional: Debian 12 (Bookworm) - Kernel Linux 6.12

Servidor Web: Apache HTTP Server (apache2)

Linguagem Server-Side: PHP 8.2

Ambiente de Desenvolvimento: Visual Studio Code via extensão SSH FS (SFTP sobre SSH)

Controle de Versão: Git integrado autenticado via Personal Access Token (PAT)

🌐 Formas de Acesso ao Servidor
1. Acesso Local (Rede Interna)
Dashboard Web: http://192.168.10.173/index.php

SSH / Desenvolvimento: ssh junior@192.168.10.173

Diretório Web: /var/www/html

2. Acesso Externo Seguro (Cloudflare Tunnel)
A aplicação está mapeada com suporte a HTTPS automático provido pela rede da Cloudflare:

Dashboard Público: https://www.beagle.corechip.com.br/index.php

Endpoint ESP32 Público: https://www.beagle.corechip.com.br/inserir_esp32.php

Nota de Infraestrutura: O daemon do Cloudflare (cloudflared) roda como serviço no Debian 12, estabelecendo conexões de saída criptografadas com os datacenters da Cloudflare. Não há exposição direta de endereços IP públicos locais.

📁 Estrutura de Arquivos e Diretórios
O diretório raiz da aplicação está localizado em

/var/www/html/
├── acoes.php           # Funções auxiliares, regras de negócio e controle
├── dados_esp32.php     # Endpoint / Leitura e processamento dos dados do ESP32
├── index.php           # Dashboard web principal de monitoramento e métricas
├── inserir_esp32.php   # Script de validação e ingestão de requisições HTTP POST
├── .gitignore          # Arquivos e diretórios ignorados pelo versionamento Git
└── README.md           # Documentação técnica completa do projeto

🔌 Rotas e Ingestão de Dados (ESP32)
Endpoint de Ingestão
URL (Local): http://192.168.10.173/inserir_esp32.php

URL (Cloudflare): https://www.beagle.corechip.com.br/inserir_esp32.php

Método HTTP: POST

Content-Type: application/x-www-form-urlencoded ou application/json

sensor_id  : esp32_node_01
temp       : 25.4
hum        : 62.1
status     : OK

🛡️ Permissões e Ciclo de Versionamento (Git)
Ajuste de Permissões no Linux
Para garantir que o usuário junior consiga editar arquivos via SSH FS e executar comandos do Git sem afetar as permissões de leitura/execução do Apache (www-data):

sudo chown -R junior:www-data /var/www/html
sudo chmod -R 775 /var/www/html

Comandos de Versionamento Diário
Sempre que alterar o código-fonte na BeagleBone, execute no terminal integrado:

cd /var/www/html
git add .
git commit -m "feat: atualiza documentação e rotas do sistema"
git push origin main

💾 Plano de Recuperação de Desastres (Disaster Recovery)
A infraestrutura possui redundância completa dividida em duas camadas para rápida recuperação em caso de falha de hardware ou corrupção do cartão de memória:

1. Camada de Aplicação (Código-Fonte no GitHub)
Todo o repositório /var/www/html é mantido sincronizado no GitHub. Para restaurar o código em uma nova instalação limpa do Debian:

sudo apt update && sudo apt install -y apache2 php libapache2-mod-php git
sudo chown -R junior:www-data /var/www/html && sudo chmod -R 775 /var/www/html
cd /var/www/html
git clone [https://github.com/robisonjunior/beaglebone-web-server.git](https://github.com/robisonjunior/beaglebone-web-server.git) .

2. Camada de Sistema Operacional & Infraestrutura (Imagem Win32 Disk Imager)
O cartão MicroSD possui um backup completo setor-a-setor (.img) contendo o Debian 12, servidor Apache2, PHP 8.2, daemon do Cloudflare Tunnel, chaves de acesso e permissões configuradas.

sudo poweroff

Backup (Read): Leitura da partição BOOT e geração do arquivo .img pelo Win32 Disk Imager no Windows.

Restauração (Write): Gravação direta da imagem .img em um novo cartão MicroSD em minutos.

## 🤖 Integração de Inteligência Artificial Embarcada (Google Gemini API)

O servidor conta com um **Agente Inteligente de Diagnóstico e Telemetria** integrado nativamente em PHP via requisições HTTP REST (cURL) para a API do Google AI Studio. 

A arquitetura permite que a BeagleBone Black não apenas receba e armazene leituras, mas analise o comportamento da rede de sensores e do próprio servidor em tempo real.

### **Arquitetura do Agente PHP + Gemini**
- **Camada de Credenciais Seguras (`config.php`):** Armazena a API Key e as definições do modelo fora do controle de versão Git (`.gitignore`).
- **Mecanismo de Resiliência e Fallback (`gemini_api.php`):** Tratamento automático de erros `503 Service Unavailable` e `404 Not Found`. Caso o modelo primário enfrente picos de demanda ou indisponibilidade, o sistema redireciona automaticamente a requisição para um modelo secundário de reserva (fallback).
- **Injeção Dinâmica de Contexto (`api_gemini.php`):** Antes de enviar a consulta para a IA, o script PHP consulta os últimos 20 registros da tabela `leituras_esp32` no MariaDB e injeta os dados reais de temperatura, umidade e carimbos de data/hora no *System Prompt*.
- **Interface Visual Interativa (`index.php`):** Módulo de chat integrado ao dashboard web em tema escuro (`gemini_agent --embedded_ai`) utilizando Fetch API assíncrona.

---

## 💻 Modelos de IA Configurados

- **Modelo Primário:** `gemini-2.0-flash`
- **Modelo de Reserva (Fallback):** `gemini-3.5-flash-lite`
- **Ferramenta de Diagnóstico de API (`listar_modelos.php`):** Script utilitário em PHP para verificar dinamicamente quais modelos da Google AI Studio possuem suporte ativo à função `generateContent` na chave cadastrada.

---

## 📊 Capacidades de Análise do Agente

Através da injeção de contexto do banco de dados local, o agente embarcado é capaz de:
1. **Análise Estatística sob Demanda:** Cálculo de médias de temperatura e umidade, identificação de máximas/mínimas e detecção de variações bruscas.
2. **Diagnóstico de Anomalias:** Avaliação de estabilidade dos nós ESP32 e verificação do status dos serviços da BeagleBone.
3. **Geração de Relatórios Técnicos (`gerar_relatorio.php`):** Automação em PHP para consolidação do histórico de telemetria em formato Markdown (`.md`), com agendamento diário via `cron` no Debian 12.

---

## 📁 Estrutura de Arquivos da Integração Gemini

```text
/var/www/html/
├── config.php          # Credenciais e definição dos modelos primário/fallback (ignorado pelo Git)
├── gemini_api.php      # Motor cURL de comunicação com a API do Gemini com fallback automático
├── api_gemini.php      # Endpoint JSON que coleta dados do MariaDB e constrói o prompt com contexto
├── listar_modelos.php  # Utilitário CLI/Web para checagem dos modelos disponíveis na chave API
├── teste_gemini.php   # Script CLI para teste de conectividade no terminal da BeagleBone
└── gerar_relatorio.php # Script de automação para geração e arquivamento de relatórios diários

👨‍💻 Autor e Desenvolvimento
Desenvolvido por Robison Lopes Junior.

Especialista em engenharia de sistemas embarcados, desenvolvimento de hardware microcontrolado, arquitetura IoT e redes de sensores.

---

### Passo a passo para atualizar na BeagleBone:

1. No **VS Code** (conectado à BeagleBone via SSH FS), abra o arquivo `README.md` na pasta `/var/www/html`.
2. Substitua todo o conteúdo pelo texto acima e salve (`Ctrl + S`).
3. No terminal da BeagleBone no VS Code, execute:

```bash
git add README.md
git commit -m "docs: atualiza README completo com instrucoes do Cloudflare Tunnel"
git push

