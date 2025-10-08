<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaterialCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MaterialCategoryController extends Controller
{
    /**
     * Display a listing of material categories (Public)
     */
    public function index(): JsonResponse
    {
        try {
            $categories = MaterialCategory::active()
                ->ordered()
                ->with(['activeMaterialGroups.activeMaterials'])
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'description' => $category->description,
                        'sort_order' => $category->sort_order,
                        'materials_count' => $category->active_materials_count,
                        'groups' => $category->activeMaterialGroups->map(function ($group) {
                            return [
                                'id' => $group->id,
                                'name' => $group->name,
                                'description' => $group->description,
                                'sort_order' => $group->sort_order,
                                'materials_count' => $group->active_materials_count,
                                'materials' => $group->activeMaterials->map(function ($material) {
                                    return [
                                        'id' => $material->id,
                                        'code' => $material->code,
                                        'name' => $material->name,
                                        'description' => $material->description,
                                        'color_hex' => $material->color_hex,
                                        'image_url' => $material->full_image_url,
                                        'display_type' => $material->display_type,
                                        'sort_order' => $material->sort_order,
                                    ];
                                }),
                            ];
                        }),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch material categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of all material categories for admin
     */
    public function adminIndex(): JsonResponse
    {
        try {
            $categories = MaterialCategory::ordered()
                ->withCount(['materialGroups', 'materials'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch material categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created material category
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:material_categories,name',
                'slug' => 'sometimes|string|max:255|unique:material_categories,slug',
                'description' => 'nullable|string',
                'sort_order' => 'sometimes|integer|min:0',
                'is_active' => 'sometimes|boolean',
            ]);

            $data = $request->only(['name', 'slug', 'description', 'sort_order', 'is_active']);
            
            // Set default sort order if not provided
            if (!isset($data['sort_order'])) {
                $data['sort_order'] = MaterialCategory::getNextSortOrder();
            }

            $category = MaterialCategory::create($data);

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Material category created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create material category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified material category
     */
    public function show($id): JsonResponse
    {
        try {
            $category = MaterialCategory::with(['activeMaterialGroups.activeMaterials'])
                ->findOrFail($id);

            $data = [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'sort_order' => $category->sort_order,
                'is_active' => $category->is_active,
                'materials_count' => $category->active_materials_count,
                'groups' => $category->activeMaterialGroups->map(function ($group) {
                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                        'description' => $group->description,
                        'sort_order' => $group->sort_order,
                        'materials_count' => $group->active_materials_count,
                        'materials' => $group->activeMaterials->map(function ($material) {
                            return [
                                'id' => $material->id,
                                'code' => $material->code,
                                'name' => $material->name,
                                'description' => $material->description,
                                'color_hex' => $material->color_hex,
                                'image_url' => $material->full_image_url,
                                'display_type' => $material->display_type,
                                'sort_order' => $material->sort_order,
                            ];
                        }),
                    ];
                }),
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Material category not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified material category
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $category = MaterialCategory::findOrFail($id);

            $request->validate([
                'name' => 'sometimes|string|max:255|unique:material_categories,name,' . $id,
                'slug' => 'sometimes|string|max:255|unique:material_categories,slug,' . $id,
                'description' => 'nullable|string',
                'sort_order' => 'sometimes|integer|min:0',
                'is_active' => 'sometimes|boolean',
            ]);

            $data = $request->only(['name', 'slug', 'description', 'sort_order', 'is_active']);
            $category->update($data);

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Material category updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update material category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified material category
     */
    public function destroy($id): JsonResponse
    {
        try {
            $category = MaterialCategory::findOrFail($id);
            
            // Check if category has groups/materials
            $groupsCount = $category->materialGroups()->count();
            if ($groupsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete category. It has {$groupsCount} material groups."
                ], 400);
            }

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Material category deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete material category',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}