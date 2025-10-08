<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certification;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CertificationController extends Controller
{
    /**
     * عرض جميع الشهادات (public)
     */
    public function index(): JsonResponse
    {
        try {
            $certifications = Certification::orderBy('title')->get();

            return response()->json([
                'success' => true,
                'data' => $certifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch certifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض جميع الشهادات للادمن (تشمل غير النشطة)
     */
    public function adminIndex(): JsonResponse
    {
        try {
            $certifications = Certification::orderBy('title')->get();

            return response()->json([
                'success' => true,
                'data' => $certifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch certifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب شهادات منتج معين
     */
    public function getProductCertifications($productId): JsonResponse
    {
        try {
            $product = Product::with(['certifications' => function($query) {
                $query->where('is_active', true);
            }])->findOrFail($productId);

            return response()->json([
                'success' => true,
                'data' => $product->certifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product certifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إنشاء شهادة جديدة
     */
    public function store(Request $request): JsonResponse
    {
        try {
            \Log::info('🔄 Creating certification', [
                'request_data' => $request->all(),
                'user' => auth()->user() ? auth()->user()->id : 'no_user'
            ]);

            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'image_url' => 'nullable|string',
                'is_active' => 'boolean'
            ]);

            $certification = Certification::create($request->all());
            
            \Log::info('✅ Certification created successfully', ['certification_id' => $certification->id]);

            return response()->json([
                'success' => true,
                'data' => $certification,
                'message' => 'Certification created successfully'
            ], 201);
        } catch (\Exception $e) {
            \Log::error('❌ Failed to create certification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create certification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض شهادة محددة
     */
    public function show($id): JsonResponse
    {
        try {
            $certification = Certification::with('products')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $certification
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Certification not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * تحديث شهادة
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $certification = Certification::findOrFail($id);

            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'image_url' => 'nullable|string',
                'is_active' => 'boolean'
            ]);

            $certification->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $certification,
                'message' => 'Certification updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update certification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف شهادة
     */
    public function destroy($id): JsonResponse
    {
        try {
            $certification = Certification::findOrFail($id);
            $certification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Certification deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete certification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ربط شهادة بمنتج
     */
    public function attachToProduct(Request $request, $productId): JsonResponse
    {
        try {
            $request->validate([
                'certification_id' => 'required|exists:certifications,id'
            ]);

            $product = Product::findOrFail($productId);
            $product->certifications()->attach($request->certification_id);

            return response()->json([
                'success' => true,
                'message' => 'Certification attached to product successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to attach certification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إلغاء ربط شهادة من منتج
     */
    public function detachFromProduct($productId, $certificationId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);
            $product->certifications()->detach($certificationId);

            return response()->json([
                'success' => true,
                'message' => 'Certification detached from product successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to detach certification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * رفع صورة للشهادة
     */
    public function uploadImage(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
            ]);

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                
                // Create certifications directory if it doesn't exist
                $uploadPath = public_path('images/certifications');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                // Move the uploaded file
                $image->move($uploadPath, $imageName);
                
                // Also copy to Next.js public folder for better integration
                $nextjsPath = 'C:/Users/HP/Desktop/VS/vs-frontEnd/vs-nextjs/public/images/certifications/';
                if (!file_exists($nextjsPath)) {
                    mkdir($nextjsPath, 0755, true);
                }
                copy($uploadPath . '/' . $imageName, $nextjsPath . $imageName);
                
                // Return local path instead of full URL for better compatibility
                $imageUrl = '/images/certifications/' . $imageName;
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'image_url' => $imageUrl,
                        'filename' => $imageName
                    ],
                    'message' => 'Image uploaded successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No image file provided'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
