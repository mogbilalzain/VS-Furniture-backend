<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CategoryProperty;
use App\Models\PropertyValue;
use App\Models\Category;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    /**
     * Get all properties for a specific category
     */
    public function getCategoryProperties($categoryId)
    {
        try {
            $category = Category::findOrFail($categoryId);
            
            $properties = $category->activeProperties()
                ->with(['activePropertyValues'])
                ->get()
                ->map(function($property) {
                    return [
                        'id' => $property->id,
                        'name' => $property->name,
                        'display_name' => $property->display_name,
                        'input_type' => $property->input_type,
                        'is_required' => $property->is_required,
                        'values' => $property->activePropertyValues->map(function($value) {
                            return [
                                'id' => $value->id,
                                'value' => $value->value,
                                'display_name' => $value->display_name,
                                'product_count' => $value->product_count,
                            ];
                        }),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'category' => ['id' => $category->id, 'name' => $category->name],
                    'properties' => $properties,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب خصائص الفئة',
            ], 500);
        }
    }

    /**
     * Store a new property for a category
     */
    public function storeProperty(Request $request, $categoryId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'input_type' => 'required|in:checkbox,radio,select',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        try {
            $property = CategoryProperty::create([
                'category_id' => $categoryId,
                'name' => $request->name,
                'display_name' => $request->display_name ?: $request->name,
                'description' => $request->description,
                'input_type' => $request->input_type,
                'is_required' => $request->boolean('is_required', false),
                'is_active' => $request->boolean('is_active', true),
                'is_filterable' => $request->boolean('is_filterable', true),
                'sort_order' => $request->integer('sort_order', 0),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Property created successfully',
                'data' => $property,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create property',
            ], 500);
        }
    }

    /**
     * Store a new property value
     */
    public function storePropertyValue(Request $request, $propertyId)
    {
        $request->validate([
            'value' => 'required|string|max:255',
            'display_name' => 'required|string|max:255',
        ]);

        try {
            $propertyValue = PropertyValue::create([
                'category_property_id' => $propertyId,
                'value' => $request->value,
                'display_name' => $request->display_name,
                'sort_order' => $request->integer('sort_order', 0),
                'product_count' => 0,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء قيمة الخاصية بنجاح',
                'data' => $propertyValue,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في إنشاء قيمة الخاصية',
            ], 500);
        }
    }

    /**
     * Update a property
     */
    public function updateProperty(Request $request, $propertyId)
    {
        try {
            $property = CategoryProperty::findOrFail($propertyId);
            $property->update($request->only([
                'name', 'display_name', 'input_type', 'is_required', 'is_active', 'sort_order'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Property updated successfully',
                'data' => $property,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update property',
            ], 500);
        }
    }

    /**
     * Delete a property
     */
    public function deleteProperty($propertyId)
    {
        try {
            $property = CategoryProperty::findOrFail($propertyId);
            $property->delete();

            return response()->json([
                'success' => true,
                'message' => 'Property deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete property',
            ], 500);
        }
    }

    /**
     * Get all properties for admin management
     */
    public function adminIndex()
    {
        try {
            $properties = CategoryProperty::with(['category', 'propertyValues'])
                ->orderBy('category_id')
                ->orderBy('sort_order')
                ->get()
                ->map(function($property) {
                    return [
                        'id' => $property->id,
                        'name' => $property->name,
                        'display_name' => $property->display_name,
                        'description' => $property->description,
                        'input_type' => $property->input_type,
                        'is_required' => $property->is_required,
                        'is_active' => $property->is_active,
                        'is_filterable' => $property->is_filterable,
                        'sort_order' => $property->sort_order,
                        'category' => $property->category ? [
                            'id' => $property->category->id,
                            'name' => $property->category->name,
                        ] : null,
                        'values_count' => $property->propertyValues->count(),
                        'active_values_count' => $property->propertyValues->where('is_active', true)->count(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $properties,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load properties',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all property values for admin management
     */
    public function adminPropertyValues()
    {
        try {
            $propertyValues = PropertyValue::with(['categoryProperty.category'])
                ->orderBy('category_property_id')
                ->orderBy('sort_order')
                ->get()
                ->map(function($value) {
                    $property = $value->categoryProperty;
                    $category = $property ? $property->category : null;
                    
                    return [
                        'id' => $value->id,
                        'value' => $value->value,
                        'display_name' => $value->display_name,
                        'sort_order' => $value->sort_order,
                        'product_count' => $value->product_count,
                        'is_active' => $value->is_active,
                        'category_property_id' => $value->category_property_id,
                        'property' => $property ? [
                            'id' => $property->id,
                            'name' => $property->name,
                            'display_name' => $property->display_name,
                            'category_id' => $property->category_id,
                        ] : null,
                        'category' => $category ? [
                            'id' => $category->id,
                            'name' => $category->name,
                        ] : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $propertyValues,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in adminPropertyValues: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load property values',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}