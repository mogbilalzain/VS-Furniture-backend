<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'short_description' => $this->short_description,
            'model' => $this->model,
            'price' => $this->price,
            'stock_quantity' => $this->stock_quantity,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', function() {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                    'description' => $this->category->description,
                    'image' => $this->category->image,
                ];
            }),
            'category_name' => $this->category?->name,
            'image' => $this->primary_image_url,
            'image_url' => $this->primary_image_url,
            'images' => $this->whenLoaded('activeImages', function() {
                return $this->activeImages->map(function($image) {
                    return [
                        'id' => $image->id,
                        'image_url' => $image->full_image_url,
                        'alt_text' => $image->alt_text,
                        'title' => $image->title,
                        'is_primary' => $image->is_primary,
                        'sort_order' => $image->sort_order,
                    ];
                });
            }),
            'status' => $this->status,
            'property_values' => $this->whenLoaded('propertyValues', function() {
                return $this->propertyValues->map(function($propertyValue) {
                    return [
                        'id' => $propertyValue->id,
                        'value' => $propertyValue->value,
                        'category_property' => [
                            'id' => $propertyValue->categoryProperty->id,
                            'name' => $propertyValue->categoryProperty->name,
                        ]
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
