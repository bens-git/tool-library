<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Item $resource
 */
class ItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'code' => $this->resource->code,

            // Ownership
            'owned_by' => $this->resource->owned_by,

            // Relationships
            'archetype' => $this->whenLoaded('archetype', function () {
                return [
                    'id' => $this->resource->archetype->id,
                    'name' => $this->resource->archetype->name,
                ];
            }),

            'brand' => $this->whenLoaded('brand', function () {
                return [
                    'id' => $this->resource->brand->id,
                    'name' => $this->resource->brand->name,
                ];
            }),

            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->resource->category->id,
                    'name' => $this->resource->category->name,
                ];
            }),

            'usage' => $this->whenLoaded('usage', function () {
                return [
                    'id' => $this->resource->usage->id,
                    'name' => $this->resource->usage->name,
                ];
            }),

            // Images (important for your v-img)
            'images' => $this->whenLoaded('images', function (): array {
                return $this->resource->images->map(function ($image): array {
                    return [
                        'id' => $image->id,
                        'url' => $image->url, // accessor on Image model
                    ];
                })->all();
            }),

            // Optional: featured flag
            'is_featured' => $this->resource->is_featured ?? false,

            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
