<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,

            // Ownership
            'owned_by' => $this->user_id,

            // Relationships
            'archetype' => $this->whenLoaded('archetype', function () {
                return [
                    'id' => $this->archetype->id,
                    'name' => $this->archetype->name,
                ];
            }),

            'brand' => $this->whenLoaded('brand', function () {
                return [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                ];
            }),

            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),

            'usage' => $this->whenLoaded('usage', function () {
                return [
                    'id' => $this->usage->id,
                    'name' => $this->usage->name,
                ];
            }),

            // Images (important for your v-img)
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'url' => $image->url, // accessor on Image model
                    ];
                });
            }),

            // Optional: featured flag
            'is_featured' => $this->is_featured ?? false,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
