<?php

namespace App\Http\Resources;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{id: int, name: string, permissions: array<int, string>}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'permissions' => $this->permissionValues(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function permissionValues(): array
    {
        return $this->permissions
            ->map(fn (Permission $permission): string => $permission->value)
            ->values()
            ->all();
    }
}
