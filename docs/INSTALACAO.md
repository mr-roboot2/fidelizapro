# 📘 Guia de Instalação — FidelizaPro

Guia passo a passo para instalar o FidelizaPro do zero no Windows com XAMPP.
Para Linux/Mac o processo é igual, só os caminhos mudam.

---

## Sumário

- [1. Pré-requisitos](#1-pré-requisitos)
- [2. Instalar XAMPP](#2-instalar-xampp)
- [3. Obter o projeto](#3-obter-o-projeto)
- [4. Instalar Composer](#4-instalar-composer)
- [5. Instalar dependências PHP](#5-instalar-dependências-php)
- [6. Configurar o ambiente (.env)](#6-configurar-o-ambiente-env)
- [7. Criar o banco de dados](#7-criar-o-banco-de-dados)
- [8. Migrations e seeders](#8-migrations-e-seeders)
- [9. Storage link](#9-storage-link)
- [10. Primeiro acesso](#10-primeiro-acesso)
- [11. Configurações iniciais por empresa](#11-configurações-iniciais-por-empresa)
- [12. Cron / agendamento](#12-cron--agendamento)
- [13. Atualização do projeto](#13-atualização-do-projeto)
- [14. Troubleshooting](#14-troubleshooting)
- [15. Checklist de produção](#15-checklist-de-produção)

---

## 1. Pré-requisitos

| Requisito | Versão | Como obter |
|---|---|---|
| **PHP** | 8.2 ou superior | Vem com o XAMPP |
| **MySQL/MariaDB** | 5.7+ ou MariaDB 10.3+ | Vem com o XAMPP |
| **Composer** | 2.x | https://getcomposer.org/ (ou ver passo 4) |
| **Apache** | 2.4+ | Vem com o XAMPP |
| **Git** (opcional) | qualquer | https://git-scm.com/ |

> ⚠️ Se você já tem **PHP 8.0 ou inferior** instalado, atualize. O Laravel 11 exige PHP 8.2+.

### Verificar versões instaladas

```bash
"/e/xampp/php/php.exe" --version
"/e/xampp/mysql/bin/mysql.exe" --version
```

---

## 2. Instalar XAMPP

1. Baixe em https://www.apachefriends.org/ (versão com PHP 8.2 ou superior)
2. Instale em `E:\xampp` (caminho padrão usado neste guia)
3. Abra o **XAMPP Control Panel**
4. Inicie **Apache** e **MySQL** (botões "Start")
5. Confirme que está rodando: http://localhost/ deve mostrar a página do XAMPP
6. Confirme phpMyAdmin: http://localhost/phpmyadmin

### Habilitar extensões PHP necessárias

Abra `E:\xampp\php\php.ini` e confirme que estas linhas estão **sem `;`** no início:

```ini
extension=fileinfo
extension=gd
extension=mbstring
extension=mysqli
extension=openssl
extension=pdo_mysql
extension=zip
```

Se editar, **reinicie o Apache** no Control Panel.

---

## 3. Obter o projeto

### Opção A — Git clone

```bash
cd /e/xampp/htdocs
git clone <url-do-repositorio> fidelizapro
cd fidelizapro
```

### Opção B — Download manual

Extraia o ZIP em `E:\xampp\htdocs\fidelizapro` (a pasta deve ficar exatamente assim).

A estrutura final deve ser:

```
E:\xampp\htdocs\fidelizapro\
├── app\
├── bootstrap\
├── composer.json
├── public\
├── ...
```

---

## 4. Instalar Composer

### Se você JÁ tem o Composer instalado globalmente

Pule para o passo 5. Confirme com:

```bash
composer --version
```

### Se NÃO tem o Composer

Instale localmente no projeto (mais fácil para Windows + XAMPP):

```bash
cd /e/xampp/htdocs/fidelizapro
curl -sS -o composer-setup.php https://getcomposer.org/installer
"/e/xampp/php/php.exe" composer-setup.php --install-dir=. --filename=composer.phar
rm composer-setup.php
```

Após isso, todos os comandos `composer ...` neste guia ficam:

```bash
"/e/xampp/php/php.exe" composer.phar <comando>
```

> 💡 Para instalar o Composer **globalmente** no Windows, baixe o instalador `.exe` em https://getcomposer.org/Composer-Setup.exe.

---

## 5. Instalar dependências PHP

```bash
cd /e/xampp/htdocs/fidelizapro
"/e/xampp/php/php.exe" composer.phar install --no-interaction
```

Esse comando baixa o Laravel 11 e todas as bibliotecas (~127 pacotes) na pasta `vendor/`.
Pode demorar de 1 a 5 minutos na primeira vez.

**Se der erro de memória:** aumente em `php.ini` → `memory_limit=512M` e reinicie Apache.

---

## 6. Configurar o ambiente (.env)

Copie o arquivo de exemplo:

```bash
cp .env.example .env
```

Gere a chave de criptografia:

```bash
"/e/xampp/php/php.exe" artisan key:generate
```

Edite o `.env` se precisar (padrão XAMPP funciona out-of-the-box):

```ini
APP_NAME=FidelizaPro
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/fidelizapro/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fidelizapro
DB_USERNAME=root
DB_PASSWORD=

# WhatsApp (config padrão = mock; ajuste depois pra produção)
WHATSAPP_PROVIDER=mock

# Asaas (gateway pagamento — só preenche em produção)
ASAAS_API_KEY=
ASAAS_ENV=sandbox
```

> 🔐 **Em produção:** mude `APP_ENV=production`, `APP_DEBUG=false` e use senha no MySQL.

---

## 7. Criar o banco de dados

Acesse http://localhost/phpmyadmin

1. Clique em **Novo** (na sidebar)
2. Nome do banco: `fidelizapro`
3. Cotejamento: `utf8mb4_unicode_ci`
4. Clique em **Criar**

Ou via linha de comando:

```bash
"/e/xampp/mysql/bin/mysql.exe" -u root -e "CREATE DATABASE fidelizapro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

---

## 8. Migrations e seeders

```bash
"/e/xampp/php/php.exe" artisan migrate --seed
```

Esse comando vai:

1. Criar **20+ tabelas** (empresas, clientes, compras, recompensas, resgates, parceiros, etc)
2. Popular com **dados fictícios**:
   - 3 empresas de exemplo (Padaria, Salão, Restaurante)
   - 7 usuários admin
   - 60 clientes
   - ~230 compras
   - 9 parceiros + 9 benefícios
   - 21 automações WhatsApp pré-configuradas
   - 15 recompensas
   - 3 planos do SaaS

> ⚠️ Se algum erro acontecer, veja a seção [Troubleshooting](#14-troubleshooting).

---

## 9. Storage link

Cria o link simbólico de `public/storage` → `storage/app/public` (necessário para servir imagens):

```bash
"/e/xampp/php/php.exe" artisan storage:link
```

> No Windows, esse comando precisa de **terminal como Administrador** OU ative o **modo desenvolvedor** em Configurações → Privacidade → Para desenvolvedores.

---

## 10. Primeiro acesso

### Painel administrativo
🔗 http://localhost/fidelizapro/public/admin/login

| Login | Senha | Tipo |
|---|---|---|
| `super@fidelizapro.com` | `password` | Super Admin (vai pra `/super`) |
| `admin@pao-quente.com` | `password` | Admin da Padaria |
| `admin@beleza-cia.com` | `password` | Admin do Salão |
| `admin@sabor-da-casa.com` | `password` | Admin do Restaurante |

### PWA do cliente

**Genérico:** http://localhost/fidelizapro/public/app/

**White label** (com cores e logo da empresa): http://localhost/fidelizapro/public/app/pao-quente/

Para login no PWA:
- Use qualquer telefone listado em **Painel → Clientes**
- Senha padrão dos clientes seedados: `123456`
- Ou clique em **Entrar com WhatsApp** — em modo mock o código aparece na tela

### Tela do parceiro (validar cupom)

A URL única do parceiro está em **Painel → Parceiros → Ver parceiro**. Algo como:

```
http://localhost/fidelizapro/public/parceiro/8nNwwbKUvT4Rq5UlDvl2DtsE7RMfekwD
```

---

## 11. Configurações iniciais por empresa

Após o primeiro login como admin da empresa:

### Identidade visual
**Configurações** → cor primária, secundária, logo, nome.
Isso afeta o painel **e** o PWA white label.

### Programa de fidelidade
**Configurações** → pontos por R$, % de cashback, dias para liberar cashback, validade dos pontos.

### Regras de pontuação
**Regras de pontuação** → ative as 5 regras seedadas (compra padrão, aniversário, indicação, cadastro, avaliação) ou crie novas.

### Recompensas
**Recompensas** → cadastre o catálogo que aparecerá no PWA.

### WhatsApp
**WhatsApp API** → escolha o provider (em dev deixe `mock`), preencha credenciais e teste.

### Automações
**Automações** → revise os templates pré-criados, ative as que fazem sentido.

### Compartilhar PWA com clientes
**Configurações** → no rodapé tem o link white label `/app/{slug}/` para enviar aos clientes.

---

## 12. Cron / agendamento

O FidelizaPro tem 2 tarefas agendadas:

| Comando | Frequência | O que faz |
|---|---|---|
| `cashback:liberar` | Diário 03:00 | Libera cashbacks pendentes que passaram do prazo |
| `automacoes:executar` | Diário 09:00 | Dispara automações de aniversário, inativos, pontos vencendo |

### Em desenvolvimento (Windows)

Você pode disparar manualmente:

```bash
"/e/xampp/php/php.exe" artisan automacoes:executar
"/e/xampp/php/php.exe" artisan cashback:liberar
```

Ou criar uma tarefa agendada no Windows que rode `php artisan schedule:run` a cada minuto.

### Em produção (Linux)

Adicione ao crontab (`crontab -e`):

```cron
* * * * * cd /caminho/para/fidelizapro && php artisan schedule:run >> /dev/null 2>&1
```

O próprio Laravel decide quais comandos rodar conforme `routes/console.php`.

---

## 13. Atualização do projeto

Quando receber uma nova versão do código:

```bash
cd /e/xampp/htdocs/fidelizapro
git pull                                          # se estiver usando git
"/e/xampp/php/php.exe" composer.phar install     # atualiza dependências
"/e/xampp/php/php.exe" artisan migrate --force   # roda migrations novas
"/e/xampp/php/php.exe" artisan config:clear
"/e/xampp/php/php.exe" artisan route:clear
"/e/xampp/php/php.exe" artisan view:clear
```

---

## 14. Troubleshooting

### `Failed to open stream: vendor/autoload.php`

Esqueceu de rodar o `composer install`. Veja o passo 5.

### `SQLSTATE[HY000] [1049] Unknown database 'fidelizapro'`

O banco não foi criado. Veja o passo 7.

### `SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'`

Senha do MySQL no `.env` está errada. No XAMPP padrão, **deixe vazia**:
```
DB_PASSWORD=
```

### `Class "Redis" not found`

Não use `CACHE_STORE=redis` ou `SESSION_DRIVER=redis` se Redis não está instalado. Mantenha:
```
CACHE_STORE=database
SESSION_DRIVER=database
```

### A página `/admin/login` retorna 404

O Apache não está fazendo o rewrite. Confirme:

1. `mod_rewrite` ativado no `httpd.conf` do Apache (procure por `LoadModule rewrite_module`)
2. Em `httpd.conf` está `AllowOverride All` para o `htdocs`
3. O arquivo `public/.htaccess` existe (presente no projeto)

### O PWA mostra apenas erros 404 no console

Está acessando `/app/` mas os caminhos no HTML estão errados. Garanta que o arquivo `public/app/index.html` esteja com paths **relativos** (`manifest.json`, não `/app/manifest.json`).

### `Permission denied` ao escrever em `storage/`

No Linux:
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

No Windows (XAMPP), normalmente não há problema. Se houver, dê controle total ao usuário do Apache para essas pastas.

### "Trailing slash" — PWA white label retorna 301

Isso já está resolvido no `.htaccess` do projeto (regra específica para `/app/{slug}/`). Se ainda acontecer, confirme que o `.htaccess` em `public/` está completo.

### `php artisan storage:link` falha no Windows

Use terminal **como Administrador** ou ative o modo desenvolvedor do Windows.
Como alternativa, copie manualmente:
```bash
mklink /D E:\xampp\htdocs\fidelizapro\public\storage E:\xampp\htdocs\fidelizapro\storage\app\public
```

### Erro de memória no `composer install`

Aumente em `php.ini`:
```
memory_limit=1G
```
Reinicie Apache.

### "View [...] not found"

Limpe o cache de views:
```bash
"/e/xampp/php/php.exe" artisan view:clear
```

### Service Worker não atualiza no PWA

No DevTools → Application → Service Workers → **Unregister**, depois Application → Storage → **Clear site data**, depois recarregue (Ctrl+Shift+R).

---

## 15. Checklist de produção

Antes de subir para produção real:

- [ ] `APP_ENV=production` no `.env`
- [ ] `APP_DEBUG=false` no `.env`
- [ ] `APP_URL` apontando pro domínio HTTPS
- [ ] Senha forte do MySQL no `.env`
- [ ] Apache com `DocumentRoot` apontando para `public/` (não para a raiz do projeto)
- [ ] HTTPS configurado (PWA exige HTTPS para instalação fora de localhost)
- [ ] `composer install --optimize-autoloader --no-dev`
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] `php artisan storage:link`
- [ ] Cron configurado com `schedule:run`
- [ ] WhatsApp provider real configurado em cada empresa (`/admin/whatsapp`)
- [ ] Asaas com `ASAAS_API_KEY` real e `ASAAS_ENV=production`
- [ ] Webhook do Asaas apontando para `https://seudominio.com/webhook/pagamento/asaas`
- [ ] Backup automático do banco
- [ ] Logs do Laravel rotacionados (`storage/logs/laravel.log`)
- [ ] Ícones PWA em `public/app/icons/` substituídos pelos definitivos da marca

---

## ❓ Próximos passos

- 🔌 [`INTEGRACOES.md`](INTEGRACOES.md) — Configurar WhatsApp, PDV externo, Asaas
- 🌐 [`API.md`](API.md) — Reference completa da API REST

Em caso de dúvida, abra uma issue no repositório.
