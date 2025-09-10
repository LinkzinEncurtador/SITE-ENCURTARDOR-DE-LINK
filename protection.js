// ===== PROTEÇÃO ANTI-COPY - CARREGAMENTO PRIORITÁRIO =====

// Proteção imediata contra DevTools
(function() {
    'use strict';
    
    // Detectar abertura de DevTools
    let devtools = false;
    const threshold = 160;
    
    setInterval(() => {
        if (window.outerHeight - window.innerHeight > threshold || 
            window.outerWidth - window.innerWidth > threshold) {
            if (!devtools) {
                devtools = true;
                // Limpar página e mostrar aviso
                document.documentElement.innerHTML = `
                    <head>
                        <title>Acesso Restrito - LinkZin</title>
                        <style>
                            body { 
                                margin: 0; 
                                padding: 0; 
                                background: #000; 
                                color: #fff; 
                                font-family: Arial, sans-serif; 
                                display: flex; 
                                justify-content: center; 
                                align-items: center; 
                                height: 100vh; 
                                text-align: center; 
                            }
                            .container { max-width: 500px; padding: 20px; }
                            h1 { color: #ffd700; margin-bottom: 20px; }
                            p { line-height: 1.6; margin-bottom: 15px; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <h1>🔒 Acesso Restrito</h1>
                            <p>O uso de ferramentas de desenvolvedor não é permitido neste site.</p>
                            <p>Por favor, feche as ferramentas de desenvolvedor e recarregue a página para continuar.</p>
                            <p><strong>LinkZin - Encurtador de Links</strong></p>
                        </div>
                    </body>
                `;
            }
        } else {
            devtools = false;
        }
    }, 100);
    
    // Proteção contra console
    const noop = () => {};
    Object.defineProperty(window, 'console', {
        value: {
            log: noop,
            warn: noop,
            error: noop,
            info: noop,
            debug: noop,
            trace: noop,
            dir: noop,
            group: noop,
            groupEnd: noop,
            time: noop,
            timeEnd: noop,
            count: noop,
            clear: noop,
            assert: noop
        },
        writable: false,
        configurable: false
    });
    
    // Proteção contra debugger
    setInterval(() => {
        if (devtools) {
            debugger;
        }
    }, 1000);
    
    // Proteção contra eval
    window.eval = function() {
        throw new Error('eval() is disabled');
    };
    
    // Proteção contra Function constructor
    window.Function = function() {
        throw new Error('Function constructor is disabled');
    };
    
})();

// Proteção contra clique direito global
document.addEventListener('DOMContentLoaded', function() {
    // Bloquear clique direito
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    });
    
    // Bloquear seleção de texto
    document.addEventListener('selectstart', function(e) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    });
    
    // Bloquear arrastar
    document.addEventListener('dragstart', function(e) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    });
    
    // Bloquear teclas de atalho
    document.addEventListener('keydown', function(e) {
        // Lista de teclas/combinações bloqueadas
        const blockedKeys = [
            'F12', // DevTools
            'F11', // Fullscreen (pode abrir DevTools)
        ];
        
        const blockedCombinations = [
            { ctrl: true, shift: true, key: 'I' }, // DevTools
            { ctrl: true, shift: true, key: 'C' }, // Element Inspector
            { ctrl: true, shift: true, key: 'J' }, // Console
            { ctrl: true, key: 'u' }, // View Source
            { ctrl: true, key: 's' }, // Save Page
            { ctrl: true, key: 'a' }, // Select All
            { ctrl: true, key: 'c' }, // Copy
            { ctrl: true, key: 'v' }, // Paste
            { ctrl: true, key: 'x' }, // Cut
            { ctrl: true, key: 'p' }, // Print
            { ctrl: true, key: 'f' }, // Find
            { ctrl: true, key: 'g' }, // Find Next
            { ctrl: true, key: 'h' }, // Find & Replace
        ];
        
        // Verificar teclas simples
        if (blockedKeys.includes(e.key)) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        
        // Verificar combinações
        for (let combo of blockedCombinations) {
            if (e.ctrlKey === combo.ctrl && 
                e.shiftKey === combo.shift && 
                e.key.toLowerCase() === combo.key.toLowerCase()) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }
    });
    
    // Proteção contra cópia de imagens
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        // Remover eventos de arrastar
        img.addEventListener('dragstart', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Remover menu de contexto
        img.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Adicionar overlay invisível
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: transparent;
            z-index: 1;
            pointer-events: none;
        `;
        img.style.position = 'relative';
        img.appendChild(overlay);
    });
    
    // Proteção contra inspeção via CSS
    const protectionStyle = document.createElement('style');
    protectionStyle.textContent = `
        * {
            -webkit-user-select: none !important;
            -moz-user-select: none !important;
            -ms-user-select: none !important;
            user-select: none !important;
            -webkit-touch-callout: none !important;
            -webkit-tap-highlight-color: transparent !important;
        }
        
        input, textarea {
            -webkit-user-select: text !important;
            -moz-user-select: text !important;
            -ms-user-select: text !important;
            user-select: text !important;
        }
        
        img {
            pointer-events: none !important;
            -webkit-user-drag: none !important;
            -khtml-user-drag: none !important;
            -moz-user-drag: none !important;
            -o-user-drag: none !important;
            user-drag: none !important;
            -webkit-touch-callout: none !important;
        }
        
        /* Proteção adicional contra seleção */
        ::selection {
            background: transparent !important;
        }
        
        ::-moz-selection {
            background: transparent !important;
        }
    `;
    document.head.appendChild(protectionStyle);
});

// Proteção contra iframe embedding
if (window.top !== window.self) {
    window.top.location = window.self.location;
}

// Proteção contra hotlinking de imagens
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.addEventListener('error', function() {
            this.style.display = 'none';
        });
    });
});

console.log('🔒 Proteções de segurança ativadas - LinkZin');
