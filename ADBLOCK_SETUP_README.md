# Sistema Anti-Adblock Adcash - Configuração Completa

## 📋 Visão Geral

Este sistema implementa a biblioteca anti-adblock do Adcash com atualização automática periódica. A biblioteca é baixada da API oficial do Adcash e atualizada automaticamente para manter a proteção contra bloqueadores de anúncios sempre ativa.

## 🚀 Funcionalidades Implementadas

### ✅ 1. Biblioteca Anti-Adblock
- **Arquivo**: `adblock-library.js`
- **Fonte**: API oficial do Adcash (`https://adbpage.com/adblock?v=3`)
- **Atualização**: Automática a cada 5 minutos
- **Cache**: Sistema de cache inteligente para evitar downloads desnecessários

### ✅ 2. Script de Atualização
- **Arquivo**: `update-adblock-library.js`
- **Função**: Baixa e atualiza a biblioteca anti-adblock
- **Validação**: Verifica se a biblioteca atual ainda é válida
- **Logs**: Sistema de logging para monitoramento

### ✅ 3. Integração no Site
- **Localização**: `blog/index.html`
- **Posição**: No `<head>` da página
- **Inicialização**: Script auto-tag configurado com zone ID `iolsp9sjjo`

### ✅ 4. Scripts de Automação
- **Linux/Unix**: `update-adblock-cron.sh`
- **Windows**: `update-adblock-cron.ps1`
- **Configuração**: `crontab-adblock.txt` e `windows-task-scheduler-adblock.txt`

## 🔧 Configuração

### 1. Configuração do Cron (Linux/Unix)

```bash
# Tornar o script executável
chmod +x update-adblock-cron.sh

# Adicionar ao crontab
crontab crontab-adblock.txt
```

### 2. Configuração do Windows Task Scheduler

1. Abra o Task Scheduler (`taskschd.msc`)
2. Siga as instruções em `windows-task-scheduler-adblock.txt`
3. Configure para executar a cada 5 minutos

### 3. Teste da Configuração

```bash
# Executar teste completo
node test-adblock-setup.js

# Forçar atualização da biblioteca
node update-adblock-library.js
```

## 📁 Estrutura de Arquivos

```
projeto/
├── adblock-library.js                    # Biblioteca anti-adblock (atualizada automaticamente)
├── update-adblock-library.js             # Script de atualização
├── update-adblock-cron.sh                # Script cron para Linux/Unix
├── update-adblock-cron.ps1               # Script para Windows
├── crontab-adblock.txt                   # Configuração do cron
├── windows-task-scheduler-adblock.txt    # Instruções para Windows
├── test-adblock-setup.js                 # Script de teste
├── adblock-update.log                    # Log de atualizações
└── blog/
    └── index.html                        # Página com integração
```

## 🔍 Monitoramento

### Logs de Atualização
- **Arquivo**: `adblock-update.log`
- **Conteúdo**: Timestamps, status de download, erros
- **Localização**: Diretório raiz do projeto

### Verificação Manual
```bash
# Verificar se a biblioteca está atualizada
node -e "const { isLibraryValid } = require('./update-adblock-library.js'); console.log('Válida:', isLibraryValid())"

# Verificar tamanho da biblioteca
ls -la adblock-library.js
```

## ⚙️ Configurações Avançadas

### Zone ID
- **Atual**: `iolsp9sjjo`
- **Localização**: `blog/index.html` linha 103
- **Alteração**: Substitua pelo seu zone ID do Adcash

### Intervalo de Atualização
- **Padrão**: 5 minutos
- **Alteração**: Modifique `CACHE_DURATION` em `update-adblock-library.js`
- **Cron**: Ajuste o intervalo no crontab

### URLs da API
- **Endpoint 1**: `https://adbpage.com/adblock?v=3` (com tags script)
- **Endpoint 2**: `https://adbpage.com/adblock?v=3&format=js` (sem tags)
- **Atual**: Usando endpoint 1 (recomendado)

## 🛠️ Solução de Problemas

### Biblioteca não atualiza
1. Verifique se o Node.js está instalado
2. Verifique permissões de escrita no diretório
3. Consulte o arquivo de log `adblock-update.log`

### Erro de rede
1. Verifique conectividade com `adbpage.com`
2. Verifique firewall/proxy
3. Teste manual: `curl https://adbpage.com/adblock?v=3`

### Script não executa
1. **Linux**: Verifique permissões `chmod +x update-adblock-cron.sh`
2. **Windows**: Verifique política de execução do PowerShell
3. Verifique se o Node.js está no PATH

## 📊 Status da Implementação

- ✅ Biblioteca anti-adblock integrada
- ✅ Sistema de atualização automática
- ✅ Scripts para Linux e Windows
- ✅ Sistema de cache inteligente
- ✅ Logs de monitoramento
- ✅ Script de teste
- ✅ Documentação completa

## 🔄 Próximos Passos

1. **Configure o cron job ou Windows Task Scheduler**
2. **Execute o script de teste para verificar funcionamento**
3. **Monitore os logs por alguns dias**
4. **Ajuste o zone ID conforme necessário**
5. **Teste o site para verificar se os anúncios aparecem**

## 📞 Suporte

Para problemas específicos do Adcash:
- Consulte a documentação oficial do Adcash
- Verifique se o zone ID está correto
- Confirme se o domínio está autorizado no painel do Adcash

---

**Nota**: Este sistema mantém automaticamente a biblioteca anti-adblock atualizada, garantindo proteção contínua contra bloqueadores de anúncios.
