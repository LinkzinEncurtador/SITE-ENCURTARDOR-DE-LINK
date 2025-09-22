const fs = require('fs');
const path = require('path');
const { updateAdblockLibrary, isLibraryValid } = require('./update-adblock-library.js');

/**
 * Script de teste para verificar a configuração da biblioteca anti-adblock
 */

console.log('🧪 Testando configuração da biblioteca anti-adblock do Adcash...\n');

// Teste 1: Verificar se o arquivo de biblioteca existe
console.log('1. Verificando arquivo de biblioteca...');
const libraryFile = path.join(__dirname, 'adblock-library.js');
if (fs.existsSync(libraryFile)) {
    console.log('   ✅ Arquivo adblock-library.js existe');
    
    const stats = fs.statSync(libraryFile);
    const fileSize = stats.size;
    const lastModified = stats.mtime;
    
    console.log(`   📊 Tamanho: ${fileSize} bytes`);
    console.log(`   📅 Última modificação: ${lastModified.toLocaleString()}`);
    
    if (fileSize > 100) {
        console.log('   ✅ Arquivo parece conter dados válidos');
    } else {
        console.log('   ⚠️  Arquivo muito pequeno, pode precisar de atualização');
    }
} else {
    console.log('   ❌ Arquivo adblock-library.js não encontrado');
}

// Teste 2: Verificar validade da biblioteca
console.log('\n2. Verificando validade da biblioteca...');
if (isLibraryValid()) {
    console.log('   ✅ Biblioteca é válida (menos de 5 minutos)');
} else {
    console.log('   ⚠️  Biblioteca precisa ser atualizada');
}

// Teste 3: Testar download da biblioteca
console.log('\n3. Testando download da biblioteca...');
updateAdblockLibrary()
    .then(() => {
        console.log('   ✅ Download e salvamento bem-sucedidos');
        
        // Verificar novamente após atualização
        if (isLibraryValid()) {
            console.log('   ✅ Biblioteca agora é válida');
        } else {
            console.log('   ❌ Biblioteca ainda não é válida após atualização');
        }
    })
    .catch((error) => {
        console.log('   ❌ Erro no download:', error.message);
    });

// Teste 4: Verificar se o arquivo HTML contém as referências corretas
console.log('\n4. Verificando integração no HTML...');
const htmlFile = path.join(__dirname, 'blog', 'index.html');
if (fs.existsSync(htmlFile)) {
    const htmlContent = fs.readFileSync(htmlFile, 'utf8');
    
    if (htmlContent.includes('adblock-library.js')) {
        console.log('   ✅ Referência à biblioteca encontrada no HTML');
    } else {
        console.log('   ❌ Referência à biblioteca não encontrada no HTML');
    }
    
    if (htmlContent.includes('aclib.runAutoTag')) {
        console.log('   ✅ Script de inicialização encontrado no HTML');
    } else {
        console.log('   ❌ Script de inicialização não encontrado no HTML');
    }
    
    if (htmlContent.includes('iolsp9sjjo')) {
        console.log('   ✅ Zone ID configurado corretamente');
    } else {
        console.log('   ❌ Zone ID não encontrado no HTML');
    }
} else {
    console.log('   ❌ Arquivo blog/index.html não encontrado');
}

console.log('\n🎯 Teste concluído!');
console.log('\n📋 Próximos passos:');
console.log('   1. Configure o cron job ou Windows Task Scheduler');
console.log('   2. Execute o script de atualização periodicamente');
console.log('   3. Monitore os logs para verificar funcionamento');
console.log('   4. Teste o site para verificar se os anúncios aparecem');
