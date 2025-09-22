#!/bin/bash

# Script para atualizar a biblioteca anti-adblock do Adcash via cron
# Execute este script a cada 5 minutos para manter a biblioteca atualizada

# Diretório do projeto
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Log file
LOG_FILE="$PROJECT_DIR/adblock-update.log"

# Função para log com timestamp
log_with_timestamp() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Verifica se Node.js está instalado
if ! command -v node &> /dev/null; then
    log_with_timestamp "ERRO: Node.js não está instalado"
    exit 1
fi

# Navega para o diretório do projeto
cd "$PROJECT_DIR"

# Executa o script de atualização
log_with_timestamp "Iniciando atualização da biblioteca anti-adblock..."
node update-adblock-library.js >> "$LOG_FILE" 2>&1

# Verifica se a atualização foi bem-sucedida
if [ $? -eq 0 ]; then
    log_with_timestamp "Atualização concluída com sucesso"
else
    log_with_timestamp "ERRO: Falha na atualização da biblioteca"
fi

log_with_timestamp "---"
