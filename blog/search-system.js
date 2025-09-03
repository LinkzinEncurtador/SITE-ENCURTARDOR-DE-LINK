// Sistema de Pesquisa do Blog LinkZin
class BlogSearch {
    constructor() {
        this.searchIndex = this.createSearchIndex();
        this.searchInput = null;
        this.searchButton = null;
        this.resultsContainer = null;
        this.currentSearch = '';
        this.searchTimeout = null;
        this.init();
    }

    // Cria o índice de pesquisa com todas as páginas do blog
    createSearchIndex() {
        return [
            {
                title: "QR Codes: Estáticos vs Dinâmicos",
                url: "qr-codes-estaticos-vs-dinamicos.html",
                keywords: [
                    "qr code", "qr codes", "código qr", "códigos qr", "estático", "dinâmico", 
                    "estratégia digital", "empresa", "negócio", "marketing", "tecnologia",
                    "smartphone", "escaneamento", "link", "url", "website", "digital",
                    "estático vs dinâmico", "diferença", "vantagem", "desvantagem",
                    "código de barras", "barcode", "mobile", "móvel", "app", "aplicativo",
                    "estratégia", "empresarial", "inovação", "moderno", "contemporâneo"
                ],
                description: "Descubra as diferenças entre QR Codes Estáticos e Dinâmicos e como escolher a melhor opção para sua estratégia digital.",
                category: "Tecnologia"
            },
            {
                title: "Desencurtar um Link",
                url: "desencurtar-um-link.html",
                keywords: [
                    "desencurtar", "link", "url", "encurtador", "bitly", "tinyurl",
                    "segurança", "phishing", "malware", "vírus", "fraude", "scam",
                    "verificar", "checar", "análise", "proteção", "cybersecurity",
                    "internet", "navegação", "browser", "navegador", "web", "online",
                    "clique", "clicar", "endereço", "site", "página", "verificação",
                    "seguro", "confiável", "transparente", "honesto", "verdadeiro"
                ],
                description: "Descubra para onde o link realmente leva antes de clicar e proteja-se de ameaças online.",
                category: "Segurança"
            },
            {
                title: "Hardening de Servidores",
                url: "hardening-de-servidores.html",
                keywords: [
                    "hardening", "servidor", "servidores", "segurança", "cybersecurity",
                    "proteção", "defesa", "firewall", "antivírus", "malware", "hacker",
                    "ataque", "vulnerabilidade", "patch", "atualização", "backup",
                    "infraestrutura", "ti", "tecnologia", "empresa", "negócio", "digital",
                    "serviços", "web", "aplicação", "sistema", "linux", "windows", "unix",
                    "rede", "protocolo", "criptografia", "autenticação", "autorização"
                ],
                description: "Proteção para negócios digitais através de técnicas avançadas de segurança de servidores.",
                category: "Segurança"
            }
        ];
    }

    // Inicializa o sistema de pesquisa
    init() {
        this.searchInput = document.querySelector('.search-input');
        this.searchButton = document.querySelector('.search-btn');
        
        if (this.searchInput && this.searchButton) {
            this.setupEventListeners();
            this.createResultsContainer();
            this.addSearchSuggestions();
        }
    }

    // Configura os event listeners
    setupEventListeners() {
        // Pesquisa ao pressionar Enter
        this.searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.performSearch();
            }
        });

        // Pesquisa ao clicar no botão
        this.searchButton.addEventListener('click', (e) => {
            e.preventDefault();
            this.performSearch();
        });

        // Pesquisa em tempo real com debounce
        this.searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            this.currentSearch = query;
            
            // Limpa timeout anterior
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }
            
            // Pesquisa após 300ms de inatividade
            if (query.length >= 2) {
                this.searchTimeout = setTimeout(() => {
                    if (this.currentSearch === query) {
                        this.performSearch(query);
                    }
                }, 300);
            } else {
                this.hideResults();
            }
        });

        // Foca no input ao pressionar Ctrl+K
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                this.searchInput.focus();
            }
        });

        // Fecha resultados ao clicar fora
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-container') && !e.target.closest('.search-results')) {
                this.hideResults();
            }
        });

        // Foca no input ao clicar na barra de pesquisa
        this.searchInput.addEventListener('click', () => {
            if (this.currentSearch.length >= 2) {
                this.showResults();
            }
        });
    }

    // Cria o container de resultados
    createResultsContainer() {
        const searchContainer = document.querySelector('.search-container');
        if (searchContainer) {
            this.resultsContainer = document.createElement('div');
            this.resultsContainer.className = 'search-results';
            this.resultsContainer.style.cssText = `
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 1000;
                max-height: 400px;
                overflow-y: auto;
                display: none;
                margin-top: 5px;
            `;
            
            // Adiciona estilos para tornar o container relativo
            searchContainer.style.position = 'relative';
            searchContainer.appendChild(this.resultsContainer);
        }
    }

    // Adiciona sugestões de pesquisa
    addSearchSuggestions() {
        const suggestions = [
            "QR Code", "Segurança", "Servidores", "Links", "Tecnologia",
            "Empresa", "Digital", "Marketing", "Cybersecurity"
        ];
        
        this.searchInput.addEventListener('focus', () => {
            if (!this.searchInput.value) {
                this.showSuggestions(suggestions);
            }
        });
    }

    // Mostra sugestões de pesquisa
    showSuggestions(suggestions) {
        if (!this.resultsContainer) return;
        
        this.resultsContainer.innerHTML = `
            <div style="padding: 15px;">
                <div style="font-size: 12px; color: #999; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">
                    <i class="fas fa-lightbulb" style="margin-right: 5px;"></i>
                    Sugestões de pesquisa
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    ${suggestions.map(suggestion => `
                        <span class="search-suggestion" 
                              style="background: #f8f9fa; padding: 5px 10px; border-radius: 15px; font-size: 12px; cursor: pointer; border: 1px solid #e9ecef; transition: all 0.2s;"
                              onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#002147'"
                              onmouseout="this.style.background='#f8f9fa'; this.style.borderColor='#e9ecef'"
                              onclick="document.querySelector('.search-input').value='${suggestion}'; this.closest('.search-results').style.display='none';">
                            ${suggestion}
                        </span>
                    `).join('')}
                </div>
            </div>
        `;
        
        this.resultsContainer.style.display = 'block';
    }

    // Executa a pesquisa
    performSearch(query = null) {
        const searchTerm = query || this.searchInput.value.trim();
        
        if (searchTerm.length < 2) {
            this.hideResults();
            return;
        }

        this.currentSearch = searchTerm;
        const results = this.searchInIndex(searchTerm);
        this.displayResults(results, searchTerm);
        
        // Adiciona ao histórico de pesquisa
        this.addToSearchHistory(searchTerm);
    }

    // Pesquisa no índice
    searchInIndex(searchTerm) {
        const results = [];
        const searchLower = searchTerm.toLowerCase();
        
        this.searchIndex.forEach(page => {
            let score = 0;
            let matches = [];
            
            // Pesquisa no título
            if (page.title.toLowerCase().includes(searchLower)) {
                score += 10;
                matches.push('título');
            }
            
            // Pesquisa nas palavras-chave
            page.keywords.forEach(keyword => {
                if (keyword.toLowerCase().includes(searchLower)) {
                    score += 5;
                    matches.push('palavra-chave');
                }
                if (searchLower.includes(keyword.toLowerCase())) {
                    score += 3;
                    matches.push('palavra-chave');
                }
            });
            
            // Pesquisa na descrição
            if (page.description.toLowerCase().includes(searchLower)) {
                score += 2;
                matches.push('descrição');
            }
            
            // Pesquisa na categoria
            if (page.category.toLowerCase().includes(searchLower)) {
                score += 4;
                matches.push('categoria');
            }
            
            if (score > 0) {
                results.push({ 
                    ...page, 
                    score,
                    matches: [...new Set(matches)] // Remove duplicatas
                });
            }
        });
        
        // Ordena por relevância
        return results.sort((a, b) => b.score - a.score);
    }

    // Exibe os resultados
    displayResults(results, searchTerm) {
        if (!this.resultsContainer) return;
        
        if (results.length === 0) {
            this.resultsContainer.innerHTML = `
                <div class="search-result-item" style="padding: 20px; text-align: center; color: #666;">
                    <i class="fas fa-search" style="font-size: 24px; margin-bottom: 10px; color: #ddd; display: block;"></i>
                    <div style="font-size: 14px; margin-bottom: 5px;">Nenhum resultado encontrado para</div>
                    <div style="font-weight: bold; color: #002147;">"${searchTerm}"</div>
                    <div style="font-size: 12px; margin-top: 10px; color: #999;">
                        Tente usar termos diferentes ou verificar a ortografia
                    </div>
                </div>
            `;
        } else {
            this.resultsContainer.innerHTML = `
                <div style="padding: 10px 15px; background: #f8f9fa; border-bottom: 1px solid #e9ecef; font-size: 12px; color: #666;">
                    <i class="fas fa-search" style="margin-right: 5px;"></i>
                    ${results.length} resultado${results.length > 1 ? 's' : ''} encontrado${results.length > 1 ? 's' : ''} para "${searchTerm}"
                </div>
                ${results.map(result => `
                    <div class="search-result-item" style="padding: 15px; border-bottom: 1px solid #eee; cursor: pointer; transition: all 0.2s;" 
                         onmouseover="this.style.backgroundColor='#f8f9fa'; this.style.transform='translateX(2px)'" 
                         onmouseout="this.style.backgroundColor='white'; this.style.transform='translateX(0)'"
                         onclick="window.location.href='${result.url}'">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                            <div style="font-weight: bold; color: #002147; font-size: 16px; flex: 1;">
                                ${this.highlightSearchTerm(result.title, searchTerm)}
                            </div>
                            <span style="background: #002147; color: white; padding: 2px 8px; border-radius: 12px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px;">
                                ${result.category}
                            </span>
                        </div>
                        <div style="font-size: 14px; color: #666; margin-bottom: 8px; line-height: 1.4;">
                            ${this.highlightSearchTerm(result.description, searchTerm)}
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="font-size: 11px; color: #999; text-transform: uppercase; letter-spacing: 0.5px;">
                                <i class="fas fa-tags" style="margin-right: 3px;"></i>
                                ${result.matches.join(', ')}
                            </div>
                            <div style="font-size: 12px; color: #002147; font-weight: 500;">
                                <i class="fas fa-external-link-alt" style="margin-right: 5px;"></i>
                                Abrir artigo
                            </div>
                        </div>
                    </div>
                `).join('')}
            `;
        }
        
        this.resultsContainer.style.display = 'block';
    }

    // Destaca o termo pesquisado no texto
    highlightSearchTerm(text, searchTerm) {
        if (!searchTerm) return text;
        
        const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return text.replace(regex, '<mark style="background-color: #ffd700; padding: 1px 3px; border-radius: 3px; font-weight: bold;">$1</mark>');
    }

    // Adiciona ao histórico de pesquisa
    addToSearchHistory(searchTerm) {
        let history = JSON.parse(localStorage.getItem('blogSearchHistory') || '[]');
        history = history.filter(term => term !== searchTerm); // Remove duplicatas
        history.unshift(searchTerm); // Adiciona no início
        history = history.slice(0, 10); // Mantém apenas os últimos 10
        localStorage.setItem('blogSearchHistory', JSON.stringify(history));
    }

    // Mostra resultados existentes
    showResults() {
        if (this.resultsContainer && this.currentSearch.length >= 2) {
            this.resultsContainer.style.display = 'block';
        }
    }

    // Oculta os resultados
    hideResults() {
        if (this.resultsContainer) {
            this.resultsContainer.style.display = 'none';
        }
    }
}

// Inicializa o sistema de pesquisa quando a página carregar
document.addEventListener('DOMContentLoaded', () => {
    new BlogSearch();
});

// Função global para pesquisa externa (pode ser chamada de outros lugares)
function searchBlog(query) {
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.value = query;
        const searchSystem = new BlogSearch();
        searchSystem.performSearch(query);
    }
}

// Função para mostrar histórico de pesquisa
function showSearchHistory() {
    const history = JSON.parse(localStorage.getItem('blogSearchHistory') || '[]');
    if (history.length > 0) {
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.focus();
            const searchSystem = new BlogSearch();
            searchSystem.showSuggestions(history);
        }
    }
}
