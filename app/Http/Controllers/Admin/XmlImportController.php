<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\XmlImport;
use App\Services\XmlImportService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * XML Import Controller
 *
 * Handles XML file uploads and import processing for database updates.
 * Only accessible to admin users.
 *
 * @package App\Http\Controllers\Admin
 */
class XmlImportController extends Controller
{
    /**
     * XML Import Service instance
     *
     * @var XmlImportService
     */
    private XmlImportService $importService;

    /**
     * Constructor
     *
     * @param XmlImportService $importService
     */
    public function __construct(XmlImportService $importService)
    {
        $this->importService = $importService;

        // Note: Admin middleware is applied in routes file (web.php)
        // No need to apply it here as well
    }

    /**
     * Display XML import page with upload form and import history
     *
     * @return View
     */
    public function index(): View
    {
        // Get import history (latest first)
        $imports = XmlImport::with('admin')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get statistics
        $stats = [
            'total_imports' => XmlImport::count(),
            'successful_imports' => XmlImport::where('status', 'success')->count(),
            'failed_imports' => XmlImport::where('status', 'failed')->count(),
            'processing_imports' => XmlImport::where('status', 'processing')->count(),
            'total_records_processed' => XmlImport::where('status', 'success')->sum('records_processed'),
        ];

        return view('admin.xml-import.index', compact('imports', 'stats'));
    }

    /**
     * Handle XML file upload and processing
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function upload(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'xml_file' => [
                'required',
                'file',
                'mimes:xml',
                'max:5120', // 5MB max
            ],
            'preview_mode' => 'nullable|boolean',
        ], [
            'xml_file.required' => 'Please select an XML file to upload',
            'xml_file.mimes' => 'File must be an XML file (.xml extension)',
            'xml_file.max' => 'File size must not exceed 5MB',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Get uploaded file
            $file = $request->file('xml_file');
            $filePath = $file->getRealPath();

            // Preview mode - validate XML and show preview without executing
            if ($request->boolean('preview_mode')) {
                return $this->previewImport($filePath, $file->getClientOriginalName());
            }

            // Process import
            $import = $this->importService->processImport(
                filePath: $filePath,
                adminId: auth()->id(),
                ipAddress: $request->ip(),
                userAgent: $request->userAgent()
            );

            // Log successful import
            Log::info('Admin XML import completed', [
                'import_id' => $import->id,
                'admin_id' => auth()->id(),
                'admin_email' => auth()->user()->email ?? auth()->user()->mobile,
                'filename' => $import->filename,
                'status' => $import->status,
                'records_processed' => $import->records_processed,
                'records_success' => $import->records_success,
                'records_failed' => $import->records_failed,
            ]);

            // Return response
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'XML import completed successfully',
                    'import' => [
                        'id' => $import->id,
                        'status' => $import->status,
                        'records_processed' => $import->records_processed,
                        'records_success' => $import->records_success,
                        'records_failed' => $import->records_failed,
                        'duration' => $import->duration,
                    ],
                ]);
            }

            return redirect()->route('admin.xml-import.show', $import->id)
                ->with('success', "XML import completed successfully! Processed {$import->records_processed} records with {$import->records_success} successful and {$import->records_failed} failed.");

        } catch (Exception $e) {
            // Log error
            Log::error('Admin XML import failed', [
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return error response
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import failed: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Import failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Preview XML import without executing
     *
     * @param string $filePath
     * @param string $filename
     * @return JsonResponse
     */
    private function previewImport(string $filePath, string $filename): JsonResponse
    {
        try {
            // Load XML
            libxml_disable_entity_loader(true);
            libxml_use_internal_errors(true);

            $xmlContent = file_get_contents($filePath);

            // Security check
            if (strpos($xmlContent, '<!DOCTYPE') !== false || strpos($xmlContent, '<!ENTITY') !== false) {
                throw new Exception('XML contains prohibited DOCTYPE or ENTITY declarations');
            }

            $xml = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);

            if ($xml === false) {
                $errors = libxml_get_errors();
                libxml_clear_errors();

                $errorMessages = array_map(function($error) {
                    return trim($error->message);
                }, $errors);

                throw new Exception('XML parsing error: ' . implode('; ', $errorMessages));
            }

            // Validate root element
            if ($xml->getName() !== 'updates') {
                throw new Exception('Invalid XML structure: root element must be <updates>');
            }

            // Extract preview data
            $preview = [
                'filename' => $filename,
                'version' => isset($xml['version']) ? (string) $xml['version'] : null,
                'tables' => [],
                'total_records' => 0,
            ];

            // Count records per table
            if (isset($xml->table)) {
                foreach ($xml->table as $table) {
                    $tableName = isset($table['name']) ? (string) $table['name'] : 'Unknown';
                    $recordCount = isset($table->record) ? count($table->record) : 0;

                    $preview['tables'][] = [
                        'name' => $tableName,
                        'records' => $recordCount,
                    ];

                    $preview['total_records'] += $recordCount;
                }
            }

            return response()->json([
                'success' => true,
                'preview' => $preview,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Preview failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Show detailed import information
     *
     * @param XmlImport $import
     * @return View
     */
    public function show(XmlImport $import): View
    {
        // Load admin relationship
        $import->load('admin');

        // Parse import log if exists
        $importLog = null;
        if ($import->import_log) {
            $importLog = json_decode($import->import_log, true);
        }

        return view('admin.xml-import.show', compact('import', 'importLog'));
    }

    /**
     * Download import log as JSON
     *
     * @param XmlImport $import
     * @return \Illuminate\Http\Response
     */
    public function downloadLog(XmlImport $import)
    {
        $filename = "import-log-{$import->id}-" . now()->format('Y-m-d-His') . ".json";

        return response($import->import_log)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=\"$filename\"");
    }

    /**
     * Delete import record
     *
     * @param XmlImport $import
     * @return RedirectResponse
     */
    public function destroy(XmlImport $import): RedirectResponse
    {
        try {
            $import->delete();

            Log::info('Admin deleted XML import record', [
                'import_id' => $import->id,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->route('admin.xml-import.index')
                ->with('success', 'Import record deleted successfully');

        } catch (Exception $e) {
            Log::error('Failed to delete XML import record', [
                'import_id' => $import->id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete import record: ' . $e->getMessage());
        }
    }
}
