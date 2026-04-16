<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BulkImportService;
use App\Models\ImportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

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
     * Download Excel template with 4 sheets.
     */
    public function downloadTemplate()
    {
        try {
            $spreadsheet = new Spreadsheet();

            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ];
            $exampleStyle = [
                'font' => ['color' => ['rgb' => '808080'], 'italic' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ];
            $noteStyle = [
                'font' => ['color' => ['rgb' => '0070C0'], 'italic' => true, 'size' => 10],
            ];

            // --- Sheet 1: Products ---
            $productsSheet = $spreadsheet->getActiveSheet();
            $productsSheet->setTitle('Products');

            $productHeaders = ['product_name', 'category', 'sku', 'model', 'description', 'short_description', 'status', 'is_featured', 'sort_order'];
            foreach ($productHeaders as $col => $header) {
                $productsSheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            }
            $productsSheet->getStyle('A1:I1')->applyFromArray($headerStyle);

            $exampleProducts = [
                ['PantoMove-LuPo', 'Office Chairs', 'VS-PML-001', 'PML-31506', 'Height-adjustable swivel student chair with double-walled polypropylene seat shell featuring air-cushion effect.', 'Height-adjustable swivel student chair', 'active', 'yes', 1],
                ['PantoMove-VF', 'Office Chairs', 'VS-PMV-001', 'PMV-31520', 'Height-adjustable swivel chair with beech plywood seat shell and anti-slip paint.', 'Swivel chair with wooden seat shell', 'active', 'yes', 2],
                ['Compass-VF Chair', 'Office Chairs', 'VS-CVF-001', 'CVF-34100', 'Stackable four-legged chair with beech plywood seat shell.', 'Stackable four-legged chair', 'active', 'no', 3],
                ['Hammer Chair', 'Office Chairs', 'VS-HAM-001', 'HAM-36200', 'Classic stacking chair with ergonomic polypropylene seat shell.', 'Classic stacking chair', 'active', 'no', 4],
                ['PantoFlex Chair', 'Office Chairs', 'VS-PFX-001', 'PFX-32400', 'Flexible four-legged chair with spring-loaded seat mechanism.', 'Flexible active sitting chair', 'active', 'yes', 5],
                ['LuPo Stacking Chair', 'Office Chairs', 'VS-LPC-001', 'LPC-35100', 'Lightweight stacking chair with double-walled LuPo polypropylene seat shell.', 'Lightweight stacking chair', 'active', 'no', 6],
            ];
            foreach ($exampleProducts as $rowIdx => $rowData) {
                foreach ($rowData as $col => $value) {
                    $productsSheet->setCellValueByColumnAndRow($col + 1, $rowIdx + 2, $value);
                }
            }
            $lastProductRow = count($exampleProducts) + 1;
            $productsSheet->getStyle("A2:I{$lastProductRow}")->applyFromArray($exampleStyle);

            $noteRow = $lastProductRow + 2;
            $productsSheet->setCellValue("A{$noteRow}", 'NOTE: Materials, Certifications, and Properties are defined in separate sheets (Sheet 2-4) linked by SKU.');
            $productsSheet->getStyle("A{$noteRow}")->applyFromArray($noteStyle);
            $noteRow++;
            $productsSheet->setCellValue("A{$noteRow}", 'PDF Files: Include PDFs in the ZIP named as SKU-category.pdf (e.g. VS-PML-001-catalog.pdf). Categories: catalog / manual / specification / warranty / installation / other');
            $productsSheet->getStyle("A{$noteRow}")->applyFromArray($noteStyle);
            $noteRow++;
            $productsSheet->setCellValue("A{$noteRow}", 'You can also keep materials/certifications/properties inline in Sheet 1 columns for backward compatibility.');
            $productsSheet->getStyle("A{$noteRow}")->applyFromArray($noteStyle);

            foreach (range('A', 'I') as $col) {
                $productsSheet->getColumnDimension($col)->setAutoSize(true);
            }
            $productsSheet->setAutoFilter('A1:I1');

            // --- Sheet 2: Properties ---
            $propsSheet = $spreadsheet->createSheet();
            $propsSheet->setTitle('Properties');

            $propsHeaders = ['sku', 'group', 'property', 'value'];
            foreach ($propsHeaders as $col => $header) {
                $propsSheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            }
            $propsSheet->getStyle('A1:D1')->applyFromArray($headerStyle);

            $exampleProps = [
                ['VS-PML-001', '',      'Chair Type',        'Swivel Chair'],
                ['VS-PML-001', '',      'Height Adjustment', 'Gas Spring'],
                ['VS-PML-001', 'Frame', 'Base Type',         'Five-Star Foot'],
                ['VS-PML-001', 'Frame', 'Material',          'Aluminum'],
                ['VS-PML-001', '',      'Seat Material',     'Polypropylene'],
                ['VS-PMV-001', '',      'Chair Type',        'Swivel Chair'],
                ['VS-PMV-001', '',      'Height Adjustment', 'Gas Spring'],
                ['VS-PMV-001', 'Frame', 'Base Type',         'Five-Star Foot'],
                ['VS-PMV-001', 'Frame', 'Material',          'Aluminum'],
                ['VS-PMV-001', '',      'Seat Material',     'Beech Plywood'],
                ['VS-CVF-001', '',      'Chair Type',        'Stacking Chair'],
                ['VS-CVF-001', '',      'Height Adjustment', 'Fixed Height'],
                ['VS-CVF-001', 'Frame', 'Material',          'Steel Tube'],
                ['VS-CVF-001', '',      'Seat Material',     'Beech Plywood'],
                ['VS-HAM-001', '',      'Chair Type',        'Stacking Chair'],
                ['VS-HAM-001', '',      'Height Adjustment', 'Fixed Height'],
                ['VS-HAM-001', 'Frame', 'Material',          'Steel Tube'],
                ['VS-HAM-001', '',      'Seat Material',     'Polypropylene'],
                ['VS-PFX-001', '',      'Chair Type',        'Four-Legged Chair'],
                ['VS-PFX-001', '',      'Height Adjustment', 'Fixed Height'],
                ['VS-PFX-001', 'Frame', 'Material',          'Steel Tube'],
                ['VS-PFX-001', '',      'Seat Material',     'Polypropylene'],
                ['VS-LPC-001', '',      'Chair Type',        'Stacking Chair'],
                ['VS-LPC-001', '',      'Height Adjustment', 'Fixed Height'],
                ['VS-LPC-001', 'Frame', 'Material',          'Steel Tube'],
                ['VS-LPC-001', '',      'Seat Material',     'Polypropylene'],
            ];
            foreach ($exampleProps as $rowIdx => $rowData) {
                foreach ($rowData as $col => $value) {
                    $propsSheet->setCellValueByColumnAndRow($col + 1, $rowIdx + 2, $value);
                }
            }
            $lastPropRow = count($exampleProps) + 1;
            $propsSheet->getStyle("A2:D{$lastPropRow}")->applyFromArray($exampleStyle);

            $propNoteRow = $lastPropRow + 2;
            $propsSheet->setCellValue("A{$propNoteRow}", 'Each row = one property-value pair for a product. "group" is optional (leave empty for ungrouped properties).');
            $propsSheet->getStyle("A{$propNoteRow}")->applyFromArray($noteStyle);

            foreach (range('A', 'D') as $col) {
                $propsSheet->getColumnDimension($col)->setAutoSize(true);
            }
            $propsSheet->setAutoFilter('A1:D1');

            // --- Sheet 3: Materials ---
            $matsSheet = $spreadsheet->createSheet();
            $matsSheet->setTitle('Materials');

            $matsHeaders = ['sku', 'material_code'];
            foreach ($matsHeaders as $col => $header) {
                $matsSheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            }
            $matsSheet->getStyle('A1:B1')->applyFromArray($headerStyle);

            $exampleMats = [
                ['VS-PML-001', 'M030'],
                ['VS-PML-001', 'F010'],
                ['VS-PMV-001', 'M030'],
                ['VS-PMV-001', 'M040'],
                ['VS-CVF-001', 'M040'],
                ['VS-CVF-001', 'F010'],
                ['VS-HAM-001', 'M030'],
                ['VS-PFX-001', 'M030'],
                ['VS-PFX-001', 'F010'],
                ['VS-LPC-001', 'M030'],
            ];
            foreach ($exampleMats as $rowIdx => $rowData) {
                foreach ($rowData as $col => $value) {
                    $matsSheet->setCellValueByColumnAndRow($col + 1, $rowIdx + 2, $value);
                }
            }
            $lastMatRow = count($exampleMats) + 1;
            $matsSheet->getStyle("A2:B{$lastMatRow}")->applyFromArray($exampleStyle);

            $matNoteRow = $lastMatRow + 2;
            $matsSheet->setCellValue("A{$matNoteRow}", 'Each row = one material assignment. Use the material code as it exists in the system.');
            $matsSheet->getStyle("A{$matNoteRow}")->applyFromArray($noteStyle);

            foreach (range('A', 'B') as $col) {
                $matsSheet->getColumnDimension($col)->setAutoSize(true);
            }
            $matsSheet->setAutoFilter('A1:B1');

            // --- Sheet 4: Certifications ---
            $certsSheet = $spreadsheet->createSheet();
            $certsSheet->setTitle('Certifications');

            $certsHeaders = ['sku', 'certification'];
            foreach ($certsHeaders as $col => $header) {
                $certsSheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            }
            $certsSheet->getStyle('A1:B1')->applyFromArray($headerStyle);

            $exampleCerts = [
                ['VS-PML-001', 'GS Tested Safety'],
                ['VS-PML-001', 'GREENGUARD Gold'],
                ['VS-PMV-001', 'GS Tested Safety'],
                ['VS-CVF-001', 'GS Tested Safety'],
                ['VS-HAM-001', 'BIFMA e3 LEVEL'],
                ['VS-PFX-001', 'GS Tested Safety'],
                ['VS-PFX-001', 'GREENGUARD Gold'],
                ['VS-LPC-001', 'GS Tested Safety'],
            ];
            foreach ($exampleCerts as $rowIdx => $rowData) {
                foreach ($rowData as $col => $value) {
                    $certsSheet->setCellValueByColumnAndRow($col + 1, $rowIdx + 2, $value);
                }
            }
            $lastCertRow = count($exampleCerts) + 1;
            $certsSheet->getStyle("A2:B{$lastCertRow}")->applyFromArray($exampleStyle);

            $certNoteRow = $lastCertRow + 2;
            $certsSheet->setCellValue("A{$certNoteRow}", 'Each row = one certification assignment. Use the certification title as it exists in the system.');
            $certsSheet->getStyle("A{$certNoteRow}")->applyFromArray($noteStyle);

            foreach (range('A', 'B') as $col) {
                $certsSheet->getColumnDimension($col)->setAutoSize(true);
            }
            $certsSheet->setAutoFilter('A1:B1');

            // Set Sheet 1 as active
            $spreadsheet->setActiveSheetIndex(0);

            // Write to temp file and stream
            $tempFile = storage_path('app/temp/product_import_template.xlsx');
            $tempDir = dirname($tempFile);
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            return response()->download($tempFile, 'product_import_template.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Failed to download template', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to download template', 'error' => $e->getMessage()], 500);
        }
    }
}
