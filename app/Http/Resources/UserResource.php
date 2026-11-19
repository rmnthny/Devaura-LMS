<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only([
                'id',
                'email',
                'account_status',
                'email_verified_at',
                'user_qr_value',
            ]),
            'roles' => $this->resource->getRoleNames(),
            'permissions' => $this->resource->getAllPermissions()->pluck('name'),
            'member' => $this->member ? new MemberResource($this->member) : null,
            'personnel' => $this->personnel?->loadMissing('department'),
        ];
    }
}
