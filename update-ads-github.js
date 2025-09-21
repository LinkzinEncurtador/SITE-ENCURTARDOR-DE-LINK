/**
 * Script para atualizar ads.txt com conteúdo da Ezoic
 * Específico para GitHub Pages (sem suporte a .htaccess)
 */

const https = require('https');
const fs = require('fs');

const EZOIC_URL = 'https://srv.adstxtmanager.com/19390/linkzinencurtador.online';
const ADS_FILE = 'ads.txt';
const BACKUP_DIR = 'backups';

// Criar diretório de backup se não existir
if (!fs.existsSync(BACKUP_DIR)) {
    fs.mkdirSync(BACKUP_DIR);
}

// Fazer backup do arquivo atual
if (fs.existsSync(ADS_FILE)) {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const backupFile = `${BACKUP_DIR}/ads-${timestamp}.txt`;
    fs.copyFileSync(ADS_FILE, backupFile);
    console.log(`Backup criado: ${backupFile}`);
}

// Baixar novo conteúdo da Ezoic
console.log('Baixando ads.txt da Ezoic...');

https.get(EZOIC_URL, (response) => {
    let data = '';
    
    response.on('data', (chunk) => {
        data += chunk;
    });
    
    response.on('end', () => {
        if (response.statusCode === 200 && data.length > 0) {
            // Adicionar cabeçalho informativo
            const header = `# Ezoic ads.txt - Atualizado automaticamente em ${new Date().toISOString()}
# Fonte: ${EZOIC_URL}
# Para conteúdo sempre atualizado, acesse: ${EZOIC_URL}

`;
            
            const content = header + data;
            
            // Salvar arquivo
            fs.writeFileSync(ADS_FILE, content);
            console.log('ads.txt atualizado com sucesso!');
            
            // Verificar se contém conteúdo válido
            if (data.includes('google.com') || data.includes('ezoic.com')) {
                console.log('Conteúdo verificado - contém publishers válidos');
            } else {
                console.log('AVISO: Conteúdo pode estar incorreto');
            }
        } else {
            console.error('Erro: Falha ao baixar ads.txt da Ezoic');
        }
    });
    
}).on('error', (error) => {
    console.error('Erro de conexão:', error.message);
});

// Limpar backups antigos (manter apenas últimos 7 dias)
const cutoffDate = new Date();
cutoffDate.setDate(cutoffDate.getDate() - 7);

fs.readdir(BACKUP_DIR, (err, files) => {
    if (err) return;
    
    files.forEach(file => {
        if (file.startsWith('ads-') && file.endsWith('.txt')) {
            const filePath = `${BACKUP_DIR}/${file}`;
            const stats = fs.statSync(filePath);
            
            if (stats.mtime < cutoffDate) {
                fs.unlinkSync(filePath);
                console.log(`Backup antigo removido: ${file}`);
            }
        }
    });
});
