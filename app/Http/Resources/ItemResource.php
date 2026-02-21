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
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'purchase_value' => $this->resource->purchase_value,
            'serial' => $this->resource->serial,
            'purchased_at' => $this->resource->purchased_at,
            'manufactured_at' => $this->resource->manufactured_at,

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

            // Access Value (ITC)
            'access_value' => $this->whenLoaded('accessValue', function () {
                return [
                    'id' => $this->resource->accessValue->id,
                    'base_credit_value' => $this->resource->accessValue->base_credit_value,
                    'current_daily_rate' => $this->resource->accessValue->current_daily_rate,
                    'current_weekly_rate' => $this->resource->accessValue->current_weekly_rate,
                    'vote_count' => $this->resource->accessValue->vote_count,
                    'average_vote' => $this->resource->accessValue->average_vote,
                ];
            }),

            // Fallback if access value isn't loaded but we have the relationship
            'current_daily_rate' => $this->resource->accessValue?->current_daily_rate ?? 1.0,
            'base_credit_value' => $this->resource->accessValue?->base_credit_value ?? 1.0,
            'vote_count' => $this->resource->accessValue?->vote_count ?? 0,

            // Optional: featured flag
            'is_featured' => $this->resource->is_featured ?? false,

            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
