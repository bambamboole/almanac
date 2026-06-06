<?php

namespace App\Http\Resources;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Passkeys\Passkey;

/**
 * @mixin Passkey
 */
class PasskeyResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{id: int, name: string, authenticator: string|null, created_at_diff: string, last_used_at_diff: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'authenticator' => $this->authenticator,
            'created_at_diff' => $this->diffForHumans($this->created_at),
            'last_used_at_diff' => $this->nullableDiffForHumans($this->last_used_at),
        ];
    }

    private function diffForHumans(CarbonInterface $date): string
    {
        return $date->diffForHumans();
    }

    private function nullableDiffForHumans(?CarbonInterface $date): ?string
    {
        return $date?->diffForHumans();
    }
}
