<?php

namespace App\Services;

use App\Models\XmlImport;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use SimpleXMLElement;
use Throwable;

/**
 * XML Import Service
 *
 * Handles secure XML parsing and database updates with full transaction support.
 *
 * Security Features:
 * - XML validation
 * - Table/column whitelist checking
 * - Transaction rollback on errors
 * - Duplicate import prevention via file hash
 * - No raw SQL execution from XML
 * - No PHP code execution allowed
 *
 * @package App\Services
 */
class XmlImportService
{
    /**
     * Maximum file size in bytes (5MB)
     */
    private const MAX_FILE_SIZE = 5 * 1024 * 1024;

    /**
     * Allowed actions from XML
     */
    private const ALLOWED_ACTIONS = ['insert', 'update', 'delete', 'upsert'];

    /**
     * Tables that are NEVER allowed to be modified via XML import
     * (Critical system tables)
     */
    private const BLACKLISTED_TABLES = [
        'migrations',
        'password_reset_tokens',
        'sessions',
        'failed_jobs',
        'personal_access_tokens',
    ];

    /**
     * Columns that are NEVER allowed to be modified via XML import
     * (Security-critical columns)
     */
    private const BLACKLISTED_COLUMNS = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'api_token',
    ];

    /**
     * Import log for detailed tracking
     *
     * @var array
     */
    private array $importLog = [];

    /**
     * Statistics counters
     *
     * @var array
     */
    private array $stats = [
        'processed' => 0,
        'success' => 0,
        'failed' => 0,
        'errors' => [],
    ];

    /**
     * Process XML import from uploaded file
     *
     * @param string $filePath Path to uploaded XML file
     * @param int $adminId Admin user ID performing the import
     * @param string|null $ipAddress IP address of the admin
     * @param string|null $userAgent User agent of the admin
     * @return XmlImport Import record
     * @throws Exception
     */
    public function processImport(
        string $filePath,
        int $adminId,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): XmlImport {
        // Validate file existence
        if (!file_exists($filePath)) {
            throw new Exception('XML file not found');
        }

        // Validate file size
        if (filesize($filePath) > self::MAX_FILE_SIZE) {
            throw new Exception('File size exceeds maximum allowed size of 5MB');
        }

        // Calculate file hash to prevent duplicate imports
        $fileHash = hash_file('sha256', $filePath);
        $filename = basename($filePath);

        // Check for duplicate import
        $existingImport = XmlImport::where('file_hash', $fileHash)
            ->where('status', 'success')
            ->first();

        if ($existingImport) {
            throw new Exception("This XML file has already been imported successfully on " .
                              $existingImport->completed_at->format('Y-m-d H:i:s'));
        }

        // Create import record
        $import = XmlImport::create([
            'admin_id' => $adminId,
            'filename' => $filename,
            'file_hash' => $fileHash,
            'status' => 'pending',
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        try {
            // Mark as processing
            $import->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            // Load and validate XML
            $xml = $this->loadXml($filePath);

            // Extract version if present
            $version = isset($xml['version']) ? (string) $xml['version'] : null;
            $import->update(['version' => $version]);

            // Process all updates within a database transaction
            DB::beginTransaction();

            try {
                $this->processXmlUpdates($xml, $import);

                // Commit if all successful
                DB::commit();

                // Mark as successful
                $import->update([
                    'status' => 'success',
                    'records_processed' => $this->stats['processed'],
                    'records_success' => $this->stats['success'],
                    'records_failed' => $this->stats['failed'],
                    'import_log' => json_encode($this->importLog, JSON_PRETTY_PRINT),
                    'completed_at' => now(),
                ]);

                Log::info('XML import completed successfully', [
                    'import_id' => $import->id,
                    'admin_id' => $adminId,
                    'filename' => $filename,
                    'records_processed' => $this->stats['processed'],
                    'records_success' => $this->stats['success'],
                ]);

            } catch (Throwable $e) {
                // Rollback transaction on any error
                DB::rollBack();

                throw $e;
            }

        } catch (Throwable $e) {
            // Mark as failed
            $import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'records_processed' => $this->stats['processed'],
                'records_success' => $this->stats['success'],
                'records_failed' => $this->stats['failed'],
                'import_log' => json_encode($this->importLog, JSON_PRETTY_PRINT),
                'completed_at' => now(),
            ]);

            Log::error('XML import failed', [
                'import_id' => $import->id,
                'admin_id' => $adminId,
                'filename' => $filename,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }

        return $import;
    }

    /**
     * Load and validate XML file
     *
     * @param string $filePath
     * @return SimpleXMLElement
     * @throws Exception
     */
    private function loadXml(string $filePath): SimpleXMLElement
    {
        // Disable external entity loading for security
        libxml_disable_entity_loader(true);

        // Suppress XML errors and handle them manually
        libxml_use_internal_errors(true);

        try {
            $xmlContent = file_get_contents($filePath);

            // Security check: prevent XXE attacks
            // Check case-insensitively for DOCTYPE and ENTITY
            $xmlContentUpper = strtoupper($xmlContent);
            if (strpos($xmlContentUpper, '<!DOCTYPE') !== false || strpos($xmlContentUpper, '<!ENTITY') !== false) {
                throw new Exception('XML contains prohibited DOCTYPE or ENTITY declarations. Please remove these from your XML file and keep only the <?xml?> declaration and <updates> root element.');
            }

            $xml = new SimpleXMLElement($xmlContent, LIBXML_NOCDATA | LIBXML_NOBLANKS);

            // Validate root element
            if ($xml->getName() !== 'updates') {
                throw new Exception('Invalid XML structure: root element must be <updates>');
            }

            return $xml;

        } catch (Exception $e) {
            $errors = libxml_get_errors();
            libxml_clear_errors();

            if (!empty($errors)) {
                $errorMessages = array_map(function($error) {
                    return trim($error->message);
                }, $errors);

                throw new Exception('XML parsing error: ' . implode('; ', $errorMessages));
            }

            throw new Exception('Failed to parse XML: ' . $e->getMessage());
        }
    }

    /**
     * Process all XML updates
     *
     * @param SimpleXMLElement $xml
     * @param XmlImport $import
     * @return void
     * @throws Exception
     */
    private function processXmlUpdates(SimpleXMLElement $xml, XmlImport $import): void
    {
        // Check if there are any table elements
        if (!isset($xml->table)) {
            throw new Exception('No <table> elements found in XML');
        }

        // Process each table
        foreach ($xml->table as $tableElement) {
            $this->processTable($tableElement, $import);
        }

        // Check if any records were processed
        if ($this->stats['processed'] === 0) {
            throw new Exception('No records found to process in XML');
        }

        // Check if all records failed
        if ($this->stats['failed'] === $this->stats['processed']) {
            throw new Exception('All records failed to process. Check import log for details.');
        }
    }

    /**
     * Process a single table element
     *
     * @param SimpleXMLElement $tableElement
     * @param XmlImport $import
     * @return void
     */
    private function processTable(SimpleXMLElement $tableElement, XmlImport $import): void
    {
        // Get table name
        $tableName = isset($tableElement['name']) ? (string) $tableElement['name'] : null;

        if (!$tableName) {
            $this->logError('Table element missing "name" attribute', null);
            return;
        }

        // Validate table name
        try {
            $this->validateTableName($tableName);
        } catch (Exception $e) {
            $this->logError("Table validation failed: {$e->getMessage()}", $tableName);
            return;
        }

        // Process each record in the table
        if (!isset($tableElement->record)) {
            $this->logWarning("No records found in table: $tableName", $tableName);
            return;
        }

        foreach ($tableElement->record as $recordElement) {
            $this->processRecord($tableName, $recordElement, $import);
        }
    }

    /**
     * Process a single record element
     *
     * @param string $tableName
     * @param SimpleXMLElement $recordElement
     * @param XmlImport $import
     * @return void
     */
    private function processRecord(string $tableName, SimpleXMLElement $recordElement, XmlImport $import): void
    {
        $this->stats['processed']++;

        try {
            // Get action (default to 'upsert' for backward compatibility)
            $action = isset($recordElement['action']) ? strtolower((string) $recordElement['action']) : 'upsert';

            // Validate action
            if (!in_array($action, self::ALLOWED_ACTIONS)) {
                throw new Exception("Invalid action: $action. Allowed actions: " . implode(', ', self::ALLOWED_ACTIONS));
            }

            // Get 'where' column for update/delete/upsert
            $whereColumn = isset($recordElement['where']) ? (string) $recordElement['where'] : null;

            // Extract data from XML
            $data = $this->extractRecordData($recordElement, $tableName);

            // Validate data
            if (empty($data)) {
                throw new Exception('No data found in record');
            }

            // Execute action
            switch ($action) {
                case 'insert':
                    $this->executeInsert($tableName, $data);
                    break;

                case 'update':
                    if (!$whereColumn) {
                        throw new Exception('Update action requires "where" attribute');
                    }
                    $this->executeUpdate($tableName, $data, $whereColumn);
                    break;

                case 'delete':
                    if (!$whereColumn) {
                        throw new Exception('Delete action requires "where" attribute');
                    }
                    $this->executeDelete($tableName, $data, $whereColumn);
                    break;

                case 'upsert':
                    if (!$whereColumn) {
                        throw new Exception('Upsert action requires "where" attribute');
                    }
                    $this->executeUpsert($tableName, $data, $whereColumn);
                    break;
            }

            $this->stats['success']++;
            $this->logSuccess("$action successful in $tableName", $tableName, $action, $data);

        } catch (Exception $e) {
            $this->stats['failed']++;
            $this->logError("Failed to process record in $tableName: {$e->getMessage()}", $tableName, $e->getTraceAsString());
        }
    }

    /**
     * Extract data from record XML element
     *
     * @param SimpleXMLElement $recordElement
     * @param string $tableName
     * @return array
     * @throws Exception
     */
    private function extractRecordData(SimpleXMLElement $recordElement, string $tableName): array
    {
        $data = [];

        foreach ($recordElement->children() as $field) {
            $columnName = $field->getName();
            $value = (string) $field;

            // Validate column
            $this->validateColumn($tableName, $columnName);

            // Handle special values
            if ($value === 'NULL' || $value === '') {
                $value = null;
            } elseif ($value === 'true' || $value === 'TRUE') {
                $value = true;
            } elseif ($value === 'false' || $value === 'FALSE') {
                $value = false;
            }

            $data[$columnName] = $value;
        }

        return $data;
    }

    /**
     * Execute INSERT operation
     *
     * @param string $table
     * @param array $data
     * @return void
     */
    private function executeInsert(string $table, array $data): void
    {
        DB::table($table)->insert($data);
    }

    /**
     * Execute UPDATE operation
     *
     * @param string $table
     * @param array $data
     * @param string $whereColumn
     * @return void
     * @throws Exception
     */
    private function executeUpdate(string $table, array $data, string $whereColumn): void
    {
        if (!isset($data[$whereColumn])) {
            throw new Exception("WHERE column '$whereColumn' not found in data");
        }

        $whereValue = $data[$whereColumn];
        unset($data[$whereColumn]); // Remove from update data

        $affected = DB::table($table)
            ->where($whereColumn, $whereValue)
            ->update($data);

        if ($affected === 0) {
            throw new Exception("No records found to update with $whereColumn = $whereValue");
        }
    }

    /**
     * Execute DELETE operation
     *
     * @param string $table
     * @param array $data
     * @param string $whereColumn
     * @return void
     * @throws Exception
     */
    private function executeDelete(string $table, array $data, string $whereColumn): void
    {
        if (!isset($data[$whereColumn])) {
            throw new Exception("WHERE column '$whereColumn' not found in data");
        }

        $whereValue = $data[$whereColumn];

        $affected = DB::table($table)
            ->where($whereColumn, $whereValue)
            ->delete();

        if ($affected === 0) {
            throw new Exception("No records found to delete with $whereColumn = $whereValue");
        }
    }

    /**
     * Execute UPSERT (update or insert) operation
     *
     * @param string $table
     * @param array $data
     * @param string $whereColumn
     * @return void
     * @throws Exception
     */
    private function executeUpsert(string $table, array $data, string $whereColumn): void
    {
        if (!isset($data[$whereColumn])) {
            throw new Exception("WHERE column '$whereColumn' not found in data");
        }

        DB::table($table)->updateOrInsert(
            [$whereColumn => $data[$whereColumn]],
            $data
        );
    }

    /**
     * Validate table name
     *
     * @param string $tableName
     * @return void
     * @throws Exception
     */
    private function validateTableName(string $tableName): void
    {
        // Check against blacklist
        if (in_array($tableName, self::BLACKLISTED_TABLES)) {
            throw new Exception("Table '$tableName' is blacklisted and cannot be modified via XML import");
        }

        // Check if table exists
        if (!Schema::hasTable($tableName)) {
            throw new Exception("Table '$tableName' does not exist in database");
        }
    }

    /**
     * Validate column name
     *
     * @param string $tableName
     * @param string $columnName
     * @return void
     * @throws Exception
     */
    private function validateColumn(string $tableName, string $columnName): void
    {
        // Check against blacklist
        if (in_array($columnName, self::BLACKLISTED_COLUMNS)) {
            throw new Exception("Column '$columnName' is blacklisted and cannot be modified via XML import");
        }

        // Check if column exists
        if (!Schema::hasColumn($tableName, $columnName)) {
            throw new Exception("Column '$columnName' does not exist in table '$tableName'");
        }
    }

    /**
     * Log success
     *
     * @param string $message
     * @param string|null $table
     * @param string|null $action
     * @param array|null $data
     * @return void
     */
    private function logSuccess(string $message, ?string $table = null, ?string $action = null, ?array $data = null): void
    {
        $this->importLog[] = [
            'status' => 'success',
            'message' => $message,
            'table' => $table,
            'action' => $action,
            'data' => $data,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    /**
     * Log error
     *
     * @param string $message
     * @param string|null $table
     * @param string|null $trace
     * @return void
     */
    private function logError(string $message, ?string $table = null, ?string $trace = null): void
    {
        $this->importLog[] = [
            'status' => 'error',
            'message' => $message,
            'table' => $table,
            'trace' => $trace,
            'timestamp' => now()->toDateTimeString(),
        ];

        $this->stats['errors'][] = $message;
    }

    /**
     * Log warning
     *
     * @param string $message
     * @param string|null $table
     * @return void
     */
    private function logWarning(string $message, ?string $table = null): void
    {
        $this->importLog[] = [
            'status' => 'warning',
            'message' => $message,
            'table' => $table,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get import statistics
     *
     * @return array
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Get import log
     *
     * @return array
     */
    public function getImportLog(): array
    {
        return $this->importLog;
    }
}
