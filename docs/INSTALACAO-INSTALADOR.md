# Instalação via instalador web (`/install`)

Guia rápido para subir o FidelizaPro em VPS com **CloudPanel + nginx**, usando o instalador web `/install`. É o caminho mais curto — 3 comandos no SSH + um wizard de 4 etapas no navegador.

> Para a referência completa do servidor (hardening, SSL, cron, atualizações), veja [`DEPLOY-CLOUDPANEL.md`](DEPLOY-CLOUDPANEL.md). Este aqui é só o fluxo de instalação inicial.

---

## Sumário

- [Pré-requisitos](#pré-requisitos)
- [1. Criar o site no CloudPanel](#1-criar-o-site-no-cloudpanel)
- [2. Criar o banco de dados](#2-criar-o-banco-de-dados)
- [3. Subir o código (como usuário do site)](#3-subir-o-código-como-usuário-do-site)
- [4. Rodar o `install.sh`](#4-rodar-o-installsh)
- [5. Apontar o "Diretório raiz" para `/public`](#5-apontar-o-diretório-raiz-para-public)
- [6. Cloudflare (se estiver usando)](#6-cloudflare-se-estiver-usando)
- [7. Abrir `/install` no navegador](#7-abrir-install-no-navegador)
- [8. Pós-instalação](#8-pós-instalação)
- [Troubleshooting](#troubleshooting)

---

## Pré-requisitos

| | |
|---|---|
| VPS | Ubuntu 22.04+ ou Debian 12, 2 vCPU / 2 GB RAM mínimo |
| CloudPanel | versão 2.x instalada, painel acessível em `https://SEU_IP:8443` |
| Domínio | DNS (registro A) já apontando pro IP da VPS |
| Acesso | SSH como `root` + senha do site user gerada pelo CloudPanel |

---

## 1. Criar o site no CloudPanel

No painel: **Sites → + Adicionar site → Criar um site PHP**.

| Campo | Valor |
|---|---|
| Nome de domínio | `seudominio.com.br` |
| Versão PHP | **8.3** |
| Aplicação | **Generic** ⚠️ |
| Usuário do site | `seusite` (vira o dono dos arquivos e do PHP-FPM) |
| Senha | gera com o gerador (anota) |

> ⚠️ **Não use o template "WordPress"** — ele coloca **Varnish** na frente do nginx, e Varnish quebra Laravel (CSRF, sessão de login, PWA). Se já criou no template errado, apague e recrie como **Generic**.

---

## 2. Criar o banco de dados

No painel do site: **Bancos de dados → + Adicionar banco**.

| Campo | Valor |
|---|---|
| Nome do banco | `seusite` |
| Usuário | `seusite_user` |
| Senha | gera (e **anota** — vai precisar no wizard) |

> O CloudPanel **não** adiciona prefixo automático no nome do banco — fica exatamente como você digitou.

---

## 3. Subir o código (como usuário do site)

⚠️ **Não clone como `root`.** O CloudPanel isola tudo no usuário do site. Se clonar como root, todos os arquivos ficam com owner `root:root` e o pool PHP-FPM (que roda como `seusite`) **não consegue ler/escrever** — vai dar erro 500 em tudo.

```bash
ssh root@SEU_IP
su - seusite                                      # vira o usuário do site
cd ~/htdocs/seudominio.com.br
rm -f index.html index.php                        # remove o placeholder do CloudPanel
git clone https://github.com/mr-roboot2/fidelizapro.git .
```

> O `.` no final do `git clone` é importante: clona o repositório dentro do diretório atual em vez de criar uma subpasta.

### Caso já tenha clonado como root

Sai do shell do `seusite`, vira root e corrige o owner:

```bash
exit                                              # volta pra root
chown -R seusite:seusite /home/seusite/htdocs/seudominio.com.br
su - seusite
cd ~/htdocs/seudominio.com.br
```

---

## 4. Rodar o `install.sh`

Ainda como `seusite`:

```bash
chmod +x install.sh && ./install.sh
```

Esse script faz, em ordem:

1. Copia `.env.example` → `.env` (se não existir)
2. Ajusta permissões: `chmod -R 775 storage bootstrap/cache`
3. Roda `composer install --no-dev --optimize-autoloader`
4. Gera a `APP_KEY`

No final mostra a URL de acesso ao instalador (ex.: `https://seudominio.com.br/install`).

> Se reclamar `composer: command not found`, rode: `php8.3 /usr/bin/composer install --no-dev --optimize-autoloader && php artisan key:generate`

---

## 5. Apontar o "Diretório raiz" para `/public`

Laravel serve a partir de `public/`, mas o CloudPanel aponta pra raiz do htdocs por padrão.

No painel: **Sites → seu site → Definições → Configurações de domínio → Diretório raiz**.

Mude de:
```
seudominio.com.br
```
para:
```
seudominio.com.br/public
```

Clica **Salvar**. Embaixo do campo deve passar a mostrar `/home/seusite/htdocs/seudominio.com.br/public`. O nginx é recarregado automaticamente.

---

## 6. Cloudflare (se estiver usando)

Se o domínio passa pelo Cloudflare (registro A com nuvem laranja), você **precisa** ajustar o modo SSL antes de continuar — caso contrário, vai dar `ERR_TOO_MANY_REDIRECTS` no `/install`.

No dash do Cloudflare:

1. Seleciona o domínio `seudominio.com.br`
2. Menu lateral: **SSL/TLS → Visão geral**
3. Clica em **Configurar**
4. Marca **Completo** (= Full em inglês)
5. **Salvar**

> Por que: o vhost do CloudPanel força redirect HTTP→HTTPS. Em modo **Flexível**, o Cloudflare conversa HTTPS com o navegador e HTTP com a origem — a origem responde 301 pra HTTPS, e o ciclo entra em loop infinito. **Completo** faz Cloudflare conversar HTTPS com a origem também.
>
> Mais tarde, depois de gerar Let's Encrypt no CloudPanel, mude para **Completo (estrito)** — é mais seguro.

---

## 7. Abrir `/install` no navegador

> Use **janela anônima** (Ctrl+Shift+N). O Chrome cacheia 301 de forma agressiva — se você tentou antes do Cloudflare estar correto, o navegador continua redirecionando do cache local sem nem bater no servidor.

Abre:
```
https://seudominio.com.br/install
```

O wizard tem **4 etapas**:

### Etapa 1 — Requisitos
Verifica PHP 8.2+, extensões (bcmath, ctype, curl, dom, fileinfo, gd, mbstring, openssl, pdo_mysql, tokenizer, xml, zip) e permissões em `storage/` e `bootstrap/cache/`. Todos os checks devem aparecer em verde.

### Etapa 2 — Banco
Preenche com as credenciais do passo 2:

| Campo | Valor |
|---|---|
| Host | `127.0.0.1` |
| Porta | `3306` |
| Nome do banco | `seusite` |
| Usuário | `seusite_user` |
| Senha | (a gerada no CloudPanel) |

O instalador **testa a conexão** antes de salvar no `.env`. Se errou alguma credencial, o erro aparece no formulário sem perder o resto.

### Etapa 3 — Aplicação
| Campo | Valor recomendado |
|---|---|
| Nome | `FidelizaPro` |
| URL | `https://seudominio.com.br` (sem `/` no final) |
| Fuso horário | `America/Sao_Paulo` |
| Carregar dados de exemplo | **Desmarcado** (em produção real) |

> Marcar a seed cria 3 empresas fictícias (padaria, salão, restaurante), 60 clientes, 230 compras e o login `super@fidelizapro.com` / `password`. Útil pra demo, mas **nunca em produção**.

Clica em **Rodar migrations** — o instalador grava o `.env`, gera a `APP_KEY` (se ainda não tiver) e cria todas as tabelas. Pode levar 5-15 segundos.

### Etapa 4 — Super Admin
| Campo | Valor |
|---|---|
| Nome | seu nome |
| E-mail | e-mail real (vai usar pra logar) |
| Senha | mínimo 8 caracteres, forte |

Clica em **Criar e finalizar**. O instalador então:
- Cria o super admin no banco
- Roda `php artisan storage:link`
- Gera os caches: `config:cache`, `route:cache`, `view:cache`
- **Trava o `/install`** gravando `storage/installed.lock`

A última tela mostra "Instalação concluída" com atalhos pro `/admin/login`, `/super` e `/app/`.

---

## 8. Pós-instalação

Esses 3 passos não são automatizados pelo instalador (precisam de acesso ao painel ou ao SSH).

### 8.1 — SSL Let's Encrypt (essencial pro PWA)

No CloudPanel: **Sites → seu site → SSL/TLS → Ações → Novo certificado Let's Encrypt**.

Marca o domínio (e `www` se quiser cobrir os dois). Clica **Criar e instalar**. Em ~30s o site fica HTTPS de verdade, e renova sozinho a cada 60 dias.

Depois disso, no Cloudflare, mude o SSL de **Completo** para **Completo (estrito)**.

### 8.2 — Cron do Laravel

No CloudPanel: **Sites → seu site → Cron Jobs → + Adicionar Cron Job**.

| Campo | Valor |
|---|---|
| Schedule | `* * * * *` (a cada minuto) |
| Command | `cd /home/seusite/htdocs/seudominio.com.br && php8.3 artisan schedule:run >> /dev/null 2>&1` |

Roda automaticamente:
- `cashback:liberar` diariamente às 03:00
- `automacoes:executar` diariamente às 09:00

### 8.3 — Configuração do super admin

Loga em `/admin/login` com a conta criada no wizard, vai pro `/super` e:
- Cria os planos do SaaS
- Cria as primeiras empresas (tenants)
- Configura WhatsApp/Asaas se for o caso (veja [`INTEGRACOES.md`](INTEGRACOES.md))

---

## Troubleshooting

### `ERR_TOO_MANY_REDIRECTS` no `/install`

Causa: Cloudflare em modo **Flexível** + redirect HTTP→HTTPS no nginx do CloudPanel.

**Diagnóstico no SSH:**
```bash
curl -sIk --max-redirs 0 https://seudominio.com.br/install
```
Se aparecer `301` com `location: https://...` (mesma URL) e `server: cloudflare`, é Cloudflare em Flexível.

**Solução:** [seção 6](#6-cloudflare-se-estiver-usando) — Cloudflare → SSL/TLS → **Completo**.

Depois de corrigir, abra em janela anônima (`Ctrl+Shift+N`) — o Chrome cacheia 301 de forma agressiva.

### Página em branco / erro 500 em todas as rotas

Causa mais comum: arquivos com owner `root:root` (você clonou como root em vez de como o usuário do site).

```bash
ls -la ~/htdocs/seudominio.com.br | head -5
```
Se aparecer `root root`, corrige (como root):
```bash
chown -R seusite:seusite /home/seusite/htdocs/seudominio.com.br
```

### `No application encryption key has been specified`

A `APP_KEY` no `.env` está vazia. Roda como `seusite`:
```bash
cd ~/htdocs/seudominio.com.br
php artisan key:generate
php artisan config:clear
```

### `SQLSTATE[HY000] [1045] Access denied`

Senha do banco errada no `.env`. Edita o arquivo:
```bash
nano ~/htdocs/seudominio.com.br/.env
```
Corrige `DB_PASSWORD=`, salva, e refaz a etapa 2 do wizard (ou apaga `storage/installed.lock` e refaz tudo).

### Wizard volta pra etapa 1 ou erra cookies

Causa: `SESSION_DRIVER=database` no `.env`, mas a tabela `sessions` ainda não foi criada (migrations não rodaram). O `.env.example` já vem com `SESSION_DRIVER=file` justamente pra evitar isso. Se mudou manualmente, volta pra `file`:
```bash
sed -i 's/^SESSION_DRIVER=database/SESSION_DRIVER=file/' .env
php artisan config:clear
```

### Quero refazer a instalação do zero

O instalador trava sozinho gravando `storage/installed.lock`. Pra reabrir:
```bash
rm ~/htdocs/seudominio.com.br/storage/installed.lock
```
E acessa `/install` de novo. **Cuidado:** se marcar "carregar dados de exemplo" na etapa 3, o `migrate:fresh` apaga **todo o banco** antes de recriar.

### "Já existe um usuário com este e-mail"

Você marcou a seed na etapa 3, e ela já criou `super@fidelizapro.com`. Use outro e-mail pro super admin novo, ou clica em "Usar este e finalizar" se for usar o seedado.

### Composer/artisan reclama de versão de PHP

CloudPanel suporta múltiplas versões de PHP. Force a 8.3:
```bash
php8.3 /usr/bin/composer install --no-dev --optimize-autoloader
php8.3 artisan key:generate
```

---

## Próximos passos

- [`DEPLOY-CLOUDPANEL.md`](DEPLOY-CLOUDPANEL.md) — referência completa: hardening, firewall, fail2ban, atualizações, script de deploy
- [`INTEGRACOES.md`](INTEGRACOES.md) — WhatsApp, Asaas (gateway), PDV
- [`API.md`](API.md) — referência da API REST
