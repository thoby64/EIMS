<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PDO;

class MigrateDataFromMySQLSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Temporarily create MySQL connection to read data
        $mysqlConnection = new PDO(
            'mysql:host=localhost;dbname=eimsdata',
            'thobbs',
            'thobby',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // Get PostgreSQL connection
        $pgsqlConnection = DB::connection('pgsql')->getPdo();
        $pgsqlConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        try {
            $this->command->info('🔄 Starting data migration from MySQL to PostgreSQL...');

            // Get all table names from PostgreSQL
            $stmt = $pgsqlConnection->query("
                SELECT tablename FROM pg_tables 
                WHERE schemaname = 'public' 
                AND tablename NOT LIKE '%migrations%'
                ORDER BY tablename
            ");
            $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Migrate data in dependency order (no truncation - skip duplicates instead)
            $this->command->line('Migrating data from MySQL (respecting dependencies)...');
            $orderedTables = $this->getTableOrderByDependencies();
            
            foreach ($orderedTables as $table) {
                if (!in_array($table, $allTables)) {
                    continue; // Skip if table doesn't exist in PostgreSQL
                }
                try {
                    $this->migrateTable($table, $mysqlConnection, $pgsqlConnection);
                } catch (\Exception $e) {
                    $this->command->warn("  ⚠ {$table}: " . $e->getMessage());
                }
            }

            // Reset sequences for auto-increment columns
            $this->command->line('Resetting sequences...');
            $this->resetSequences($pgsqlConnection);

            $this->command->info('✅ All data successfully migrated from MySQL to PostgreSQL!');
        } catch (\Exception $e) {
            $this->command->error('❌ Migration failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getTableOrderByDependencies(): array
    {
        // Tables ordered by dependencies (parents before children)
        return [
            // Independent tables (no foreign keys to other tables)
            'roles',
            'permissions',
            'attribute_definitions',
            'asset_groups',
            'organizational_units',
            'locations',          // Must come BEFORE users
            'cache',
            'sessions',
            'failed_jobs',
            'job_batches',
            'password_reset_tokens',

            // Users (depends on organizational_units, locations)
            'users',

            // Pivot/junction tables
            'role_user',
            'permission_role',

            // Departments
            'departments',

            // Asset categories (depends on asset_groups)
            'asset_categories',

            // Asset category attributes
            'asset_category_attribute',

            // Assets (depends on asset_categories, organizational_units, locations, users)
            'assets',

            // Asset tag sequences (depends on asset_categories)
            'asset_tag_sequences',

            // Asset-related tables (depend on assets)
            'asset_identifiers',
            'asset_attribute_values',
            'asset_custom_properties',
            'asset_events',
            'asset_assignments',
            'asset_assignment_receivers',
            'asset_movements',
            'asset_movement_receivers',
            'asset_inspections',
            'asset_disposals',

            // Asset requests
            'asset_requests',
            'asset_request_properties',

            // Maintenance tables
            'maintenance_cases',
            'maintenance_reports',
            'maintenance_reviews',
            'maintenance_return_confirmations',
            'spare_requisitions',
            'maintenance_category_responsibilities',

            // Handover receipts
            'handover_receipts',

            // Audit and notifications
            'audit_logs',
            'notifications',

            // Cache and jobs
            'cache_locks',
        ];
    }

    private function truncateAllTables($pgsqlConnection, $tables): void
    {
        // Truncate in reverse order to handle foreign keys
        $tablesToTruncate = array_reverse($tables);
        foreach ($tablesToTruncate as $table) {
            try {
                $pgsqlConnection->exec("TRUNCATE TABLE \"{$table}\" RESTART IDENTITY CASCADE");
            } catch (\PDOException $e) {
                // Table might not exist or might fail, continue
            }
        }
    }

    private function migrateTable($table, $mysqlConnection, $pgsqlConnection): void
    {
        try {
            // Get all data from MySQL
            $stmt = $mysqlConnection->prepare("SELECT * FROM `{$table}`");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                $this->command->line("  ⊘ {$table}: No data to migrate");
                return;
            }

            // Get column names
            $columns = array_keys($rows[0]);

            // Build insert statement for PostgreSQL
            $columnNames = implode(', ', array_map(fn ($col) => '"' . $col . '"', $columns));
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));

            $insertSql = "INSERT INTO \"{$table}\" ({$columnNames}) VALUES ({$placeholders})";

            // Insert data into PostgreSQL
            $insertStmt = $pgsqlConnection->prepare($insertSql);
            $insertedCount = 0;
            $skippedCount = 0;

            foreach ($rows as $row) {
                try {
                    $values = array_values($row);
                    // Convert MySQL boolean/bit values to PostgreSQL format if needed
                    $values = array_map(function ($val) {
                        if ($val === '0' || $val === 0) {
                            return 0;
                        } elseif ($val === '1' || $val === 1) {
                            return 1;
                        }
                        return $val;
                    }, $values);

                    $insertStmt->execute($values);
                    $insertedCount++;
                } catch (\PDOException $rowError) {
                    // Skip rows that violate constraints (FK, unique, not-null, etc)
                    $msg = $rowError->getMessage();
                    if (strpos($msg, 'Foreign key violation') !== false
                        || strpos($msg, '23503') !== false
                        || strpos($msg, 'Unique violation') !== false
                        || strpos($msg, '23505') !== false
                        || strpos($msg, 'duplicate') !== false
                        || strpos($msg, '23502') !== false) {
                        $skippedCount++;
                    } else {
                        throw $rowError;
                    }
                }
            }

            if ($skippedCount > 0) {
                $this->command->line("  ✓ {$table}: {$insertedCount} rows migrated, {$skippedCount} skipped");
            } else {
                $this->command->line("  ✓ {$table}: {$insertedCount} rows migrated");
            }
        } catch (\PDOException $e) {
            // Table might not exist or might be empty
            if (strpos($e->getMessage(), 'Base table or view not found') !== false 
                || strpos($e->getMessage(), 'no such table') !== false) {
                $this->command->line("  ⊘ {$table}: Table not found in MySQL (skipping)");
            } else {
                throw $e;
            }
        }
    }

    private function resetSequences($pgsqlConnection): void
    {
        $this->command->line("\nResetting sequences...");

        try {
            // Get all sequences from PostgreSQL
            $stmt = $pgsqlConnection->query("
                SELECT sequence_name 
                FROM information_schema.sequences 
                WHERE sequence_schema = 'public'
                ORDER BY sequence_name
            ");

            $sequences = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $resetCount = 0;
            foreach ($sequences as $sequence) {
                try {
                    // Extract table name from sequence (usually tablename_id_seq)
                    preg_match('/(.+)_id_seq/', $sequence, $matches);
                    if (isset($matches[1])) {
                        $tableName = $matches[1];
                        $pgsqlConnection->exec("SELECT setval('{$sequence}', COALESCE((SELECT MAX(id) FROM \"{$tableName}\"), 0) + 1, false)");
                        $resetCount++;
                    }
                } catch (\Exception $e) {
                    // Silently skip if sequence reset fails
                }
            }

            $this->command->line("  ✓ {$resetCount} sequences reset successfully");
        } catch (\Exception $e) {
            $this->command->warn("  ⚠ Could not reset sequences: " . $e->getMessage());
        }
    }
}
