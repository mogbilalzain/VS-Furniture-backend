<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
            'image' => $this->image,
            'image_url' => $this->image_url,
            'alt_text' => $this->alt_text,
            'icon' => $this->icon,
            'color' => $this->color,
            'status' => $this->status,
            'revenue' => $this->revenue,
            'orders_count' => $this->orders_count,
            'products_count' => $this->whenCounted('products', $this->products_count),
            'properties_count' => $this->whenCounted('properties', $this->properties_count),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
