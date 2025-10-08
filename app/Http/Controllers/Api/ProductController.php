<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Products",
 *     description="Product management endpoints"
 * )
 */
class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/products",
     *     summary="Get all products (public)",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in product name and description",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
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
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="page", type="integer"),
     *                 @OA\Property(property="limit", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="pages", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Product::with('category')->active();

        // Search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->has('category')) {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Property filters
        $propertyFilters = $request->all();
        
        // Skip non-property parameters
        $skipParams = ['category', 'search', 'sort', 'page', 'limit'];
        
        foreach ($propertyFilters as $key => $values) {
            if (!in_array($key, $skipParams) && is_array($values) && !empty($values)) {
                // Filter products that have property values with the specified IDs
                $query->whereHas('propertyValues', function($q) use ($values) {
                    $q->whereIn('property_values.id', $values);
                });
            }
        }

        // Sort products
        $sortBy = $request->get('sort', 'name');
        switch ($sortBy) {
            case 'name':
                $query->orderBy('name');
                break;
            case 'newest':
                $query->latest();
                break;
            case 'oldest':
                $query->oldest();
                break;
            default:
                $query->orderBy('name');
        }

        $products = $query->with(['category', 'propertyValues.categoryProperty', 'activeImages'])->paginate($request->get('limit', 10));

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
            'pagination' => [
                'page' => $products->currentPage(),
                'limit' => $products->perPage(),
                'total' => $products->total(),
                'pages' => $products->lastPage(),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/products/{product}",
     *     summary="Get single product (public)",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */
    public function show(Product $product)
    {
        if ($product->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product->load(['category', 'activeImages']))
        ]);
    }

    /**
     * @OA\Post(
     *     path="/products",
     *     summary="Create product (admin only)",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","price","category_id"},
     *             @OA\Property(property="name", type="string", maxLength=255),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="model", type="string", maxLength=255),
     *             @OA\Property(property="price", type="number", minimum=0),
     *             @OA\Property(property="stock_quantity", type="integer", minimum=0),
     *             @OA\Property(property="category_id", type="integer"),
     *             @OA\Property(property="status", type="string", enum={"active","inactive"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
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
        \Log::info('📥 Product store request received');
        \Log::info('📄 Request method: ' . $request->getMethod());
        \Log::info('📄 Content type: ' . $request->header('Content-Type'));
        \Log::info('📄 Request data: ', $request->all());
        \Log::info('📄 Request files: ', $request->allFiles());
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'specifications' => 'nullable|array',
            'model' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'status' => 'nullable|in:active,inactive',
            'is_featured' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'property_values' => 'nullable|array',
            'property_values.*' => 'integer|exists:property_values,id',
        ]);

        if ($validator->fails()) {
            \Log::error('❌ Product validation failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $productData = $request->all();
        
        // Remove property_values from product data (handle separately)
        $propertyValues = $productData['property_values'] ?? [];
        unset($productData['property_values']);

        $product = Product::create($productData);

        // Attach property values if provided
        if (!empty($propertyValues)) {
            $product->attachProperties($propertyValues);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => new ProductResource($product->load(['category', 'propertyValues', 'activeFiles']))
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/products/{product}",
     *     summary="Update product (admin only)",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","price","category_id"},
     *             @OA\Property(property="name", type="string", maxLength=255),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="model", type="string", maxLength=255),
     *             @OA\Property(property="price", type="number", minimum=0),
     *             @OA\Property(property="stock_quantity", type="integer", minimum=0),
     *             @OA\Property(property="category_id", type="integer"),
     *             @OA\Property(property="status", type="string", enum={"active","inactive"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
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
    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug,' . $product->id,
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'specifications' => 'nullable|array',
            'model' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'status' => 'nullable|in:active,inactive',
            'is_featured' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'property_values' => 'nullable|array',
            'property_values.*' => 'integer|exists:property_values,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $product->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => new ProductResource($product->load('category'))
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/products/{product}",
     *     summary="Delete product (admin only)",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Admin access required"
     *     )
     * )
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/admin/products",
     *     summary="Get all products for admin",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in product name and description",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active","inactive"})
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
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
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="page", type="integer"),
     *                 @OA\Property(property="limit", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="pages", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Admin access required"
     *     )
     * )
     */
    public function adminIndex(Request $request)
    {
        $query = Product::with('category');

        // Search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->has('category')) {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('name', $request->category);
            });
        }

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->latest()->paginate($request->get('limit', 10));

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
            'pagination' => [
                'page' => $products->currentPage(),
                'limit' => $products->perPage(),
                'total' => $products->total(),
                'pages' => $products->lastPage(),
            ]
        ]);
    }

    /**
     * Upload product image
     */
    public function uploadImage(Request $request)
    {
        \Log::info('📥 Image upload request received');
        \Log::info('📄 Request method: ' . $request->getMethod());
        \Log::info('📄 Content type: ' . $request->header('Content-Type'));
        \Log::info('📄 Request data: ', $request->all());
        \Log::info('📄 Request files: ', $request->allFiles());
        
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        if ($validator->fails()) {
            \Log::error('❌ Image validation failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            $imagePath = $image->storeAs('products', $filename, 'public');
            $imageUrl = '/storage/' . $imagePath;

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'data' => [
                    'url' => $imageUrl,
                    'filename' => $filename
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product properties for a specific product
     */
    public function getProductProperties($productId)
    {
        try {
            $product = Product::with(['propertyValues.categoryProperty', 'category'])
                ->findOrFail($productId);

            $properties = $product->propertyValues->groupBy(function($item) {
                return $item->categoryProperty->name;
            })->map(function($group, $propertyName) {
                $property = $group->first()->categoryProperty;
                return [
                    'property_id' => $property->id,
                    'property_name' => $property->name,
                    'property_display_name' => $property->display_name,
                    'input_type' => $property->input_type,
                    'values' => $group->map(function($value) {
                        return [
                            'id' => $value->id,
                            'value' => $value->value,
                            'display_name' => $value->display_name,
                        ];
                    })->toArray(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'category' => $product->category->name,
                    ],
                    'properties' => $properties->values(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب خصائص المنتج',
            ], 500);
        }
    }

    /**
     * Attach properties to a product
     */
    public function attachProperties(Request $request, $productId)
    {
        $request->validate([
            'property_values' => 'required|array',
            'property_values.*' => 'integer|exists:property_values,id',
        ]);

        try {
            $product = Product::findOrFail($productId);
            $count = $product->attachProperties($request->property_values);

            return response()->json([
                'success' => true,
                'message' => 'تم ربط الخصائص بالمنتج بنجاح',
                'data' => [
                    'attached_count' => $count,
                    'product_id' => $productId,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في ربط الخصائص بالمنتج',
            ], 500);
        }
    }

    /**
     * Get category properties when creating/editing a product
     */
    public function getCategoryPropertiesForProduct($categoryId)
    {
        try {
            $category = Category::with(['activeProperties.activePropertyValues'])
                ->findOrFail($categoryId);

            $properties = $category->activeProperties->map(function($property) {
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
                        ];
                    }),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                    ],
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
}
