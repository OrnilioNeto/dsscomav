<?php

namespace App\Support;

use App\Models\RolePermission;

/**
 * Cache em memória das permissões por papel, escopado ao ciclo de vida do
 * container (um request). Evita ~20 queries por página na renderização do menu.
 */
class RolePermissionCache
{
    /** @var array<int, array<string, RolePermission>> */
    private array $maps = [];

    /**
     * @return array<string, RolePermission>
     */
    public function mapFor(int $roleId): array
    {
        if (! array_key_exists($roleId, $this->maps)) {
            $this->maps[$roleId] = RolePermission::query()
                ->where('role_id', $roleId)
                ->get()
                ->keyBy('module')
                ->all();
        }

        return $this->maps[$roleId];
    }

    public function forget(?int $roleId = null): void
    {
        if ($roleId === null) {
            $this->maps = [];

            return;
        }

        unset($this->maps[$roleId]);
    }
}
