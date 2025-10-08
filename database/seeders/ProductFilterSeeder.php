<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\FilterOption;

class ProductFilterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some products and filter options
        $products = Product::all();
        $filterOptions = FilterOption::all();
        
        if ($products->isEmpty() || $filterOptions->isEmpty()) {
            $this->command->warn('No products or filter options found. Please run product and filter seeders first.');
            return;
        }

        // Example associations - you can customize this based on your needs
        foreach ($products as $product) {
            // Randomly assign some filter options to each product
            $randomOptions = $filterOptions->random(rand(3, 8));
            
            foreach ($randomOptions as $option) {
                // Check if association doesn't already exist
                if (!$product->filterOptions()->where('filter_option_id', $option->id)->exists()) {
                    $product->filterOptions()->attach($option->id);
                }
            }
        }

        // Update product counts for all filter options
        foreach ($filterOptions as $option) {
            $option->updateProductCount();
        }

        $this->command->info('Product filters associated successfully!');
    }
}