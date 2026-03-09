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

            // Thumbnail (single image per item)
            'thumbnail_path' => $this->resource->thumbnail_path,
            'thumbnail_url' => $this->resource->thumbnail_path 
                ? asset('storage/' . $this->resource->thumbnail_path) 
                : null,

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
            'current_daily_rate' => $this->resource->accessValue->current_daily_rate ?? 1.0,
            'base_credit_value' => $this->resource->accessValue->base_credit_value ?? 1.0,
            'vote_count' => $this->resource->accessValue->vote_count ?? 0,

            // Optional: featured flag
            'is_featured' => $this->resource->is_featured ?? false,

            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
