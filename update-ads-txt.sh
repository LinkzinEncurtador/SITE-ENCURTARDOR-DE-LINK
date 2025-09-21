#!/bin/bash

# Script para atualização automática do ads.txt da Ezoic
# Execute este script diariamente via cron job

# Configurações
DOMAIN="linkzinencurtador.online"
EZOIC_URL="https://srv.adstxtmanager.com/19390/${DOMAIN}"
ADS_FILE="ads.txt"
BACKUP_DIR="backups"
LOG_FILE="ads-update.log"

# Criar diretório de backup se não existir
mkdir -p "$BACKUP_DIR"

# Fazer backup do arquivo atual
if [ -f "$ADS_FILE" ]; then
    cp "$ADS_FILE" "$BACKUP_DIR/ads-$(date +%Y%m%d-%H%M%S).txt"
    echo "$(date): Backup criado" >> "$LOG_FILE"
fi

# Baixar novo ads.txt da Ezoic
echo "$(date): Iniciando atualização do ads.txt" >> "$LOG_FILE"

if curl -L -s "$EZOIC_URL" > "$ADS_FILE.tmp"; then
    # Verificar se o download foi bem-sucedido
    if [ -s "$ADS_FILE.tmp" ]; then
        mv "$ADS_FILE.tmp" "$ADS_FILE"
        echo "$(date): ads.txt atualizado com sucesso" >> "$LOG_FILE"
        
        # Verificar se o arquivo contém conteúdo válido
        if grep -q "google.com" "$ADS_FILE"; then
            echo "$(date): Conteúdo do ads.txt verificado" >> "$LOG_FILE"
        else
            echo "$(date): AVISO: Conteúdo do ads.txt pode estar incorreto" >> "$LOG_FILE"
        fi
    else
        echo "$(date): ERRO: Arquivo baixado está vazio" >> "$LOG_FILE"
        rm -f "$ADS_FILE.tmp"
    fi
else
    echo "$(date): ERRO: Falha ao baixar ads.txt da Ezoic" >> "$LOG_FILE"
    rm -f "$ADS_FILE.tmp"
fi

# Limpar backups antigos (manter apenas últimos 7 dias)
find "$BACKUP_DIR" -name "ads-*.txt" -mtime +7 -delete

echo "$(date): Script de atualização concluído" >> "$LOG_FILE"
