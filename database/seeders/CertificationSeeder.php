<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Certification;
use App\Models\Product;

class CertificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء الشهادات
        $certifications = [
            [
                'title' => 'GS Tested Safety',
                'description' => 'The GS mark is our guarantee of the safety of our furniture. In a GS test, an independent accredited organization tests products for stability, strength, and durability, while also focusing on ergonomic requirements. Most VS products bear the GS mark.',
                'image_url' => '/images/certifications/gs-tested-safety.svg',
                'is_active' => true,
            ],
            [
                'title' => 'BIFMA e3 LEVEL',
                'description' => 'BIFMA e3 LEVEL is the sustainability certification program of the Business and Institutional Furniture Manufacturers Association (BIFMA). It evaluates and certifies school, office, and contract furniture based on environmental and social responsibility criteria. Most VS products have been certified to BIFMA e3 LEVEL.',
                'image_url' => '/images/certifications/bifma-e3-level.svg',
                'is_active' => true,
            ],
            [
                'title' => 'GREENGUARD Gold',
                'description' => 'GREENGUARD Gold certification ensures that our products meet strict chemical emissions limits, helping to reduce indoor air pollution and the risk of chemical exposure.',
                'image_url' => '/images/certifications/greenguard-gold.svg',
                'is_active' => true,
            ],
            [
                'title' => 'Forest Stewardship Council (FSC)',
                'description' => 'FSC certification ensures that our wood products come from responsibly managed forests that provide environmental, social and economic benefits.',
                'image_url' => '/images/certifications/fsc-certified.svg',
                'is_active' => true,
            ]
        ];

        foreach ($certifications as $certificationData) {
            Certification::create($certificationData);
        }

        // ربط الشهادات بالمنتجات (جميع المنتجات تحصل على أول شهادتين، بعض المنتجات تحصل على شهادات إضافية)
        $allCertifications = Certification::all();
        $products = Product::limit(10)->get(); // أول 10 منتجات

        foreach ($products as $product) {
            // جميع المنتجات تحصل على GS Tested Safety و BIFMA e3 LEVEL
            $product->certifications()->attach([1, 2]);
            
            // 50% من المنتجات تحصل على GREENGUARD Gold
            if (rand(1, 2) == 1) {
                $product->certifications()->attach([3]);
            }
            
            // 30% من المنتجات تحصل على FSC
            if (rand(1, 3) == 1) {
                $product->certifications()->attach([4]);
            }
        }
    }
}
