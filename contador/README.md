# Contador de Cliques - LinkZin

## Como Funciona

O contador de cliques é uma ferramenta que permite verificar quantos cliques um link encurtado pelo LinkZin recebeu.

## Funcionalidades

- ✅ **Contagem de Cliques**: Mostra o total de cliques de cada link
- ✅ **Data de Criação**: Exibe quando o link foi criado
- ✅ **URL Original**: Mostra a URL original que foi encurtada
- ✅ **Interface Intuitiva**: Design simples e fácil de usar
- ✅ **Validação de URLs**: Verifica se o link é válido do LinkZin

## Como Usar

### 1. Encurte um Link
- Vá para a [página principal](/) do LinkZin
- Cole a URL que deseja encurtar
- Clique em "Encurtar Link"
- Copie o link encurtado gerado

### 2. Verifique os Cliques
- Volte para esta página do contador
- Cole o link encurtado no campo de entrada
- Clique em "Verificar Cliques"
- Veja as estatísticas do link

## Estrutura dos Dados

Cada link encurtado armazena:
```javascript
{
  "shortCode": {
    "id": "shortCode",
    "originalUrl": "https://exemplo.com",
    "shortUrl": "https://linkzin.com/?r=shortCode",
    "createdAt": "2025-01-15T10:30:00.000Z",
    "clicks": 42
  }
}
```

## Como Funciona Tecnicamente

### 1. Extração do Código
- O sistema extrai o código curto do parâmetro `?r=` na URL
- Exemplo: `https://linkzin.com/?r=abc123` → código: `abc123`

### 2. Busca no Armazenamento
- O código é usado para buscar os dados do link no localStorage
- Se encontrado, as estatísticas são exibidas

### 3. Exibição dos Resultados
- Total de cliques
- Data de criação (formatada em português brasileiro)
- URL original

## Armazenamento

- **Local**: Os dados são salvos no localStorage do navegador
- **Persistente**: Os dados permanecem entre sessões
- **Sem Expiração**: Links não expiram automaticamente

## Compatibilidade

- ✅ Todos os navegadores modernos
- ✅ Desktop, tablet e mobile
- ✅ Funciona offline (dados locais)

## Exemplo de Uso

1. **Link Encurtado**: `https://linkzin.com/?r=abc123`
2. **Cole no contador** e clique em "Verificar Cliques"
3. **Resultado**:
   - Total de Cliques: 15
   - Criado em: 15/01/2025
   - URL Original: https://exemplo.com

## Solução de Problemas

### Link não encontrado
- Verifique se o link foi realmente criado pelo LinkZin
- Certifique-se de que o link está completo (incluindo `?r=`)
- Tente acessar o link primeiro para garantir que existe

### Erro de validação
- O link deve estar no formato correto do LinkZin
- Deve conter o parâmetro `?r=` seguido do código

### Dados não atualizados
- Os cliques são contados em tempo real
- Recarregue a página se necessário
- Verifique se não há problemas no localStorage

## Desenvolvimento

### Arquivos Principais
- `index.html` - Interface do usuário
- `../script.js` - Lógica do contador (classe ClickCounter)
- `../styles.css` - Estilos da página

### Classe ClickCounter
```javascript
class ClickCounter {
  constructor() {
    this.linkZin = new LinkZin();
    this.init();
  }
  
  checkClicks() {
    // Lógica para verificar cliques
  }
}
```

### Inicialização Automática
A classe é inicializada automaticamente quando a página carrega, detectando que está na pasta `/contador/`.

## Suporte

Para dúvidas ou problemas:
- [Página de Contato](../contato/)
- [Termos de Serviço](../termos/)
- [Política de Privacidade](../privacidade/)
