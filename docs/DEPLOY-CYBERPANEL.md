# 🚀 Deploy em VPS — CyberPanel + OpenLiteSpeed

Guia completo passo a passo para colocar o FidelizaPro em produção em um VPS Linux com **CyberPanel + OpenLiteSpeed (OLS)**.

> ✅ **Funciona perfeitamente.** OLS é mais rápido que Apache, a config é simples e o CyberPanel facilita SSL e cron.

---

## Sumário

- [1. Pré-requisitos do VPS](#1-pré-requisitos-do-vps)
- [2. Criar website no CyberPanel](#2-criar-website-no-cyberpanel)
- [3. Configurar PHP 8.2+](#3-configurar-php-82)
- [4. Criar banco de dados MySQL](#4-criar-banco-de-dados-mysql)
- [5. Subir o código](#5-subir-o-código)
- [6. Document root para `public/`](#6-document-root-para-public)
- [7. Configurar .env de produção](#7-configurar-env-de-produção)
- [8. Composer install + migrations](#8-composer-install--migrations)
- [9. Permissões de pastas](#9-permissões-de-pastas)
- [10. SSL (Let's Encrypt)](#10-ssl-lets-encrypt)
- [11. Cron / Schedule](#11-cron--schedule)
- [12. Otimizações finais](#12-otimizações-finais)
- [13. Troubleshooting OLS específico](#13-troubleshooting-ols-específico)
- [14. Atualização contínua](#14-atualização-contínua)

---

## 1. Pré-requisitos do VPS

| Recurso | Mínimo recomendado |
|---|---|
| **CPU** | 2 vCPU |
| **RAM** | 2 GB |
| **Disco** | 20 GB SSD |
| **SO** | Ubuntu 22.04 LTS (recomendado pela CyberPanel) ou AlmaLinux 9 |
| **CyberPanel** | versão 2.3+ (já vem com OLS) |
| **PHP** | 8.2 ou 8.3 (instalável pelo painel) |
| **MySQL/MariaDB** | já incluso no CyberPanel |

### Provedores recomendados (Brasil)
- **Hostinger** VPS — R$ 35/mês (1GB) ou R$ 60/mês (2GB)
- **Vultr** — US$ 5/mês (1GB) — Cloud Compute
- **DigitalOcean** — US$ 6/mês (1GB) — Droplet
- **Hetzner** — €4/mês — CX22 (excelente custo-benefício)
- **Contabo** — €5/mês — VPS S (mais RAM por menos)

### Instalar CyberPanel (se ainda não tem)

SSH no VPS recém-criado:

```bash
sh <(curl https://cyberpanel.net/install.sh || wget -O - https://cyberpanel.net/install.sh)
```

Escolha:
- Opção `1`: Install CyberPanel
- Opção `1`: Install OpenLiteSpeed
- Memcached, Redis, etc: `Y` (recomendado, melhora cache)
- Defina senhas de admin

Após ~15 min instala. Acesse: `https://SEU_IP:8090` (admin: `admin`, senha definida).

---

## 2. Criar website no CyberPanel

No painel do CyberPanel:

**Websites → Create Website**

| Campo | Valor |
|---|---|
| Package | `Default` |
| Owner | `admin` |
| Domain Name | `fidelizapro.seudominio.com.br` (ou raiz: `seudominio.com.br`) |
| Email | seu e-mail |
| PHP | **8.2** ou **8.3** |
| SSL | ✅ marcar |
| DKIM Support | opcional |
| Open Basedir Protection | ❌ **desmarcar** (Laravel precisa acesso a `/tmp` etc) |

Clica em **Create Website**.

Isso cria a pasta `/home/SEUDOMINIO/public_html` que será o webroot.

---

## 3. Configurar PHP 8.2+

Confirme que o PHP do site é 8.2 ou superior em **Websites → List Websites → seu site → Manage**.

Se PHP 8.2+ não estiver disponível: **Server → PHP → Install PHP** e escolha 8.2 ou 8.3.

### Habilitar extensões necessárias

**Server → PHP → Edit PHP Configs → seu PHP → Extensions**:

Marque (a maioria já vem habilitada):
- ✅ `bcmath`
- ✅ `ctype`
- ✅ `curl`
- ✅ `dom`
- ✅ `fileinfo`
- ✅ `gd`
- ✅ `intl`
- ✅ `mbstring`
- ✅ `mysqli`, `mysqlnd`
- ✅ `openssl`
- ✅ `pdo`, `pdo_mysql`
- ✅ `tokenizer`
- ✅ `xml`
- ✅ `zip`

### Aumentar limites do PHP

**Server → PHP → Edit PHP Configs → seu PHP → Basic Options**:

```ini
memory_limit = 512M
upload_max_filesize = 32M
post_max_size = 32M
max_execution_time = 300
```

Salvar e reiniciar OLS.

---

## 4. Criar banco de dados MySQL

No CyberPanel: **Databases → Create Database**

| Campo | Valor |
|---|---|
| Database Name | `fidelizapro` |
| User | `fidelizapro_user` |
| Password | use o gerador (anote!) |

**Anote** o nome final do banco e do usuário (o CyberPanel adiciona um prefixo automaticamente, algo como `seudomi_fidelizapro`).

---

## 5. Subir o código

SSH no VPS:

```bash
ssh root@SEU_IP
```

### Opção A — Git clone (recomendada)

```bash
cd /home/SEUDOMINIO/
rm -rf public_html
git clone https://github.com/mr-roboot2/fidelizapro.git public_html
cd public_html
chown -R SEUDOMINIO:SEUDOMINIO .
```

> Substitua `SEUDOMINIO` pelo nome real (ex: `fidelizapro_seudominio_com_br`). Veja com `ls /home/`.

### Opção B — Upload via SFTP

Use FileZilla, WinSCP ou similar:
- Host: SEU_IP, porta 22
- Usuário: `root` (ou um sftp user criado no CyberPanel)
- Suba os arquivos do projeto (sem `vendor/`, sem `.env`, sem `node_modules/`) para `/home/SEUDOMINIO/public_html/`

---

## 6. Document root para `public/`

**Importante:** O webroot do CyberPanel por padrão é `/home/SEUDOMINIO/public_html`, mas o Laravel serve a partir de `public/`. Duas soluções:

### Solução A (recomendada) — Apontar para `public/`

CyberPanel → **Websites → List Websites → seu site → vHost Conf**

Procure por:
```
docRoot                   /home/SEUDOMINIO/public_html
```

Mude para:
```
docRoot                   /home/SEUDOMINIO/public_html/public
```

Salve e clique em **Restart LiteSpeed**.

### Solução B — Mover conteúdo

Se preferir não mexer no vHost:

```bash
cd /home/SEUDOMINIO/public_html
mv * .[!.]* ../tmp_proj/ 2>/dev/null
mv ../tmp_proj/public/* .
mv ../tmp_proj/public/.* . 2>/dev/null
mkdir laravel
mv ../tmp_proj/* laravel/
mv ../tmp_proj/.* laravel/ 2>/dev/null
rmdir ../tmp_proj

# Editar public/index.php para apontar 1 nível acima
sed -i 's|__DIR__.\x27/../|__DIR__.\x27/../laravel/|g' index.php
```

A solução A é muito mais limpa. **Use a A.**

---

## 7. Configurar .env de produção

```bash
cd /home/SEUDOMINIO/public_html
cp .env.example .env
nano .env
```

Configure:

```ini
APP_NAME=FidelizaPro
APP_ENV=production
APP_KEY=                       # gera no próximo passo
APP_DEBUG=false                # IMPORTANTE: false em produção
APP_TIMEZONE=America/Sao_Paulo
APP_URL=https://fidelizapro.seudominio.com.br

LOG_CHANNEL=stack
LOG_LEVEL=warning              # menos ruído em produção

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=seudomi_fidelizapro      # nome real do banco criado
DB_USERNAME=seudomi_fidelizapro_user # usuário real
DB_PASSWORD=SUA_SENHA_SEGURA

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true     # HTTPS only

CACHE_STORE=database

# WhatsApp (configure depois pelo painel admin)
WHATSAPP_PROVIDER=mock

# Asaas (gateway pagamento)
ASAAS_API_KEY=
ASAAS_ENV=production           # mude pra production quando estiver pronto
```

Salvar (`Ctrl+O`, `Enter`, `Ctrl+X`).

---

## 8. Composer install + migrations

### Instalar Composer (se ainda não tem)

```bash
cd /tmp
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
```

### Instalar dependências

```bash
cd /home/SEUDOMINIO/public_html
composer install --no-dev --optimize-autoloader
```

> Use o caminho específico do PHP 8.2 do CyberPanel se o PHP padrão não for 8.2:
> ```bash
> /usr/local/lsws/lsphp82/bin/php /usr/local/bin/composer install --no-dev --optimize-autoloader
> ```

### Gerar APP_KEY

```bash
php artisan key:generate
```

### Rodar migrations + seeders

```bash
php artisan migrate --seed --force
php artisan storage:link
```

> Cuidado: `--seed` cria os 3 tenants de exemplo. Em produção, talvez você queira **só** rodar migrations e cadastrar a primeira empresa pelo super admin. Nesse caso:
> ```bash
> php artisan migrate --force
> php artisan db:seed --class=PlanoSeeder --force
> # cria super admin manualmente:
> php artisan tinker
> >>> User::create(['name'=>'Super Admin','email'=>'super@seudominio.com','password'=>bcrypt('UMA_SENHA_FORTE'),'role'=>'super_admin'])
> ```

---

## 9. Permissões de pastas

```bash
cd /home/SEUDOMINIO/public_html
chown -R SEUDOMINIO:SEUDOMINIO .
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
```

> O usuário do site no CyberPanel geralmente é o nome do domínio (ex: `fidelizapro_seudominio_com_br`). Verifique com `ls -la /home/`.

---

## 10. SSL (Let's Encrypt)

CyberPanel → **SSL → Manage SSL → seu site → Issue SSL**

Em ~30s seu site fica HTTPS. **Essencial pra PWA funcionar instalável fora de localhost.**

Verifique: https://fidelizapro.seudominio.com.br

---

## 11. Cron / Schedule

CyberPanel → **Server → Cronjobs → Add Cronjob**

| Campo | Valor |
|---|---|
| User | `root` (ou o usuário do site) |
| Frequency | **Every 1 minute** ou: `* * * * *` |
| Command | `cd /home/SEUDOMINIO/public_html && /usr/local/lsws/lsphp82/bin/php artisan schedule:run >> /dev/null 2>&1` |

> O caminho do PHP varia: `/usr/local/lsws/lsphp82/bin/php` ou `/usr/local/lsws/lsphp83/bin/php`. Veja com `which php` ou nas configs do CyberPanel.

Isso roda automaticamente:
- `cashback:liberar` diariamente às 03:00
- `automacoes:executar` diariamente às 09:00

---

## 12. Otimizações finais

```bash
cd /home/SEUDOMINIO/public_html
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> ⚠️ Se mudar `.env` depois, rode `php artisan config:clear` antes de fazer `config:cache` de novo.

### Cache do OpenCache do OLS (opcional, ganha 30%+ velocidade)

CyberPanel → **Websites → Manage → LSCache** → **Enable LSCache**

Adicione no `vHost Conf` para excluir rotas dinâmicas do cache:

```
context /admin {
    location                    $DOC_ROOT/admin
    cache no
}
context /api {
    location                    $DOC_ROOT/api
    cache no
}
```

Páginas estáticas (PWA, recursos) ficam cacheadas, dinâmicas não.

---

## 13. Troubleshooting OLS específico

### `.htaccess` não funciona

OpenLiteSpeed lê `.htaccess` igual ao Apache, mas precisa estar habilitado.

CyberPanel → **Server → OpenLiteSpeed Status → Server Configuration → Tuning**:

Confirme:
- `AutoLoadHtaccess`: `Yes`

Salve e reinicie OLS.

### `Error 500` ao acessar

Verifique:
1. Logs: `tail -f /usr/local/lsws/logs/error.log`
2. Logs Laravel: `tail -f /home/SEUDOMINIO/public_html/storage/logs/laravel.log`
3. Permissões: `chmod -R 775 storage bootstrap/cache`

### `Class "PDO" not found`

PHP do site não tem `pdo_mysql`. Volte ao [passo 3](#3-configurar-php-82).

### PWA não instala / não vê manifest

Confirme HTTPS funcionando. PWA exige HTTPS fora de localhost.

### Webhook do Asaas dá 419 (CSRF)

Verifique se a rota `/webhook/pagamento/*` está na lista de exceções do CSRF (já está em `bootstrap/app.php`).

### Imagens uploadadas não aparecem

```bash
cd /home/SEUDOMINIO/public_html
php artisan storage:link
chmod -R 775 storage/app/public
```

### Logs crescendo demais

Configure logrotate ou mude no `.env`:

```ini
LOG_CHANNEL=daily
LOG_DAILY_DAYS=14
```

---

## 14. Atualização contínua

Para atualizar o sistema quando você fizer push de alterações:

```bash
cd /home/SEUDOMINIO/public_html

# 1. Backup do banco antes (sempre)
mysqldump -u DB_USER -p DB_NAME > /root/backup_$(date +%F).sql

# 2. Atualizar código
git pull origin main

# 3. Atualizar dependências
composer install --no-dev --optimize-autoloader

# 4. Rodar migrations novas
php artisan migrate --force

# 5. Limpar e recriar caches
php artisan config:clear && php artisan config:cache
php artisan route:clear && php artisan route:cache
php artisan view:clear && php artisan view:cache
```

### Script de deploy automatizado

Crie `/home/SEUDOMINIO/deploy.sh`:

```bash
#!/bin/bash
set -e
cd /home/SEUDOMINIO/public_html

echo "📥 Pulling..."
git pull origin main

echo "📦 Composer..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "🗃️ Migrate..."
php artisan migrate --force

echo "🧹 Cache..."
php artisan config:clear && php artisan config:cache
php artisan route:clear && php artisan route:cache
php artisan view:clear && php artisan view:cache

echo "✅ Deploy concluído!"
```

```bash
chmod +x /home/SEUDOMINIO/deploy.sh
# Para deployar:
/home/SEUDOMINIO/deploy.sh
```

### Webhook GitHub → deploy automático

Se quiser deploy automático em cada push:

1. CyberPanel → **Server → Cronjobs** → não funciona (precisa endpoint HTTP)
2. **Solução simples:** crie um endpoint Laravel protegido por secret ou use o GitHub Actions com SSH

Posso criar isso depois se quiser.

---

## 📋 Checklist final

- [ ] CyberPanel + OLS instalados
- [ ] Website criado com PHP 8.2+
- [ ] Banco MySQL criado, credenciais anotadas
- [ ] Código clonado/uploadado em `/home/SEUDOMINIO/public_html`
- [ ] vHost com `docRoot` apontando pra `/public`
- [ ] `.env` configurado com `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `composer install --no-dev --optimize-autoloader` rodado
- [ ] `php artisan key:generate`
- [ ] `php artisan migrate --seed --force`
- [ ] `php artisan storage:link`
- [ ] Permissões: `775` em `storage/` e `bootstrap/cache/`
- [ ] SSL ativo (Let's Encrypt)
- [ ] Cron configurado para `schedule:run`
- [ ] `config:cache`, `route:cache`, `view:cache` aplicados
- [ ] Acessar https://seudominio.com/admin/login → funciona ✅
- [ ] Acessar https://seudominio.com/app/ → PWA carrega ✅
- [ ] Webhook do Asaas configurado (se for usar)

---

## 🆘 Precisa de ajuda?

- 📘 [`INSTALACAO.md`](INSTALACAO.md) — instalação local (XAMPP)
- 🔌 [`INTEGRACOES.md`](INTEGRACOES.md) — WhatsApp, Asaas, PDV
- 🌐 [`API.md`](API.md) — reference da API
