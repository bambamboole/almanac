<?php

namespace App\Http\Requests\Users;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'role' => ['required', new Enum(UserRole::class)],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $user = $this->route('user');

                if (! $user instanceof User) {
                    return;
                }

                if (UserRole::tryFrom((string) $this->input('role')) !== UserRole::Admin && $this->isLastAdmin($user)) {
                    $validator->errors()->add('role', __('The last administrator cannot be changed to a member.'));
                }
            },
        ];
    }

    private function isLastAdmin(User $user): bool
    {
        $adminRole = Role::admin();

        return $user->role_id === $adminRole->id
            && User::query()->where('role_id', $adminRole->id)->count() === 1;
    }
}
