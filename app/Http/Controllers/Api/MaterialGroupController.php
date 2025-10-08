<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaterialGroup;
use App\Models\MaterialCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MaterialGroupController extends Controller
{
    /**
     * Display a listing of material groups (Public)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = MaterialGroup::query()->active()->with(['category', 'activeMaterials']);

            // Filter by category
            if ($request->has('category_id')) {
                $query->forCategory($request->category_id);
            }

            $groups = $query->ordered()->get()->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'sort_order' => $group->sort_order,
                    'materials_count' => $group->active_materials_count,
                    'category' => [
                        'id' => $group->category->id,
                        'name' => $group->category->name,
                        'slug' => $group->category->slug,
                    ],
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
            });

            return response()->json([
                'success' => true,
                'data' => $groups
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch material groups',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of all material groups for admin
     */
    public function adminIndex(): JsonResponse
    {
        try {
            $groups = MaterialGroup::with(['category'])
                ->withCount(['materials'])
                ->ordered()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $groups
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch material groups',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created material group
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'category_id' => 'required|exists:material_categories,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'sort_order' => 'sometimes|integer|min:0',
                'is_active' => 'sometimes|boolean',
            ]);

            $data = $request->only(['category_id', 'name', 'description', 'sort_order', 'is_active']);
            
            // Set default sort order if not provided
            if (!isset($data['sort_order'])) {
                $data['sort_order'] = MaterialGroup::getNextSortOrder($data['category_id']);
            }

            $group = MaterialGroup::create($data);
            $group->load(['category']);

            return response()->json([
                'success' => true,
                'data' => $group,
                'message' => 'Material group created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create material group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified material group
     */
    public function show($id): JsonResponse
    {
        try {
            $group = MaterialGroup::with(['category', 'activeMaterials'])
                ->findOrFail($id);

            $data = [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'sort_order' => $group->sort_order,
                'is_active' => $group->is_active,
                'materials_count' => $group->active_materials_count,
                'category' => [
                    'id' => $group->category->id,
                    'name' => $group->category->name,
                    'slug' => $group->category->slug,
                ],
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

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Material group not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified material group
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $group = MaterialGroup::findOrFail($id);

            $request->validate([
                'category_id' => 'sometimes|exists:material_categories,id',
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'sort_order' => 'sometimes|integer|min:0',
                'is_active' => 'sometimes|boolean',
            ]);

            $data = $request->only(['category_id', 'name', 'description', 'sort_order', 'is_active']);
            $group->update($data);
            $group->load(['category']);

            return response()->json([
                'success' => true,
                'data' => $group,
                'message' => 'Material group updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update material group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified material group
     */
    public function destroy($id): JsonResponse
    {
        try {
            $group = MaterialGroup::findOrFail($id);
            
            // Check if group has materials
            $materialsCount = $group->materials()->count();
            if ($materialsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete group. It has {$materialsCount} materials."
                ], 400);
            }

            $group->delete();

            return response()->json([
                'success' => true,
                'message' => 'Material group deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete material group',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}