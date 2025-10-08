<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialGroup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MaterialController extends Controller
{
    /**
     * Display a listing of materials (Public)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Material::query()->active()->with(['group.category']);

            // Filter by group
            if ($request->has('group_id')) {
                $query->forGroup($request->group_id);
            }

            // Filter by category
            if ($request->has('category_id')) {
                $query->forCategory($request->category_id);
            }

            $materials = $query->ordered()->get()->map(function ($material) {
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
            });

            return response()->json([
                'success' => true,
                'data' => $materials
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch materials',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of all materials for admin
     */
    public function adminIndex(): JsonResponse
    {
        try {
            $materials = Material::with(['group.category'])
                ->withCount('products')
                ->ordered()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $materials
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch materials',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created material
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'group_id' => 'required|exists:material_groups,id',
                'code' => 'required|string|max:50|unique:materials,code',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'color_hex' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'image_url' => 'nullable|string',
                'sort_order' => 'sometimes|integer|min:0',
                'is_active' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Ensure at least color or image is provided
            if (empty($request->color_hex) && empty($request->image_url)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Either color_hex or image_url must be provided'
                ], 422);
            }

            $data = $request->only(['group_id', 'code', 'name', 'description', 'color_hex', 'image_url', 'sort_order', 'is_active']);
            
            // Set default sort order if not provided
            if (!isset($data['sort_order'])) {
                $data['sort_order'] = Material::getNextSortOrder($data['group_id']);
            }

            $material = Material::create($data);
            $material->load(['group.category']);

            return response()->json([
                'success' => true,
                'data' => $material,
                'message' => 'Material created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create material',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified material
     */
    public function show($id): JsonResponse
    {
        try {
            $material = Material::with(['group.category', 'products'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $material
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Material not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified material
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $material = Material::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'group_id' => 'sometimes|exists:material_groups,id',
                'code' => 'sometimes|string|max:50|unique:materials,code,' . $id,
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'color_hex' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'image_url' => 'nullable|string',
                'sort_order' => 'sometimes|integer|min:0',
                'is_active' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->only(['group_id', 'code', 'name', 'description', 'color_hex', 'image_url', 'sort_order', 'is_active']);
            $material->update($data);
            $material->load(['group.category']);

            return response()->json([
                'success' => true,
                'data' => $material,
                'message' => 'Material updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update material',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified material
     */
    public function destroy($id): JsonResponse
    {
        try {
            $material = Material::findOrFail($id);
            
            // Check if material is used by products
            $productsCount = $material->products()->count();
            if ($productsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete material. It is used by {$productsCount} products."
                ], 400);
            }

            $material->delete();

            return response()->json([
                'success' => true,
                'message' => 'Material deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete material',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload material image
     */
    public function uploadImage(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid image file',
                    'errors' => $validator->errors()
                ], 422);
            }

            $image = $request->file('image');
            $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            
            // Store in Laravel storage
            $storagePath = $image->storeAs('materials', $imageName, 'public');
            
            // Also copy to Next.js public directory
            $nextjsPath = '../vs-nextjs/public/images/materials/';
            if (!file_exists($nextjsPath)) {
                mkdir($nextjsPath, 0755, true);
            }
            
            $image->move($nextjsPath, $imageName);
            
            // Return local path that Next.js can resolve
            $imageUrl = '/images/materials/' . $imageName;

            return response()->json([
                'success' => true,
                'data' => [
                    'image_url' => $imageUrl,
                    'storage_path' => $storagePath
                ],
                'message' => 'Image uploaded successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}