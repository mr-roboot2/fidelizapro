# 🔌 Guia de Integrações — FidelizaPro

Configurações de provedores externos: WhatsApp, PDV, gateway de pagamento.

---

## Sumário

- [WhatsApp (4 providers)](#whatsapp)
  - [Mock (dev)](#mock)
  - [Evolution API](#evolution-api)
  - [Z-API](#z-api)
  - [Meta Cloud API](#meta-cloud-api)
- [PDV externo](#pdv-externo)
  - [Webhook](#webhook-pdv)
  - [Importação CSV](#importação-csv)
- [Gateway de pagamento (SaaS)](#gateway-de-pagamento)
  - [Asaas](#asaas)
- [Cron / Agendamento](#cron--agendamento)

---

## WhatsApp

A configuração do provider é **por empresa**, em `/admin/whatsapp`.
Cada empresa pode usar um provider diferente.

### Mock

Modo dev. Não envia mensagem real — só registra no log:

```
storage/logs/laravel.log
[2026-04-26 18:41:05] local.INFO: [WhatsApp MOCK][pao-quente] → (11) 99999-9999: Mensagem...
```

Use durante desenvolvimento e testes.

### Evolution API

Open-source brasileira, self-hosted. Mais econômica para volumes médios.

📖 Docs: https://doc.evolution-api.com/

**Configuração no painel admin (`/admin/whatsapp`):**

| Campo | Valor |
|---|---|
| Provedor | `Evolution API` |
| API URL | `https://sua-instancia-evolution.com` (sem `/` final) |
| API Token | `apikey` da sua instância |
| Instance ID | nome da instância criada na Evolution |
| Ativo | ✅ |

**Setup da Evolution:**
1. Suba uma instância da Evolution API (Docker recomendado)
2. Crie uma instância de WhatsApp pelo painel deles
3. Conecte com seu número (escaneando QR)
4. Pegue a `apikey` e o `instance` name
5. Cole no FidelizaPro e clique em **Enviar teste**

### Z-API

Provedor brasileiro pago. Planos a partir de ~R$60/mês. Suporte em PT-BR.

📖 Docs: https://developer.z-api.io/

**Configuração:**

| Campo | Valor |
|---|---|
| Provedor | `Z-API` |
| API URL | `https://api.z-api.io` |
| API Token | `Client-Token` da sua conta |
| Instance ID | ID da instância (formato `3DAA...`) |
| Ativo | ✅ |

**Setup:**
1. Crie conta em https://app.z-api.io/
2. Crie uma instância e conecte seu WhatsApp
3. Pegue o **Instance ID** e **Token** no painel da Z-API
4. Cole no FidelizaPro e teste

### Meta Cloud API

API oficial do WhatsApp Business. Cobra por conversa. Requer cadastro completo no Business Manager.

📖 Docs: https://developers.facebook.com/docs/whatsapp/cloud-api

**Configuração:**

| Campo | Valor |
|---|---|
| Provedor | `Meta Cloud` |
| API Token | Bearer token (System User) |
| Phone Number ID | ID do número aprovado |
| Ativo | ✅ |

**Setup:**
1. Crie um app no Meta for Developers
2. Adicione produto **WhatsApp**
3. Verifique seu número de telefone
4. Crie um **System User** e gere token permanente
5. Pegue o **Phone Number ID** no painel
6. Em modo dev, só consegue enviar para números pré-aprovados na seção de teste

> ⚠️ **Atenção:** A Cloud API exige que mensagens fora da janela de 24h sejam **templates aprovados** pelo Meta. Para automações de marketing, considere usar Evolution ou Z-API.

---

## PDV externo

Duas formas de receber compras de um sistema externo:

### Webhook PDV

Endpoint REST autenticado por header secreto, configurado por empresa.

**URL:** `POST /api/v1/pdv/{slug}/compras`

**Header obrigatório:**
```http
X-Pdv-Secret: sk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

A secret é gerada automaticamente para cada empresa.
Veja em **Painel → Importação / PDV** ou consulte a coluna `pdv_secret` da tabela `empresas`.

**Body (JSON):**

```json
{
  "telefone": "(11) 99999-1111",
  "valor": 150.50,
  "codigo": "PED-001",
  "descricao": "Pedido balcão",
  "desconto": 0,
  "meta": {
    "numero_pedido": "001",
    "qualquer_dado": "extra"
  }
}
```

**Identificação do cliente** (1 dos 3 obrigatórios):
- `telefone` — formato livre, será buscado por match
- `cpf` — sem ou com máscara
- `codigo_qr` — código QR do cliente (`CLI-XXXXXXXX`)

**Auto-cadastro:** se enviar `nome` + `telefone` e o cliente não existir, ele é criado automaticamente.

**Resposta de sucesso (HTTP 201):**
```json
{
  "message": "Compra registrada via PDV.",
  "cliente_criado": false,
  "cliente": {
    "id": 9,
    "nome": "Maria Silva",
    "pontos_atual": 1500.00,
    "cashback_atual": 32.50
  },
  "compra": {
    "id": 234,
    "valor": 150.50,
    "pontos_gerados": 150.0,
    "cashback_gerado": 4.50,
    "created_at": "2026-04-26T18:40:09-03:00"
  }
}
```

**Erros:**
- `401` — secret inválido
- `404` — cliente não encontrado (e dados insuficientes para auto-cadastro)
- `422` — dados inválidos
- `429` — rate limit (60 req/min por IP)

**Exemplo cURL:**

```bash
curl -X POST https://seudominio.com/api/v1/pdv/pao-quente/compras \
  -H "Content-Type: application/json" \
  -H "X-Pdv-Secret: sk_xxxx..." \
  -d '{
    "telefone": "(11) 99999-1111",
    "valor": 150.50,
    "codigo": "PED-001"
  }'
```

### Importação CSV

Acesse **Painel → Importação / PDV** e faça upload de um CSV.

**Formato esperado:**

```csv
telefone,nome,cpf,valor,descricao,codigo
(11)99999-1111,Maria Silva,123.456.789-00,150.50,Almoço,PED001
(11)99999-2222,,,87.30,Compra balcão,
```

**Colunas:**
- **Obrigatórias:** `telefone`, `valor`
- **Opcionais:** `nome`, `cpf`, `descricao`, `codigo`

**Opção:** marque "Criar clientes novos automaticamente" se quiser que o sistema cadastre clientes ausentes (precisa coluna `nome`).

A tela mostra erros linha-a-linha após processar.

---

## Gateway de pagamento

Para cobrar mensalidade das empresas (modelo SaaS).

### Asaas

Gateway brasileiro recomendado. Suporta PIX, boleto e cartão de crédito.

📖 Docs: https://docs.asaas.com/

**Configuração:**

1. Crie conta em https://www.asaas.com/ (sandbox grátis)
2. Pegue sua API Key em **Integrações → API**
3. Adicione no `.env`:

```ini
ASAAS_API_KEY=$aact_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
ASAAS_ENV=sandbox
```

Em produção mude `ASAAS_ENV=production`.

**Configurar webhook do Asaas:**

No painel do Asaas, em **Integrações → Notificações Webhook**:

| Campo | Valor |
|---|---|
| URL | `https://seudominio.com/webhook/pagamento/asaas` |
| Eventos | `PAYMENT_RECEIVED`, `PAYMENT_CONFIRMED` |
| Versão | API v3 |

**Fluxo de uma assinatura:**

1. Super admin cria assinatura: `/super/assinaturas/criar`
2. Sistema chama Asaas para criar **customer** e **subscription**
3. Status inicial: `trial` (configurável, padrão 7 dias)
4. Após trial: gera **cobrança** mensal automaticamente
5. Cliente paga via PIX/boleto/cartão no link do Asaas
6. Asaas chama o webhook → cobrança vira `pago`, próximo vencimento +1 mês
7. Se não pagar até o vencimento → cron marca como `inadimplente`

### Mock (dev)

Em dev, use o gateway `mock`. Ele:
- Gera link fake (`/pagamento-mock/{cobranca_id}`)
- Tem uma tela com botão "Confirmar pagamento" para simular o ciclo completo

Útil pra testar todo o fluxo sem usar o sandbox do Asaas.

---

## Cron / Agendamento

O FidelizaPro tem 2 tarefas agendadas em `routes/console.php`:

| Comando | Frequência | Descrição |
|---|---|---|
| `cashback:liberar` | Diário 03:00 | Libera cashbacks pendentes |
| `automacoes:executar` | Diário 09:00 | Dispara automações de aniversário/inativos/pontos vencendo |

### Linux (recomendado em produção)

```bash
crontab -e
```

Adicione:

```cron
* * * * * cd /var/www/fidelizapro && php artisan schedule:run >> /dev/null 2>&1
```

O Laravel se encarrega de decidir o que rodar a cada minuto.

### Windows (Tarefas Agendadas)

1. Abra **Agendador de Tarefas**
2. **Criar tarefa**
3. Disparador: a cada 1 minuto
4. Ação: programa
   ```
   E:\xampp\php\php.exe E:\xampp\htdocs\fidelizapro\artisan schedule:run
   ```

### Shared hosting sem cron

Algumas hospedagens compartilhadas oferecem cron via painel (cPanel, Plesk).
Use a mesma linha `cd ... && php artisan schedule:run`.

Se não tiver cron, dispare manualmente quando precisar:

```bash
"/e/xampp/php/php.exe" artisan automacoes:executar
"/e/xampp/php/php.exe" artisan cashback:liberar
```

Ou crie um endpoint protegido que execute via HTTP — não recomendado para produção.

---

## 🔍 Verificando integrações

### Health-check de WhatsApp

No painel `/admin/whatsapp` há um botão "Enviar teste" que dispara uma mensagem para o número informado. Confira:
- Se chegou no WhatsApp do destinatário (em modo real)
- Se foi registrado em `storage/logs/laravel.log` (em modo mock)

### Health-check de PDV

```bash
curl -s http://localhost/fidelizapro/public/api/v1/pdv/pao-quente/compras \
  -H "X-Pdv-Secret: <secret>" \
  -H "Content-Type: application/json" \
  -d '{"telefone":"(11) 91033-0108","valor":1.00,"descricao":"teste"}'
```

Resposta esperada: HTTP 201 com JSON.

### Health-check do Asaas

Crie uma assinatura mock primeiro pra ver o fluxo completo. Depois mude para `gateway=asaas` em uma assinatura nova e verifique se o customer/subscription aparece no painel do Asaas (sandbox).

---

## 📚 Próximos passos

- 🌐 [`API.md`](API.md) — Reference completa da API REST
- 📘 [`INSTALACAO.md`](INSTALACAO.md) — Voltar para instalação
