# Segurança — Plataforma DSS

Documento de referência das regras de segurança implementadas, configurações de
produção e itens de hardening. Complementa `AUDITORIA.md` (trilha de ações) e
`LOGS_SISTEMA.md` (logs técnicos).

## 1. Uploads de arquivos

Regras aplicadas em todos os pontos de upload:

| Módulo | Allowlist |
|---|---|
| Treinamentos / materiais | `pdf, doc, docx, xls, xlsx, jpg, jpeg, png, gif, zip, rar, txt` (máx. 250 MB) |
| Materiais em chunks | mesmo allowlist, validado no merge final pelo conteúdo real (finfo) |
| Splash | `jpg, jpeg, png, pdf` (máx. 10 MB) |
| Fotos de EPI / comprovantes / recibos | `jpg, jpeg, png, webp` (máx. 5 MB) |
| Perfil / rede social | imagens (reencode via GD quando disponível) |
| Logos de tenant | `png, jpg, jpeg, webp` (máx. 2 MB) |
| Projeto pedagógico | somente PDF |

Garantias:

- **Nunca** se usa nome/extensão enviados pelo cliente no caminho físico.
  `App\Support\SafeUpload` deriva a extensão do MIME real e gera nome UUID.
- Defesa em profundidade no Apache: `public/.htaccess` bloqueia execução de
  `.php`, `.phtml`, `.phar`, etc. em `storage/` e `uploads/`.
- Materiais ficam no disco `public` (servidos por `/storage/`); o download
  autenticado passa por `TrainingMaterialController::download` e é auditado.

## 2. Autenticação e sessão

- Login por CPF com `Hash::check` (bcrypt, `BCRYPT_ROUNDS`, default 12).
- Sessão regenerada no login (anti-fixation) e invalidada no logout.
- **Rate limit no login web**: `throttle:login` → 5 tentativas/min por CPF+IP e
  20/min por IP. Login da API: `throttle:10,1`. Avaliação: `throttle:10,1`.
- Senha **não** é gravada no flash de sessão em falhas (`withInput($request->except('password'))`).
- Rotas públicas de consulta (validação de certificado, ficha por token) têm
  `throttle:60,1` (web) e `throttle:30,1` (API).
- Token Sanctum expira em 30 dias por padrão (`SANCTUM_EXPIRATION`, em minutos;
  `0`/vazio = sem expiração).

Configuração recomendada em produção (`.env`):

```
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=true
SESSION_DRIVER=file           # ou database (tabela sessions criada nas migrations)
TRUSTED_PROXIES=              # IPs do proxy/LB, separados por vírgula (se houver)
CORS_ALLOWED_ORIGINS=https://app.suamarca.com.br
SANCTUM_EXPIRATION=43200
```

## 3. Autorização (RBAC)

- Middlewares: `admin`/`api.admin` (role ≠ `usuario`), `permission:<módulo>,<ação>`,
  `module:<slug>` (módulos contratados por tenant) e `role:super_admin` (alias `role`).
- **Escalação de privilégio bloqueada**: apenas super_admin pode atribuir o papel
  `super_admin`; admins não editam/excluem super_admin nem a própria conta.
  Campos enviados fora da whitelist (`tenant_id`, `status` em criação etc.) são ignorados.
- Permissões gerenciadas em `/admin/permissoes` (somente super_admin).
  O módulo **Auditoria** é concedível por lá (default: super_admin).

## 4. Dados pessoais (LGPD)

- Rotas públicas exibem apenas o necessário: validação de certificado e ficha
  pública da API mascaram CPF (formato `***.222.222-**`), e-mail e telefone.
- `mask_cpf()`, `mask_email()` e `mask_phone()` em `app/Support/helpers.php`.
- Auditoria mascara `password`, tokens e chaves (`AuditLogger::sanitize`).
- Backups e logs não devem sair do ambiente controlado; a ficha QR usa token
  aleatório de 32 caracteres.

## 5. Headers e transporte

`App\Http\Middleware\SecurityHeaders` (global) envia:

- `X-Frame-Options: SAMEORIGIN`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: geolocation=(), camera=(), microphone=()`
- `X-Permitted-Cross-Domain-Policies: none`
- `Strict-Transport-Security` (somente HTTPS + produção)
- `Content-Security-Policy-Report-Only` (não bloqueia; base para CSP definitiva)

## 6. Checklist de deploy seguro

1. `php artisan migrate --force` (cria `audit_logs` e tabelas de infraestrutura).
2. `.env` com `APP_DEBUG=false`, `APP_ENV=production`, `SESSION_SECURE_COOKIE=true`.
3. Trocar as senhas dos seeders padrão (`10178415430`, `11111111111`, `22222222222`).
4. Definir `CORS_ALLOWED_ORIGINS` e `TRUSTED_PROXIES` conforme a infraestrutura.
5. `php artisan config:cache` após ajustar variáveis (config lê `env()`).
6. `php artisan audit:prune` manual (ou garantir cron para o schedule diário).
7. Confirmar que `public/.htaccess` foi enviado no deploy.

## 7. Itens pendentes / evolução sugerida

- CSP definitiva (hoje apenas `Report-Only`) — exige migrar scripts inline para
  arquivos/`nonce`.
- QR Codes ainda usam `api.qrserver.com` (o PNG local exige extensão Imagick;
  `simple-qrcode` usa SVG sem Imagick, incompatível com o TCPDF).
- 2FA e recuperação de senha self-service (hoje a troca é feita por admin).
- Tokens Sanctum com abilities específicas em vez de `*`.
- Mover materiais sigilosos para disco privado com download assinado.
