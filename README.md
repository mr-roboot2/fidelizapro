# FidelizaPro

> **Sistema SaaS multitenancy de fidelização de clientes — PWA, multi-empresa, com gateway de pagamento e auditoria completa.**

[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

---

## ✨ Visão geral

FidelizaPro é uma plataforma **SaaS** que empresas usam para criar seu próprio programa de fidelidade, com app instalável (PWA), pontos, cashback, prêmios, parceiros e cobrança recorrente automatizada.

Cada empresa tem:
- Seu **portal cliente PWA** (instalável no celular, com cores e logo próprios — _white label_).
- Painel administrativo completo com dashboard, métricas, gráficos.
- Configuração própria de pontuação, cashback, regras e prêmios.
- Integrações com WhatsApp (4 providers), PDV externo, importação CSV.

E o **operador do SaaS** tem:
- Painel super admin com métricas globais (MRR, empresas, clientes).
- Cobrança recorrente (Asaas / Mock para dev).
- Logs de auditoria de tudo.
- Gestão de planos e limites por empresa.

---

## 📋 Módulos

| Módulo | O que entrega |
|---|---|
| **Portal Cliente PWA** | Login (senha ou OTP via WhatsApp), saldo de pontos/cashback, histórico, catálogo de prêmios, resgate, indicação de amigos, pesquisa de satisfação, QR Code do cliente, cupons de parceiros, instalável no celular |
| **Painel Admin** | Dashboard com métricas + gráficos, caixa rápido (PDV web), CRUD de clientes/compras/recompensas/regras, aprovação de resgates, gestão de cashback, campanhas WhatsApp, automações agendadas, parceiros, antifraude, importação CSV, configurações |
| **Sistema de Pontos** | Regras configuráveis (R$ → pontos), multiplicadores por faixa, bônus (aniversário/indicação/cadastro/avaliação), validade configurável, ledger completo |
| **Cashback** | % configurável por empresa, período de confirmação opcional (ex: liberar após 30d), saldo disponível vs pendente, ajuste manual |
| **Resgate de Prêmios** | Catálogo com imagem/estoque/validade, fluxo aprovação→entrega→cancelamento com estorno automático |
| **WhatsApp** | 4 drivers: Mock (dev), Evolution API, Z-API, Meta Cloud. Campanhas com segmentação + automações agendadas (boas-vindas, aniversário, inativos, pontos vencendo, pós-compra, agradecimento de resgate) |
| **Integrações PDV** | Webhook autenticado (`X-Pdv-Secret`), importação CSV manual, lançamento manual no caixa |
| **Parceiros** | Cadastro de parceiros + benefícios + cupons únicos por cliente. Tela pública de validação para o parceiro escanear/digitar e dar baixa |
| **White Label PWA** | URL única `/app/{slug}/` com manifest, ícone, cores e título próprios. App instalável com identidade da empresa |
| **Antifraude** | Rate limiting nas APIs, limites de resgate por cliente/24h, IP tracking, monitor de atividade suspeita |
| **Super Admin** | Dashboard global, CRUD empresas, gestão de usuários, impersonate, planos, assinaturas (MRR), auditoria |
| **SaaS / Cobrança** | Drivers: Mock (dev) + Asaas (Brasil). Trial, cobrança recorrente, webhook de pagamento, status (trial/ativa/inadimplente/cancelada) |
| **Auditoria** | Trait `Auditavel` em models críticos, registro automático de create/update/delete + login/logout, visualização com diff antes/depois |

---

## 🛠️ Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.2+, Laravel 11 |
| Banco | MySQL 5.7+ (InnoDB, utf8mb4) |
| Auth | Laravel Sanctum (API) + Session (web) |
| Painel | Blade + Tailwind CSS (CDN) + Alpine.js + Chart.js |
| PWA | Vanilla JS, Service Worker, Web Manifest dinâmico |
| QR Code | qrcode.js |
| Hospedagem | XAMPP (dev) — qualquer PHP host (prod) |

**Por que sem build step?** Tailwind via CDN + JS vanilla = começa a funcionar em 1 minuto, sem npm/Vite. Em produção pode-se trocar por build otimizado.

---

## 🚀 Quick start

```bash
cd /e/xampp/htdocs/fidelizapro
"/e/xampp/php/php.exe" composer.phar install
cp .env.example .env
"/e/xampp/php/php.exe" artisan key:generate
# Crie o banco "fidelizapro" no phpMyAdmin
"/e/xampp/php/php.exe" artisan migrate --seed
"/e/xampp/php/php.exe" artisan storage:link
```

Acesse:

- **Painel admin:** http://localhost/fidelizapro/public/admin/login
- **PWA cliente (genérico):** http://localhost/fidelizapro/public/app/
- **PWA white label:** http://localhost/fidelizapro/public/app/pao-quente/

> Setup completo em [`docs/INSTALACAO.md`](docs/INSTALACAO.md) — passo a passo com troubleshooting, instalação do Composer, configuração de XAMPP e do gateway Asaas.

---

## 🔐 Acessos de teste (criados pelos seeders)

### Painel admin
| E-mail | Senha | Empresa | Tipo |
|---|---|---|---|
| `super@fidelizapro.com` | `password` | — | **Super Admin** (gestão do SaaS) |
| `admin@pao-quente.com` | `password` | Padaria Pão Quente | Admin |
| `admin@beleza-cia.com` | `password` | Salão Beleza & Cia | Admin |
| `admin@sabor-da-casa.com` | `password` | Restaurante Sabor da Casa | Admin |

### PWA cliente
- Use qualquer telefone listado em **Clientes** no painel admin
- Senha padrão: `123456`
- Ou login por WhatsApp OTP (em modo mock o código aparece na tela)

---

## 📁 Estrutura do projeto

```
fidelizapro/
├── app/
│   ├── Console/Commands/         # cashback:liberar, automacoes:executar
│   ├── Http/Controllers/
│   │   ├── Admin/                # Painel da empresa (15+ controllers)
│   │   ├── Api/                  # API REST (PWA + PDV + OTP)
│   │   ├── Auth/                 # Login web
│   │   ├── SuperAdmin/           # Painel SaaS (planos, assinaturas, auditoria)
│   │   ├── Pwa*Controller        # PWA white label
│   │   ├── Webhook*Controller    # Webhooks externos
│   │   └── ParceiroPublico*      # Tela pública de validação de cupom
│   ├── Http/Middleware/          # AdminAuth, SuperAdminAuth, EmpresaScope
│   ├── Models/                   # 20+ models Eloquent
│   │   └── Concerns/Auditavel    # Trait que audita create/update/delete
│   └── Services/                 # Camada de regras de negócio
│       ├── PontuacaoService
│       ├── CashbackService
│       ├── CompraService
│       ├── ResgateService
│       ├── CupomService
│       ├── AutomacaoService
│       ├── WhatsappService
│       ├── PlanoLimiteService
│       ├── AssinaturaService
│       ├── AuditoriaService
│       ├── Whatsapp/             # Drivers (Mock, Evolution, Zapi, MetaCloud)
│       └── Pagamento/            # Drivers (Mock, Asaas)
├── database/
│   ├── migrations/               # 20+ migrations
│   └── seeders/                  # Dados fictícios (3 empresas, 60 clientes)
├── public/
│   ├── app/                      # PWA cliente (HTML/JS/SW estáticos)
│   └── index.php                 # Front controller Laravel
├── resources/views/
│   ├── auth/                     # Login
│   ├── layouts/                  # admin, super
│   ├── admin/                    # Painel da empresa
│   ├── super/                    # Painel SaaS
│   ├── pwa/                      # White label PWA
│   ├── parceiro_publico/         # Validação de cupom
│   └── pagamento_mock/           # Mock de pagamento (dev)
├── routes/
│   ├── web.php                   # Admin, super admin, públicas
│   ├── api.php                   # API REST (PWA + PDV)
│   └── console.php               # Comandos artisan + cron
└── docs/                         # Documentação detalhada
```

---

## 🌐 URLs principais

| Tipo | URL | Descrição |
|---|---|---|
| Admin | `/admin/login` | Login único (redireciona conforme role) |
| Admin | `/admin` | Dashboard da empresa |
| Admin | `/admin/caixa` | Caixa rápido (lançar compra em 1 tela) |
| Admin | `/admin/automacoes` | Configurar automações WhatsApp |
| Admin | `/admin/parceiros` | Gerenciar parceiros e benefícios |
| Admin | `/admin/atividade-suspeita` | Monitor antifraude |
| Admin | `/admin/meu-plano` | Plano atual + consumo vs limites |
| Super | `/super` | Dashboard global (MRR, ranking) |
| Super | `/super/empresas` | CRUD empresas (multitenancy) |
| Super | `/super/planos` | CRUD planos do SaaS |
| Super | `/super/assinaturas` | Gestão de assinaturas e cobranças |
| Super | `/super/auditoria` | Logs de auditoria |
| PWA | `/app/` | App genérico (escolhe empresa) |
| PWA | `/app/{slug}/` | App white label (empresa específica) |
| Público | `/parceiro/{secret}` | Tela do parceiro validar cupom |
| API | `/api/v1/*` | Endpoints REST (ver [`docs/API.md`](docs/API.md)) |
| Webhook | `/api/v1/pdv/{slug}/compras` | Receber compras de PDV externo |
| Webhook | `/webhook/pagamento/{gateway}` | Receber confirmação de pagamento |

---

## 🧩 Integrações suportadas

### WhatsApp (4 providers)
- **Mock** — log em arquivo (dev)
- **Evolution API** — open-source, self-hosted
- **Z-API** — provedor brasileiro pago
- **Meta Cloud API** — oficial do WhatsApp Business

Configuração em `/admin/whatsapp` por empresa. Detalhes em [`docs/INTEGRACOES.md`](docs/INTEGRACOES.md).

### Pagamento (gateway SaaS)
- **Mock** — gera link fake, pra dev
- **Asaas** — gateway brasileiro com PIX/boleto/cartão (recomendado)

Configuração via `.env` (`ASAAS_API_KEY`).

### PDV externo
- Webhook REST autenticado por `X-Pdv-Secret` (única por empresa)
- Importação CSV manual via painel
- Auto-cadastro de cliente se enviar `nome` + `telefone`

---

## ⏰ Tarefas agendadas

Configurado em `routes/console.php`:

- **`cashback:liberar`** — diariamente 03:00 — libera cashbacks pendentes que passaram do prazo de confirmação
- **`automacoes:executar`** — diariamente 09:00 — dispara automações em batch (aniversário, clientes inativos, pontos vencendo)

Em produção, configure o cron:

```cron
* * * * * cd /caminho/fidelizapro && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🗄️ Modelo de dados (resumo)

```
empresas (multitenancy raiz)
├── plano_id              → planos
├── assinatura            → assinaturas → cobrancas
├── users                 (admins/atendentes)
├── clientes
│   ├── compras
│   ├── transacoes_pontos (ledger)
│   ├── movimentos_cashback (ledger, com período de confirmação)
│   ├── resgates → recompensas
│   ├── indicacoes
│   └── cupons → beneficios → parceiros
├── regras_pontuacao
├── recompensas
├── parceiros → beneficios → cupons
├── automacoes → automacao_logs
├── campanhas → campanha_envios
├── otp_codigos
└── pesquisas_satisfacao

(globais, sem empresa)
auditoria_logs
planos
```

---

## 🛡️ Segurança

- **Sanctum** para tokens API com escopo
- **Throttle** em endpoints críticos (10 req/min em auth, 60/min em PDV)
- **Limite de resgates** (3/24h por cliente) configurável
- **OTP** com expiração 5min, max 3 códigos/15min, 5 tentativas/código
- **Rate limit** baseado em IP
- **Antifraude**: tela de monitoramento de IPs compartilhados, cadastros em rajada, resgates suspeitos
- **Auditoria completa** com diff antes/depois de toda mutação em models críticos

---

## 📚 Documentação detalhada

- 📘 [`docs/INSTALACAO.md`](docs/INSTALACAO.md) — Instalação passo a passo (XAMPP, Composer, banco, troubleshooting)
- 🔌 [`docs/INTEGRACOES.md`](docs/INTEGRACOES.md) — Configurar WhatsApp, Asaas, PDV externo, cron
- 🌐 [`docs/API.md`](docs/API.md) — Reference completa da API REST

---

## 🗺️ Roadmap entregue

| Sprint | Status | Itens |
|---|---|---|
| **Sprint 0** | ✅ | Skeleton Laravel, PWA, painel admin, super admin, multitenancy |
| **Sprint 1** | ✅ | Caixa rápido, webhook PDV, importação CSV, pontos por avaliação, cashback com confirmação |
| **Sprint 2** | ✅ | Login OTP via WhatsApp, automações agendadas, drivers WhatsApp reais |
| **Sprint 3** | ✅ | Parceiros + cupons, white label PWA, antifraude |
| **Sprint 4** | ✅ | Auditoria, planos com limites, gateway de pagamento (Asaas) |

---

## 📜 Licença

MIT
