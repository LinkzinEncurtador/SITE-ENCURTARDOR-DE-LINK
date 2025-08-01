// Configuração do Firebase (opcional - para persistência de dados)
// Se você quiser usar Firebase, descomente e configure as linhas abaixo
/*
const firebaseConfig = {
    apiKey: "sua-api-key",
    authDomain: "seu-projeto.firebaseapp.com",
    projectId: "seu-projeto",
    storageBucket: "seu-projeto.appspot.com",
    messagingSenderId: "123456789",
    appId: "seu-app-id"
};

firebase.initializeApp(firebaseConfig);
const db = firebase.firestore();
*/

// Classe principal para gerenciar o encurtador de links
class LinkZin {
    constructor() {
        this.links = this.loadLinks();
        this.init();
    }

    // Inicializar a aplicação
    init() {
        this.setupEventListeners();
        this.setupMobileMenu();
        this.checkForRedirect();
    }

    // Configurar event listeners
    setupEventListeners() {
        // Encurtamento de links
        const shortenBtn = document.getElementById('shortenBtn');
        if (shortenBtn) {
            shortenBtn.addEventListener('click', () => this.shortenUrl());
        }

        // Copiar link
        const copyBtn = document.getElementById('copyBtn');
        if (copyBtn) {
            copyBtn.addEventListener('click', () => this.copyToClipboard());
        }

        // Download QR Code
        const downloadQRBtn = document.getElementById('downloadQR');
        if (downloadQRBtn) {
            downloadQRBtn.addEventListener('click', () => this.downloadQRCode());
        }

        // Enter key para encurtar
        const longUrlInput = document.getElementById('longUrl');
        if (longUrlInput) {
            longUrlInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.shortenUrl();
                }
            });
        }
    }

    // Configurar menu mobile
    setupMobileMenu() {
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const nav = document.querySelector('.nav');

        if (mobileMenuToggle && nav) {
            mobileMenuToggle.addEventListener('click', () => {
                nav.classList.toggle('active');
            });
        }
    }

    // Verificar se há redirecionamento na URL
    checkForRedirect() {
        const urlParams = new URLSearchParams(window.location.search);
        const shortCode = urlParams.get('r');

        if (shortCode) {
            this.redirectToOriginal(shortCode);
        }
    }

    // Encurtar URL
    async shortenUrl() {
        const longUrlInput = document.getElementById('longUrl');
        const resultContainer = document.getElementById('result');
        const shortenBtn = document.getElementById('shortenBtn');

        if (!longUrlInput || !resultContainer) return;

        const longUrl = longUrlInput.value.trim();

        if (!longUrl) {
            this.showMessage('Por favor, insira uma URL válida.', 'error');
            return;
        }

        if (!this.isValidUrl(longUrl)) {
            this.showMessage('Por favor, insira uma URL válida (ex: https://www.exemplo.com).', 'error');
            return;
        }

        // Mostrar loading
        shortenBtn.classList.add('loading');
        shortenBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Encurtando...';

        try {
            // Gerar código único
            const shortCode = this.generateShortCode();

            // Criar URL curta
            const shortUrl = `${window.location.origin}${window.location.pathname}?r=${shortCode}`;

            // Salvar link
            const linkData = {
                id: shortCode,
                originalUrl: longUrl,
                shortUrl: shortUrl,
                createdAt: new Date().toISOString(),
                clicks: 0
            };

            this.links[shortCode] = linkData;
            this.saveLinks();

            // Exibir resultado
            const shortUrlInput = document.getElementById('shortUrl');
            if (shortUrlInput) {
                shortUrlInput.value = shortUrl;
            }

            // Gerar QR Code
            await this.generateQRCode(shortUrl);

            // Mostrar resultado
            resultContainer.style.display = 'block';

            this.showMessage('Link encurtado com sucesso!', 'success');

        } catch (error) {
            console.error('Erro ao encurtar URL:', error);
            this.showMessage('Erro ao encurtar URL. Tente novamente.', 'error');
        } finally {
            // Remover loading
            shortenBtn.classList.remove('loading');
            shortenBtn.innerHTML = '<i class="fas fa-link"></i> Encurtar';
        }
    }

    // Gerar código único para o link
    generateShortCode() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        for (let i = 0; i < 6; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    }

    // Validar URL
    isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }

    // Redirecionar para URL original
    redirectToOriginal(shortCode) {
        const linkData = this.links[shortCode];

        if (linkData) {
            // Incrementar contador de cliques
            linkData.clicks++;
            this.saveLinks();

            // Redirecionar
            window.location.href = linkData.originalUrl;
        } else {
            this.showMessage('Link não encontrado ou expirado.', 'error');
        }
    }

    // Gerar QR Code
    async generateQRCode(url) {
        const qrContainer = document.getElementById('qrCode');
        if (!qrContainer) return;

        try {
            // Limpar container
            qrContainer.innerHTML = '';

            // Gerar QR Code
            await QRCode.toCanvas(qrContainer, url, {
                width: 200,
                height: 200,
                margin: 2,
                color: {
                    dark: '#0a1a3f',
                    light: '#ffffff'
                }
            });
        } catch (error) {
            console.error('Erro ao gerar QR Code:', error);
        }
    }

    // Download QR Code
    downloadQRCode() {
        const qrCanvas = document.querySelector('#qrCode canvas');
        if (!qrCanvas) return;

        const link = document.createElement('a');
        link.download = 'qrcode-linkzin.png';
        link.href = qrCanvas.toDataURL();
        link.click();
    }

    // Copiar para clipboard
    async copyToClipboard() {
        const shortUrlInput = document.getElementById('shortUrl');
        if (!shortUrlInput) return;

        try {
            await navigator.clipboard.writeText(shortUrlInput.value);
            this.showMessage('Link copiado para a área de transferência!', 'success');
        } catch (error) {
            // Fallback para navegadores mais antigos
            shortUrlInput.select();
            document.execCommand('copy');
            this.showMessage('Link copiado para a área de transferência!', 'success');
        }
    }

    // Mostrar mensagem
    showMessage(message, type = 'info') {
        // Remover mensagens anteriores
        const existingMessages = document.querySelectorAll('.message');
        existingMessages.forEach(msg => msg.remove());

        // Criar nova mensagem
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        messageDiv.textContent = message;

        // Inserir mensagem
        const shortenerForm = document.querySelector('.shortener-form');
        if (shortenerForm) {
            shortenerForm.insertBefore(messageDiv, shortenerForm.firstChild);
        }

        // Remover mensagem após 5 segundos
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 5000);
    }

    // Carregar links do localStorage
    loadLinks() {
        try {
            const saved = localStorage.getItem('linkzin_links');
            return saved ? JSON.parse(saved) : {};
        } catch (error) {
            console.error('Erro ao carregar links:', error);
            return {};
        }
    }

    // Salvar links no localStorage
    saveLinks() {
        try {
            localStorage.setItem('linkzin_links', JSON.stringify(this.links));
        } catch (error) {
            console.error('Erro ao salvar links:', error);
        }
    }

    // Obter estatísticas de um link
    getLinkStats(shortCode) {
        const linkData = this.links[shortCode];
        return linkData ? {
            clicks: linkData.clicks,
            createdAt: linkData.createdAt,
            originalUrl: linkData.originalUrl
        } : null;
    }

    // Desencurtar URL
    unshortenUrl(shortUrl) {
        const shortCode = this.extractShortCode(shortUrl);
        if (!shortCode) return null;

        const linkData = this.links[shortCode];
        return linkData ? linkData.originalUrl : null;
    }

    // Extrair código curto da URL
    extractShortCode(url) {
        try {
            const urlObj = new URL(url);
            const params = new URLSearchParams(urlObj.search);
            return params.get('r');
        } catch (error) {
            return null;
        }
    }

    // Limpar links antigos (mais de 30 dias)
    cleanupOldLinks() {
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);

        Object.keys(this.links).forEach(key => {
            const linkData = this.links[key];
            const createdAt = new Date(linkData.createdAt);

            if (createdAt < thirtyDaysAgo) {
                delete this.links[key];
            }
        });

        this.saveLinks();
    }
}

// Classe para contador de cliques
class ClickCounter {
    constructor() {
        this.linkZin = new LinkZin();
        this.init();
    }

    init() {
        const checkBtn = document.getElementById('checkClicksBtn');
        if (checkBtn) {
            checkBtn.addEventListener('click', () => this.checkClicks());
        }

        const urlInput = document.getElementById('shortUrlInput');
        if (urlInput) {
            urlInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.checkClicks();
                }
            });
        }
    }

    checkClicks() {
        const urlInput = document.getElementById('shortUrlInput');
        const resultDiv = document.getElementById('clicksResult');

        if (!urlInput || !resultDiv) return;

        const shortUrl = urlInput.value.trim();

        if (!shortUrl) {
            this.showMessage('Por favor, insira uma URL curta.', 'error');
            return;
        }

        const shortCode = this.linkZin.extractShortCode(shortUrl);
        if (!shortCode) {
            this.showMessage('URL inválida. Certifique-se de que é um link do LinkZin.', 'error');
            return;
        }

        const stats = this.linkZin.getLinkStats(shortCode);

        if (stats) {
            const createdAt = new Date(stats.createdAt).toLocaleDateString('pt-BR');
            resultDiv.innerHTML = `
                <div class="stats-card">
                    <h3>Estatísticas do Link</h3>
                    <div class="stat-item">
                        <span class="stat-label">Total de Cliques:</span>
                        <span class="stat-value">${stats.clicks}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Criado em:</span>
                        <span class="stat-value">${createdAt}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">URL Original:</span>
                        <span class="stat-value url-original">${stats.originalUrl}</span>
                    </div>
                </div>
            `;
            resultDiv.style.display = 'block';
        } else {
            this.showMessage('Link não encontrado ou expirado.', 'error');
        }
    }

    showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        messageDiv.textContent = message;

        const form = document.querySelector('.click-counter-form');
        if (form) {
            form.insertBefore(messageDiv, form.firstChild);
        }

        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 5000);
    }
}

// Classe para desencurtar URLs
class UrlUnshortener {
    constructor() {
        this.linkZin = new LinkZin();
        this.init();
    }

    init() {
        const unshortenBtn = document.getElementById('unshortenBtn');
        if (unshortenBtn) {
            unshortenBtn.addEventListener('click', () => this.unshorten());
        }

        const urlInput = document.getElementById('shortUrlInput');
        if (urlInput) {
            urlInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.unshorten();
                }
            });
        }
    }

    unshorten() {
        const urlInput = document.getElementById('shortUrlInput');
        const resultDiv = document.getElementById('unshortenResult');

        if (!urlInput || !resultDiv) return;

        const shortUrl = urlInput.value.trim();

        if (!shortUrl) {
            this.showMessage('Por favor, insira uma URL curta.', 'error');
            return;
        }

        const originalUrl = this.linkZin.unshortenUrl(shortUrl);

        if (originalUrl) {
            resultDiv.innerHTML = `
                <div class="unshorten-card">
                    <h3>URL Original</h3>
                    <div class="original-url">
                        <input type="text" value="${originalUrl}" readonly class="url-display">
                        <button type="button" class="copy-btn" onclick="this.copyOriginalUrl('${originalUrl}')">
                            <i class="fas fa-copy"></i> Copiar
                        </button>
                    </div>
                </div>
            `;
            resultDiv.style.display = 'block';
        } else {
            this.showMessage('URL não encontrada ou inválida.', 'error');
        }
    }

    async copyOriginalUrl(url) {
        try {
            await navigator.clipboard.writeText(url);
            this.showMessage('URL copiada para a área de transferência!', 'success');
        } catch (error) {
            const tempInput = document.createElement('input');
            tempInput.value = url;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            this.showMessage('URL copiada para a área de transferência!', 'success');
        }
    }

    showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        messageDiv.textContent = message;

        const form = document.querySelector('.unshorten-form');
        if (form) {
            form.insertBefore(messageDiv, form.firstChild);
        }

        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 5000);
    }
}

// Classe para gerador de QR Code
class QRCodeGenerator {
    constructor() {
        this.init();
    }

    init() {
        const generateBtn = document.getElementById('generateQRBtn');
        if (generateBtn) {
            generateBtn.addEventListener('click', () => this.generateQR());
        }

        const urlInput = document.getElementById('qrUrlInput');
        if (urlInput) {
            urlInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.generateQR();
                }
            });
        }
    }

    async generateQR() {
        const urlInput = document.getElementById('qrUrlInput');
        const qrContainer = document.getElementById('qrCodeResult');
        const downloadBtn = document.getElementById('downloadQRBtn');

        if (!urlInput || !qrContainer) return;

        const url = urlInput.value.trim();

        if (!url) {
            this.showMessage('Por favor, insira uma URL.', 'error');
            return;
        }

        if (!this.isValidUrl(url)) {
            this.showMessage('Por favor, insira uma URL válida.', 'error');
            return;
        }

        try {
            // Limpar container
            qrContainer.innerHTML = '';

            // Gerar QR Code
            await QRCode.toCanvas(qrContainer, url, {
                width: 250,
                height: 250,
                margin: 2,
                color: {
                    dark: '#0a1a3f',
                    light: '#ffffff'
                }
            });

            // Mostrar botão de download
            if (downloadBtn) {
                downloadBtn.style.display = 'inline-flex';
                downloadBtn.onclick = () => this.downloadQR();
            }

        } catch (error) {
            console.error('Erro ao gerar QR Code:', error);
            this.showMessage('Erro ao gerar QR Code. Tente novamente.', 'error');
        }
    }

    isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }

    downloadQR() {
        const qrCanvas = document.querySelector('#qrCodeResult canvas');
        if (!qrCanvas) return;

        const link = document.createElement('a');
        link.download = 'qrcode-linkzin.png';
        link.href = qrCanvas.toDataURL();
        link.click();
    }

    showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        messageDiv.textContent = message;

        const form = document.querySelector('.qr-generator-form');
        if (form) {
            form.insertBefore(messageDiv, form.firstChild);
        }

        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 5000);
    }
}

// Classe para gerador de link WhatsApp
class WhatsAppLinkGenerator {
    constructor() {
        this.init();
    }

    init() {
        const generateBtn = document.getElementById('generateWhatsAppBtn');
        if (generateBtn) {
            generateBtn.addEventListener('click', () => this.generateWhatsAppLink());
        }

        const numberInput = document.getElementById('whatsappNumber');
        if (numberInput) {
            numberInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.generateWhatsAppLink();
                }
            });
        }
    }

    generateWhatsAppLink() {
        const numberInput = document.getElementById('whatsappNumber');
        const messageInput = document.getElementById('whatsappMessage');
        const resultDiv = document.getElementById('whatsappResult');

        if (!numberInput || !resultDiv) return;

        let number = numberInput.value.trim();
        const message = messageInput ? messageInput.value.trim() : '';

        if (!number) {
            this.showMessage('Por favor, insira um número de telefone.', 'error');
            return;
        }

        // Limpar número (remover caracteres especiais)
        number = number.replace(/\D/g, '');

        // Adicionar código do país se não estiver presente
        if (number.length === 11 && number.startsWith('0')) {
            number = '55' + number.substring(1);
        } else if (number.length === 10) {
            number = '55' + number;
        }

        // Criar link do WhatsApp
        let whatsappUrl = `https://wa.me/${number}`;
        if (message) {
            whatsappUrl += `?text=${encodeURIComponent(message)}`;
        }

        // Exibir resultado
        resultDiv.innerHTML = `
            <div class="whatsapp-link-card">
                <h3>Link do WhatsApp Gerado</h3>
                <div class="whatsapp-link">
                    <input type="text" value="${whatsappUrl}" readonly class="whatsapp-url-display">
                    <button type="button" class="copy-btn" onclick="this.copyWhatsAppLink('${whatsappUrl}')">
                        <i class="fas fa-copy"></i> Copiar
                    </button>
                </div>
                <div class="whatsapp-actions">
                    <a href="${whatsappUrl}" target="_blank" class="whatsapp-btn">
                        <i class="fab fa-whatsapp"></i> Abrir WhatsApp
                    </a>
                    <button type="button" class="qr-btn" onclick="this.generateWhatsAppQR('${whatsappUrl}')">
                        <i class="fas fa-qrcode"></i> Gerar QR Code
                    </button>
                </div>
                <div id="whatsappQR" class="whatsapp-qr-container" style="display: none;"></div>
            </div>
        `;
        resultDiv.style.display = 'block';
    }

    async copyWhatsAppLink(url) {
        try {
            await navigator.clipboard.writeText(url);
            this.showMessage('Link copiado para a área de transferência!', 'success');
        } catch (error) {
            const tempInput = document.createElement('input');
            tempInput.value = url;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            this.showMessage('Link copiado para a área de transferência!', 'success');
        }
    }

    async generateWhatsAppQR(url) {
        const qrContainer = document.getElementById('whatsappQR');
        if (!qrContainer) return;

        try {
            qrContainer.innerHTML = '';
            qrContainer.style.display = 'block';

            await QRCode.toCanvas(qrContainer, url, {
                width: 200,
                height: 200,
                margin: 2,
                color: {
                    dark: '#25D366',
                    light: '#ffffff'
                }
            });
        } catch (error) {
            console.error('Erro ao gerar QR Code:', error);
        }
    }

    showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        messageDiv.textContent = message;

        const form = document.querySelector('.whatsapp-form');
        if (form) {
            form.insertBefore(messageDiv, form.firstChild);
        }

        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 5000);
    }
}

// Inicializar aplicação baseada na página atual
document.addEventListener('DOMContentLoaded', function () {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';

    switch (currentPage) {
        case 'index.html':
        case '':
            new LinkZin();
            break;
        case 'contador.html':
            new ClickCounter();
            break;
        case 'desencurtar.html':
            new UrlUnshortener();
            break;
        case 'qr-code.html':
            new QRCodeGenerator();
            break;
        case 'whatsapp.html':
            new WhatsAppLinkGenerator();
            break;
        default:
            new LinkZin();
    }

    // Limpar links antigos periodicamente
    setInterval(() => {
        const linkZin = new LinkZin();
        linkZin.cleanupOldLinks();
    }, 24 * 60 * 60 * 1000); // A cada 24 horas
}); 