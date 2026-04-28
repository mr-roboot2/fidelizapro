# 🌐 API REST — FidelizaPro

Reference dos endpoints expostos pelo backend. Base: `/api/v1`.

---

## Sumário

- [Autenticação](#autenticação)
- [Endpoints públicos](#endpoints-públicos)
- [Endpoints autenticados (PWA)](#endpoints-autenticados-pwa)
- [Webhook PDV](#webhook-pdv)
- [Webhook Pagamento](#webhook-pagamento)
- [Códigos de erro](#códigos-de-erro)

---

## Autenticação

A API usa **Sanctum** com tokens Bearer.

### Como obter um token

Faça login com senha **ou** OTP:

```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "telefone": "(11) 99999-9999",
  "password": "123456",
  "empresa_slug": "pao-quente"
}
```

Resposta:

```json
{
  "token": "3|pmrTRPkItUp8pqmzgdJCf4F2D6kcMDbdQk...",
  "cliente": { ... },
  "empresa": { ... }
}
```

### Usar o token

```http
GET /api/v1/cliente/dashboard
Authorization: Bearer 3|pmrTRPkItUp8pqmzgdJCf4F2D6kcMDbdQk...
Accept: application/json
```

### Throttle

Endpoints públicos de auth: **10 req/min por IP**
Endpoints PDV: **60 req/min por IP**

---

## Endpoints públicos

### Listar empresas

```http
GET /api/v1/empresas
```

Resposta:
```json
{
  "empresas": [
    { "slug": "pao-quente", "nome": "Padaria Pão Quente", "logo": null, "cor_primaria": "#f59e0b" },
    ...
  ]
}
```

### Login com senha

```http
POST /api/v1/auth/login
```

**Body:**
```json
{
  "telefone": "(11) 99999-9999",
  "password": "123456",
  "empresa_slug": "pao-quente"
}
```

`empresa_slug` é opcional se o telefone for único. Se omitido e houver match em múltiplas empresas, retorna erro.

### Registrar novo cliente

```http
POST /api/v1/auth/registrar
```

**Body:**
```json
{
  "empresa_slug": "pao-quente",
  "nome": "João Silva",
  "telefone": "(11) 99999-9999",
  "email": "joao@email.com",
  "data_nascimento": "1990-05-15",
  "password": "minhasenha",
  "codigo_indicacao": "ABC12345"
}
```

`email`, `data_nascimento` e `codigo_indicacao` são opcionais.
Se `codigo_indicacao` for válido, o indicador ganha pontos automaticamente.

**Bônus de cadastro** é creditado se houver regra `tipo=cadastro` ativa.

Dispara automação `boas_vindas` se configurada.

### Solicitar OTP via WhatsApp

```http
POST /api/v1/auth/otp/solicitar
```

**Body:**
```json
{
  "telefone": "(11) 99999-9999",
  "empresa_slug": "pao-quente"
}
```

**Resposta:**
```json
{
  "message": "Código enviado via WhatsApp.",
  "expira_em_segundos": 300,
  "codigo_dev": "123456"
}
```

`codigo_dev` só vem em modo mock — facilita teste. Em produção retorna `null`.

**Throttle:** 3 códigos por telefone em 15 minutos.

### Validar OTP

```http
POST /api/v1/auth/otp/validar
```

**Body:**
```json
{
  "telefone": "(11) 99999-9999",
  "codigo": "123456",
  "empresa_slug": "pao-quente"
}
```

**Resposta:** mesmo formato do `/auth/login` (token + cliente + empresa).

Limites: 5 tentativas por código, código expira em 5 minutos.

---

## Endpoints autenticados (PWA)

Todos exigem header `Authorization: Bearer {token}`.

### Dados do cliente logado

```http
GET /api/v1/auth/me
```

```json
{
  "cliente": {
    "id": 9,
    "nome": "Maria Silva",
    "telefone": "(11) 91033-0108",
    "email": "maria@email.com",
    "pontos": 527.01,
    "cashback": 85.81,
    "codigo_qr": "CLI-LU5V0N80O7",
    "codigo_indicacao": "EA5OIPLV"
  },
  "empresa": { ... }
}
```

### Logout

```http
POST /api/v1/auth/logout
```

Revoga o token atual.

### Dashboard do cliente

```http
GET /api/v1/cliente/dashboard
```

```json
{
  "pontos": 527.01,
  "cashback": 85.81,
  "cashback_pendente": 12.50,
  "total_gasto": 4145.76,
  "total_compras": 50,
  "ultima_compra": "2026-04-25 18:40:09"
}
```

### Histórico de compras

```http
GET /api/v1/cliente/compras
```

```json
{
  "compras": [
    {
      "id": 234,
      "codigo": "PED-001",
      "data": "2026-04-25 18:40:09",
      "data_formatada": "25/04/2026 18:40",
      "valor": 99.90,
      "pontos_gerados": 99.90,
      "cashback_gerado": 2.00,
      "descricao": "Compra balcão"
    }
  ]
}
```

Retorna até 50 últimas.

### Extrato de pontos

```http
GET /api/v1/cliente/extrato
```

```json
{
  "transacoes": [
    {
      "id": 100,
      "data": "25/04/2026 18:40",
      "tipo": "credito",
      "origem": "compra",
      "pontos": 99.90,
      "saldo_posterior": 527.01,
      "descricao": "Pontos pela compra #234"
    }
  ]
}
```

### Atualizar perfil

```http
PUT /api/v1/cliente/perfil
```

```json
{
  "nome": "Maria Silva Santos",
  "email": "maria.santos@email.com",
  "data_nascimento": "1990-05-15",
  "aceita_whatsapp": true
}
```

Todos os campos opcionais — só envia o que quer atualizar.

### Catálogo de prêmios

```http
GET /api/v1/recompensas
```

```json
{
  "recompensas": [
    {
      "id": 1,
      "nome": "Pão Francês — 6 unidades",
      "descricao": "...",
      "imagem": "https://...",
      "custo_pontos": 100,
      "tipo": "produto",
      "valor_estimado": 5.00,
      "destaque": true,
      "estoque": 50,
      "disponivel": true,
      "pode_resgatar": true
    }
  ]
}
```

`pode_resgatar` é `true` se a recompensa está disponível **e** o cliente tem pontos suficientes.

### Listar resgates

```http
GET /api/v1/resgates
```

```json
{
  "resgates": [
    {
      "id": 21,
      "codigo": "RSG-W73KXZJU",
      "recompensa": "Pão Francês — 6 unidades",
      "pontos_usados": 100,
      "status": "pendente",
      "data": "25/04/2026 18:40",
      "aprovado_em": null,
      "entregue_em": null
    }
  ]
}
```

### Solicitar resgate

```http
POST /api/v1/resgates
```

```json
{
  "recompensa_id": 1,
  "observacao": "Retiro amanhã"
}
```

**Resposta:**
```json
{
  "message": "Resgate solicitado! Aguarde aprovação da empresa.",
  "resgate": {
    "id": 21,
    "codigo": "RSG-W73KXZJU",
    "pontos_usados": 100,
    "status": "pendente"
  },
  "novo_saldo_pontos": 427.01
}
```

**Limite antifraude:** 3 resgates por cliente em 24h.

### Listar parceiros e benefícios

```http
GET /api/v1/parceiros
```

```json
{
  "parceiros": [
    {
      "id": 1,
      "nome": "Auto Posto Central",
      "categoria": "Combustível",
      "logo": null,
      "endereco": "Rua X, 100",
      "telefone": "(11) 1234-5678",
      "site": null,
      "beneficios": [
        {
          "id": 1,
          "nome": "5% desconto na gasolina",
          "tipo_descricao": "5% de desconto",
          "valor": 5.00,
          "condicoes": "Limite 50L",
          "valido_ate": "27/10/2026",
          "destaque": true,
          "pode_resgatar": true,
          "restantes_para_voce": 1
        }
      ]
    }
  ]
}
```

### Gerar cupom de parceiro

```http
POST /api/v1/parceiros/cupons
```

```json
{
  "beneficio_id": 1
}
```

**Resposta:**
```json
{
  "message": "Cupom ativado!",
  "cupom": {
    "codigo": "ABC123XYZ0",
    "valido_ate": "26/05/2026 23:59",
    "beneficio": "5% desconto na gasolina",
    "parceiro": "Auto Posto Central"
  }
}
```

### Meus cupons

```http
GET /api/v1/parceiros/meus-cupons
```

```json
{
  "cupons": [
    {
      "id": 12,
      "codigo": "ABC123XYZ0",
      "beneficio": "5% desconto na gasolina",
      "parceiro": "Auto Posto Central",
      "tipo_descricao": "5% de desconto",
      "status": "disponivel",
      "utilizavel": true,
      "valido_ate": "26/05/2026 23:59",
      "usado_em": null
    }
  ]
}
```

### Indicações

```http
GET /api/v1/indicacoes
```

```json
{
  "codigo_indicacao": "EA5OIPLV",
  "link": "http://localhost/fidelizapro/public/app/?ref=EA5OIPLV",
  "total_indicacoes": 3,
  "total_convertidas": 1,
  "total_pontos_ganhos": 50.00,
  "indicacoes": [...]
}
```

### Indicar amigo

```http
POST /api/v1/indicacoes
```

```json
{
  "nome_indicado": "João Amigo",
  "telefone_indicado": "(11) 99999-1111"
}
```

### Pesquisa de satisfação

```http
POST /api/v1/pesquisas
```

```json
{
  "compra_id": 234,
  "nota": 5,
  "comentario": "Excelente atendimento!",
  "respostas": {
    "limpeza": "ótima",
    "atendimento": "ótimo"
  }
}
```

`compra_id` é opcional — se enviado, evita avaliação duplicada da mesma compra.

**Resposta:**
```json
{
  "message": "Obrigado! Você ganhou 10 pontos pela avaliação.",
  "pontos_creditados": 10,
  "novo_saldo_pontos": 537.01
}
```

Pontos só são creditados se houver regra `tipo=avaliacao` ativa.

---

## Webhook PDV

Para sistemas externos lançarem compras automaticamente.

```http
POST /api/v1/pdv/{empresa_slug}/compras
Content-Type: application/json
X-Pdv-Secret: sk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**Body:**
```json
{
  "telefone": "(11) 99999-1111",
  "valor": 150.50,
  "codigo": "PED-001",
  "descricao": "Pedido balcão",
  "desconto": 0,
  "meta": { "qualquer": "extra" }
}
```

**Identificação alternativa:** `cpf`, `codigo_qr`.

**Auto-cadastro:** envia `nome` + `telefone` se cliente não existir.

**Resposta:**
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

Detalhes em [`INTEGRACOES.md`](INTEGRACOES.md#pdv-externo).

---

## Webhook Pagamento

Endpoint para gateways de pagamento confirmarem cobranças.

```http
POST /webhook/pagamento/{gateway}
Content-Type: application/json
```

`{gateway}` = `asaas` (ou `mock` em dev).

O sistema:
1. Identifica a cobrança pelo `gateway_charge_id` ou `externalReference`
2. Atualiza status para `pago`
3. Avança o `proximo_vencimento` da assinatura em +1 mês
4. Marca assinatura como `ativa` (caso estivesse `inadimplente` ou `trial`)

**Eventos aceitos:**
- `PAYMENT_RECEIVED`
- `PAYMENT_CONFIRMED`

Outros eventos são logados e ignorados (HTTP 200).

---

## Códigos de erro

| HTTP | Significado |
|---|---|
| `200 OK` | Sucesso |
| `201 Created` | Recurso criado (registro novo) |
| `401 Unauthorized` | Token ausente, inválido ou expirado |
| `403 Forbidden` | Token válido mas sem permissão (ex: tentar acessar dado de outra empresa) |
| `404 Not Found` | Recurso não existe |
| `422 Unprocessable Entity` | Dados inválidos (ver `errors`) |
| `429 Too Many Requests` | Throttle estourou |
| `500 Internal Server Error` | Erro inesperado (ver logs) |

### Formato dos erros de validação (422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "telefone": ["Telefone já cadastrado."],
    "valor": ["O valor deve ser maior que 0."]
  }
}
```

### Formato de erros de domínio

```json
{
  "message": "Limite de 3 resgates em 24h atingido."
}
```

---

## 🔧 Testando a API

### Postman / Insomnia

Importe o arquivo `docs/postman_collection.json` (se disponível).

### cURL exemplo completo

```bash
# 1. Login
TOKEN=$(curl -s -X POST http://localhost/fidelizapro/public/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"telefone":"(11) 91033-0108","password":"123456","empresa_slug":"pao-quente"}' \
  | jq -r .token)

# 2. Dashboard
curl -s -H "Authorization: Bearer $TOKEN" \
  http://localhost/fidelizapro/public/api/v1/cliente/dashboard | jq

# 3. Solicitar resgate
curl -s -X POST http://localhost/fidelizapro/public/api/v1/resgates \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"recompensa_id":1}' | jq
```

---

## 📚 Próximos passos

- 📘 [`INSTALACAO.md`](INSTALACAO.md) — Instalação
- 🔌 [`INTEGRACOES.md`](INTEGRACOES.md) — WhatsApp, PDV, Asaas
