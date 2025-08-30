# Sistema de Validade de Links do WhatsApp - LinkZin

## Visão Geral

O sistema de geração de links do WhatsApp do LinkZin agora inclui uma funcionalidade de validade de **1 ano** para todos os links criados. Isso garante que os links permaneçam ativos por um período determinado e sejam automaticamente removidos após a expiração.

## Funcionalidades Implementadas

### 1. Validade de 1 Ano
- Todos os links do WhatsApp criados têm validade de **365 dias**
- A data de expiração é calculada automaticamente na criação
- Links expirados são automaticamente removidos do sistema

### 2. Armazenamento Local
- Os links são armazenados no `localStorage` do navegador
- Chave de armazenamento: `linkzin_whatsapp_links`
- Estrutura de dados inclui todas as informações necessárias

### 3. Sistema de Redirecionamento
- Links curtos no formato: `whatsapp.html?w=CODIGO_UNICO`
- Verificação automática de validade antes do redirecionamento
- Contador de cliques para cada link

### 4. Limpeza Automática
- Sistema de limpeza automática a cada 24 horas
- Remove links expirados automaticamente
- Mantém o sistema otimizado

## Estrutura de Dados

```javascript
{
  "CODIGO_UNICO": {
    "id": "CODIGO_UNICO",
    "whatsappUrl": "https://wa.me/5511999999999?text=Olá",
    "shortUrl": "https://linkzin.com/whatsapp.html?w=CODIGO_UNICO",
    "phoneNumber": "5511999999999",
    "message": "Olá",
    "countryCode": "+55",
    "customLink": "",
    "createdAt": "2025-01-15T10:30:00.000Z",
    "clicks": 0,
    "expiresAt": "2026-01-15T10:30:00.000Z"
  }
}
```

## Fluxo de Funcionamento

### 1. Criação do Link
1. Usuário insere número e mensagem
2. Sistema gera código único de 8 caracteres
3. Cria link curto com parâmetro `?w=CODIGO`
4. Salva dados com data de expiração (1 ano)
5. Exibe informações completas incluindo validade

### 2. Acesso ao Link
1. Usuário acessa link curto
2. Sistema verifica se link existe
3. Verifica se não expirou
4. Incrementa contador de cliques
5. Redireciona para WhatsApp com mensagem

### 3. Expiração
1. Sistema verifica validade automaticamente
2. Links expirados são removidos
3. Usuário recebe mensagem de erro se tentar acessar link expirado

## Interface do Usuário

### Informações Exibidas
- **Número**: Número de telefone formatado
- **Mensagem**: Mensagem personalizada
- **Criado em**: Data de criação
- **Validade**: Data de expiração (1 ano)
- **Cliques**: Contador de acessos
- **Status**: Indicador visual de link ativo

### Mensagens de Redirecionamento
- **Link Ativo**: Mostra spinner e redireciona automaticamente
- **Link Expirado**: Exibe erro com opção de criar novo link

## Benefícios

### Para Usuários
- **Segurança**: Links não ficam ativos indefinidamente
- **Controle**: Sabem exatamente quando o link expira
- **Estatísticas**: Podem acompanhar o uso dos links
- **Organização**: Sistema automático de limpeza

### Para o Sistema
- **Performance**: Remove dados desnecessários automaticamente
- **Armazenamento**: Otimiza uso do localStorage
- **Manutenção**: Sistema autogerenciável

## Configurações Técnicas

### Validade
- **Duração**: 365 dias (1 ano)
- **Cálculo**: `Date.now() + (365 * 24 * 60 * 60 * 1000)`
- **Formato**: ISO 8601

### Limpeza
- **Frequência**: A cada 24 horas
- **Método**: Verificação automática em background
- **Ação**: Remoção de links expirados

### Códigos
- **Comprimento**: 8 caracteres
- **Caracteres**: A-Z, a-z, 0-9
- **Unicidade**: Verificação automática

## Compatibilidade

- **Navegadores**: Todos os navegadores modernos
- **Dispositivos**: Desktop, tablet e mobile
- **Armazenamento**: localStorage (sem necessidade de servidor)

## Manutenção

O sistema é completamente automático e não requer intervenção manual. A limpeza de links expirados acontece automaticamente, mantendo o sistema otimizado e funcional.

---

**Desenvolvido por Bruno Ulrich - LinkZin**
