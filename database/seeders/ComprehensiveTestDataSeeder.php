<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComprehensiveTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "🚀 Starting Comprehensive Test Data Seeding...\n";
        echo "=" . str_repeat("=", 50) . "\n\n";

        // Disable foreign key checks temporarily (SQLite compatible)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } else {
            DB::statement('PRAGMA foreign_keys=OFF;');
        }

        try {
            // Step 1: Enhanced Properties and Categories
            echo "📋 Step 1: Seeding Enhanced Properties and Categories...\n";
            $this->call(EnhancedPropertiesSeeder::class);
            echo "✅ Enhanced Properties completed!\n\n";

            // Step 2: Enhanced Products with Properties
            echo "📦 Step 2: Seeding Enhanced Products with Properties...\n";
            $this->call(EnhancedProductsSeeder::class);
            echo "✅ Enhanced Products completed!\n\n";

            // Step 3: Product Files
            echo "📄 Step 3: Seeding Product Files...\n";
            $this->call(ProductFilesSeeder::class);
            echo "✅ Product Files completed!\n\n";

            // Step 4: Update statistics and relationships
            echo "📊 Step 4: Updating Statistics and Relationships...\n";
            $this->updateStatistics();
            echo "✅ Statistics updated!\n\n";

        } catch (\Exception $e) {
            echo "❌ Error during seeding: " . $e->getMessage() . "\n";
            throw $e;
        } finally {
            // Re-enable foreign key checks (SQLite compatible)
            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } else {
                DB::statement('PRAGMA foreign_keys=ON;');
            }
        }

        echo "🎉 Comprehensive Test Data Seeding Completed Successfully!\n";
        echo "=" . str_repeat("=", 50) . "\n";
        $this->displayFinalStatistics();
    }

    /**
     * Update various statistics and relationships
     */
    private function updateStatistics(): void
    {
        // Update category product counts (skip if column doesn't exist)
        $categories = \App\Models\Category::all();
        foreach ($categories as $category) {
            $productCount = $category->products()->count();
            echo "  📂 {$category->name}: {$productCount} products\n";
        }

        // Update property product counts (already done in ProductPropertyValue creation)
        echo "  ⚙️ Property value counts updated\n";

        // Update product view counts randomly
        \App\Models\Product::chunk(10, function ($products) {
            foreach ($products as $product) {
                $product->update(['views_count' => rand(10, 1000)]);
            }
        });
        echo "  👁️ Product view counts randomized\n";

        // Update file download counts randomly
        \App\Models\ProductFile::chunk(20, function ($files) {
            foreach ($files as $file) {
                $file->update(['download_count' => rand(0, 200)]);
            }
        });
        echo "  📥 File download counts randomized\n";
    }

    /**
     * Display final statistics
     */
    private function displayFinalStatistics(): void
    {
        echo "\n📊 FINAL STATISTICS:\n";
        echo "-" . str_repeat("-", 30) . "\n";

        // Categories
        $categoriesCount = \App\Models\Category::count();
        $activeCategories = \App\Models\Category::where('is_active', true)->count();
        echo "📂 Categories: {$categoriesCount} total ({$activeCategories} active)\n";

        // Properties
        $propertiesCount = \App\Models\CategoryProperty::count();
        $activeProperties = \App\Models\CategoryProperty::where('is_active', true)->count();
        echo "⚙️ Properties: {$propertiesCount} total ({$activeProperties} active)\n";

        // Property Values
        $valuesCount = \App\Models\PropertyValue::count();
        $activeValues = \App\Models\PropertyValue::where('is_active', true)->count();
        echo "🏷️ Property Values: {$valuesCount} total ({$activeValues} active)\n";

        // Products
        $productsCount = \App\Models\Product::count();
        $activeProducts = \App\Models\Product::where('status', 'active')->count();
        $featuredProducts = \App\Models\Product::where('is_featured', true)->count();
        echo "📦 Products: {$productsCount} total ({$activeProducts} active, {$featuredProducts} featured)\n";

        // Product Files
        $filesCount = \App\Models\ProductFile::count();
        $activeFiles = \App\Models\ProductFile::where('is_active', true)->count();
        $featuredFiles = \App\Models\ProductFile::where('is_featured', true)->count();
        echo "📄 Product Files: {$filesCount} total ({$activeFiles} active, {$featuredFiles} featured)\n";

        // Relationships
        $productPropertyCount = \App\Models\ProductPropertyValue::count();
        echo "🔗 Product-Property Links: {$productPropertyCount}\n";

        echo "\n📈 BREAKDOWN BY CATEGORY:\n";
        echo "-" . str_repeat("-", 30) . "\n";

        $categories = \App\Models\Category::withCount(['products', 'properties'])->get();
        foreach ($categories as $category) {
            $filesCount = \App\Models\ProductFile::whereHas('product', function($q) use ($category) {
                $q->where('category_id', $category->id);
            })->count();
            
            echo "📂 {$category->name}:\n";
            echo "   📦 Products: {$category->products_count}\n";
            echo "   ⚙️ Properties: {$category->properties_count}\n";
            echo "   📄 Files: {$filesCount}\n";
        }

        echo "\n📊 FILE CATEGORIES:\n";
        echo "-" . str_repeat("-", 30) . "\n";
        
        $fileCategories = \App\Models\ProductFile::select('file_category', DB::raw('count(*) as count'))
            ->groupBy('file_category')
            ->get();
            
        foreach ($fileCategories as $fileCat) {
            echo "📄 {$fileCat->file_category}: {$fileCat->count} files\n";
        }

        echo "\n🎯 READY FOR TESTING!\n";
        echo "You can now test:\n";
        echo "• Admin Panel: Categories, Products, Properties, Files\n";
        echo "• Frontend: Product browsing, filtering, file downloads\n";
        echo "• API Endpoints: All CRUD operations\n";
        echo "• Integration: Full system workflow\n";
    }
}