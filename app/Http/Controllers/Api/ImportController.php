<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BulkImportService;
use App\Models\ImportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{
    protected BulkImportService $bulkImportService;

    public function __construct(BulkImportService $bulkImportService)
    {
        $this->bulkImportService = $bulkImportService;
    }

    /**
     * Step 1: Validate and preview uploaded files.
     * Parses Excel, scans ZIP structure, returns preview data.
     */
    public function validateImport(Request $request)
    {
        $rules = [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ];

        if ($request->hasFile('zip_file')) {
            $rules['zip_file'] = 'file|mimes:zip|max:102400';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $excelFile = $request->file('excel_file');
            $excelPath = $tempDir . '/' . uniqid('import_') . '.' . $excelFile->getClientOriginalExtension();
            $excelFile->move($tempDir, basename($excelPath));

            $zipPath = null;
            if ($request->hasFile('zip_file')) {
                $zipFile = $request->file('zip_file');
                $zipPath = $tempDir . '/' . uniqid('import_') . '.' . $zipFile->getClientOriginalExtension();
                $zipFile->move($tempDir, basename($zipPath));
            }

            $result = $this->bulkImportService->validateAndPreview(
                $excelPath,
                $zipPath,
                $request->user()->id
            );

            // Files were moved into session dir by validateAndPreview, no cleanup needed here

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Files validated successfully',
                    'data' => $result,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => $result['error'],
            ], 422);
        } catch (\Exception $e) {
            Log::error('Import validation failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Step 2: Execute import using a validated session.
     */
    public function importProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string|uuid',
            'selected_rows' => 'required|array|min:1',
            'selected_rows.*' => 'integer',
        ]);

        // Legacy support: if no session_id, fall back to direct file upload
        if ($validator->fails() && $request->hasFile('excel_file')) {
            return $this->legacyImport($request);
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->bulkImportService->executeImport(
                $request->input('session_id'),
                $request->input('selected_rows'),
                $request->user()->id
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Import completed successfully',
                    'data' => $result,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Import failed',
                'error' => $result['error'],
            ], 500);
        } catch (\Exception $e) {
            Log::error('Import request failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Import failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Legacy import: accepts file uploads directly (backward compatible).
     */
    private function legacyImport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'zip_file' => 'required|file|mimes:zip|max:102400',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $excelFile = $request->file('excel_file');
            $excelPath = $tempDir . '/' . uniqid('import_') . '.' . $excelFile->getClientOriginalExtension();
            $excelFile->move($tempDir, basename($excelPath));

            $zipFile = $request->file('zip_file');
            $zipPath = $tempDir . '/' . uniqid('import_') . '.' . $zipFile->getClientOriginalExtension();
            $zipFile->move($tempDir, basename($zipPath));

            $result = $this->bulkImportService->processBulkImport($excelPath, $zipPath, $request->user()->id);

            if ($result['success']) {
                return response()->json(['success' => true, 'message' => 'Import completed successfully', 'data' => $result]);
            }

            return response()->json(['success' => false, 'message' => 'Import failed', 'error' => $result['error']], 500);
        } catch (\Exception $e) {
            Log::error('Legacy import failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Import failed', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get import logs for current user.
     */
    public function getImportLogs(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);

            $logs = ImportLog::where('user_id', $request->user()->id)
                ->with('details')
                ->orderBy('created_at', 'DESC')
                ->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => $logs->items(),
                'pagination' => [
                    'page' => $logs->currentPage(),
                    'limit' => $logs->perPage(),
                    'total' => $logs->total(),
                    'pages' => $logs->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get import logs', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to get import logs', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get specific import log details.
     */
    public function getImportLogDetails(Request $request, $id)
    {
        try {
            $importLog = ImportLog::with(['details.product', 'user'])->findOrFail($id);

            if ($importLog->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            return response()->json(['success' => true, 'data' => $importLog]);
        } catch (\Exception $e) {
            Log::error('Failed to get import log details', ['import_log_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to get import log details', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Download Excel template.
     */
    public function downloadTemplate()
    {
        try {
            $csvContent = "product_name,description,short_description,category,sku,model,status,is_featured,sort_order,specifications\n";
            $csvContent .= "عسل فاخر Premium,عسل طبيعي 100%,عسل طبيعي,منتجات غذائية,HONEY-001,HN-2024,active,yes,10,\"{\"\"الوزن\"\":\"\"500جم\"\",\"\"المنشأ\"\":\"\"الإمارات\"\"}\"\n";
            $csvContent .= "زيت زيتون بكر,زيت زيتون معصور على البارد,زيت زيتون,منتجات غذائية,OLIVE-001,OO-2024,active,no,20,\"{\"\"الحجم\"\":\"\"1 لتر\"\",\"\"المنشأ\"\":\"\"اليونان\"\"}\"\n";
            $csvContent .= "دبس التمر طبيعي,دبس تمر طبيعي 100%,دبس تمر,منتجات غذائية,DATE-001,DS-2024,active,no,30,\"{\"\"الوزن\"\":\"\"350جم\"\",\"\"المنشأ\"\":\"\"السعودية\"\"}\"\n";

            $csvContent = "\xEF\xBB\xBF" . $csvContent;

            return response($csvContent)
                ->header('Content-Type', 'text/csv; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="product_import_template.csv"');
        } catch (\Exception $e) {
            Log::error('Failed to download template', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to download template', 'error' => $e->getMessage()], 500);
        }
    }
}
