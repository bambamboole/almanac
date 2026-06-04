<?php

namespace App\Models;

use App\Enums\Permission;
use App\Enums\UserRole;
use Carbon\CarbonImmutable;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property Collection<int, Permission> $permissions
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * @property-read int|null $users_count
 *
 * @method static \Database\Factories\RoleFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role wherePermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'name',
    'permissions',
])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    public const Admin = UserRole::Admin->value;

    public const Member = UserRole::Member->value;

    protected $attributes = [
        'permissions' => '[]',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => AsEnumCollection::of(Permission::class),
        ];
    }

    public function hasPermission(Permission $permission): bool
    {
        return $this->permissions->contains($permission);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public static function admin(): self
    {
        return static::forName(UserRole::Admin);
    }

    public static function member(): self
    {
        return static::forName(UserRole::Member);
    }

    public static function forName(UserRole|string $name): self
    {
        $role = self::roleName($name);

        return static::firstOrCreate(['name' => $role], ['permissions' => static::defaultPermissions($role)]);
    }

    /**
     * @return array<int, Permission>
     */
    public static function defaultPermissions(UserRole|string $name): array
    {
        return self::roleName($name) === UserRole::Admin->value ? Permission::cases() : [];
    }

    private static function roleName(UserRole|string $role): string
    {
        return $role instanceof UserRole ? $role->value : $role;
    }
}
