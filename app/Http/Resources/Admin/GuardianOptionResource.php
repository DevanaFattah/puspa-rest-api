<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuardianOptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'family_id'         => $this->family_id,
            'guardian_id'       => $this->id,
            'guardian_name'     => $this->guardian_name,
            'guardian_type'     => $this->guardian_type,
            'guardian_phone'    => $this->guardian_phone,
            'parent_email'      => $this->user?->email ?? $this->temp_email,
            'existing_children' => $this->family?->children ? $this->family->children->map(fn($child) => [
                'child_id'   => $child->id,
                'child_name' => $child->child_name,
            ])->values() : [],
        ];
    }
}
