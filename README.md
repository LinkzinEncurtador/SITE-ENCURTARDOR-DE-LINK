# LinkZin - Encurtador de Links

Um site completo para encurtamento de links com funcionalidades avançadas, similar ao Bitly, desenvolvido com HTML, CSS e JavaScript puro.

## 🚀 Funcionalidades

### Principais
- **Encurtamento de URLs**: Transforme URLs longas em links curtos e fáceis de compartilhar
- **Contador de Cliques**: Acompanhe o desempenho dos seus links em tempo real
- **Desencurtar URLs**: Descubra a URL original de links encurtados
- **Gerador de QR Code**: Crie QR codes para qualquer URL
- **Gerador de Link WhatsApp**: Crie links diretos para o WhatsApp com mensagem personalizada

### Recursos Adicionais
- **Design Responsivo**: Compatível com desktop, tablet e dispositivos móveis
- **Armazenamento Local**: Links salvos no localStorage do navegador
- **Interface Moderna**: Design limpo e intuitivo
- **Segurança**: Verificação automática de URLs maliciosas
- **Estatísticas**: Análise detalhada do desempenho dos links

## 🎨 Design

- **Cores**: Azul marinho (#0a1a3f) e amarelo (#ffcc00)
- **Logo**: LOGO_LINKZIN_005.png integrado no cabeçalho e rodapé
- **Tipografia**: Segoe UI para melhor legibilidade
- **Ícones**: Font Awesome para ícones modernos
- **Animações**: Transições suaves e efeitos hover

## 📁 Estrutura do Projeto

```
SITE ENCURTARDOR DE LINK/
├── index.html              # Página principal
├── contador.html           # Contador de cliques
├── desencurtar.html        # Desencurtador de URLs
├── qr-code.html            # Gerador de QR Code
├── whatsapp.html           # Gerador de link WhatsApp
├── recursos.html           # Página de recursos
├── contato.html            # Página de contato
├── termos.html             # Termos de serviço
├── privacidade.html        # Política de privacidade
├── denunciar.html          # Denúncia de URLs
├── styles.css              # Estilos CSS
├── script.js               # JavaScript principal
├── LOGO_LINKZIN_005.png    # Logo principal
└── README.md               # Documentação
```

## 🛠️ Tecnologias Utilizadas

- **HTML5**: Estrutura semântica e acessível
- **CSS3**: Estilos modernos com Flexbox e Grid
- **JavaScript ES6+**: Funcionalidades interativas
- **Font Awesome**: Ícones
- **QRCode.js**: Geração de QR codes
- **reCAPTCHA**: Proteção contra spam

## 🚀 Como Usar

### Instalação Local
1. Clone ou baixe o projeto
2. Abra o arquivo `index.html` em seu navegador
3. Todas as funcionalidades funcionam offline

### Deploy no GitHub Pages
1. Faça upload dos arquivos para um repositório GitHub
2. Vá em Settings > Pages
3. Selecione a branch main como fonte
4. O site estará disponível em `https://seu-usuario.github.io/seu-repositorio`

## 📱 Funcionalidades Detalhadas

### Encurtamento de Links
- Validação automática de URLs
- Geração de códigos únicos de 6 caracteres
- QR code automático para cada link
- Botão de copiar para área de transferência
- Download do QR code

### Contador de Cliques
- Verificação de estatísticas por link
- Exibição de total de cliques
- Data de criação do link
- URL original

### Desencurtador
- Descoberta da URL original
- Verificação de segurança
- Botão de copiar URL original

### Gerador de QR Code
- QR codes personalizáveis
- Download em alta qualidade
- Cores personalizadas (azul marinho e branco)

### Gerador de Link WhatsApp
- Suporte a números brasileiros e internacionais
- Mensagem pré-definida opcional
- QR code integrado
- Botão de abrir WhatsApp

## 🔧 Configuração

### Firebase (Opcional)
Para persistência de dados em nuvem, descomente e configure as linhas no `script.js`:

```javascript
const firebaseConfig = {
    apiKey: "sua-api-key",
    authDomain: "seu-projeto.firebaseapp.com",
    projectId: "seu-projeto",
    storageBucket: "seu-projeto.appspot.com",
    messagingSenderId: "123456789",
    appId: "seu-app-id"
};
```

### reCAPTCHA
Substitua a chave do reCAPTCHA nas páginas de contato e denúncia:

```html
<div class="g-recaptcha" data-sitekey="SUA_CHAVE_AQUI"></div>
```

## 📊 Armazenamento

### LocalStorage
- Links encurtados são salvos localmente
- Limpeza automática de links antigos (30 dias)
- Dados persistentes entre sessões

### Estrutura dos Dados
```javascript
{
    "shortCode": {
        "id": "shortCode",
        "originalUrl": "https://exemplo.com/url-muito-longa",
        "shortUrl": "https://linkzin.com/?r=shortCode",
        "createdAt": "2025-01-01T00:00:00.000Z",
        "clicks": 0
    }
}
```

## 🎯 Casos de Uso

### Marketing Digital
- Campanhas publicitárias
- Redes sociais
- Email marketing
- QR codes para materiais impressos

### Negócios
- Atendimento ao cliente
- Vendas e suporte
- Cartões de visita digitais
- Catálogos online

### Educação
- Compartilhamento de recursos
- Materiais didáticos
- Comunicação com alunos
- Apresentações

### Eventos
- Convites digitais
- Inscrições
- Informações do evento
- Credenciais

## 🔒 Segurança

- Validação de URLs
- Proteção contra spam
- Verificação de links maliciosos
- Sistema de denúncias
- reCAPTCHA em formulários

## 📈 Performance

- Carregamento rápido
- Otimização de imagens
- CSS e JavaScript minificados
- Cache local eficiente
- Responsividade otimizada

## 🌐 Compatibilidade

### Navegadores
- Chrome (recomendado)
- Firefox
- Safari
- Edge

### Dispositivos
- Desktop
- Tablet
- Mobile

## 📞 Suporte

Para suporte ou dúvidas:
- **Email**: contato@linkzin.com.br
- **Página de Contato**: [linkzin.com.br/contato](contato.html)

## 📄 Licença

Este projeto foi desenvolvido por Bruno Ulrich. Todos os direitos reservados.

## 🚀 Roadmap

### Próximas Funcionalidades
- [ ] Autenticação de usuários
- [ ] Links personalizados
- [ ] Analytics avançados
- [ ] API pública
- [ ] Integração com redes sociais
- [ ] Links com expiração
- [ ] Campanhas de marketing
- [ ] Integração com Google Analytics

### Melhorias Técnicas
- [ ] PWA (Progressive Web App)
- [ ] Service Workers
- [ ] Cache offline
- [ ] Notificações push
- [ ] Compressão de imagens
- [ ] Lazy loading

## 🤝 Contribuição

Contribuições são bem-vindas! Para contribuir:

1. Faça um fork do projeto
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Push para a branch
5. Abra um Pull Request

## 📝 Changelog

### v1.0.0 (Janeiro 2025)
- ✅ Encurtamento de links
- ✅ Contador de cliques
- ✅ Desencurtador de URLs
- ✅ Gerador de QR Code
- ✅ Gerador de link WhatsApp
- ✅ Design responsivo
- ✅ Sistema de denúncias
- ✅ Páginas legais (Termos e Privacidade)
- ✅ Formulário de contato com reCAPTCHA

---

**Desenvolvido com ❤️ por Bruno Ulrich** 