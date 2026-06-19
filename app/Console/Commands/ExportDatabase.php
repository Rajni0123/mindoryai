<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ExportDatabase extends Command
{
    protected $signature = 'db:export {--file=database.sql : Output SQL file name}';
    protected $description = 'Export database schema and seeders to SQL file for production deployment';

    public function handle(): int
    {
        $filename = $this->option('file');
        $filepath = base_path($filename);

        $this->info("🗄️  Exporting database to: {$filename}");

        try {
            $sql = $this->generateSQL();

            File::put($filepath, $sql);

            $size = File::size($filepath);
            $sizeKB = round($size / 1024, 2);

            $this->info("✅ Database exported successfully!");
            $this->table(
                ['File', 'Size', 'Location'],
                [[$filename, "{$sizeKB} KB", $filepath]]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Export failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    protected function generateSQL(): string
    {
        $sql = "-- ================================================================\n";
        $sql .= "-- BLINKSTUDY AI - Database Export\n";
        $sql .= "-- Generated: " . now()->toDateTimeString() . "\n";
        $sql .= "-- For Shared Hosting Deployment\n";
        $sql .= "-- ================================================================\n\n";

        $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql .= "START TRANSACTION;\n";
        $sql .= "SET time_zone = \"+00:00\";\n\n";

        $sql .= "-- Database: blinkstudy_ai\n\n";

        // Get all tables
        $tables = DB::select('SHOW TABLES');
        $databaseName = DB::getDatabaseName();
        $tableKey = "Tables_in_{$databaseName}";

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;

            $this->line("Exporting table: {$tableName}");

            // Drop table if exists
            $sql .= "-- --------------------------------------------------------\n";
            $sql .= "-- Table structure for table `{$tableName}`\n";
            $sql .= "-- --------------------------------------------------------\n\n";
            $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n\n";

            // Get create table statement
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $sql .= $createTable[0]->{'Create Table'} . ";\n\n";

            // Get table data (only for certain tables)
            if (in_array($tableName, ['user_plans', 'pricing_plans', 'settings'])) {
                $rows = DB::table($tableName)->get();

                if ($rows->count() > 0) {
                    $sql .= "-- Dumping data for table `{$tableName}`\n\n";

                    foreach ($rows as $row) {
                        $row = (array) $row;
                        $columns = array_keys($row);
                        $values = array_values($row);

                        // Escape values
                        $escapedValues = array_map(function($value) {
                            if (is_null($value)) {
                                return 'NULL';
                            }
                            return "'" . addslashes($value) . "'";
                        }, $values);

                        $sql .= "INSERT INTO `{$tableName}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $escapedValues) . ");\n";
                    }

                    $sql .= "\n";
                }
            }
        }

        $sql .= "COMMIT;\n\n";
        $sql .= "-- ================================================================\n";
        $sql .= "-- End of export\n";
        $sql .= "-- ================================================================\n";

        return $sql;
    }
}
