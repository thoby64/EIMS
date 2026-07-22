<?php

use App\Http\Controllers\AssetAssignmentController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetDisposalController;
use App\Http\Controllers\AssetInspectionController;
use App\Http\Controllers\AssetLabelController;
use App\Http\Controllers\AssetMovementController;
use App\Http\Controllers\AssetRequestController;
use App\Http\Controllers\AssetScanController;
use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentManagementController;
use App\Http\Controllers\HandoverReceiptController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\MaintenanceCaseController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicAssetVerificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthCheckController::class)->name('health');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::get('/verify/{token}', PublicAssetVerificationController::class)
    ->where('token', '[A-Za-z0-9]{48}')
    ->name('assets.verify');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/inspections', [AssetInspectionController::class, 'index'])->name('inspections.index');
    Route::get('/inspections/create', [AssetInspectionController::class, 'create'])->middleware('permission:inspections.manage')->name('inspections.create');
    Route::post('/inspections', [AssetInspectionController::class, 'store'])->middleware('permission:inspections.manage')->name('inspections.store');
    Route::get('/inspections/{inspection}', [AssetInspectionController::class, 'show'])->name('inspections.show');
    Route::post('/inspections/{inspection}/complete', [AssetInspectionController::class, 'complete'])->name('inspections.complete');
    Route::get('/disposals', [AssetDisposalController::class, 'index'])->name('disposals.index');
    Route::get('/assets/{asset}/retirement', [AssetDisposalController::class, 'create'])->middleware('permission:disposals.propose')->name('disposals.create');
    Route::post('/assets/{asset}/retirement', [AssetDisposalController::class, 'store'])->middleware('permission:disposals.propose')->name('disposals.store');
    Route::get('/disposals/{disposal}', [AssetDisposalController::class, 'show'])->name('disposals.show');
    Route::post('/disposals/{disposal}/review', [AssetDisposalController::class, 'review'])->middleware('permission:disposals.review')->name('disposals.review');
    Route::post('/disposals/{disposal}/decision', [AssetDisposalController::class, 'approve'])->middleware('permission:disposals.approve')->name('disposals.approve');
    Route::post('/disposals/{disposal}/surrender', [AssetDisposalController::class, 'surrender'])->name('disposals.surrender');
    Route::post('/disposals/{disposal}/finalize', [AssetDisposalController::class, 'finalize'])->middleware('permission:disposals.finalize')->name('disposals.finalize');
    Route::get('/reports', [ReportController::class, 'index'])->middleware('permission:reports.view')->name('reports.index');
    Route::get('/reports/assets', [ReportController::class, 'assets'])->middleware('permission:reports.view')->name('reports.assets');
    Route::get('/reports/assets/export', [ReportController::class, 'exportAssets'])->middleware('permission:reports.view')->name('reports.assets.export');
    Route::get('/audit-trail', AuditTrailController::class)->middleware('permission:audit.view')->name('audit.index');
    Route::get('/audit-trail/{auditLog}', [AuditTrailController::class, 'show'])->middleware('permission:audit.view')->name('audit.show');

    Route::get('/assignments', [AssetAssignmentController::class, 'index'])->middleware('permission:assignments.view')->name('assignments.index');
    Route::get('/assignments/create', [AssetAssignmentController::class, 'create'])->middleware('permission:assets.assign')->name('assignments.create');
    Route::post('/assignments', [AssetAssignmentController::class, 'store'])->middleware('permission:assets.assign')->name('assignments.store');
    Route::get('/assignments/{assignment}', [AssetAssignmentController::class, 'show'])->middleware('permission:assignments.view')->name('assignments.show');

    Route::get('/movements', [AssetMovementController::class, 'index'])->name('movements.index');
    Route::get('/assets/{asset}/movement', [AssetMovementController::class, 'create'])->name('movements.create');
    Route::post('/assets/{asset}/movement', [AssetMovementController::class, 'store'])->name('movements.store');
    Route::get('/movements/{movement}', [AssetMovementController::class, 'show'])->name('movements.show');
    Route::post('/movements/{movement}/decision', [AssetMovementController::class, 'decide'])->middleware('permission:assets.transfer')->name('movements.decision');
    Route::post('/movements/{movement}/confirm', [AssetMovementController::class, 'confirm'])->name('movements.confirm');

    Route::get('/my-handovers', [HandoverReceiptController::class, 'pending'])->middleware('permission:handovers.confirm')->name('handovers.pending');
    Route::get('/my-handovers/{assignment}/confirm', [HandoverReceiptController::class, 'create'])->middleware('permission:handovers.confirm')->name('handovers.confirm');
    Route::post('/my-handovers/{assignment}', [HandoverReceiptController::class, 'store'])->middleware('permission:handovers.confirm')->name('handovers.store');

    Route::get('/administration/users', [UserManagementController::class, 'index'])->middleware('permission:access.manage')->name('administration.users.index');
    Route::get('/administration/users/create', [UserManagementController::class, 'create'])->middleware('permission:access.manage')->name('administration.users.create');
    Route::post('/administration/users', [UserManagementController::class, 'store'])->middleware('permission:access.manage')->name('administration.users.store');
    Route::get('/administration/users/{user}/edit', [UserManagementController::class, 'edit'])->middleware('permission:access.manage')->name('administration.users.edit');
    Route::patch('/administration/users/{user}', [UserManagementController::class, 'update'])->middleware('permission:access.manage')->name('administration.users.update');
    Route::patch('/administration/users/{user}/password', [UserManagementController::class, 'resetPassword'])->middleware('permission:access.manage')->name('administration.users.password');
    Route::get('/administration/departments', [DepartmentManagementController::class, 'index'])->middleware('permission:access.manage')->name('administration.departments.index');
    Route::get('/administration/departments/create', [DepartmentManagementController::class, 'create'])->middleware('permission:access.manage')->name('administration.departments.create');
    Route::post('/administration/departments', [DepartmentManagementController::class, 'store'])->middleware('permission:access.manage')->name('administration.departments.store');
    Route::get('/administration/departments/{department}/edit', [DepartmentManagementController::class, 'edit'])->middleware('permission:access.manage')->name('administration.departments.edit');
    Route::patch('/administration/departments/{department}', [DepartmentManagementController::class, 'update'])->middleware('permission:access.manage')->name('administration.departments.update');

    Route::get('/maintenance', [MaintenanceCaseController::class, 'index'])->name('maintenance.index');
    Route::get('/maintenance/report', [MaintenanceCaseController::class, 'create'])->middleware('permission:incidents.create')->name('maintenance.create');
    Route::post('/maintenance', [MaintenanceCaseController::class, 'store'])->middleware('permission:incidents.create')->name('maintenance.store');
    Route::get('/maintenance/{case}', [MaintenanceCaseController::class, 'show'])->name('maintenance.show');
    Route::post('/maintenance/{case}/reports', [MaintenanceCaseController::class, 'report'])->middleware('permission:maintenance.manage')->name('maintenance.reports.store');
    Route::post('/maintenance/reports/{report}/review', [MaintenanceCaseController::class, 'review'])->middleware('permission:maintenance.review')->name('maintenance.reviews.store');
    Route::post('/maintenance/spares/{requisition}/decision', [MaintenanceCaseController::class, 'procurement'])->middleware('permission:maintenance.spares.procure')->name('maintenance.spares.decision');
    Route::post('/maintenance/spares/{requisition}/issue', [MaintenanceCaseController::class, 'issue'])->middleware('permission:maintenance.spares.procure')->name('maintenance.spares.issue');
    Route::post('/maintenance/spares/{requisition}/relay', [MaintenanceCaseController::class, 'relay'])->middleware('permission:maintenance.review')->name('maintenance.spares.relay');
    Route::post('/maintenance/spares/{requisition}/receive', [MaintenanceCaseController::class, 'receiveSpare'])->middleware('permission:maintenance.manage')->name('maintenance.spares.receive');
    Route::post('/maintenance/{case}/finalize', [MaintenanceCaseController::class, 'finalize'])->middleware('permission:maintenance.manage')->name('maintenance.finalize');
    Route::post('/maintenance/{case}/return', [MaintenanceCaseController::class, 'confirmReturn'])->middleware('permission:incidents.create')->name('maintenance.return');
    Route::get('/requests', [AssetRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/create', [AssetRequestController::class, 'create'])->middleware('permission:requests.create')->name('requests.create');
    Route::post('/requests', [AssetRequestController::class, 'store'])->middleware('permission:requests.create')->name('requests.store');
    Route::get('/requests/{assetRequest}', [AssetRequestController::class, 'show'])->name('requests.show');
    Route::post('/requests/{assetRequest}/decision', [AssetRequestController::class, 'decide'])->middleware('permission:requests.approve')->name('requests.decision');
    Route::post('/requests/{assetRequest}/allocate', [AssetRequestController::class, 'allocate'])->middleware('permission:requests.allocate')->name('requests.allocate');

    Route::get('/assets', [AssetController::class, 'index'])->middleware('permission:assets.view')->name('assets.index');
    Route::get('/assets/register', [AssetController::class, 'create'])->middleware('permission:assets.create')->name('assets.create');
    Route::get('/assets/scan', [AssetScanController::class, 'index'])->middleware('permission:assets.view')->name('assets.scan');
    Route::post('/assets/scan', [AssetScanController::class, 'lookup'])->middleware('permission:assets.view')->name('assets.scan.lookup');
    Route::post('/assets', [AssetController::class, 'store'])->middleware('permission:assets.create')->name('assets.store');
    Route::get('/assets/{asset}/edit', [AssetController::class, 'edit'])->middleware('permission:assets.update')->name('assets.edit');
    Route::patch('/assets/{asset}', [AssetController::class, 'update'])->middleware('permission:assets.update')->name('assets.update');
    Route::get('/assets/{asset}/label', [AssetLabelController::class, 'show'])->middleware('permission:assets.labels.print')->name('assets.label');
    Route::get('/assets/{asset}/label/qr.svg', [AssetLabelController::class, 'qr'])->middleware('permission:assets.labels.print')->name('assets.label.qr');
    Route::get('/assets/{asset}/label/barcode.svg', [AssetLabelController::class, 'barcode'])->middleware('permission:assets.labels.print')->name('assets.label.barcode');
    Route::get('/assets/{asset}', [AssetController::class, 'show'])->middleware('permission:assets.view')->name('assets.show');
});
