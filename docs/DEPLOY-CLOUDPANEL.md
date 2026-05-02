# Deploy em VPS — CloudPanel + Nginx + PHP-FPM

Guia passo a passo para colocar o FidelizaPro em produção em VPS Linux com **CloudPanel** (nginx + PHP-FPM + MariaDB).

> CloudPanel é leve, usa nginx, isola cada site num usuário próprio (não roda como root) e tem histórico de segurança bem melhor que CyberPanel.

---

## Sumário

- [1. Pré-requisitos do VPS](#1-pré-requisitos-do-vps)
- [2. Criar site PHP no CloudPanel](#2-criar-site-php-no-cloudpanel)
- [3. Configurar PHP 8.3](#3-configurar-php-83)
- [4. Criar banco de dados](#4-criar-banco-de-dados)
- [5. Subir o código](#5-subir-o-código)
- [6. Document root para `public/`](#6-document-root-para-public)
- [7. Configurar `.env` de produção](#7-configurar-env-de-produção)
- [8. Composer install + migrations](#8-composer-install--migrations)
- [9. Permissões de pastas](#9-permissões-de-pastas)
- [10. SSL (Let's Encrypt)](#10-ssl-lets-encrypt)
- [11. Cron / Schedule](#11-cron--schedule)
- [12. Otimizações finais](#12-otimizações-finais)
- [13. Hardening do servidor](#13-hardening-do-servidor)
- [14. Atualização contínua](#14-atualização-contínua)

---

## 1. Pré-requisitos do VPS

| Recurso | Mínimo recomendado |
|---|---|
| **CPU** | 2 vCPU |
| **RAM** | 2 GB |
| **Disco** | 20 GB SSD |
| **SO** | Ubuntu 22.04 ou 24.04 LTS, Debian 12 |
| **CloudPanel** | versão 2.x (última) |
| **PHP** | 8.3 |
| **MariaDB** | já incluso no CloudPanel |

### Instalar CloudPanel (se ainda não tem)

SSH no VPS recém-criado, **como root**:

```bash
# Ubuntu 22.04 / 24.04 / Debian 12
curl -sS https://installer.cloudpanel.io/ce/v2/install.sh -o install.sh; \
echo "0d27e6cdfd6e9b6c2a4d7e8a9f1c2b3e4d5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b install.sh" | sha256sum -c \
  || bash install.sh
```

> Confere o hash atual em https://www.cloudpanel.io/docs/v2/getting-started/installation/ antes de executar.

Após ~5 min instala. Acesse: `https://SEU_IP:8443`. Crie a conta admin de gerenciamento.

---

## 2. Criar site PHP no CloudPanel

No painel do CloudPanel:

**Sites → + Add Site → Create a PHP Site**

| Campo | Valor |
|---|---|
| Domain Name | `fidelizapro.seudominio.com.br` (ou raiz: `seudominio.com.br`) |
| PHP Version | **8.3** |
| Application | **Generic** |
| Site User | `fidelizapro` (vira o dono dos arquivos e do PHP-FPM pool) |
| Site User Password | use o gerador (anote!) |

Clica **Create**.

Isso cria:
- Usuário Linux: `fidelizapro`
- Diretório base: `/home/fidelizapro/htdocs/fidelizapro.seudominio.com.br/`
- Pool PHP-FPM dedicado rodando como `fidelizapro` (não root)
- vHost nginx em `/etc/nginx/sites-enabled/fidelizapro.seudominio.com.br.conf`

> Aponta o DNS do domínio (registro A) pro IP da VPS antes de seguir, senão o SSL no passo 10 vai falhar.

---

## 3. Configurar PHP 8.3

Confirme PHP 8.3 em **Sites → seu site → Settings**.

### Habilitar extensões necessárias

CloudPanel já vem com as extensões padrão habilitadas. Confirma via SSH:

```bash
php8.3 -m | grep -E "bcmath|ctype|curl|dom|fileinfo|gd|intl|mbstring|mysqli|openssl|pdo|tokenizer|xml|zip"
```

Deve listar todas. Se faltar alguma:

```bash
apt install -y php8.3-bcmath php8.3-curl php8.3-gd php8.3-intl \
  php8.3-mbstring php8.3-mysql php8.3-xml php8.3-zip
systemctl restart php8.3-fpm
```

### Aumentar limites do PHP

**Sites → seu site → PHP Settings**:

```ini
memory_limit = 512M
upload_max_filesize = 32M
post_max_size = 32M
max_execution_time = 300
```

Salva — o CloudPanel reinicia o pool PHP-FPM sozinho.

---

## 4. Criar banco de dados

CloudPanel: **Sites → seu site → Databases → + Add Database**

| Campo | Valor |
|---|---|
| Database Name | `fidelizapro` |
| User Name | `fidelizapro_user` |
| User Password | gerador (anote!) |

O CloudPanel **não** adiciona prefixo automático — o nome fica exatamente como você digitou.

---

## 5. Subir o código

SSH no VPS como o usuário do site (não como root):

```bash
ssh fidelizapro@SEU_IP
# ou: su - fidelizapro
```

### Git clone (recomendado)

```bash
cd ~/htdocs/fidelizapro.seudominio.com.br
# remove o index.html / phpinfo padrão
rm -f index.html index.php
# clone direto pra dentro
git clone https://github.com/mr-roboot2/fidelizapro.git .
```

> O `.` no final é importante: clona o repo no diretório atual em vez de criar uma subpasta.

### Ou via SFTP

Use FileZilla/WinSCP:
- Host: SEU_IP, porta 22
- Usuário: `fidelizapro`, senha definida ao criar o site
- Sobe os arquivos (sem `vendor/`, `.env`, `node_modules/`) pra `/home/fidelizapro/htdocs/fidelizapro.seudominio.com.br/`

---

## 6. Document root para `public/`

Laravel serve a partir de `public/`, mas o CloudPanel aponta pra raiz do htdocs.

**Sites → seu site → Settings → Site Settings → Web Root**

Mude o campo **Web Root** de:
```
/
```
para:
```
/public
```

Clica **Save**. O nginx é recarregado automaticamente.

---

## 7. Configurar `.env` de produção

```bash
cd ~/htdocs/fidelizapro.seudominio.com.br
cp .env.example .env
nano .env
```

```ini
APP_NAME=FidelizaPro
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=America/Sao_Paulo
APP_URL=https://fidelizapro.seudominio.com.br

LOG_CHANNEL=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=14

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fidelizapro
DB_USERNAME=fidelizapro_user
DB_PASSWORD=SUA_SENHA_DO_BANCO

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

CACHE_STORE=database

WHATSAPP_PROVIDER=mock

ASAAS_API_KEY=
ASAAS_ENV=production
```

Salva (`Ctrl+O`, Enter, `Ctrl+X`).

Bloqueia leitura por outros usuários:

```bash
chmod 600 .env
```

---

## 8. Composer install + migrations

Composer já vem instalado no CloudPanel:

```bash
cd ~/htdocs/fidelizapro.seudominio.com.br
composer install --no-dev --optimize-autoloader
```

Se der erro de versão de PHP:

```bash
php8.3 /usr/bin/composer install --no-dev --optimize-autoloader
```

### Gerar APP_KEY

```bash
php artisan key:generate
```

### Rodar migrations

```bash
php artisan migrate --force
php artisan storage:link
```

### Criar super admin

```bash
php artisan tinker
```

```php
>>> User::create(['name'=>'Super Admin','email'=>'super@seudominio.com','password'=>bcrypt('UMA_SENHA_FORTE'),'role'=>'super_admin'])
>>> exit
```

> Se quiser os 3 tenants de exemplo (padaria, salão, restaurante): `php artisan migrate --seed --force` em vez do `migrate --force` acima.

---

## 9. Permissões de pastas

CloudPanel já criou tudo com o owner correto (`fidelizapro:fidelizapro`). Só precisa garantir que `storage/` e `bootstrap/cache/` sejam graváveis:

```bash
cd ~/htdocs/fidelizapro.seudominio.com.br
chmod -R 775 storage bootstrap/cache
```

> **Não** rode `chown -R` como root — o CloudPanel já isolou tudo no usuário do site, e mexer com permissões de outro usuário pode quebrar o pool PHP-FPM.

---

## 10. SSL (Let's Encrypt)

CloudPanel: **Sites → seu site → SSL/TLS → Actions → New Let's Encrypt Certificate**

Marca:
- ✅ Domain (já vem preenchido)
- ✅ www (se quiser cobrir `www.dominio.com` também)

Clica **Create and Install**. Em ~30s o site fica HTTPS, e ele renova automaticamente a cada 60 dias.

> **Essencial** pro PWA funcionar instalável.

---

## 11. Cron / Schedule

CloudPanel: **Sites → seu site → Cron Jobs → + Add Cron Job**

| Campo | Valor |
|---|---|
| Schedule | **Every 1 minute** (`* * * * *`) |
| Command | `cd /home/fidelizapro/htdocs/fidelizapro.seudominio.com.br && php8.3 artisan schedule:run >> /dev/null 2>&1` |

Esse cron roda como o usuário do site, não como root — bom pra segurança.

Roda automaticamente:
- `cashback:liberar` diariamente às 03:00
- `automacoes:executar` diariamente às 09:00

---

## 12. Otimizações finais

```bash
cd ~/htdocs/fidelizapro.seudominio.com.br
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> Se mudar `.env` depois, rode `php artisan config:clear` antes do `config:cache` de novo.

### Cache do nginx (opcional)

CloudPanel não vem com FastCGI cache habilitado — o Laravel cuida bem do cache via `CACHE_STORE=database` ou Redis. Se quiser Redis:

```bash
sudo apt install -y redis-server
systemctl enable --now redis-server
```

E no `.env`:

```ini
CACHE_STORE=redis
SESSION_DRIVER=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

```bash
php artisan config:cache
```

---

## 13. Hardening do servidor

Como CloudPanel já isola por usuário, falta só o básico:

### SSH apenas com chave (sem senha)

Na sua máquina local (Windows PowerShell ou Git Bash):

```bash
ssh-keygen -t ed25519 -C "lucasfelipelipe@gmail.com"
ssh-copy-id root@SEU_IP
ssh-copy-id fidelizapro@SEU_IP
```

Na VPS:

```bash
nano /etc/ssh/sshd_config.d/00-hardening.conf
```

Cola:

```
PasswordAuthentication no
PermitRootLogin prohibit-password
```

```bash
systemctl restart ssh
```

### Firewall (UFW)

```bash
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 8443/tcp   # painel CloudPanel
ufw enable
ufw status
```

### Fail2ban (já vem no CloudPanel, mas confirma)

```bash
systemctl status fail2ban
fail2ban-client status sshd
```

### Atualização automática de segurança

```bash
apt install -y unattended-upgrades
dpkg-reconfigure -plow unattended-upgrades
```

---

## 14. Atualização contínua

Pra subir alterações novas em produção:

```bash
ssh fidelizapro@SEU_IP
cd ~/htdocs/fidelizapro.seudominio.com.br

# 1. Backup do banco (sempre antes)
mysqldump -u fidelizapro_user -p fidelizapro > ~/backup_$(date +%F).sql

# 2. Atualizar código
git pull origin main

# 3. Atualizar dependências (só se composer.lock mudou)
composer install --no-dev --optimize-autoloader

# 4. Rodar migrations novas
php artisan migrate --force

# 5. Limpar e recriar caches
php artisan config:clear && php artisan config:cache
php artisan route:clear && php artisan route:cache
php artisan view:clear && php artisan view:cache
```

### Script de deploy

Cria `~/deploy.sh`:

```bash
#!/bin/bash
set -e
cd ~/htdocs/fidelizapro.seudominio.com.br

echo "Pull..."
git pull origin main

echo "Composer..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "Migrate..."
php artisan migrate --force

echo "Cache..."
php artisan config:clear && php artisan config:cache
php artisan route:clear && php artisan route:cache
php artisan view:clear && php artisan view:cache

echo "OK."
```

```bash
chmod +x ~/deploy.sh
~/deploy.sh
```

---

## Checklist final

- [ ] CloudPanel instalado, painel acessível em `https://SEU_IP:8443`
- [ ] Site PHP criado com PHP 8.3, usuário próprio
- [ ] Banco MariaDB criado, credenciais anotadas
- [ ] Código clonado em `~/htdocs/dominio/`
- [ ] Web Root apontado pra `/public`
- [ ] `.env` configurado com `APP_ENV=production`, `APP_DEBUG=false`, `chmod 600`
- [ ] `composer install --no-dev` rodado sem erros
- [ ] `php artisan key:generate`
- [ ] `php artisan migrate --force`
- [ ] `php artisan storage:link`
- [ ] Permissões `775` em `storage/` e `bootstrap/cache/`
- [ ] SSL Let's Encrypt ativo
- [ ] Cron `schedule:run` rodando a cada minuto
- [ ] Caches gerados (`config:cache`, `route:cache`, `view:cache`)
- [ ] SSH sem senha, firewall, fail2ban ativos
- [ ] `https://seudominio.com/admin/login` → funciona
- [ ] `https://seudominio.com/app/` → PWA carrega

---

## Diferenças importantes vs CyberPanel

| | CyberPanel | CloudPanel |
|---|---|---|
| Web server | OpenLiteSpeed | nginx |
| Cache stack | LSCache | FastCGI/Redis |
| Pool PHP | roda como nobody/lscpd | roda como usuário do site |
| Painel | porta 8090 | porta 8443 |
| `.htaccess` | suporta (modo Apache) | **não usa** — regras direto no nginx vhost |
| Vhost | edita pelo painel ("vHost Conf") | edita pelo painel (Vhost) ou direto em `/etc/nginx/sites-enabled/` |
| Cron | global, qualquer usuário | por site, sempre como usuário do site |
| Histórico CVE | várias RCE pré-auth (2024) | bem mais limpo |

Se um arquivo `.htaccess` do projeto antigo tinha alguma regra (rewrite, header, deny), precisa traduzir pra `location` no vhost nginx do CloudPanel.

---

## Precisa de ajuda?

- [`INSTALACAO.md`](INSTALACAO.md) — instalação local (XAMPP)
- [`INTEGRACOES.md`](INTEGRACOES.md) — WhatsApp, Asaas, PDV
- [`API.md`](API.md) — reference da API
