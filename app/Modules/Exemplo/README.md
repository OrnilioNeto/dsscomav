# Módulo de Exemplo

Esqueleto de referência da infraestrutura modular (`app/Modules`).

Ele demonstra, funcionando de ponta a ponta:

- **Discovery automático** — `App\Providers\ModulesServiceProvider` encontra
  `App\Modules\Exemplo\Providers\ExemploServiceProvider` (nada a registrar em
  `config/app.php`).
- **Rotas próprias** — `routes/web.php` com `auth` + `role:super_admin`.
- **Views namespaced** — `view('exemplo::index')`.
- **Migration própria** — carregada no `php artisan migrate`.
- **Model com as traits do projeto** — `BelongsToTenant` + `Auditable`.
- **Menu automático** — via `registerMenu()` (visível apenas para quem tem a
  permissão; super_admin sempre vê).

Para criar um módulo real (ex.: Agendamento), siga
`docs/modulos/GUIA_MODULOS.md`. Para remover este exemplo, apague esta pasta —
o item de menu some automaticamente.
