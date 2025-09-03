/**
 * Ticker de Cotações Automático - LinkZin
 * Sistema que atualiza automaticamente as taxas de câmbio e a data
 */

class CurrencyTicker {
    constructor() {
        this.isInitialized = false;
        this.updateInterval = null;
        this.init();
    }

    init() {
        try {
            // Aguarda o DOM estar pronto
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setupTicker());
            } else {
                this.setupTicker();
            }
        } catch (error) {
            console.error('Erro ao inicializar o ticker:', error);
        }
    }

    setupTicker() {
        try {
            // Verifica se o ticker existe na página
            const tickerElement = document.querySelector('.currency-ticker');
            if (!tickerElement) {
                console.warn('Elemento do ticker não encontrado na página');
                return;
            }

            // Atualiza o ticker imediatamente
            this.updateTicker();

            // Configura atualização automática a cada minuto
            this.startAutoUpdate();

            // Adiciona indicador de última atualização
            this.addLastUpdateIndicator();

            // Adiciona funcionalidade de atualização manual
            this.addManualUpdateButton();

            this.isInitialized = true;
            console.log('Ticker de cotações inicializado com sucesso');

        } catch (error) {
            console.error('Erro ao configurar o ticker:', error);
        }
    }

    updateTicker() {
        try {
            // Verifica se as funções estão disponíveis
            if (typeof generateTickerText === 'function') {
                const tickerText = generateTickerText();
                this.updateTickerDisplay(tickerText);
                this.updateLastUpdateTime();
            } else {
                // Fallback se as funções não estiverem disponíveis
                this.updateTickerFallback();
            }
        } catch (error) {
            console.error('Erro ao atualizar ticker:', error);
            this.updateTickerFallback();
        }
    }

    updateTickerDisplay(text) {
        const tickerElements = document.querySelectorAll('.ticker-content span');
        if (tickerElements.length > 0) {
            tickerElements.forEach(element => {
                element.textContent = text;
            });
        }
    }

    updateTickerFallback() {
        // Fallback com data atual e cotações fixas
        const today = new Date();
        const dayOfWeek = this.getDayOfWeek(today);
        const formattedDate = this.formatDate(today);
        
        const fallbackText = `Hoje ${dayOfWeek} - Taxas de Câmbio ${formattedDate} - Dólar R$5,46 - Euro R$6,37 - Libra Esterlina R$6,37 - Dólar Canadense R$3,96 - Dólar Australiano R$3,56`;
        
        this.updateTickerDisplay(fallbackText);
    }

    formatDate(date) {
        const options = { 
            day: '2-digit', 
            month: '2-digit', 
            year: 'numeric' 
        };
        return date.toLocaleDateString('pt-BR', options);
    }

    getDayOfWeek(date) {
        const days = [
            'Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira',
            'Quinta-feira', 'Sexta-feira', 'Sábado'
        ];
        return days[date.getDay()];
    }

    startAutoUpdate() {
        // Atualiza a cada minuto (60000 ms)
        this.updateInterval = setInterval(() => {
            this.updateTicker();
        }, 60000);
    }

    stopAutoUpdate() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }
    }

    addLastUpdateIndicator() {
        try {
            const tickerElement = document.querySelector('.currency-ticker');
            if (!tickerElement) return;

            // Cria o indicador de última atualização
            const updateIndicator = document.createElement('div');
            updateIndicator.className = 'last-update-indicator';
            updateIndicator.style.cssText = `
                position: absolute;
                top: 5px;
                right: 10px;
                font-size: 10px;
                color: #666;
                background: rgba(255,255,255,0.8);
                padding: 2px 6px;
                border-radius: 3px;
                font-family: Arial, sans-serif;
            `;
            updateIndicator.id = 'last-update-time';

            // Adiciona ao ticker
            tickerElement.style.position = 'relative';
            tickerElement.appendChild(updateIndicator);

            // Atualiza o tempo inicial
            this.updateLastUpdateTime();

        } catch (error) {
            console.error('Erro ao adicionar indicador de atualização:', error);
        }
    }

    updateLastUpdateTime() {
        try {
            const indicator = document.getElementById('last-update-time');
            if (indicator) {
                const now = new Date();
                const timeString = now.toLocaleTimeString('pt-BR', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
                indicator.textContent = `Atualizado: ${timeString}`;
            }
        } catch (error) {
            console.error('Erro ao atualizar tempo:', error);
        }
    }

    addManualUpdateButton() {
        try {
            const tickerElement = document.querySelector('.currency-ticker');
            if (!tickerElement) return;

            // Cria o botão de atualização manual
            const updateButton = document.createElement('button');
            updateButton.className = 'manual-update-btn';
            updateButton.innerHTML = '🔄';
            updateButton.title = 'Atualizar cotações';
            updateButton.style.cssText = `
                position: absolute;
                top: 5px;
                left: 10px;
                background: #ffd700;
                border: none;
                border-radius: 50%;
                width: 24px;
                height: 24px;
                cursor: pointer;
                font-size: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
            `;

            // Adiciona efeitos hover
            updateButton.addEventListener('mouseenter', () => {
                updateButton.style.transform = 'scale(1.1)';
                updateButton.style.background = '#ffed4e';
            });

            updateButton.addEventListener('mouseleave', () => {
                updateButton.style.transform = 'scale(1)';
                updateButton.style.background = '#ffd700';
            });

            // Adiciona funcionalidade de clique
            updateButton.addEventListener('click', () => {
                this.manualUpdate();
            });

            // Adiciona ao ticker
            tickerElement.appendChild(updateButton);

        } catch (error) {
            console.error('Erro ao adicionar botão de atualização:', error);
        }
    }

    manualUpdate() {
        try {
            const button = document.querySelector('.manual-update-btn');
            if (button) {
                // Adiciona animação de rotação
                button.style.transform = 'rotate(360deg)';
                button.style.transition = 'transform 0.5s ease';
                
                // Atualiza o ticker
                this.updateTicker();
                
                // Remove a animação após 500ms
                setTimeout(() => {
                    button.style.transform = 'rotate(0deg)';
                }, 500);
            }
        } catch (error) {
            console.error('Erro na atualização manual:', error);
        }
    }

    // Método para atualizar cotações externamente
    updateRates(newRates) {
        try {
            if (typeof window.updateRates === 'function') {
                window.updateRates(newRates);
            } else {
                // Fallback se a função não estiver disponível
                console.log('Atualizando cotações:', newRates);
                this.updateTicker();
            }
        } catch (error) {
            console.error('Erro ao atualizar cotações:', error);
        }
    }

    // Método para destruir o ticker
    destroy() {
        this.stopAutoUpdate();
        this.isInitialized = false;
        console.log('Ticker de cotações destruído');
    }
}

// Inicializa o ticker quando o script for carregado
let currencyTicker;

// Função para inicializar o ticker
function initCurrencyTicker() {
    if (!currencyTicker) {
        currencyTicker = new CurrencyTicker();
    }
    return currencyTicker;
}

// Inicializa automaticamente
document.addEventListener('DOMContentLoaded', () => {
    initCurrencyTicker();
});

// Exporta para uso global
window.CurrencyTicker = CurrencyTicker;
window.initCurrencyTicker = initCurrencyTicker;

