const https = require('https');
const fs = require('fs');
const path = require('path');

/**
 * Script para atualizar a biblioteca anti-adblock do Adcash
 * Este script deve ser executado periodicamente (a cada 5 minutos) via cron
 */

const ADBLOCK_API_URL = 'https://adbpage.com/adblock?v=3';
const LIBRARY_FILE = path.join(__dirname, 'adblock-library.js');
const CACHE_DURATION = 5 * 60 * 1000; // 5 minutos em millisegundos

/**
 * Verifica se o arquivo da biblioteca existe e se ainda é válido
 */
function isLibraryValid() {
    try {
        if (!fs.existsSync(LIBRARY_FILE)) {
            return false;
        }
        
        const stats = fs.statSync(LIBRARY_FILE);
        const now = Date.now();
        const fileAge = now - stats.mtime.getTime();
        
        return fileAge < CACHE_DURATION;
    } catch (error) {
        console.error('Erro ao verificar validade da biblioteca:', error);
        return false;
    }
}

/**
 * Baixa a biblioteca anti-adblock do Adcash
 */
function downloadAdblockLibrary() {
    return new Promise((resolve, reject) => {
        console.log('Baixando biblioteca anti-adblock do Adcash...');
        
        https.get(ADBLOCK_API_URL, (response) => {
            let data = '';
            
            response.on('data', (chunk) => {
                data += chunk;
            });
            
            response.on('end', () => {
                if (response.statusCode === 200) {
                    // Remove as tags <script></script> se existirem
                    let cleanData = data.trim();
                    if (cleanData.startsWith('<script>') && cleanData.endsWith('</script>')) {
                        cleanData = cleanData.slice(8, -9);
                    }
                    
                    resolve(cleanData);
                } else {
                    reject(new Error(`Erro HTTP: ${response.statusCode}`));
                }
            });
        }).on('error', (error) => {
            reject(error);
        });
    });
}

/**
 * Salva a biblioteca no arquivo
 */
function saveLibrary(libraryCode) {
    try {
        fs.writeFileSync(LIBRARY_FILE, libraryCode, 'utf8');
        console.log('Biblioteca anti-adblock salva com sucesso!');
        return true;
    } catch (error) {
        console.error('Erro ao salvar biblioteca:', error);
        return false;
    }
}

/**
 * Função principal
 */
async function updateAdblockLibrary() {
    try {
        // Verifica se a biblioteca atual ainda é válida
        if (isLibraryValid()) {
            console.log('Biblioteca anti-adblock ainda é válida, não é necessário atualizar.');
            return;
        }
        
        // Baixa a nova biblioteca
        const libraryCode = await downloadAdblockLibrary();
        
        // Salva no arquivo
        if (saveLibrary(libraryCode)) {
            console.log('Biblioteca anti-adblock atualizada com sucesso!');
        } else {
            console.error('Falha ao salvar biblioteca anti-adblock');
        }
        
    } catch (error) {
        console.error('Erro ao atualizar biblioteca anti-adblock:', error);
    }
}

// Executa se chamado diretamente
if (require.main === module) {
    updateAdblockLibrary();
}

module.exports = { updateAdblockLibrary, isLibraryValid };
