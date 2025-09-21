# Script PowerShell para atualização automática do ads.txt da Ezoic
# Execute este script diariamente via Task Scheduler

# Configurações
$Domain = "linkzinencurtador.online"
$EzoicUrl = "https://srv.adstxtmanager.com/19390/$Domain"
$AdsFile = "ads.txt"
$BackupDir = "backups"
$LogFile = "ads-update.log"

# Criar diretório de backup se não existir
if (!(Test-Path $BackupDir)) {
    New-Item -ItemType Directory -Path $BackupDir | Out-Null
}

# Fazer backup do arquivo atual
if (Test-Path $AdsFile) {
    $BackupName = "ads-$(Get-Date -Format 'yyyyMMdd-HHmmss').txt"
    Copy-Item $AdsFile "$BackupDir\$BackupName"
    Add-Content $LogFile "$(Get-Date): Backup criado"
}

# Baixar novo ads.txt da Ezoic
Add-Content $LogFile "$(Get-Date): Iniciando atualização do ads.txt"

try {
    $Response = Invoke-WebRequest -Uri $EzoicUrl -UseBasicParsing
    
    if ($Response.StatusCode -eq 200 -and $Response.Content.Length -gt 0) {
        $Response.Content | Out-File -FilePath $AdsFile -Encoding UTF8
        Add-Content $LogFile "$(Get-Date): ads.txt atualizado com sucesso"
        
        # Verificar se o arquivo contém conteúdo válido
        $Content = Get-Content $AdsFile -Raw
        if ($Content -match "google\.com") {
            Add-Content $LogFile "$(Get-Date): Conteúdo do ads.txt verificado"
        } else {
            Add-Content $LogFile "$(Get-Date): AVISO: Conteúdo do ads.txt pode estar incorreto"
        }
    } else {
        Add-Content $LogFile "$(Get-Date): ERRO: Resposta inválida da Ezoic"
    }
} catch {
    Add-Content $LogFile "$(Get-Date): ERRO: Falha ao baixar ads.txt da Ezoic - $($_.Exception.Message)"
}

# Limpar backups antigos (manter apenas últimos 7 dias)
$CutoffDate = (Get-Date).AddDays(-7)
Get-ChildItem $BackupDir -Filter "ads-*.txt" | Where-Object { $_.LastWriteTime -lt $CutoffDate } | Remove-Item

Add-Content $LogFile "$(Get-Date): Script de atualização concluído"
