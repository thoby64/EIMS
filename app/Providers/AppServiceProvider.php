<?php

namespace App\Providers;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetAttributeValue;
use App\Models\AssetCategory;
use App\Models\AssetCustomProperty;
use App\Models\AssetDisposal;
use App\Models\AssetEvent;
use App\Models\AssetGroup;
use App\Models\AssetIdentifier;
use App\Models\AssetInspection;
use App\Models\AssetMovement;
use App\Models\AssetRequest;
use App\Models\AssetRequestProperty;
use App\Models\AttributeDefinition;
use App\Models\Department;
use App\Models\HandoverReceipt;
use App\Models\Location;
use App\Models\MaintenanceCase;
use App\Models\MaintenanceReport;
use App\Models\MaintenanceReview;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SpareRequisition;
use App\Models\User;
use App\Observers\AuditableModelObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default string length to 191 for MySQL compatibility with utf8mb4
        Schema::defaultStringLength(191);

        // Ensure migrations table is properly structured with AUTO_INCREMENT
        $this->ensureMigrationsTable();

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $models = [
            Asset::class, AssetAssignment::class, AssetAttributeValue::class,
            AssetCategory::class, AssetCustomProperty::class, AssetDisposal::class,
            AssetEvent::class, AssetGroup::class, AssetIdentifier::class,
            AssetInspection::class, AssetMovement::class, AssetRequest::class,
            AssetRequestProperty::class, AttributeDefinition::class, Department::class,
            HandoverReceipt::class, Location::class, MaintenanceCase::class,
            MaintenanceReport::class, MaintenanceReview::class, Permission::class,
            Role::class, SpareRequisition::class, User::class,
        ];
        foreach ($models as $model) {
            $model::observe(AuditableModelObserver::class);
        }
    }

    /**
     * Ensure the migrations table has proper AUTO_INCREMENT structure.
     */
    private function ensureMigrationsTable(): void
    {
        try {
            if (Schema::hasTable('migrations')) {
                // Check if the id column has AUTO_INCREMENT
                $columns = DB::select('SHOW CREATE TABLE migrations');
                if (!empty($columns)) {
                    $createTableSql = $columns[0]->{'Create Table'} ?? '';
                    if (strpos($createTableSql, 'AUTO_INCREMENT') === false) {
                        // Try to fix it
                        try {
                            DB::statement('ALTER TABLE migrations DROP PRIMARY KEY');
                        } catch (\Exception $e) {
                            // Primary key might not exist, continue
                        }
                        DB::statement('ALTER TABLE migrations MODIFY id int UNSIGNED AUTO_INCREMENT PRIMARY KEY');
                    }
                }
            } else {
                // Create the migrations table with proper structure
                DB::statement('
                    CREATE TABLE migrations (
                        id int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        migration varchar(255) NOT NULL,
                        batch int NOT NULL
                    ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
                ');
            }
        } catch (\Exception $e) {
            // Silently fail if we can't check/fix the table
            // The actual migration error will bubble up to the user
        }
    }
}
