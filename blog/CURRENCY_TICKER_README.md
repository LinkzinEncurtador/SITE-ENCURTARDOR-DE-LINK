# 🔄 Sistema de Ticker de Cotações Automático - LinkZin

## 📋 Visão Geral

Este sistema atualiza automaticamente as taxas de câmbio e a data no seu site, eliminando a necessidade de atualizações manuais diárias.

## ✨ Funcionalidades

- **📅 Data Automática**: A data se atualiza automaticamente todos os dias
- **💰 Cotações em Tempo Real**: Atualizações automáticas a cada minuto
- **🔄 Atualização Manual**: Botão para forçar atualização quando necessário
- **⏰ Indicador de Tempo**: Mostra quando foi a última atualização
- **📱 Responsivo**: Funciona perfeitamente em dispositivos móveis e desktop

## 🚀 Instalação

### 1. Incluir os Arquivos

Adicione os seguintes scripts ao seu HTML, antes do fechamento da tag `</body>`:

```html
<!-- Scripts do Ticker de Cotações -->
<script src="currency-config.js"></script>
<script src="currency-ticker.js"></script>
```

### 2. Adicionar o HTML do Ticker

Inclua este HTML onde desejar exibir as cotações:

```html
<div class="currency-ticker">
    <div class="ticker-content">
        <span>Carregando taxas de câmbio...</span>
        <span>Carregando taxas de câmbio...</span>
    </div>
</div>
```

### 3. Adicionar CSS (Opcional)

Para estilizar o ticker, adicione este CSS:

```css
.currency-ticker {
    background: linear-gradient(90deg, #002147, #004a94);
    color: white;
    padding: 15px;
    border-radius: 8px;
    overflow: hidden;
    position: relative;
}

.ticker-content {
    display: flex;
    animation: ticker 30s linear infinite;
    white-space: nowrap;
}

.ticker-content span {
    padding-right: 50px;
    font-size: 14px;
}

@keyframes ticker {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
```

## ⚙️ Configuração

### Atualizar Cotações

Para atualizar as cotações, edite o arquivo `currency-config.js`:

```javascript
const CURRENCY_RATES = {
    USD: 5.46,        // Dólar Americano
    EUR: 6.37,        // Euro
    GBP: 6.37,        // Libra Esterlina
    CAD: 3.96,        // Dólar Canadense
    AUD: 3.56         // Dólar Australiano
};
```

### Adicionar Novas Moedas

Para adicionar uma nova moeda:

1. Adicione a cotação em `CURRENCY_RATES`:
```javascript
const CURRENCY_RATES = {
    USD: 5.46,
    EUR: 6.37,
    GBP: 6.37,
    CAD: 3.96,
    AUD: 3.56,
    JPY: 0.037        // Nova moeda: Iene Japonês
};
```

2. Adicione o nome em português em `CURRENCY_NAMES`:
```javascript
const CURRENCY_NAMES = {
    USD: "Dólar",
    EUR: "Euro", 
    GBP: "Libra Esterlina",
    CAD: "Dólar Canadense",
    AUD: "Dólar Australiano",
    JPY: "Iene Japonês"    // Nome da nova moeda
};
```

## 🎯 Uso Avançado

### Atualizar Cotações Programaticamente

```javascript
// Atualizar uma cotação específica
if (window.currencyTicker) {
    window.currencyTicker.updateRates({
        USD: 5.50,
        EUR: 6.40
    });
}
```

### Forçar Atualização Manual

```javascript
// Forçar atualização do ticker
if (window.currencyTicker) {
    window.currencyTicker.updateTicker();
}
```

### Verificar Status do Sistema

```javascript
// Verificar se o ticker está funcionando
if (window.currencyTicker && window.currencyTicker.isInitialized) {
    console.log('Ticker funcionando perfeitamente!');
} else {
    console.log('Ticker não inicializado');
}
```

## 🔧 Personalização

### Alterar Intervalo de Atualização

No arquivo `currency-ticker.js`, altere o valor em `startAutoUpdate()`:

```javascript
// Atualiza a cada 5 minutos (300000 ms)
this.updateInterval = setInterval(() => {
    this.updateTicker();
}, 300000);
```

### Personalizar Formato da Data

No arquivo `currency-config.js`, modifique a função `formatDate()`:

```javascript
function formatDate(date) {
    // Formato: Segunda-feira, 02 de Setembro de 2025
    const options = { 
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    };
    return date.toLocaleDateString('pt-BR', options);
}
```

### Personalizar Texto do Ticker

Modifique a função `generateTickerText()` em `currency-config.js`:

```javascript
function generateTickerText() {
    const today = new Date();
    const dayOfWeek = getDayOfWeek(today);
    const formattedDate = formatDate(today);
    
    // Formato personalizado
    let tickerText = `📊 ${dayOfWeek} - Cotações ${formattedDate} | `;
    
    const currencies = Object.keys(CURRENCY_RATES);
    currencies.forEach((currency, index) => {
        const name = CURRENCY_NAMES[currency];
        const rate = CURRENCY_RATES[currency];
        tickerText += `${name}: R$${rate.toFixed(2)}`;
        
        if (index < currencies.length - 1) {
            tickerText += " | ";
        }
    });
    
    return tickerText;
}
```

## 🐛 Solução de Problemas

### Ticker Não Aparece

1. Verifique se os scripts estão sendo carregados
2. Confirme se o HTML tem a classe `currency-ticker`
3. Verifique o console do navegador para erros

### Cotações Não Atualizam

1. Verifique se o arquivo `currency-config.js` está correto
2. Confirme se as funções estão sendo exportadas
3. Verifique se não há conflitos com outros scripts

### Erro de JavaScript

1. Abra o console do navegador (F12)
2. Procure por mensagens de erro
3. Verifique se todos os arquivos estão sendo carregados

## 📱 Compatibilidade

- ✅ Chrome 60+
- ✅ Firefox 55+
- ✅ Safari 12+
- ✅ Edge 79+
- ✅ Dispositivos móveis (iOS/Android)

## 🔄 Atualizações Futuras

### Integração com API

Para integrar com uma API de cotações em tempo real:

```javascript
// Exemplo de integração com API
async function fetchLiveRates() {
    try {
        const response = await fetch('https://api.exemplo.com/rates');
        const data = await response.json();
        
        // Atualizar cotações
        window.currencyTicker.updateRates(data);
    } catch (error) {
        console.error('Erro ao buscar cotações:', error);
    }
}

// Executar a cada 15 minutos
setInterval(fetchLiveRates, 15 * 60 * 1000);
```

## 📞 Suporte

Para dúvidas ou problemas:

1. Verifique este README
2. Teste a página de demonstração (`demo-ticker.html`)
3. Verifique o console do navegador
4. Entre em contato com o suporte técnico

## 📄 Licença

Este sistema foi desenvolvido para o LinkZin e está disponível para uso interno.

---

**Desenvolvido por Bruno Ulrich para LinkZin** 🚀
