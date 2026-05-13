<?php

namespace App\Http\Resources\Api\Partner;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UniversitySummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'city'                => $this->city,
            'state'               => $this->state,
            'type'                => $this->type,                  // tu / hochschule / private
            'is_public'           => (bool) $this->is_public,
            'is_uni_assist_member'=> (bool) $this->is_uni_assist_member,
            'image_url'           => $this->image_path ? url($this->image_path) : null,
        ];
    }
}
