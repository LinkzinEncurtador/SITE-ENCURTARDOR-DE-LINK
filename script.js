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
            // Suporte para toque e clique
            const toggleMenu = (e) => {
                e.preventDefault();
                e.stopPropagation();
                nav.classList.toggle('active');
                
                // Animar o ícone do hambúrguer
                mobileMenuToggle.classList.toggle('active');
            };

            mobileMenuToggle.addEventListener('click', toggleMenu);
            mobileMenuToggle.addEventListener('touchend', toggleMenu);

            // Fechar menu ao clicar fora
            document.addEventListener('click', (e) => {
                if (nav.classList.contains('active') && 
                    !nav.contains(e.target) && 
                    !mobileMenuToggle.contains(e.target)) {
                    nav.classList.remove('active');
                    mobileMenuToggle.classList.remove('active');
                }
            });

            // Fechar menu ao clicar em um link
            const navLinks = nav.querySelectorAll('a');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    nav.classList.remove('active');
                    mobileMenuToggle.classList.remove('active');
                });
            });
        }
    }

    // Verificar se há redirecionamento na URL
    checkForRedirect() {
        // Evitar múltiplas verificações
        if (this.redirectChecked) return;
        this.redirectChecked = true;

        const urlParams = new URLSearchParams(window.location.search);
        const shortCode = urlParams.get('r');

        if (shortCode) {
            // Mostrar indicador de redirecionamento
            this.showRedirectMessage();

            // Adicionar um pequeno delay para garantir que a página carregou
            setTimeout(() => {
                this.redirectToOriginal(shortCode);
            }, 100);
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
            console.log('URL curta gerada:', shortUrl);

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
        console.log('Tentando redirecionar para código:', shortCode);
        console.log('Links disponíveis:', Object.keys(this.links));

        const linkData = this.links[shortCode];

        if (linkData) {
            console.log('Link encontrado:', linkData);

            // Incrementar contador de cliques
            linkData.clicks++;
            this.saveLinks();

            // Verificar se a URL original é válida
            if (linkData.originalUrl && this.isValidUrl(linkData.originalUrl)) {
                console.log('Redirecionando para:', linkData.originalUrl);

                // Adicionar timeout para evitar travamento
                setTimeout(() => {
                    window.location.href = linkData.originalUrl;
                }, 500);
            } else {
                console.error('URL original inválida:', linkData.originalUrl);
                this.showMessage('URL original inválida.', 'error');
            }
        } else {
            console.error('Link não encontrado para código:', shortCode);
            this.showMessage('Link não encontrado.', 'error');
        }
    }

    // Gerar QR Code
    async generateQRCode(url) {
        const qrContainer = document.getElementById('qrCode');
        if (!qrContainer) return;

        try {
            // Limpar container
            qrContainer.innerHTML = '';

            // Gerar QR Code com configurações permanentes
            await QRCode.toCanvas(qrContainer, url, {
                width: 200,
                height: 200,
                margin: 2,
                color: {
                    dark: '#0a1a3f',
                    light: '#ffffff'
                },
                // Configurações para garantir que o QR code seja permanente
                errorCorrectionLevel: 'H', // Nível mais alto de correção de erros
                version: 1, // Versão fixa para consistência
                maskPattern: 0, // Padrão de máscara fixo
                // Não há configuração de expiração - QR codes são permanentes por padrão
            });

            // Adicionar informações sobre a permanência do QR code
            const infoDiv = document.createElement('div');
            infoDiv.className = 'qr-info';
            infoDiv.innerHTML = '<small style="color: #666; font-size: 12px; margin-top: 10px; display: block;">✓ QR Code permanente - não expira</small>';
            qrContainer.appendChild(infoDiv);

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

    // Mostrar mensagem de redirecionamento
    showRedirectMessage() {
        // Remover mensagens anteriores
        const existingMessages = document.querySelectorAll('.message');
        existingMessages.forEach(msg => msg.remove());

        // Criar mensagem de redirecionamento
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message redirect';
        messageDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Redirecionando...';
        messageDiv.style.textAlign = 'center';
        messageDiv.style.fontSize = '1.2rem';
        messageDiv.style.padding = '2rem';

        // Inserir mensagem no topo da página
        const main = document.querySelector('main');
        if (main) {
            main.insertBefore(messageDiv, main.firstChild);
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
            // Verificar se localStorage está disponível (importante para mobile)
            if (typeof(Storage) === "undefined") {
                console.warn('localStorage não disponível');
                return {};
            }
            
            const saved = localStorage.getItem('linkzin_links');
            const links = saved ? JSON.parse(saved) : {};
            console.log('Links carregados do localStorage:', links);
            return links;
        } catch (error) {
            console.error('Erro ao carregar links:', error);
            // Tentar limpar e retornar objeto vazio em caso de erro
            try {
                localStorage.removeItem('linkzin_links');
            } catch (e) {
                console.error('Erro ao limpar localStorage:', e);
            }
            return {};
        }
    }

    // Salvar links no localStorage
    saveLinks() {
        try {
            // Verificar se localStorage está disponível
            if (typeof(Storage) === "undefined") {
                console.warn('localStorage não disponível - link não será salvo');
                return false;
            }
            
            const data = JSON.stringify(this.links);
            
            // Verificar tamanho dos dados (mobile tem limites menores)
            if (data.length > 4000000) { // 4MB - limite seguro para mobile
                console.warn('Dados muito grandes para localStorage');
                return false;
            }
            
            localStorage.setItem('linkzin_links', data);
            return true;
        } catch (error) {
            console.error('Erro ao salvar links:', error);
            
            // Tentar limpar espaço e salvar novamente
            if (error.name === 'QuotaExceededError' || error.code === 22) {
                console.warn('Quota do localStorage excedida, limpando links antigos...');
                try {
                    // Manter apenas os últimos 100 links
                    const linkKeys = Object.keys(this.links);
                    if (linkKeys.length > 100) {
                        const sortedKeys = linkKeys.sort((a, b) => {
                            const dateA = new Date(this.links[a].createdAt || 0);
                            const dateB = new Date(this.links[b].createdAt || 0);
                            return dateB - dateA;
                        });
                        const keysToKeep = sortedKeys.slice(0, 100);
                        const newLinks = {};
                        keysToKeep.forEach(key => {
                            newLinks[key] = this.links[key];
                        });
                        this.links = newLinks;
                        localStorage.setItem('linkzin_links', JSON.stringify(this.links));
                        return true;
                    }
                } catch (e) {
                    console.error('Erro ao salvar mesmo após limpeza:', e);
                }
            }
            
            return false;
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

    // Função para limpeza de links (desabilitada - links não expiram)
    cleanupOldLinks() {
        // Links não expiram mais - função mantida para compatibilidade
        // mas não executa nenhuma limpeza
        console.log('Limpeza de links desabilitada - links não expiram');
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
            this.showMessage('Link não encontrado.', 'error');
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
        this.whatsappLinks = this.loadWhatsAppLinks();
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

        // Adicionar listener para mudança do código do país
        const countryCodeSelect = document.getElementById('countryCode');
        if (countryCodeSelect) {
            countryCodeSelect.addEventListener('change', () => this.updateCountryFlag());
        }

        // Verificar se há redirecionamento de link WhatsApp
        this.checkForWhatsAppRedirect();
    }

    // Carregar links do WhatsApp do localStorage
    loadWhatsAppLinks() {
        try {
            // CORREÇÃO: Verificar se localStorage está disponível (importante para mobile)
            if (typeof(Storage) === "undefined") {
                console.warn('localStorage não disponível');
                return {};
            }
            
            let saved = localStorage.getItem('linkzin_whatsapp_links');
            
            // CORREÇÃO: Tentar carregar do sessionStorage se localStorage estiver vazio
            if (!saved) {
                console.log('Tentando carregar do sessionStorage...');
                saved = sessionStorage.getItem('linkzin_whatsapp_links');
                
                // Tentar também o backup
                if (!saved) {
                    saved = sessionStorage.getItem('linkzin_whatsapp_links_backup');
                }
            }
            
            if (!saved) {
                console.log('Nenhum link do WhatsApp salvo');
                return {};
            }
            
            const links = JSON.parse(saved);
            console.log('Links do WhatsApp carregados:', Object.keys(links).length, 'links');
            
            // CORREÇÃO: Validar e corrigir links com datas inválidas
            Object.keys(links).forEach(code => {
                const link = links[code];
                if (!link.expiresAt || isNaN(new Date(link.expiresAt).getTime())) {
                    console.warn(`Corrigindo data de expiração para link ${code}`);
                    link.expiresAt = new Date(Date.now() + (365 * 24 * 60 * 60 * 1000)).toISOString();
                }
            });
            
            return links;
        } catch (error) {
            console.error('Erro ao carregar links do WhatsApp:', error);
            // CORREÇÃO: Tentar limpar e retornar objeto vazio em caso de erro
            try {
                localStorage.removeItem('linkzin_whatsapp_links');
                sessionStorage.removeItem('linkzin_whatsapp_links');
            } catch (e) {
                console.error('Erro ao limpar storage:', e);
            }
            return {};
        }
    }

    // Salvar links do WhatsApp no localStorage
    saveWhatsAppLinks() {
        try {
            // CORREÇÃO: Verificar se localStorage está disponível
            if (typeof(Storage) === "undefined") {
                console.warn('localStorage não disponível - link não será salvo');
                return false;
            }
            
            // CORREÇÃO: Verificar espaço disponível no localStorage (importante para mobile)
            const data = JSON.stringify(this.whatsappLinks);
            
            // Verificar tamanho do dados (mobile tem limites menores)
            if (data.length > 4000000) { // 4MB - limite seguro para mobile
                console.warn('Dados muito grandes para localStorage');
                // Limpar links antigos para liberar espaço
                this.cleanupExpiredWhatsAppLinks();
            }
            
            localStorage.setItem('linkzin_whatsapp_links', data);
            console.log('Links do WhatsApp salvos com sucesso no localStorage');
            
            // CORREÇÃO: Salvar também no sessionStorage como backup (importante para mobile)
            try {
                sessionStorage.setItem('linkzin_whatsapp_links_backup', data);
                console.log('Backup salvo no sessionStorage');
            } catch (e) {
                console.warn('Falha ao salvar backup no sessionStorage:', e);
            }
            
            return true;
        } catch (error) {
            console.error('Erro ao salvar links do WhatsApp:', error);
            
            // CORREÇÃO: Tentar limpar espaço e salvar novamente
            if (error.name === 'QuotaExceededError' || error.code === 22) {
                console.warn('Quota do localStorage excedida, limpando links antigos...');
                try {
                    this.cleanupExpiredWhatsAppLinks();
                    localStorage.setItem('linkzin_whatsapp_links', JSON.stringify(this.whatsappLinks));
                    return true;
                } catch (e) {
                    console.error('Erro ao salvar mesmo após limpeza:', e);
                }
            }
            
            // CORREÇÃO: Fallback para sessionStorage se localStorage falhar
            try {
                console.warn('Tentando salvar no sessionStorage como fallback...');
                sessionStorage.setItem('linkzin_whatsapp_links', JSON.stringify(this.whatsappLinks));
                console.log('Links salvos no sessionStorage com sucesso');
                return true;
            } catch (e) {
                console.error('Erro ao salvar no sessionStorage também:', e);
            }
            
            return false;
        }
    }

    // Verificar se há redirecionamento de link WhatsApp
    checkForWhatsAppRedirect() {
        const urlParams = new URLSearchParams(window.location.search);
        const whatsappCode = urlParams.get('w');

        if (whatsappCode) {
            // CORREÇÃO: Aguardar um pouco para garantir que o localStorage carregou
            setTimeout(() => {
                this.redirectToWhatsApp(whatsappCode);
            }, 100);
        }
    }

    // Redirecionar para WhatsApp
    redirectToWhatsApp(whatsappCode) {
        console.log('Tentando redirecionar WhatsApp para código:', whatsappCode);
        
        const linkData = this.whatsappLinks[whatsappCode];

        if (linkData) {
            console.log('Link do WhatsApp encontrado:', linkData);

            // Verificar se o link não expirou (1 ano de validade)
            // CORREÇÃO: Melhorado para mobile com validação mais robusta
            try {
                const expirationDate = new Date(linkData.expiresAt);
                const now = new Date();
                
                // Validar se as datas são válidas
                if (isNaN(expirationDate.getTime())) {
                    console.warn('Data de expiração inválida, recriando...');
                    // Recriar data de expiração se estiver inválida
                    linkData.expiresAt = new Date(Date.now() + (365 * 24 * 60 * 60 * 1000)).toISOString();
                    this.saveWhatsAppLinks();
                }

                // Verificar expiração apenas se a data for válida
                if (!isNaN(expirationDate.getTime()) && now > expirationDate) {
                    console.error('Link do WhatsApp expirado');
                    this.showRedirectMessage('Este link do WhatsApp expirou (validade de 1 ano).', 'error');
                    return;
                }
            } catch (error) {
                console.error('Erro ao verificar expiração:', error);
                // Em caso de erro, continuar com o redirecionamento
            }

            // Incrementar contador de cliques
            try {
                linkData.clicks = (linkData.clicks || 0) + 1;
                this.saveWhatsAppLinks();
            } catch (error) {
                console.error('Erro ao salvar cliques:', error);
                // Continuar mesmo se não conseguir salvar
            }

            // Mostrar mensagem de redirecionamento
            this.showRedirectMessage('Redirecionando para o WhatsApp...', 'info');

            // Redirecionar para o WhatsApp
            console.log('Redirecionando para WhatsApp:', linkData.whatsappUrl);
            
            // CORREÇÃO: Reduzir delay em mobile para melhor experiência
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            const redirectDelay = isMobile ? 500 : 1000;
            
            setTimeout(() => {
                window.location.href = linkData.whatsappUrl;
            }, redirectDelay);
        } else {
            console.error('Link do WhatsApp não encontrado para código:', whatsappCode);
            this.showRedirectMessage('Link do WhatsApp não encontrado.', 'error');
        }
    }

    // Mostrar mensagem de redirecionamento
    showRedirectMessage(message, type = 'info') {
        // Remover mensagens anteriores
        const existingMessages = document.querySelectorAll('.redirect-message');
        existingMessages.forEach(msg => msg.remove());

        // Criar mensagem de redirecionamento
        const messageDiv = document.createElement('div');
        messageDiv.className = `redirect-message ${type}`;
        messageDiv.innerHTML = `
            <div style="text-align: center; padding: 3rem; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin: 2rem auto; max-width: 500px;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">
                    ${type === 'error' ? '❌' : '📱'}
                </div>
                <h2 style="color: #0a1a3f; margin-bottom: 1rem;">${type === 'error' ? 'Link Expirado' : 'Redirecionando...'}</h2>
                <p style="color: #666; font-size: 1.1rem; margin-bottom: 2rem;">${message}</p>
                ${type === 'error' ? `
                    <a href="whatsapp.html" style="background: #0a1a3f; color: white; padding: 0.8rem 1.5rem; text-decoration: none; border-radius: 8px; display: inline-block;">
                        Criar Novo Link
                    </a>
                ` : `
                    <div style="display: flex; justify-content: center; align-items: center; gap: 0.5rem;">
                        <div class="spinner" style="width: 20px; height: 20px; border: 2px solid #f3f3f3; border-top: 2px solid #25D366; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                        <span>Abrindo WhatsApp...</span>
                    </div>
                `}
            </div>
        `;

        // Adicionar estilos para o spinner
        if (!document.querySelector('#redirect-spinner-styles')) {
            const style = document.createElement('style');
            style.id = 'redirect-spinner-styles';
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }

        // Inserir mensagem no topo da página
        const main = document.querySelector('main');
        if (main) {
            main.insertBefore(messageDiv, main.firstChild);
        }
    }

    updateCountryFlag() {
        const countryCodeSelect = document.getElementById('countryCode');
        const flagIcon = document.querySelector('.flag-icon');

        if (!countryCodeSelect || !flagIcon) return;

        const countryFlags = {
            '+55': 'br',
            '+1': 'us',
            '+44': 'gb',
            '+33': 'fr',
            '+49': 'de',
            '+34': 'es',
            '+39': 'it',
            '+31': 'nl',
            '+351': 'pt',
            '+54': 'ar',
            '+56': 'cl',
            '+57': 'co',
            '+52': 'mx',
            '+51': 'pe',
            '+58': 've'
        };

        const selectedCode = countryCodeSelect.value;
        const countryCode = countryFlags[selectedCode] || 'br';

        flagIcon.src = `https://flagcdn.com/${countryCode}.svg`;
        flagIcon.alt = selectedCode;
    }

    generateWhatsAppLink() {
        const numberInput = document.getElementById('whatsappNumber');
        const messageInput = document.getElementById('whatsappMessage');
        const countryCodeSelect = document.getElementById('countryCode');
        const customLinkInput = document.getElementById('customLink');
        const resultDiv = document.getElementById('whatsappResult');

        if (!numberInput || !resultDiv) return;

        let number = numberInput.value.trim();
        const message = messageInput ? messageInput.value.trim() : '';
        const countryCode = countryCodeSelect ? countryCodeSelect.value : '+55';
        const customLink = customLinkInput ? customLinkInput.value.trim() : '';

        if (!number) {
            this.showMessage('Por favor, insira um número de telefone.', 'error');
            return;
        }

        // Limpar número (remover caracteres especiais)
        number = number.replace(/\D/g, '');

        // Remover código do país do número se já estiver presente
        const countryCodeNumber = countryCode.replace('+', '');
        if (number.startsWith(countryCodeNumber)) {
            number = number.substring(countryCodeNumber.length);
        }

        // Remover zeros à esquerda se necessário
        if (number.startsWith('0')) {
            number = number.substring(1);
        }

        // Combinar código do país com número
        const fullNumber = countryCodeNumber + number;

        // Criar link do WhatsApp
        let whatsappUrl = `https://wa.me/${fullNumber}`;
        if (message) {
            whatsappUrl += `?text=${encodeURIComponent(message)}`;
        }

        // Gerar código único para o link
        const whatsappCode = this.generateWhatsAppCode();
        
        // Criar URL curta do WhatsApp
        const shortWhatsappUrl = `${window.location.origin}${window.location.pathname}?w=${whatsappCode}`;

        // CORREÇÃO: Salvar link do WhatsApp com validade de 1 ano (data válida garantida)
        const now = Date.now();
        const oneYearInMs = 365 * 24 * 60 * 60 * 1000;
        const expirationDate = new Date(now + oneYearInMs);
        
        const linkData = {
            id: whatsappCode,
            whatsappUrl: whatsappUrl,
            shortUrl: shortWhatsappUrl,
            phoneNumber: fullNumber,
            message: message,
            countryCode: countryCode,
            customLink: customLink,
            createdAt: new Date(now).toISOString(),
            clicks: 0,
            expiresAt: expirationDate.toISOString() // 1 ano
        };

        console.log('Salvando link com expiração:', linkData.expiresAt);
        
        this.whatsappLinks[whatsappCode] = linkData;
        const saved = this.saveWhatsAppLinks();
        
        // CORREÇÃO: Verificar se salvou com sucesso
        if (!saved) {
            console.warn('Falha ao salvar no localStorage - link pode não persistir');
            this.showMessage('Link gerado, mas pode não ser salvo permanentemente no dispositivo.', 'warning');
        }

        // Calcular data de expiração para exibição
        const expirationDateFormatted = new Date(linkData.expiresAt).toLocaleDateString('pt-BR');
        const createdDate = new Date(linkData.createdAt).toLocaleDateString('pt-BR');

        // Exibir resultado
        resultDiv.innerHTML = `
            <div class="whatsapp-link-card">
                <h3>Link do WhatsApp Gerado</h3>
                <div class="whatsapp-link">
                    <input type="text" value="${shortWhatsappUrl}" readonly class="whatsapp-url-display">
                    <button type="button" class="copy-btn" onclick="whatsappGenerator.copyWhatsAppLink('${shortWhatsappUrl}')">
                        <i class="fas fa-copy"></i> Copiar
                    </button>
                </div>
                <div class="whatsapp-info">
                    <p><strong>Número:</strong> ${countryCode} ${number}</p>
                    <p><strong>Mensagem:</strong> ${message || 'Nenhuma mensagem'}</p>
                    <p><strong>Criado em:</strong> ${createdDate}</p>
                    <p><strong>Validade:</strong> <span class="expiration-ok">1 ano (expira em ${expirationDateFormatted})</span></p>
                    <p><strong>Cliques:</strong> 0</p>
                    <p><strong>Status:</strong> <span class="expiration-ok">✓ Link ativo</span></p>
                </div>
                <div class="whatsapp-actions">
                    <a href="${shortWhatsappUrl}" target="_blank" class="whatsapp-btn">
                        <i class="fab fa-whatsapp"></i> Testar Link
                    </a>
                    <button type="button" class="qr-btn" onclick="whatsappGenerator.generateWhatsAppQR('${shortWhatsappUrl}')">
                        <i class="fas fa-qrcode"></i> Gerar QR Code
                    </button>
                </div>
                <div id="whatsappQR" class="whatsapp-qr-container" style="display: none;"></div>
            </div>
        `;
        resultDiv.style.display = 'block';
    }

    // Gerar código único para link do WhatsApp
    generateWhatsAppCode() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        for (let i = 0; i < 8; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
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
                },
                // Configurações para garantir que o QR code seja permanente
                errorCorrectionLevel: 'H', // Nível mais alto de correção de erros
                version: 1, // Versão fixa para consistência
                maskPattern: 0 // Padrão de máscara fixo
            });

            // Adicionar informações sobre a permanência do QR code
            const infoDiv = document.createElement('div');
            infoDiv.className = 'whatsapp-qr-info';
            infoDiv.innerHTML = '<small style="color: #666; font-size: 12px; margin-top: 10px; display: block; text-align: center;">✓ QR Code permanente - não expira</small>';
            qrContainer.appendChild(infoDiv);

        } catch (error) {
            console.error('Erro ao gerar QR Code:', error);
        }
    }

    showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        messageDiv.textContent = message;

        const form = document.querySelector('.whatsapp-form-card');
        if (form) {
            form.insertBefore(messageDiv, form.firstChild);
        }

        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 5000);
    }

    // Limpar links do WhatsApp expirados
    cleanupExpiredWhatsAppLinks() {
        const now = new Date();
        let expiredCount = 0;

        Object.keys(this.whatsappLinks).forEach(code => {
            const linkData = this.whatsappLinks[code];
            const expirationDate = new Date(linkData.expiresAt);

            if (now > expirationDate) {
                delete this.whatsappLinks[code];
                expiredCount++;
            }
        });

        if (expiredCount > 0) {
            this.saveWhatsAppLinks();
            console.log(`${expiredCount} links do WhatsApp expirados foram removidos`);
        }
    }

    // Obter estatísticas de um link do WhatsApp
    getWhatsAppLinkStats(whatsappCode) {
        const linkData = this.whatsappLinks[whatsappCode];
        if (!linkData) return null;

        const expirationDate = new Date(linkData.expiresAt);
        const now = new Date();
        const isExpired = now > expirationDate;

        return {
            phoneNumber: linkData.phoneNumber,
            message: linkData.message,
            clicks: linkData.clicks,
            createdAt: linkData.createdAt,
            expiresAt: linkData.expiresAt,
            isExpired: isExpired,
            daysUntilExpiration: isExpired ? 0 : Math.ceil((expirationDate - now) / (1000 * 60 * 60 * 24))
        };
    }
}

// Inicializar aplicação baseada na página atual
document.addEventListener('DOMContentLoaded', function () {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const currentPath = window.location.pathname;

    console.log('Página atual:', currentPage);
    console.log('Caminho completo:', currentPath);

    // Verificar se estamos na página do contador
    if (currentPath.includes('/contador/') || currentPath.includes('/contador')) {
        console.log('Inicializando ClickCounter...');
        window.clickCounter = new ClickCounter();
    }
    // Verificar se estamos na página de desencurtar
    else if (currentPath.includes('/desencurtar/') || currentPath.includes('/desencurtar')) {
        console.log('Inicializando UrlUnshortener...');
        new UrlUnshortener();
    }
    // Verificar se estamos na página de QR Code
    else if (currentPath.includes('/qr-code/') || currentPath.includes('/qr-code')) {
        console.log('Inicializando QRCodeGenerator...');
        new QRCodeGenerator();
    }
    // Verificar se estamos na página do WhatsApp
    else if (currentPath.includes('/whatsapp/') || currentPath.includes('/whatsapp')) {
        console.log('Inicializando WhatsAppLinkGenerator...');
        window.whatsappGenerator = new WhatsAppLinkGenerator();
        // Limpar links do WhatsApp expirados a cada 24 horas
        setInterval(() => {
            window.whatsappGenerator.cleanupExpiredWhatsAppLinks();
        }, 24 * 60 * 60 * 1000); // A cada 24 horas
    }
    // Página principal ou outras páginas
    else {
        console.log('Inicializando LinkZin...');
        new LinkZin();
    }

    // Limpeza de links desabilitada - links não expiram mais
    // setInterval(() => {
    //     const linkZin = new LinkZin();
    //     linkZin.cleanupOldLinks();
    // }, 24 * 60 * 60 * 1000); // A cada 24 horas
});

// ===== PROTEÇÕES CONTRA CÓPIA DE CÓDIGO =====

// Proteção contra clique direito
document.addEventListener('contextmenu', function(e) {
    e.preventDefault();
    return false;
});

// Proteção contra seleção de texto
document.addEventListener('selectstart', function(e) {
    e.preventDefault();
    return false;
});

// Proteção contra arrastar elementos
document.addEventListener('dragstart', function(e) {
    e.preventDefault();
    return false;
});

// Proteção contra teclas de atalho (apenas em desktop)
// CORREÇÃO: Desabilitar em mobile para não interferir na experiência
const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

if (!isMobile) {
    document.addEventListener('keydown', function(e) {
        // F12 - DevTools
        if (e.key === 'F12') {
            e.preventDefault();
            return false;
        }
        
        // Ctrl+Shift+I - DevTools
        if (e.ctrlKey && e.shiftKey && e.key === 'I') {
            e.preventDefault();
            return false;
        }
        
        // Ctrl+Shift+C - Element Inspector
        if (e.ctrlKey && e.shiftKey && e.key === 'C') {
            e.preventDefault();
            return false;
        }
        
        // Ctrl+U - View Source
        if (e.ctrlKey && e.key === 'u') {
            e.preventDefault();
            return false;
        }
        
        // Ctrl+S - Save Page
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            return false;
        }
        
        // Ctrl+A - Select All - REMOVIDO para permitir seleção em inputs
        // if (e.ctrlKey && e.key === 'a') {
        //     e.preventDefault();
        //     return false;
        // }
        
        // Ctrl+C - Copy - REMOVIDO para permitir copiar links
        // if (e.ctrlKey && e.key === 'c') {
        //     e.preventDefault();
        //     return false;
        // }
        
        // Ctrl+X - Cut - REMOVIDO para permitir edição
        // if (e.ctrlKey && e.key === 'x') {
        //     e.preventDefault();
        //     return false;
        // }
        
        // Ctrl+P - Print
        if (e.ctrlKey && e.key === 'p') {
            e.preventDefault();
            return false;
        }
    });
}

// Proteção contra inspeção de elementos (apenas em desktop)
// CORREÇÃO: Desabilitar em mobile para não interferir na experiência
if (!isMobile) {
    let devtools = {open: false, orientation: null};
    const threshold = 160;

    setInterval(() => {
        // Verificar apenas se não estiver em mobile
        if (!isMobile && (window.outerHeight - window.innerHeight > threshold || 
            window.outerWidth - window.innerWidth > threshold)) {
            if (!devtools.open) {
                devtools.open = true;
                // Redirecionar ou mostrar aviso
                document.body.innerHTML = '<div style="display:flex;justify-content:center;align-items:center;height:100vh;background:#000;color:#fff;font-family:Arial,sans-serif;text-align:center;"><div><h1>🔒 Acesso Restrito</h1><p>O uso de ferramentas de desenvolvedor não é permitido neste site.</p><p>Por favor, feche as ferramentas de desenvolvedor para continuar.</p></div></div>';
            }
        } else {
            devtools.open = false;
        }
    }, 500);
}

// Proteção contra cópia de imagens
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.addEventListener('dragstart', function(e) {
            e.preventDefault();
            return false;
        });
        
        img.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
    });
});

// Proteção adicional contra console (apenas em desktop)
// CORREÇÃO: Desabilitar em mobile para não interferir na experiência
if (!isMobile) {
    (function() {
        let devtools = false;
        const element = new Image();
        Object.defineProperty(element, 'id', {
            get: function() {
                devtools = true;
                document.body.innerHTML = '<div style="display:flex;justify-content:center;align-items:center;height:100vh;background:#000;color:#fff;font-family:Arial,sans-serif;text-align:center;"><div><h1>🔒 Acesso Restrito</h1><p>O uso de ferramentas de desenvolvedor não é permitido neste site.</p></div></div>';
            }
        });
        console.log(element);
    })();
}

// Proteção contra debugger (apenas em desktop)
// CORREÇÃO: Desabilitar em mobile para não interferir na experiência
if (!isMobile) {
    setInterval(() => {
        debugger;
    }, 1000);
}

// Proteção contra cópia via CSS
document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = `
        * {
            -webkit-user-select: none !important;
            -moz-user-select: none !important;
            -ms-user-select: none !important;
            user-select: none !important;
            -webkit-touch-callout: none !important;
            -webkit-tap-highlight-color: transparent !important;
        }
        
        img {
            pointer-events: none !important;
            -webkit-user-drag: none !important;
            -khtml-user-drag: none !important;
            -moz-user-drag: none !important;
            -o-user-drag: none !important;
            user-drag: none !important;
        }
        
        input, textarea {
            -webkit-user-select: text !important;
            -moz-user-select: text !important;
            -ms-user-select: text !important;
            user-select: text !important;
        }
    `;
    document.head.appendChild(style);
}); 