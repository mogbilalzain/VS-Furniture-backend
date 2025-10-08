<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Material;
use App\Models\ProductMaterial;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductMaterialController extends Controller
{
    /**
     * Get materials for a specific product (Public)
     */
    public function index($productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);
            
            $materials = $product->materials()
                ->with(['group.category'])
                ->get()
                ->groupBy(function ($material) {
                    return $material->group->category->name ?? 'Uncategorized';
                })
                ->map(function ($categoryMaterials, $categoryName) {
                    return [
                        'category' => $categoryName,
                        'materials' => $categoryMaterials->map(function ($material) {
                            return [
                                'id' => $material->id,
                                'code' => $material->code,
                                'name' => $material->name,
                                'description' => $material->description,
                                'color_hex' => $material->color_hex,
                                'image_url' => $material->full_image_url,
                                'display_type' => $material->display_type,
                                'is_default' => $material->pivot->is_default,
                                'sort_order' => $material->pivot->sort_order,
                                'group' => [
                                    'id' => $material->group->id,
                                    'name' => $material->group->name,
                                ]
                            ];
                        })->sortBy('sort_order')->values()
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'materials_by_category' => $materials
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product materials',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign material to product (Admin)
     */
    public function store(Request $request, $productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);

            $request->validate([
                'material_id' => 'required|exists:materials,id',
                'is_default' => 'sometimes|boolean',
                'sort_order' => 'sometimes|integer|min:0',
            ]);

            $materialId = $request->material_id;
            $isDefault = $request->boolean('is_default', false);
            $sortOrder = $request->sort_order;

            // Check if material is already assigned
            if (ProductMaterial::isAssigned($productId, $materialId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Material is already assigned to this product'
                ], 400);
            }

            // Assign the material
            $assignment = ProductMaterial::assignMaterial($productId, $materialId, $isDefault, $sortOrder);

            // Load the material with relationships
            $material = Material::with(['group.category'])->find($materialId);

            return response()->json([
                'success' => true,
                'data' => [
                    'assignment' => $assignment,
                    'material' => $material
                ],
                'message' => 'Material assigned to product successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign material to product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update material assignment for product (Admin)
     */
    public function update(Request $request, $productId, $materialId): JsonResponse
    {
        try {
            $assignment = ProductMaterial::where('product_id', $productId)
                ->where('material_id', $materialId)
                ->firstOrFail();

            $request->validate([
                'is_default' => 'sometimes|boolean',
                'sort_order' => 'sometimes|integer|min:0',
            ]);

            $data = $request->only(['is_default', 'sort_order']);
            
            // If setting as default, handle it properly
            if (isset($data['is_default']) && $data['is_default']) {
                $assignment->setAsDefault();
                unset($data['is_default']); // Remove from data since setAsDefault handles it
            }

            // Update other fields
            if (!empty($data)) {
                $assignment->update($data);
            }

            $assignment->load(['material.group.category']);

            return response()->json([
                'success' => true,
                'data' => $assignment,
                'message' => 'Material assignment updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update material assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove material from product (Admin)
     */
    public function destroy($productId, $materialId): JsonResponse
    {
        try {
            $assignment = ProductMaterial::where('product_id', $productId)
                ->where('material_id', $materialId)
                ->firstOrFail();

            $wasDefault = $assignment->is_default;
            $assignment->delete();

            // If we deleted the default material, set another one as default (if any exist)
            if ($wasDefault) {
                $nextMaterial = ProductMaterial::where('product_id', $productId)
                    ->orderBy('sort_order')
                    ->first();
                
                if ($nextMaterial) {
                    $nextMaterial->setAsDefault();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Material removed from product successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove material from product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder materials for a product (Admin)
     */
    public function reorder(Request $request, $productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);

            $request->validate([
                'material_ids' => 'required|array',
                'material_ids.*' => 'exists:materials,id',
            ]);

            $materialIds = $request->material_ids;
            ProductMaterial::reorderForProduct($productId, $materialIds);

            return response()->json([
                'success' => true,
                'message' => 'Materials reordered successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder materials',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set material as default for product (Admin)
     */
    public function setDefault($productId, $materialId): JsonResponse
    {
        try {
            $assignment = ProductMaterial::where('product_id', $productId)
                ->where('material_id', $materialId)
                ->firstOrFail();

            $assignment->setAsDefault();
            $assignment->load(['material.group.category']);

            return response()->json([
                'success' => true,
                'data' => $assignment,
                'message' => 'Material set as default successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set material as default',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}