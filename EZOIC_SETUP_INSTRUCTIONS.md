# Instruções para Configuração da Ezoic - LinkZin

## 🚀 Passos para Configuração Completa

### 1. Configuração no Painel da Ezoic

1. **Acesse o painel da Ezoic:**
   - URL: [app.ezoic.com](https://app.ezoic.com)
   - Faça login com sua conta

2. **Adicione o site:**
   - Clique em "Add Site"
   - Digite: `linkzinencurtador.online`
   - Confirme o domínio

3. **Configure o ads.txt:**
   - Vá para "Settings" > "ads.txt"
   - Ative o "ads.txt Manager"
   - Anote o ID do site (deve ser 19390)

### 2. Verificação da Integração

Após configurar no painel da Ezoic, teste a URL:
```
https://srv.adstxtmanager.com/19390/linkzinencurtador.online
```

Se retornar conteúdo válido, execute:
```bash
node update-ads-github.js
```

### 3. Configuração de Atualização Automática

#### Opção A: GitHub Actions (Recomendado)

Crie o arquivo `.github/workflows/update-ads.yml`:

```yaml
name: Update ads.txt
on:
  schedule:
    - cron: '0 2 * * *'  # Diário às 2:00 AM UTC
  workflow_dispatch:  # Permite execução manual

jobs:
  update-ads:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      - name: Update ads.txt
        run: node update-ads-github.js
      - name: Commit changes
        run: |
          git config --local user.email "action@github.com"
          git config --local user.name "GitHub Action"
          git add ads.txt
          git diff --staged --quiet || git commit -m "Update ads.txt from Ezoic"
          git push
```

#### Opção B: Execução Manual

Execute periodicamente:
```bash
# Atualizar ads.txt
node update-ads-github.js

# Verificar status
npm run test-ads
```

### 4. Verificação Final

1. **Teste o ads.txt:**
   ```bash
   curl https://linkzinencurtador.online/ads.txt
   ```

2. **Verifique no Google Ad Manager:**
   - Acesse [admanager.google.com](https://admanager.google.com)
   - Vá para "Inventory" > "ads.txt"
   - Verifique se o domínio aparece corretamente

3. **Teste no navegador:**
   - Acesse: `https://linkzinencurtador.online/ads.txt`
   - Deve mostrar o conteúdo atualizado

## 🔧 Solução de Problemas

### Problema: URL da Ezoic retorna 404
**Solução:**
1. Verifique se o site foi adicionado corretamente no painel da Ezoic
2. Confirme se o ID do site está correto (19390)
3. Aguarde até 24h para propagação

### Problema: Script de atualização falha
**Solução:**
1. Verifique se o Node.js está instalado
2. Execute: `npm install` (se necessário)
3. Verifique a conectividade: `npm run test-ads`

### Problema: ads.txt não atualiza automaticamente
**Solução:**
1. Verifique se o GitHub Actions está habilitado
2. Confirme se o workflow está configurado corretamente
3. Execute manualmente: `node update-ads-github.js`

## 📊 Monitoramento

### Verificações Recomendadas:
- **Diário:** Verificar se o GitHub Action executou
- **Semanal:** Testar a URL da Ezoic
- **Mensal:** Revisar performance no dashboard da Ezoic

### Logs:
- GitHub Actions: Veja em "Actions" no repositório
- Script local: Verifique o arquivo `ads-update.log`

## 📞 Suporte

- **Ezoic Support:** [support.ezoic.com](https://support.ezoic.com)
- **Documentação:** [help.ezoic.com](https://help.ezoic.com)
- **GitHub Issues:** Abra uma issue no repositório

---

**Status:** ⏳ Aguardando configuração no painel da Ezoic
**Próximo passo:** Configurar o site no painel da Ezoic
