<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductFile;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductFileController extends Controller
{
    /**
     * Get all files for a product
     */
    public function index($productId)
    {
        try {
            $product = Product::findOrFail($productId);
            
            $files = $product->activeFiles()->get()->map(function($file) {
                return [
                    'id' => $file->id,
                    'display_name' => $file->display_name,
                    'description' => $file->description,
                    'file_category' => $file->file_category,
                    'file_size' => $file->file_size_human,
                    'download_count' => $file->download_count,
                    'is_featured' => $file->is_featured,
                    'download_url' => $file->download_url,
                    'created_at' => $file->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $files,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب ملفات المنتج',
            ], 500);
        }
    }

    /**
     * Upload a new file for a product
     */
    public function store(Request $request, $productId)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file_category' => 'nullable|string|max:100',
            'is_featured' => 'boolean',
        ]);

        try {
            $product = Product::findOrFail($productId);
            $file = $request->file('file');
            
            // Generate unique filename
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $filePath = "products/{$productId}/files/{$filename}";
            
            // Store file
            $path = $file->storeAs('public/' . dirname($filePath), basename($filePath));
            
            if (!$path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload file',
                ], 500);
            }

            // Create file record
            $productFile = ProductFile::create([
                'product_id' => $productId,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'file_type' => $file->getMimeType(),
                'mime_type' => $file->getMimeType(),
                'display_name' => $request->display_name,
                'description' => $request->description,
                'file_category' => $request->file_category ?? 'other',
                'is_featured' => $request->boolean('is_featured', false),
                'sort_order' => ProductFile::where('product_id', $productId)->count(),
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => $productFile,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a file
     */
    public function download($productId, $fileId)
    {
        try {
            $file = ProductFile::where('product_id', $productId)
                ->where('id', $fileId)
                ->where('is_active', true)
                ->firstOrFail();

            if (!$file->fileExists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found',
                ], 404);
            }

            // Increment download count
            $file->incrementDownload();

            // Return file download
            return Storage::disk('public')->download($file->file_path, $file->file_name);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في تحميل الملف',
            ], 500);
        }
    }

    /**
     * Update file information
     */
    public function update(Request $request, $productId, $fileId)
    {
        $request->validate([
            'display_name' => 'string|max:255',
            'description' => 'nullable|string',
            'file_category' => 'nullable|string|max:100',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        try {
            $file = ProductFile::where('product_id', $productId)
                ->where('id', $fileId)
                ->firstOrFail();

            $file->update($request->only([
                'display_name', 'description', 'file_category',
                'is_featured', 'is_active', 'sort_order'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث معلومات الملف بنجاح',
                'data' => $file,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update file',
            ], 500);
        }
    }

    /**
     * Delete a file
     */
    public function destroy($productId, $fileId)
    {
        try {
            $file = ProductFile::where('product_id', $productId)
                ->where('id', $fileId)
                ->firstOrFail();

            // Delete physical file
            if ($file->fileExists()) {
                Storage::disk('public')->delete($file->file_path);
            }

            // Delete record
            $file->delete();

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file',
            ], 500);
        }
    }

    /**
     * Get all files for admin management
     */
    public function adminIndex()
    {
        try {
            $files = ProductFile::with(['product'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($file) {
                    return [
                        'id' => $file->id,
                        'product_id' => $file->product_id,
                        'file_name' => $file->file_name,
                        'display_name' => $file->display_name,
                        'description' => $file->description,
                        'file_category' => $file->file_category,
                        'file_size' => $file->file_size,
                        'file_type' => $file->file_type,
                        'download_count' => $file->download_count,
                        'is_active' => $file->is_active,
                        'is_featured' => $file->is_featured,
                        'sort_order' => $file->sort_order,
                        'created_at' => $file->created_at,
                        'updated_at' => $file->updated_at,
                        'product' => $file->product ? [
                            'id' => $file->product->id,
                            'name' => $file->product->name,
                        ] : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $files,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load files',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new file (admin)
     */
    public function adminStore(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'file' => 'required|file|mimes:pdf|max:10240', // 10MB max
                'display_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'file_category' => 'required|in:manual,catalog,specification,warranty,installation,other',
                'is_active' => 'boolean',
                'is_featured' => 'boolean',
                'sort_order' => 'integer|min:0',
            ]);

            $file = $request->file('file');
            $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('products/files', $fileName, 'public');

            $productFile = ProductFile::create([
                'product_id' => $request->product_id,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'file_type' => $file->getMimeType(),
                'mime_type' => $file->getMimeType(),
                'display_name' => $request->display_name,
                'description' => $request->description,
                'file_category' => $request->file_category,
                'is_active' => $request->boolean('is_active', true),
                'is_featured' => $request->boolean('is_featured', false),
                'sort_order' => $request->integer('sort_order', 0),
                'download_count' => 0,
                'metadata' => json_encode([
                    'original_name' => $file->getClientOriginalName(),
                    'uploaded_by' => auth()->id(),
                ]),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => $productFile,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update file information (admin)
     */
    public function adminUpdate(Request $request, $fileId)
    {
        try {
            $file = ProductFile::findOrFail($fileId);

            $request->validate([
                'display_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'file_category' => 'required|in:manual,catalog,specification,warranty,installation,other',
                'is_active' => 'boolean',
                'is_featured' => 'boolean',
                'sort_order' => 'integer|min:0',
            ]);

            $file->update([
                'display_name' => $request->display_name,
                'description' => $request->description,
                'file_category' => $request->file_category,
                'is_active' => $request->boolean('is_active', true),
                'is_featured' => $request->boolean('is_featured', false),
                'sort_order' => $request->integer('sort_order', 0),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File updated successfully',
                'data' => $file,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a file (admin)
     */
    public function adminDestroy($fileId)
    {
        try {
            $file = ProductFile::findOrFail($fileId);
            
            // Delete physical file
            if (Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }
            
            $file->delete();

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a file (admin)
     */
    public function adminDownload($fileId)
    {
        try {
            $file = ProductFile::findOrFail($fileId);
            
            if (!Storage::disk('public')->exists($file->file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found',
                ], 404);
            }

            // Increment download count
            $file->increment('download_count');
            $file->update(['last_downloaded_at' => now()]);

            return Storage::disk('public')->download($file->file_path, $file->display_name . '.pdf');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get files by product (admin)
     */
    public function getByProduct($productId)
    {
        try {
            $product = Product::findOrFail($productId);
            
            $files = $product->files()->orderBy('sort_order')->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $files,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load product files',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}