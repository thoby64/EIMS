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
}
