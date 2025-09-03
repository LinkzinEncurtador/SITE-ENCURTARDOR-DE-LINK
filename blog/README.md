# Blog LinkZin

Este diretório contém as páginas do blog do LinkZin, implementadas conforme o design do Figma fornecido.

## 📁 Estrutura dos Arquivos

```
blog/
├── index.html                    # Página de listagem dos artigos
├── qr-codes-estaticos-vs-dinamicos.html  # Página individual do artigo
├── blog-styles.css              # Estilos específicos do blog
└── README.md                    # Este arquivo
```

## 🎯 Páginas Implementadas

### 1. Página de Listagem (`index.html`)
- **Navbar**: Menu superior fixo com logo e navegação
- **Ticker de Cotações**: Faixa horizontal com taxas de câmbio em movimento
- **Barra de Busca**: Campo de pesquisa para o blog
- **Hero Section**: Título principal e imagem destaque com blocos QR
- **Cards dos Artigos**: 3 cards em grid responsivo
- **Banner Promocional**: Oferta do Kit Social Media
- **Footer**: Rodapé padrão do site

### 2. Página Individual do Artigo (`qr-codes-estaticos-vs-dinamicos.html`)
- **Header**: Navbar e ticker de cotações
- **Cabeçalho do Artigo**: Título, autor, áudio player e botão voltar
- **Compartilhamento Social**: Ícones para redes sociais
- **Conteúdo**: Artigo completo com seções estruturadas
- **Ícones e Elementos Visuais**: ⚠️, ✔️, setas e outros elementos

## 🎨 Características do Design

### Ticker de Cotações
- Animação CSS contínua (marquee effect)
- Fundo branco com texto escuro
- Informações de taxas de câmbio em loop infinito
- Responsivo para diferentes tamanhos de tela

### Layout Responsivo
- Grid adaptativo para cards dos artigos
- Menu mobile com toggle
- Imagens responsivas
- Breakpoints: 768px (tablet) e 480px (mobile)

### Elementos Visuais
- **QR Codes Simulados**: Padrões CSS para representar QR codes
- **Ícones FontAwesome**: Ícones para funcionalidades
- **Cores do Brand**: Azul escuro (#0a1a3f) e amarelo (#ffcc00)
- **Sombras e Transições**: Efeitos hover e animações suaves

## 🔧 Funcionalidades Implementadas

### Áudio Player
- Botão play/pause
- Barra de progresso
- Controle de volume
- Tempo de reprodução

### Sistema de Busca
- Campo de pesquisa responsivo
- Botão de busca estilizado
- Placeholder informativo

### Compartilhamento Social
- Facebook, Twitter, WhatsApp
- Email e bookmark
- Menu de opções adicionais

### Navegação
- Links internos funcionais
- Botão "voltar" na página do artigo
- Menu ativo destacado

## 📱 Responsividade

O blog é totalmente responsivo com:
- Layout em grid que se adapta a diferentes telas
- Menu mobile com toggle
- Imagens que se ajustam ao container
- Textos que mantêm legibilidade em todos os dispositivos

## 🎯 Próximos Passos Sugeridos

1. **Implementar Funcionalidade de Busca**: Conectar com backend para pesquisa real
2. **Sistema de Áudio**: Implementar player de áudio funcional
3. **Compartilhamento Social**: Adicionar funcionalidade real de compartilhamento
4. **Sistema de Comentários**: Implementar seção de comentários
5. **API de Cotações**: Conectar ticker com API real de taxas de câmbio
6. **Sistema de Tags**: Adicionar categorização de artigos
7. **Paginação**: Implementar navegação entre páginas de artigos

## 🚀 Como Usar

1. Acesse `/blog/` para ver a listagem de artigos
2. Clique em qualquer card para ler o artigo completo
3. Use a barra de busca para encontrar conteúdo específico
4. Navegue pelo menu superior para acessar outras seções do site

## 📝 Notas Técnicas

- **CSS Variables**: Utiliza variáveis CSS para cores e tipografia
- **Flexbox/Grid**: Layout moderno com CSS Grid e Flexbox
- **FontAwesome**: Ícones vetoriais para melhor performance
- **Animações CSS**: Transições e keyframes para interatividade
- **SEO Friendly**: Estrutura HTML semântica e meta tags apropriadas

---

Desenvolvido para o LinkZin por Bruno Ulrich
© 2025 LinkZin - Ferramenta para encurtar link
