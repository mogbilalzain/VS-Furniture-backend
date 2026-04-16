<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Helpers\ImageHelper;

/**
 * @OA\Tag(
 *     name="Categories",
 *     description="Category management endpoints"
 * )
 */
class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/categories",
     *     summary="Get all categories",
     *     tags={"Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="Categories retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Category")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $categories = Category::withCount('products')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories)
        ]);
    }

    /**
     * @OA\Get(
     *     path="/admin/categories",
     *     summary="Get all categories for admin",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Categories retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Category")
     *             )
     *         )
     *     )
     * )
     */
    public function adminIndex()
    {
        $categories = Category::withCount(['products', 'properties'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories)
        ]);
    }

    /**
     * @OA\Get(
     *     path="/categories/{category}",
     *     summary="Get single category",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         description="Category ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Category")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found"
     *     )
     * )
     */
    public function show(Category $category)
    {
        if ($category->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $category->loadCount('products');

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category)
        ]);
    }

    /**
     * @OA\Get(
     *     path="/categories/{category}/products",
     *     summary="Get products in category",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         description="Category ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Products retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Product")
     *             )
     *         )
     *     )
     * )
     */
    public function products(Category $category)
    {
        $products = Product::with('category')
            ->where('category_id', $category->id)
            ->active()
            ->get();

        return response()->json([
            'success' => true,
            'data' => \App\Http\Resources\ProductResource::collection($products)
        ]);
    }

    /**
     * @OA\Post(
     *     path="/categories",
     *     summary="Create category (admin only)",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", maxLength=255),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Category created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Category created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Category")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Admin access required"
     *     )
     * )
     */
    public function store(Request $request)
    {
        // Log incoming request for debugging
        Log::info('Category store request:', $request->all());
        
        // Check if name already exists
        $nameExists = Category::where('name', $request->name)->exists();
        if ($nameExists) {
            Log::error('Category name already exists:', ['name' => $request->name]);
            return response()->json([
                'success' => false,
                'message' => 'Category name already exists! Please choose a different name.',
                'errors' => [
                    'name' => ['The category "' . $request->name . '" already exists in the system.']
                ],
                'suggestions' => 'Try adding additional words like "New", "Updated", or a number for distinction.'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            Log::error('Category validation failed:', $validator->errors()->toArray());
            
            // Create user-friendly error messages
            $friendlyErrors = [];
            foreach ($validator->errors()->toArray() as $field => $messages) {
                switch ($field) {
                    case 'name':
                        $friendlyErrors[$field] = ['Category name is required and must be less than 255 characters.'];
                        break;
                    case 'description':
                        $friendlyErrors[$field] = ['Category description must be valid text.'];
                        break;
                    case 'color':
                        $friendlyErrors[$field] = ['Category color must be a valid color code (e.g., #3d5c4d).'];
                        break;
                    case 'status':
                        $friendlyErrors[$field] = ['Category status must be "active" or "inactive".'];
                        break;
                    default:
                        $friendlyErrors[$field] = ['The value entered in ' . $field . ' field is invalid.'];
                }
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Please check your input data and try again.',
                'errors' => $friendlyErrors
            ], 400);
        }

        try {
            // Generate slug if not provided
            $slug = $request->slug ?: Str::slug($request->name);
            
            // Ensure slug is unique
            $originalSlug = $slug;
            $counter = 1;
            while (Category::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $category = Category::create([
                'name' => $request->name,
                'slug' => $slug,
                'description' => $request->description,
                'icon' => $request->icon ?: 'fas fa-cube',
                'color' => $request->color ?: '#3d5c4d',
                'status' => $request->status ?: 'active',
                'revenue' => 0,
                'orders_count' => 0,
            ]);
        } catch (\Exception $e) {
            Log::error('Category creation failed:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Sorry, an error occurred while creating the category. Please try again.',
                'error_details' => config('app.debug') ? $e->getMessage() : null,
                'support_message' => 'If the problem persists, please contact technical support.'
            ], 500);
        }

        $category->loadCount('products');

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully! 🎉',
            'data' => new CategoryResource($category),
            'next_steps' => 'You can now add products to this category or modify its properties.'
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/categories/{category}",
     *     summary="Update category (admin only)",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         description="Category ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", maxLength=255),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Category updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Category")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Admin access required"
     *     )
     * )
     */
    public function update(Request $request, Category $category)
    {
        // Log update request
        Log::info('Category update request:', array_merge($request->all(), ['category_id' => $category->id]));
        
        // Check if name already exists (excluding current category)
        $nameExists = Category::where('name', $request->name)
                            ->where('id', '!=', $category->id)
                            ->exists();
        if ($nameExists) {
            Log::error('Category name already exists during update:', ['name' => $request->name, 'category_id' => $category->id]);
            return response()->json([
                'success' => false,
                'message' => 'Cannot update category! The new name already exists.',
                'errors' => [
                    'name' => ['The name "' . $request->name . '" is already used by another category.']
                ],
                'current_category' => [
                    'name' => $category->name,
                    'id' => $category->id
                ]
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            Log::error('Category update validation failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            // Generate slug if not provided or changed
            $slug = $request->slug ?: Str::slug($request->name);
            
            // Ensure slug is unique (excluding current category)
            $originalSlug = $slug;
            $counter = 1;
            while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $category->update([
                'name' => $request->name,
                'slug' => $slug,
                'description' => $request->description,
                'icon' => $request->icon ?: $category->icon,
                'color' => $request->color ?: $category->color,
                'status' => $request->status ?: $category->status,
            ]);
        } catch (\Exception $e) {
            Log::error('Category update failed:', ['error' => $e->getMessage(), 'category_id' => $category->id]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category: ' . $e->getMessage()
            ], 500);
        }

        $category->loadCount('products');

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => new CategoryResource($category)
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/categories/{category}",
     *     summary="Delete category (admin only)",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         description="Category ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Category deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot delete category with products"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Admin access required"
     *     )
     * )
     */
    public function destroy(Category $category)
    {
        // Check if category has products
        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with products'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }

    /**
     * Get category properties
     */
    public function getCategoryProperties(Category $category)
    {
        try {
            $properties = $category->properties()->with(['propertyValues', 'propertyGroup'])->get();
            
            return response()->json([
                'success' => true,
                'data' => $properties
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get category properties: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update category properties
     */
    public function updateProperties(Request $request, Category $category)
    {
        $request->validate([
            'property_ids' => 'required|array',
            'property_ids.*' => 'exists:category_properties,id'
        ]);

        try {
            // Get the property IDs
            $propertyIds = $request->property_ids;
            
            // First, remove all properties from this category
            \App\Models\CategoryProperty::where('category_id', $category->id)
                ->update(['category_id' => null]);
            
            // Then, assign the selected properties to this category
            if (!empty($propertyIds)) {
                \App\Models\CategoryProperty::whereIn('id', $propertyIds)
                    ->update(['category_id' => $category->id]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Category properties updated successfully',
                'data' => [
                    'category_id' => $category->id,
                    'assigned_properties' => $propertyIds,
                    'total_assigned' => count($propertyIds)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating category properties: ' . $e->getMessage(), [
                'category_id' => $category->id,
                'property_ids' => $request->property_ids,
                'exception' => $e
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category properties: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload image for a category
     */
    public function uploadImage(Request $request, $categoryId)
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120',
                'alt_text' => 'sometimes|string|max:255',
            ]);

            $category = Category::findOrFail($categoryId);
            
            // Delete old image if exists (من النظام القديم أو الجديد)
            if ($category->image) {
                // محاولة حذف من النظام الجديد
                $oldNewPath = str_replace('/uploads/', '', $category->image);
                ImageHelper::deleteImage($oldNewPath);
                
                // محاولة حذف من النظام القديم
                $oldImagePath = str_replace('/storage/', '', $category->image);
                if (\Storage::disk('public')->exists($oldImagePath)) {
                    \Storage::disk('public')->delete($oldImagePath);
                }
            }
            
            $image = $request->file('image');
            
            // استخدام النظام الجديد
            $result = ImageHelper::uploadImage($image, 'categories');
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result['error'] ?? null
                ], 400);
            }

            $imageData = $result['data'];

            // Update category with new image
            $category->update([
                'image' => $imageData['url'], // المسار الجديد
                'alt_text' => $request->get('alt_text', $category->name . ' category image'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category image uploaded successfully to new storage system',
                'data' => [
                    'image' => $imageData['url'],
                    'image_url' => $imageData['full_url'],
                    'alt_text' => $category->alt_text,
                    'size' => $imageData['size_formatted'],
                    'dimensions' => $imageData['dimensions'],
                    'mime_type' => $imageData['mime_type']
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Category image upload failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload category image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete category image
     */
    public function deleteImage($categoryId)
    {
        try {
            $category = Category::findOrFail($categoryId);
            
            // Delete image file if exists
            if ($category->image) {
                $imagePath = str_replace('/storage/', '', $category->image);
                if (\Storage::disk('public')->exists($imagePath)) {
                    \Storage::disk('public')->delete($imagePath);
                }
            }
            
            // Update category to remove image
            $category->update([
                'image' => null,
                'alt_text' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category image deleted successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Category image deletion failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category image',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
