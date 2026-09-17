<?php

namespace App\Support\Modules;

/**
 * Registro dos itens de menu declarados pelos módulos (via provider).
 *
 * O layout renderiza apenas os itens que o usuário logado pode acessar,
 * então adicionar um módulo novo não exige editar resources/views/layout.blade.php.
 */
class ModuleRegistry
{
    /**
     * @var array<int, array{slug: string, route: string, label: string, icon: string, permission: ?string, order: int}>
     */
    protected array $items = [];

    protected ?array $resolved = null;

    protected ?int $resolvedForUserId = null;

    public function register(array $item): void
    {
        $this->items[] = $item;
        $this->resolved = null;
    }

    /**
     * Itens visíveis para o usuário atual, ordenados.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $user = auth()->user();
        $userId = $user?->getAuthIdentifier();

        if ($this->resolved !== null && $this->resolvedForUserId === $userId) {
            return $this->resolved;
        }

        $this->resolvedForUserId = $userId;

        return $this->resolved = collect($this->items)
            ->filter(function (array $item) use ($user) {
                if (empty($item['permission'])) {
                    return true;
                }

                return $user && method_exists($user, 'hasPermission')
                    && $user->hasPermission($item['permission'], 'view');
            })
            ->sortBy('order')
            ->values()
            ->all();
    }

    /**
     * Todos os itens registrados, sem filtro de permissão (diagnóstico/testes).
     *
     * @return array<int, array<string, mixed>>
     */
    public function items(): array
    {
        return $this->items;
    }
}
