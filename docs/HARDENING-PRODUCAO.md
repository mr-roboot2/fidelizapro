# Hardening de produção

Checklist e comandos para deixar a instância em produção com as
proteções da auditoria de segurança aplicadas. Roda **uma vez** logo
após o deploy ou quando promover um ambiente pra produção real.

> Pré-requisito: sistema já instalado (passou pelo `/install` ou pelo
> script). Migrations atualizadas. Veja [INSTALACAO.md](INSTALACAO.md)
> e [DEPLOY-CLOUDPANEL.md](DEPLOY-CLOUDPANEL.md) /
> [DEPLOY-CYBERPANEL.md](DEPLOY-CYBERPANEL.md) antes deste documento.

---

## 1. Desativar debug e forçar production

Sem isso, qualquer exception abre o Spatie Ignition com stack trace,
variáveis de ambiente e leitura/escrita de arquivos exposta. Em
produção, **NUNCA** deixe `APP_DEBUG=true` ou `APP_ENV=local`.

No servidor, na pasta do projeto:

```bash
sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env
sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env

# Também recomendado em produção:
sed -i 's/^LOG_LEVEL=.*/LOG_LEVEL=warning/' .env
sed -i 's/^SESSION_ENCRYPT=.*/SESSION_ENCRYPT=true/' .env

php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Conferir:

```bash
grep -E "^(APP_DEBUG|APP_ENV|LOG_LEVEL|SESSION_ENCRYPT)" .env
# Esperado:
#   APP_ENV=production
#   APP_DEBUG=false
#   LOG_LEVEL=warning
#   SESSION_ENCRYPT=true

php artisan tinker --execute="echo config('app.env'), ' / debug=', var_export(config('app.debug'),1);"
# Esperado: production / debug=false
```

Efeitos colaterais (todos desejados):

- Spatie Ignition para de servir stack trace nas respostas de erro.
- `URL::forceScheme('https')` ativa em `AppServiceProvider::boot`.
- HSTS começa a sair no header (`Strict-Transport-Security: max-age=31536000; includeSubDomains`).
- `SESSION_SECURE_COOKIE` resolve para `true` (default da config quando `APP_ENV !== 'local'`).

---

## 2. Aplicar migrations da auditoria

Cinco migrations da rodada de segurança precisam estar aplicadas. Em
ambientes que rodam o instalador `2026-05-15` ou mais novo, já vêm
incluídas. Em servidores antigos:

```bash
php artisan migrate --force
```

Verificar que rodaram:

```bash
php artisan migrate:status | grep '2026_05_15'
```

Devem aparecer todas como `[N] Ran`:

| Migration | O que faz |
|---|---|
| `..._000003_add_instalado_em_to_configuracoes_sistema` | Flag em DB pra defesa em profundidade do install (lock + flag) |
| `..._000004_encrypt_pdv_secret_and_empresa_tokens` | Cifra `pdv_secret` + tokens WhatsApp na tabela `empresas` |
| `..._000005_add_whatsapp_app_secret_to_configuracoes` | Coluna `whatsapp_app_secret` (HMAC do Meta Cloud) |
| `..._000006_unique_gateway_charge_id_on_cobrancas` | UNIQUE em `cobrancas.gateway_charge_id` |
| `..._000007_add_telefone_digits_to_clientes` | Coluna pré-computada indexada para busca de telefone |

---

## 3. Nginx — hardening de /storage

Apache já vem com `storage/app/public/.htaccess` no repo (ativo
automaticamente). **Nginx ignora `.htaccess`** — precisa do snippet
manual.

Copiar o conteúdo de [`nginx-storage.conf`](nginx-storage.conf) para
dentro do bloco `server { ... }` do vhost (antes do
`location ~ \.php$`). Reload:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

O snippet bloqueia execução de `.php|.phtml|.phar|.phps|.php5|.php7|
.pht|.html|.svg|.cgi|.pl|.py|.asp|.aspx|.sh|.exe|.inc|.shtml|.xml|.js`
em `/storage/*` (uploads do usuário), além de negar acesso a
`.env`, `composer.json`, `.git/`, etc.

---

## 4. Permissões de arquivo

```bash
chmod 600 .env                                  # só dono lê/escreve
chmod -R 775 storage bootstrap/cache             # web server escreve
chown -R $USER:www-data storage bootstrap/cache  # ou equivalente do painel
```

---

## 5. RBAC do painel admin

A auditoria adicionou roles. Confira que cada usuário em
`users.role` tem o nível adequado:

| Role | Onde acessa |
|---|---|
| `super_admin` | `/super/*` + qualquer rota (impersonate) |
| `admin` | Painel `/admin/*` completo |
| `gerente` | Painel `/admin/*` completo (igual a admin no escopo da empresa) |
| `atendente` | Caixa, listagem/visualização de cliente, ajuste manual de pontos, compras (read-only), aprovação/entrega de resgate, cashback (read-only), avaliações (read-only), ajuda |

Atendente **NÃO** acessa: configurações, regras, recompensas,
parceiros, roleta config, sorteios config, AI Growth, importação CSV,
edição/exclusão de cliente, ajuste manual de cashback, exclusão de
avaliações ou resgates.

Pra mover um usuário de role, edite via `/super/users/{id}` ou direto
no DB:

```sql
UPDATE users SET role = 'admin' WHERE email = 'fulano@empresa.com';
```

---

## 6. Confiar no proxy (Cloudflare / CloudPanel / Nginx reverse)

Se o sistema está atrás de um proxy reverso (qualquer painel moderno
faz isso), `Request::ip()` precisa ser configurado pra ver o IP real
do cliente. Sem isso, `EmpresaThrottle` e antifraude ficam globais
(todos os requests parecem vir do mesmo IP do proxy).

Já vem configurado em `bootstrap/app.php`:

```php
$middleware->trustProxies(at: 'private_ranges');
```

Aceita os ranges privados padrão. Se sua infra usa IPs públicos
específicos no proxy (Cloudflare, AWS ALB, etc.), substitua por uma
lista explícita:

```php
$middleware->trustProxies(at: [
    '173.245.48.0/20',  // Cloudflare
    '103.21.244.0/22',
    // ... lista completa em https://www.cloudflare.com/ips/
]);
```

---

## 7. (Opcional) Ligar captcha em login/cadastro/OTP

Default `disabled`. Pra ligar (recomendado se o sistema for público):

1. Criar widget em <https://dash.cloudflare.com> → Turnstile → Add site.
   Widget Mode = **Managed** (sem fricção pro usuário legítimo).
2. Copiar **Site Key** e **Secret Key**.
3. Acessar `/super/configuracoes` → seção **Captcha (anti-robô)**.
   Selecionar provider = `Cloudflare Turnstile`, colar as duas chaves,
   salvar.

Alternativa via env (útil em CI/staging onde quer fixar comportamento):

```env
CAPTCHA_PROVIDER=turnstile
TURNSTILE_SITE_KEY=0x4AAAAAAA...
TURNSTILE_SECRET_KEY=0x4AAAAAAA...
```

DB tem preferência sobre env — o super admin pode ligar/desligar
sem precisar mexer no servidor. `secret_key` fica cifrada
(cast `encrypted`).

Aplica-se em: `/admin/login`, `/api/v1/auth/{login,registrar,recuperar-senha,otp/solicitar}`, `/api/v1/loja/login`.

Validação backend é **fail-closed**: se Cloudflare estiver fora ou
timeout, o request é rejeitado.

---

## 8. (Opcional) WhatsApp Cloud API: validar assinatura HMAC

Quando usar o provider `meta_cloud`, configure `whatsapp_app_secret`
em `/super/whatsapp` pra que o webhook valide
`X-Hub-Signature-256` (HMAC SHA256). Sem app secret configurado, o
webhook aceita mas grava warning no log.

Pegar o App Secret em <https://developers.facebook.com> → seu app →
Settings → Basic → App Secret → Show.

---

## 9. Verificações pós-hardening

Pra fechar, valida que tudo está em pé:

```bash
# Headers de segurança ativos
curl -sIk https://SEU_DOMINIO/ | grep -iE "(strict-transport|x-frame|x-content-type|content-security|referrer-policy|permissions-policy)"

# CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy
# e Permissions-Policy devem aparecer.

# Tela de erro NÃO mostra stack trace
curl -sk https://SEU_DOMINIO/rota-que-nao-existe | grep -i "stack\|whoops\|ignition"
# Esperado: vazio.

# Webhook bloqueia gateway forjado
curl -X POST https://SEU_DOMINIO/webhook/pagamento/stripe \
  -H 'Content-Type: application/json' -d '{}' -o /dev/null -w "%{http_code}\n"
# Esperado: 404 (rota restrita a gateway=asaas)

# /install não reabre
curl -sk https://SEU_DOMINIO/install/ | grep -i "instalado"
# Esperado: página "Sistema já instalado" ou redirect.

# pdv_secret está cifrado no banco
php artisan tinker --execute="
\$raw = DB::table('empresas')->where('id', 1)->value('pdv_secret');
echo strlen(\$raw) > 100 ? 'CIFRADO' : 'PLAIN — REAPLICAR migration 000004';
echo PHP_EOL;
"
```

---

## 10. Cron diário

Garantir que está rodando (recomendado de hora em hora):

```bash
crontab -e
# Adicionar:
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

O scheduler dispara:
- Liberação de cashback pendente
- Avisos de cobrança (X dias antes/depois do vencimento)
- Marcação de assinaturas vencidas como inadimplentes
- Disparos de campanhas WhatsApp

---

## Resumo do que foi corrigido na auditoria (referência)

- 🔴 5 críticos: IDOR cross-tenant PDV, bypass de webhook gateway, race em `marcarPaga`, RBAC inexistente, races em saldos.
- 🟠 18 altos: XSS no PWA, DOS de OTP, info disclosure em 500, login admin sem throttle por email, sem HSTS, etc.
- 🟡 19 médios: timing attack no login, exports sem cap, install reabrível, etc.
- ⚪ 14 baixos / hardening: SSRF whatsapp_api_url, logs com PII, nginx extensions, etc.

**Total: 56 vulnerabilidades fechadas.** Detalhes técnicos no commit log
sob `feat(seguranca):` e `fix(seguranca):`.
