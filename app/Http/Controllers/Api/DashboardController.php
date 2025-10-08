<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Certification;
use App\Models\Solution;
use App\Models\Material;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Admin dashboard statistics and analytics"
 * )
 */
class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/dashboard/stats",
     *     summary="Get comprehensive dashboard statistics",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="totalProducts", type="integer"),
     *                 @OA\Property(property="totalCategories", type="integer"),
     *                 @OA\Property(property="totalMessages", type="integer"),
     *                 @OA\Property(property="totalCertifications", type="integer"),
     *                 @OA\Property(property="totalSolutions", type="integer"),
     *                 @OA\Property(property="totalMaterials", type="integer"),
     *                 @OA\Property(property="systemHealth", type="object"),
     *                 @OA\Property(property="adminUsers", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function getStats()
    {
        try {
            // Cache the stats for 5 minutes to improve performance
            $stats = Cache::remember('dashboard_stats', 300, function () {
                return [
                    // Core Statistics
                    'totalProducts' => Product::active()->count(),
                    'totalCategories' => Category::where('is_active', true)->count(),
                    'totalMessages' => ContactMessage::count(),
                    'totalCertifications' => Certification::where('is_active', true)->count(),
                    'totalSolutions' => Solution::count(),
                    'totalMaterials' => Material::count(),
                    'adminUsers' => User::where('role', 'admin')->count(),
                    
                    // System Health
                    'systemHealth' => $this->getSystemHealth(),
                    
                    // Recent Data
                    'recentProducts' => $this->getRecentProducts(),
                    'recentMessages' => $this->getRecentMessages(),
                    'recentActivity' => $this->getRecentActivity(),
                    
                    // Content Analytics
                    'contentAnalytics' => $this->getContentAnalytics(),
                    
                    // System Reports
                    'systemReports' => $this->getSystemReports(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard stats error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/dashboard/content-analytics",
     *     summary="Get detailed content analytics",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Content analytics retrieved successfully"
     *     )
     * )
     */
    public function getContentAnalytics()
    {
        try {
            $analytics = Cache::remember('dashboard_content_analytics', 600, function () {
                return [
                    // Most Popular Categories
                    'popularCategories' => Category::withCount('products')
                        ->where('is_active', true)
                        ->orderBy('products_count', 'desc')
                        ->take(5)
                        ->get()
                        ->map(function ($category) {
                            return [
                                'name' => $category->name,
                                'products_count' => $category->products_count,
                                'percentage' => $this->calculatePercentage($category->products_count, Product::count())
                            ];
                        }),

                    // Products without Images
                    'productsWithoutImages' => Product::doesntHave('images')->count(),
                    
                    // Empty Categories
                    'emptyCategories' => Category::doesntHave('products')
                        ->where('is_active', true)
                        ->count(),
                    
                    // Solutions without Products
                    'solutionsWithoutProducts' => Solution::doesntHave('products')->count(),
                    
                    // Recent Uploads
                    'recentUploads' => $this->getRecentUploads(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);

        } catch (\Exception $e) {
            Log::error('Content analytics error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve content analytics'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/dashboard/charts-data",
     *     summary="Get data for dashboard charts",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Charts data retrieved successfully"
     *     )
     * )
     */
    public function getChartsData()
    {
        try {
            $chartsData = Cache::remember('dashboard_charts_data', 900, function () {
                return [
                    // Monthly Activity Chart
                    'monthlyActivity' => $this->getMonthlyActivity(),
                    
                    // Category Distribution
                    'categoryDistribution' => $this->getCategoryDistribution(),
                    
                    // Content Growth
                    'contentGrowth' => $this->getContentGrowth(),
                    
                    // Messages Trend
                    'messagesTrend' => $this->getMessagesTrend(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $chartsData
            ]);

        } catch (\Exception $e) {
            Log::error('Charts data error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve charts data'
            ], 500);
        }
    }

    /**
     * Get system health status
     */
    private function getSystemHealth()
    {
        try {
            $dbStatus = DB::connection()->getPdo() ? 'healthy' : 'error';
            $diskSpace = disk_free_space('/') / disk_total_space('/') * 100;
            
            return [
                'database' => $dbStatus,
                'disk_space' => round($diskSpace, 2),
                'status' => $dbStatus === 'healthy' && $diskSpace > 10 ? 'healthy' : 'warning',
                'last_check' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            return [
                'database' => 'error',
                'disk_space' => 0,
                'status' => 'error',
                'last_check' => now()->toISOString(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get recent products
     */
    private function getRecentProducts()
    {
        return Product::with('category')
            ->active()
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category->name ?? 'Uncategorized',
                    'status' => $product->status,
                    'date' => $product->created_at->format('M d, Y'),
                    'image' => $product->image
                ];
            });
    }

    /**
     * Get recent messages
     */
    private function getRecentMessages()
    {
        return ContactMessage::latest()
            ->take(5)
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'from' => $message->name,
                    'email' => $message->email,
                    'subject' => $message->subject ?? 'General Inquiry',
                    'status' => $message->status,
                    'date' => $message->created_at->format('M d, Y'),
                    'excerpt' => Str::limit($message->message, 50)
                ];
            });
    }

    /**
     * Get recent activity
     */
    private function getRecentActivity()
    {
        $activities = collect();

        // Recent products
        $recentProducts = Product::latest()->take(3)->get();
        foreach ($recentProducts as $product) {
            $activities->push([
                'type' => 'product',
                'action' => 'created',
                'description' => "New product '{$product->name}' was added",
                'date' => $product->created_at,
                'icon' => 'fas fa-box'
            ]);
        }

        // Recent messages
        $recentMessages = ContactMessage::latest()->take(3)->get();
        foreach ($recentMessages as $message) {
            $activities->push([
                'type' => 'message',
                'action' => 'received',
                'description' => "New message from {$message->name}",
                'date' => $message->created_at,
                'icon' => 'fas fa-envelope'
            ]);
        }

        return $activities->sortByDesc('date')->take(6)->values();
    }

    /**
     * Get system reports
     */
    private function getSystemReports()
    {
        return [
            'unreadMessages' => ContactMessage::where('status', 'unread')->count(),
            'pendingOrders' => Order::where('status', 'pending')->count(),
            'lowStockProducts' => 0, // Placeholder for future inventory management
            'systemErrors' => 0, // Placeholder for error logging
        ];
    }

    /**
     * Get monthly activity data
     */
    private function getMonthlyActivity()
    {
        $months = [];
        $productsData = [];
        $messagesData = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');
            
            $productsData[] = Product::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
                
            $messagesData[] = ContactMessage::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
        }

        return [
            'labels' => $months,
            'datasets' => [
                [
                    'label' => 'Products Added',
                    'data' => $productsData,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)'
                ],
                [
                    'label' => 'Messages Received',
                    'data' => $messagesData,
                    'borderColor' => '#8b5cf6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.1)'
                ]
            ]
        ];
    }

    /**
     * Get category distribution data
     */
    private function getCategoryDistribution()
    {
        $categories = Category::withCount('products')
            ->where('is_active', true)
            ->orderBy('products_count', 'desc')
            ->take(8)
            ->get();

        return [
            'labels' => $categories->pluck('name'),
            'data' => $categories->pluck('products_count'),
            'backgroundColor' => [
                '#3b82f6', '#8b5cf6', '#10b981', '#f59e0b',
                '#ef4444', '#06b6d4', '#84cc16', '#f97316'
            ]
        ];
    }

    /**
     * Get content growth data
     */
    private function getContentGrowth()
    {
        $months = [];
        $cumulativeProducts = [];
        $cumulativeCategories = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i)->endOfMonth();
            $months[] = $date->format('M Y');
            
            $cumulativeProducts[] = Product::where('created_at', '<=', $date)->count();
            $cumulativeCategories[] = Category::where('created_at', '<=', $date)->count();
        }

        return [
            'labels' => $months,
            'datasets' => [
                [
                    'label' => 'Total Products',
                    'data' => $cumulativeProducts,
                    'borderColor' => '#3b82f6',
                    'fill' => false
                ],
                [
                    'label' => 'Total Categories',
                    'data' => $cumulativeCategories,
                    'borderColor' => '#10b981',
                    'fill' => false
                ]
            ]
        ];
    }

    /**
     * Get messages trend data
     */
    private function getMessagesTrend()
    {
        $days = [];
        $messagesData = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $days[] = $date->format('M d');
            
            $messagesData[] = ContactMessage::whereDate('created_at', $date)->count();
        }

        return [
            'labels' => $days,
            'data' => $messagesData,
            'borderColor' => '#8b5cf6',
            'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
            'fill' => true
        ];
    }

    /**
     * Get recent uploads
     */
    private function getRecentUploads()
    {
        // This would track file uploads - placeholder for now
        return [
            'images' => 0,
            'documents' => 0,
            'total_size' => '0 MB'
        ];
    }

    /**
     * Calculate percentage
     */
    private function calculatePercentage($value, $total)
    {
        return $total > 0 ? round(($value / $total) * 100, 1) : 0;
    }
}
