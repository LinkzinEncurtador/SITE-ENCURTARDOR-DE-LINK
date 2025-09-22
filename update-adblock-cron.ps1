# Script PowerShell para atualizar a biblioteca anti-adblock do Adcash
# Execute este script a cada 5 minutos para manter a biblioteca atualizada

# Diretório do projeto
$ProjectDir = Split-Path -Parent $MyInvocation.MyCommand.Path

# Arquivo de log
$LogFile = Join-Path $ProjectDir "adblock-update.log"

# Função para log com timestamp
function Write-LogWithTimestamp {
    param([string]$Message)
    $Timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $LogEntry = "[$Timestamp] $Message"
    Add-Content -Path $LogFile -Value $LogEntry
}

# Verifica se Node.js está instalado
try {
    $nodeVersion = node --version 2>$null
    if (-not $nodeVersion) {
        Write-LogWithTimestamp "ERRO: Node.js não está instalado"
        exit 1
    }
} catch {
    Write-LogWithTimestamp "ERRO: Node.js não está instalado"
    exit 1
}

# Navega para o diretório do projeto
Set-Location $ProjectDir

# Executa o script de atualização
Write-LogWithTimestamp "Iniciando atualização da biblioteca anti-adblock..."

try {
    $output = node update-adblock-library.js 2>&1
    Write-LogWithTimestamp $output
    
    if ($LASTEXITCODE -eq 0) {
        Write-LogWithTimestamp "Atualização concluída com sucesso"
    } else {
        Write-LogWithTimestamp "ERRO: Falha na atualização da biblioteca"
    }
} catch {
    Write-LogWithTimestamp "ERRO: Exceção durante a atualização - $($_.Exception.Message)"
}

Write-LogWithTimestamp "---"
