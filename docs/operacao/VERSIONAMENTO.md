# Versionamento — Plataforma DSS

A versão do sistema aparece no rodapé de todas as páginas:
`v2.0.0 — atualizado em 16/09/2026`.

## Fonte única

`config/version.php`:

```php
return [
    'version' => '2.0.0',        // exibido como v2.0.0
    'released_at' => '2026-09-16', // exibido como 16/09/2026
];
```

Helpers de exibição (`app/Support/helpers.php`):

- `app_version()` → `v2.0.0`
- `app_version_date()` → `16/09/2026`

O rodapé está em `resources/views/layout.blade.php` (bloco `<footer class="site-footer">`).
Páginas stand-alone (ficha pública, PDFs) não exibem a versão.

## Como lançar uma nova versão

1. Atualize `config/version.php` (`version` + `released_at`).
2. Adicione uma seção no topo do `CHANGELOG.md` (formato Keep a Changelog):

   ```markdown
   ## [2.1.0] - 2026-10-01

   ### Adicionado
   - ...
   ### Corrigido
   - ...
   ```

3. Rode a suíte de testes: `php -d extension=gd -d extension=fileinfo vendor/phpunit/phpunit/phpunit`
4. Faça o deploy normal (`php artisan migrate --force` + limpeza de cache).
5. Em produção, rode `php artisan config:cache` para a nova versão aparecer.

## Convenção (SemVer)

| Mudança | Exemplo |
|---|---|
| Correções e ajustes internos | `2.0.1` |
| Novas funcionalidades compatíveis | `2.1.0` |
| Mudanças incompatíveis / grandes marcos | `3.0.0` |

Datas sempre em `AAAA-MM-DD` no config e `DD/MM/AAAA` na exibição.

## Observações

- Não há build step: a versão não vem de tooling de frontend nem de hash de assets.
- O rodapé aparece em todas as views que usam `@extends('layout')` (praticamente
  todo o sistema).
- `CHANGELOG.md` é versionado no Git; mantenha-o junto com o bump da versão.
