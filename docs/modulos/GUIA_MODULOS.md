# Guia de Módulos — Plataforma DSS

Como criar um módulo novo no padrão `app/Modules` (infraestrutura própria, sem
dependências externas). Módulos antigos (`epi`, `trainings`, `folgas`, ...)
continuam onde estão; a migração deles é opcional e feita aos poucos.

## Quando usar módulo

Use módulo quando a funcionalidade tem **domínio próprio** (ex.: agendamento,
ouvidoria, inspeções) — com rotas, telas, tabelas e regras que fazem sentido
juntas. Ajustes em módulos existentes não precisam virar módulo novo.

## Estrutura

```
app/Modules/Agendamento/
├── Providers/AgendamentoServiceProvider.php   # obrigatório
├── Http/Controllers/...                       # controllers do módulo
├── Http/Controllers/Api/...                   # controllers da API (opcional)
├── Models/...                                 # models do módulo
├── Services/...                               # regras de negócio (opcional)
├── Console/Commands/...                       # commands artisan (opcional)
├── routes/web.php                             # rotas web (opcional)
├── routes/api.php                             # rotas /api (opcional)
├── resources/views/...                        # views `agendamento::nome`
├── database/migrations/...                    # migrations do módulo
└── lang/...                                   # traduções (opcional)
```

Regras de ouro:
- A pasta, a classe do provider e o slug devem ter o **mesmo nome**:
  `Agendamento/Providers/AgendamentoServiceProvider.php` → `App\Modules\Agendamento\Providers\AgendamentoServiceProvider`.
  No Linux (produção) maiúsculas/minúsculas importam.
- Nada de registrar o provider em `config/app.php`: o
  `App\Providers\ModulesServiceProvider` descobre e registra tudo sozinho.
- Não é preciso `composer dump-autoload` (PSR-4 `App\` → `app/` já cobre).

## Passo a passo

### 1. Provider

```php
namespace App\Modules\Agendamento\Providers;

use App\Support\Modules\ModuleServiceProvider;

class AgendamentoServiceProvider extends ModuleServiceProvider
{
    protected string $slug = 'agendamento';
    protected string $name = 'Agendamento';

    public function boot(): void
    {
        parent::boot(); // rotas, views, migrations, commands, lang

        $this->registerMenu(
            route: 'agendamento.index',
            label: 'Agendamento',
            icon: 'fas fa-calendar-check',
            order: 50
        );
    }

    protected function moduleCommands(): array
    {
        return [\App\Modules\Agendamento\Console\Commands\AgendamentoFechar::class];
    }
}
```

### 2. Rotas do módulo

`routes/web.php` (o grupo `web` já é aplicado pelo provider; adicione auth/permissão):

```php
use App\Modules\Agendamento\Http\Controllers\AgendamentoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin', 'permission:agendamento', 'module:agendamento'])
    ->prefix('admin/agendamento')
    ->name('agendamento.')
    ->group(function () {
        Route::get('/', [AgendamentoController::class, 'index'])->name('index');
        Route::post('/', [AgendamentoController::class, 'store'])->name('store');
    });

// Área do usuário final (sem 'admin')
Route::middleware(['auth'])->prefix('agendamento')->name('agendamento.user.')->group(function () {
    Route::get('/meus', [AgendamentoController::class, 'meus'])->name('meus');
});
```

`routes/api.php` (health do módulo; o prefixo `/api` e o grupo `api` são aplicados):

```php
Route::prefix('v1/agendamento')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [\App\Modules\Agendamento\Http\Controllers\Api\AgendamentoController::class, 'index']);
});
```

Middlewares disponíveis: `auth`, `admin`/`api.admin`, `permission:<slug>,<view|edit>`,
`module:<slug>` (módulo contratado pelo tenant), `role:super_admin`.

### 3. Models

Sempre `BelongsToTenant` (isolamento por tenant) e `Auditable` (trilha de auditoria):

```php
namespace App\Modules\Agendamento\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Agendamento extends Model
{
    use Auditable, BelongsToTenant;

    protected $fillable = ['titulo', 'data', 'user_id'];
}
```

### 4. Migrations

Em `database/migrations/` dentro do módulo (o `php artisan migrate` acha sozinho).
Prefixo de data + guarda `hasTable` + `tenant_id` nullable indexado:

```php
Schema::create('agendamentos', function (Blueprint $table) {
    $table->id();
    $table->string('titulo');
    $table->dateTime('data');
    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->unsignedBigInteger('tenant_id')->nullable();
    $table->timestamps();
    $table->index('tenant_id');
});
```

### 5. Views

Namespace automático: `view('agendamento::index')` →
`app/Modules/Agendamento/resources/views/index.blade.php` (estenda `layout`).

### 6. Permissões e módulos por tenant

Adicione o módulo em **`config/modules.php`** (fonte única — alimenta a RBAC e o
gate `module:` por tenant):

```php
'agendamento' => [
    'label' => 'Agendamento',
    'description' => 'Agenda de treinamentos e inspeções',
],
```

Depois, em `/admin/permissoes`, conceda Visualizar/Editar aos perfis (a matriz já
mostra o módulo novo). No painel `/plataforma` o cliente também passa a listar o
módulo para liberação por tenant.

### 7. Menu

O `layout.blade.php` já renderiza os itens registrados em `registerMenu()` para
quem tem a permissão informada (super_admin sempre vê). Basta o item do provider.

### 8. Auditoria e uploads

- Ações CRUD são auditadas automaticamente pela trait `Auditable`.
- Ações fora de CRUD (download, exportação, sincronização):
  `app(\App\Services\AuditLogger::class)->log('downloaded', ['module' => 'agendamento', ...])`.
- Uploads: validação `mimes:`/`max` + `App\Support\SafeUpload` (nunca use nome do
  cliente no caminho físico).

### 9. Testes

`tests/Feature/Modules/Agendamento/AgendamentoTest.php` (namespace
`Tests\Feature\Modules\Agendamento`) — roda junto com a suíte:

```
php -d extension=gd -d extension=fileinfo vendor/phpunit/phpunit/phpunit
```

Cubra: permissão (403/200), isolamento por tenant quando multi-tenancy ativo,
auditoria gerada, validação de upload.

### 10. Deploy e versionamento

1. Envie a pasta `app/Modules/Agendamento` (nada a editar em `config/app.php`).
2. `php artisan migrate --force` (migrations do módulo entram na fila).
3. `php artisan config:cache` se usar cache de config (o catálogo mudou).
4. Atualize `CHANGELOG.md` e `config/version.php` (ver `VERSIONAMENTO.md`).
5. Documente o módulo em `docs/modulos/AGENDAMENTO.md`.

## Checklist rápido

- [ ] Pasta/classe/slug com o mesmo nome (case-sensitive)
- [ ] Provider estende `ModuleServiceProvider` e chama `parent::boot()`
- [ ] Rotas com `auth` + `permission:` (+ `module:` se for módulo de tenant)
- [ ] Tabelas com `tenant_id` + models com `BelongsToTenant`/`Auditable`
- [ ] Entrada em `config/modules.php` (RBAC + tenant)
- [ ] Item de menu via `registerMenu()`
- [ ] Uploads com `SafeUpload` + `mimes:`
- [ ] Testes em `tests/Feature/Modules/<Modulo>/`
- [ ] `docs/modulos/<MODULO>.md` + CHANGELOG + versão

## Referência viva

O módulo `app/Modules/Exemplo` é o esqueleto mínimo funcionando (rota, view,
migration, model e menu) e é usado pelos testes da infraestrutura. Pode ser
apagado a qualquer momento — o menu some junto.
