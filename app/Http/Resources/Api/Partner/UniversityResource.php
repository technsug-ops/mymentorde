<?php

namespace App\Http\Resources\Api\Partner;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UniversityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'city'                => $this->city,
            'state'               => $this->state,
            'type'                => $this->type,
            'is_public'           => (bool) $this->is_public,
            'is_uni_assist_member'=> (bool) $this->is_uni_assist_member,
            'uni_assist_id'       => $this->uni_assist_id,
            'image_url'           => $this->image_path ? url($this->image_path) : null,
            'video_url'           => $this->video_url,
            'video_caption'       => $this->video_caption,
        ];
    }
}
