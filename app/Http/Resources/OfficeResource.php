<?php

namespace App\Http\Resources;

use App\Models\Image;
use App\Models\Tag;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;

class OfficeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request): array|JsonSerializable|Arrayable
    {
        return [
            'user' => UserResource::make($this->user),
            'images' => ImageResource::make($this->images),
            'tags' => TagResource::make($this->tags),

            $this->merge(Arr::except(parent::toArray($request), [
                'user_id', 'created_at', 'updated_at', 'deleted_at'
            ]))
        ];
    }
}
