# Configuração do ads.txt da Ezoic - LinkZin

## 📋 Visão Geral

Este documento descreve a implementação do redirecionamento do arquivo `ads.txt` para o sistema de gerenciamento da Ezoic, garantindo que os anúncios sejam servidos corretamente.

## 🔧 Implementações Disponíveis

### 1. Redirecionamento via .htaccess (Apache/Nginx)

**Status:** ✅ Implementado (para servidores com suporte a .htaccess)

O arquivo `.htaccess` foi atualizado com a regra de redirecionamento:

```apache
# Ezoic ads.txt redirect
RewriteRule ^ads\.txt$ https://srv.adstxtmanager.com/19390/linkzinencurtador.online [R=301,L]
```

**Como funciona:**
- Qualquer requisição para `/ads.txt` será redirecionada para o servidor da Ezoic
- Redirecionamento 301 (permanente) para SEO
- Funciona automaticamente sem necessidade de manutenção

### 1.1. Atualização Automática (GitHub Pages)

**Status:** ✅ Implementado (para GitHub Pages)

Como o site está hospedado no GitHub Pages (que não suporta .htaccess), foi implementada uma solução alternativa:

**Arquivo `ads.txt` atualizado:**
- Contém o conteúdo da Ezoic
- Inclui referência para o servidor oficial
- Mantém compatibilidade com Google AdSense

**Script de atualização automática:**
```bash
# Executar manualmente
node update-ads-github.js

# Ou via npm
npm run update-ads
```

### 2. Script PHP Alternativo

**Arquivo:** `ads.php`

Caso prefira usar PHP em vez de .htaccess:

```php
<?php
header('Content-Type: text/plain');
header('Location: https://srv.adstxtmanager.com/19390/linkzinencurtador.online', true, 301);
exit;
?>
```

**Para usar:**
1. Renomeie `ads.php` para `ads.txt`
2. Ou configure o servidor para servir este arquivo quando `ads.txt` for solicitado

### 3. Atualização Automática (Opcional)

#### Script Bash (Linux/Mac)
**Arquivo:** `update-ads-txt.sh`

**Configuração do Cron:**
```bash
# Executar diariamente às 2:00 AM
0 2 * * * /caminho/para/seu/projeto/update-ads-txt.sh
```

#### Script PowerShell (Windows)
**Arquivo:** `update-ads-txt.ps1`

**Configuração do Task Scheduler:**
- Frequência: Diário às 2:00 AM
- Ação: Executar `powershell.exe -ExecutionPolicy Bypass -File update-ads-txt.ps1`

## 🧪 Testando a Implementação

### 1. Teste Manual
```bash
# Teste o redirecionamento
curl -I https://linkzinencurtador.online/ads.txt

# Deve retornar:
# HTTP/1.1 301 Moved Permanently
# Location: https://srv.adstxtmanager.com/19390/linkzinencurtador.online
```

### 2. Teste no Navegador
1. Acesse: `https://linkzinencurtador.online/ads.txt`
2. Deve redirecionar automaticamente para o servidor da Ezoic
3. Verifique se o conteúdo do ads.txt está correto

### 3. Verificação de SEO
- Use ferramentas como Google Search Console
- Verifique se o redirecionamento 301 está funcionando
- Confirme que não há erros de crawl

## 📊 Monitoramento

### Logs de Atualização Automática
- **Linux/Mac:** `ads-update.log`
- **Windows:** `ads-update.log`

### Verificações Recomendadas
1. **Semanalmente:** Verificar se o redirecionamento está funcionando
2. **Mensalmente:** Revisar logs de atualização automática
3. **Trimestralmente:** Verificar performance dos anúncios no dashboard da Ezoic

## 🔍 Solução de Problemas

### Problema: Redirecionamento não funciona
**Soluções:**
1. Verificar se o módulo `mod_rewrite` está habilitado no Apache
2. Confirmar que o arquivo `.htaccess` está na raiz do site
3. Testar com `curl -I` para verificar headers

### Problema: Conteúdo do ads.txt incorreto
**Soluções:**
1. Verificar se a URL da Ezoic está correta
2. Confirmar que o ID do site (19390) está correto
3. Entrar em contato com o suporte da Ezoic

### Problema: Atualização automática falha
**Soluções:**
1. Verificar permissões de escrita nos arquivos
2. Confirmar conectividade com o servidor da Ezoic
3. Revisar logs de erro

## 📞 Suporte

- **Ezoic Support:** [support.ezoic.com](https://support.ezoic.com)
- **Documentação:** [help.ezoic.com](https://help.ezoic.com)

## 📝 Notas Importantes

1. **Backup:** O arquivo `ads.txt` original foi mantido como backup
2. **SEO:** O redirecionamento 301 preserva o valor SEO
3. **Performance:** O redirecionamento é mais eficiente que download automático
4. **Confiabilidade:** A Ezoic gerencia automaticamente o conteúdo do ads.txt

---

**Última atualização:** $(date)
**Versão:** 1.0
**Status:** ✅ Implementado e Testado
