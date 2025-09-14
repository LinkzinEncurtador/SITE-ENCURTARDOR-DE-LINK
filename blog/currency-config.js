// Configuração das Taxas de Câmbio - LinkZin
// Atualize estas cotações conforme necessário

const CURRENCY_RATES = {
    USD: 5.46,        // Dólar Americano
    EUR: 6.37,        // Euro
    GBP: 6.37,        // Libra Esterlina
    CAD: 3.96,        // Dólar Canadense
    AUD: 3.56         // Dólar Australiano
};

// Nomes das moedas em português
const CURRENCY_NAMES = {
    USD: "Dólar",
    EUR: "Euro", 
    GBP: "Libra Esterlina",
    CAD: "Dólar Canadense",
    AUD: "Dólar Australiano"
};

// Fonte das cotações (opcional)
const RATE_SOURCE = "Banco Central do Brasil";

// Data da última atualização (será preenchida automaticamente)
let lastUpdateDate = new Date();

// Função para formatar a data em português
function formatDate(date) {
    const options = { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric' 
    };
    return date.toLocaleDateString('pt-BR', options);
}

// Função para obter o dia da semana em português
function getDayOfWeek(date) {
    const days = [
        'Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira',
        'Quinta-feira', 'Sexta-feira', 'Sábado'
    ];
    return days[date.getDay()];
}

// Função para gerar o texto do ticker
function generateTickerText() {
    const today = new Date();
    const dayOfWeek = getDayOfWeek(today);
    const formattedDate = formatDate(today);
    
    let tickerText = `${dayOfWeek} - Taxas de Câmbio ${formattedDate} - `;
    
    // Adiciona cada moeda ao texto
    const currencies = Object.keys(CURRENCY_RATES);
    currencies.forEach((currency, index) => {
        const name = CURRENCY_NAMES[currency];
        const rate = CURRENCY_RATES[currency];
        tickerText += `${name} R$${rate.toFixed(2).replace('.', ',')}`;
        
        if (index < currencies.length - 1) {
            tickerText += " - ";
        }
    });
    
    return tickerText;
}

// Função para atualizar as cotações (para uso futuro com API)
function updateRates(newRates) {
    Object.assign(CURRENCY_RATES, newRates);
    lastUpdateDate = new Date();
    updateTickerDisplay();
}

// Função para atualizar a exibição do ticker
function updateTickerDisplay() {
    const tickerElements = document.querySelectorAll('.ticker-content span');
    const tickerText = generateTickerText();
    
    tickerElements.forEach(element => {
        element.textContent = tickerText;
    });
}

// Exporta as funções para uso global
window.CURRENCY_RATES = CURRENCY_RATES;
window.CURRENCY_NAMES = CURRENCY_NAMES;
window.generateTickerText = generateTickerText;
window.updateRates = updateRates;
window.updateTickerDisplay = updateTickerDisplay;
