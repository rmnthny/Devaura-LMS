<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only([
                'member_id',
                'account_id',
                'member_role',
                'department_id',
                'reference_number',
                'member_code',
                'first_name',
                'last_name',
                'middle_initial',
                'deleted_at',
            ]),
            'department' => $this->department,
        ];
    }
}
