<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FilterCategory;
use App\Models\FilterOption;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FilterController extends Controller
{
    /**
     * Get all filter categories with their options
     */
    public function index()
    {
        try {
            $categories = FilterCategory::active()
                ->ordered()
                ->with(['activeFilterOptions' => function($query) {
                    $query->ordered();
                }])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'message' => 'Filters retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve filters: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get filtered products
     */
    public function filterProducts(Request $request)
    {
        try {
            $query = Product::with(['category'])
                ->where('status', 'active');

            // Apply filters
            if ($request->has('filters') && is_array($request->filters)) {
                foreach ($request->filters as $categoryName => $optionValues) {
                    if (empty($optionValues)) continue;

                    $query->whereHas('filterOptions', function($q) use ($categoryName, $optionValues) {
                        $q->whereHas('filterCategory', function($categoryQuery) use ($categoryName) {
                            $categoryQuery->where('name', $categoryName);
                        })
                        ->whereIn('value', $optionValues);
                    });
                }
            }

            // Apply category filter if provided
            if ($request->has('category') && $request->category) {
                $query->whereHas('category', function($q) use ($request) {
                    $q->where('name', $request->category);
                });
            }

            // Apply search if provided
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('model', 'like', "%{$search}%");
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 12);
            $products = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $products->items(),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ],
                'message' => 'Filtered products retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to filter products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get filter statistics for current products
     */
    public function getFilterStats(Request $request)
    {
        try {
            $query = Product::where('status', 'active');

            // Apply existing filters to get counts for remaining options
            if ($request->has('filters') && is_array($request->filters)) {
                foreach ($request->filters as $categoryName => $optionValues) {
                    if (empty($optionValues)) continue;

                    $query->whereHas('filterOptions', function($q) use ($categoryName, $optionValues) {
                        $q->whereHas('filterCategory', function($categoryQuery) use ($categoryName) {
                            $categoryQuery->where('name', $categoryName);
                        })
                        ->whereIn('value', $optionValues);
                    });
                }
            }

            $productIds = $query->pluck('id');

            // Get filter option counts
            $filterStats = DB::table('filter_options')
                ->join('filter_categories', 'filter_options.filter_category_id', '=', 'filter_categories.id')
                ->leftJoin('product_filter_options', 'filter_options.id', '=', 'product_filter_options.filter_option_id')
                ->select(
                    'filter_categories.name as category_name',
                    'filter_options.id',
                    'filter_options.value',
                    'filter_options.display_name',
                    DB::raw('COUNT(CASE WHEN product_filter_options.product_id IN (' . $productIds->implode(',') . ') THEN 1 END) as current_count')
                )
                ->where('filter_categories.is_active', true)
                ->where('filter_options.is_active', true)
                ->groupBy('filter_categories.name', 'filter_options.id', 'filter_options.value', 'filter_options.display_name')
                ->get()
                ->groupBy('category_name');

            return response()->json([
                'success' => true,
                'data' => $filterStats,
                'total_products' => $productIds->count(),
                'message' => 'Filter statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get filter statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Associate filter options with a product (Admin only)
     */
    public function associateProductFilters(Request $request, $productId)
    {
        try {
            $product = Product::findOrFail($productId);

            $request->validate([
                'filter_options' => 'required|array',
                'filter_options.*' => 'exists:filter_options,id'
            ]);

            // Sync filter options with the product
            $product->filterOptions()->sync($request->filter_options);

            // Update product counts for affected filter options
            FilterOption::whereIn('id', $request->filter_options)
                ->each(function($option) {
                    $option->updateProductCount();
                });

            return response()->json([
                'success' => true,
                'message' => 'Product filters updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product filters: ' . $e->getMessage()
            ], 500);
        }
    }
}