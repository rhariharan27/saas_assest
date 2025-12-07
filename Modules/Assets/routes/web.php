<?php

use Illuminate\Support\Facades\Route;
use Modules\Assets\app\Http\Controllers\AssetAssignmentController;
use Modules\Assets\app\Http\Controllers\AssetCategoryController;
use Modules\Assets\app\Http\Controllers\AssetController;
use Modules\Assets\app\Http\Controllers\AssetMaintenanceController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Http\Middleware\AddonCheckMiddleware;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['middleware' => function ($request, $next) {
  $request->headers->set('addon', ModuleConstants::ASSETS);
  return $next($request);
}], function () {
  Route::middleware([
    'web',
    'auth',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    AddonCheckMiddleware::class,
  ])->group(function () {
    Route::middleware(['web', 'auth']) // Apply standard web & auth middleware
      ->prefix('asset-categories')      // URL Prefix
      ->name('assetCategories.') // Route Name Prefix (adjust if needed)
      ->group(function () {

        // Display list view page (loads DataTables structure)
        Route::get('/', [AssetCategoryController::class, 'index'])->name('index');

        // Handle DataTables server-side request
        Route::get('/list', [AssetCategoryController::class, 'listAjax'])->name('listAjax');

        // Store a new category (from modal/offcanvas)
        Route::post('/', [AssetCategoryController::class, 'store'])->name('store');

        // Get data for editing a category (for modal/offcanvas)
        Route::get('/{category}/edit', [AssetCategoryController::class, 'edit'])->name('edit');

        // Update an existing category (from modal/offcanvas)
        Route::put('/{category}', [AssetCategoryController::class, 'update'])->name('update');

        // Delete a category
        Route::delete('/{category}', [AssetCategoryController::class, 'destroy'])->name('destroy');

        Route::put('/{category}/toggle-status', [AssetCategoryController::class, 'toggleStatus'])->name('toggleStatus');
      });

    Route::middleware(['web', 'auth'])
      ->prefix('asset-categories')
      ->name('tenant.assetCategories.')
      ->group(function () {
        // ... category routes ...
      });

    // --- Asset Routes ---
    Route::middleware(['web', 'auth'])
      ->prefix('assetsManagement')
      ->name('assets.') // Route Name Prefix
      ->group(function () {
        Route::get('/', [AssetController::class, 'index'])->name('index');
        Route::get('/list', [AssetController::class, 'listAjax'])->name('listAjax'); // DataTables GET source
        Route::post('/', [AssetController::class, 'store'])->name('store'); // Create POST
        Route::get('/{asset}/edit', [AssetController::class, 'edit'])->name('edit'); // Fetch data for edit GET
        Route::put('/{asset}', [AssetController::class, 'update'])->name('update'); // Update PUT
        Route::delete('/{asset}', [AssetController::class, 'destroy'])->name('destroy'); // Delete DELETE


        Route::get('/{asset}', [AssetController::class, 'show'])->name('show');
        Route::get('/{asset}/availability-check', [AssetController::class, 'checkAvailability'])->name('availability-check');

        Route::post('/{asset}/assign', [AssetAssignmentController::class, 'store'])->name('assignStore');
        Route::post('/{asset}/return', [AssetAssignmentController::class, 'returnAsset'])->name('return');

        Route::post('/{asset}/maintenance', [AssetMaintenanceController::class, 'store'])->name('maintenance.store');
      });

    // --- Asset Assignment Approval Workflow Routes ---
    Route::middleware(['web', 'auth'])
      ->prefix('assignments')
      ->name('tenant.assignments.')
      ->group(function () {
        // Dashboard and Overview
        Route::get('/dashboard', [AssetAssignmentController::class, 'approvalDashboard'])->name('dashboard');
        Route::get('/dashboard/data', [AssetAssignmentController::class, 'dashboardData'])->name('dashboardData');
        Route::get('/quick-stats', [AssetAssignmentController::class, 'quickStats'])->name('quickStats');

        // Pending Employee Approvals
        Route::get('/pending-approvals', [AssetAssignmentController::class, 'pendingApprovalsView'])->name('pendingApprovalsView');
        Route::get('/pending-approvals/data', [AssetAssignmentController::class, 'getPendingApprovals'])->name('pendingApproval');
        
        // Pending Return Requests
        Route::get('/pending-returns', [AssetAssignmentController::class, 'pendingReturnsView'])->name('pendingReturnsView');
        Route::get('/pending-returns/data', [AssetAssignmentController::class, 'getPendingReturns'])->name('pendingReturns');
        
        // Assignment Actions
        Route::post('/{assignment}/respond', [AssetAssignmentController::class, 'respondToAssignment'])->name('respond');
        Route::post('/{assignment}/request-return', [AssetAssignmentController::class, 'requestReturn'])->name('requestReturn');
        Route::post('/{assignment}/respond-return', [AssetAssignmentController::class, 'respondToReturn'])->name('respondReturn');
        Route::post('/{assignment}/return', [AssetAssignmentController::class, 'returnAssetEnhanced'])->name('return');
        
        // Assignment Details and Management
        Route::get('/{assignment}/details', [AssetAssignmentController::class, 'assignmentDetails'])->name('details');
        Route::post('/{assignment}/send-reminder', [AssetAssignmentController::class, 'sendReminder'])->name('sendReminder');
        Route::post('/{assignment}/resend-notification', [AssetAssignmentController::class, 'sendReminder'])->name('resendNotification');
        Route::post('/{assignment}/cancel', [AssetAssignmentController::class, 'cancelAssignment'])->name('cancel');
        Route::put('/{assignment}/update', [AssetAssignmentController::class, 'updateAssignment'])->name('update');
        Route::post('/bulk-remind', [AssetAssignmentController::class, 'bulkSendReminders'])->name('bulkRemind');
        Route::post('/bulk-approve-returns', [AssetAssignmentController::class, 'bulkApproveReturns'])->name('bulkApproveReturns');
        
        // Export and Reporting
        Route::get('/export/pending-approvals', [AssetAssignmentController::class, 'exportPendingApprovals'])->name('exportPendingApprovals');
        Route::get('/export/pending-returns', [AssetAssignmentController::class, 'exportPendingReturns'])->name('exportPendingReturns');
      });

    // --- Menu-compatible routes for Asset Assignment Approval Workflow ---
    Route::middleware(['web', 'auth'])
      ->name('assets.assignment.')
      ->group(function () {
        // Dashboard route matching menu URL
        Route::get('/asset-assignment-dashboard', [AssetAssignmentController::class, 'approvalDashboard'])->name('dashboard');
        
        // Pending approvals route matching menu URL
        Route::get('/pending-approvals', [AssetAssignmentController::class, 'pendingApprovalsView'])->name('pending');
        
        // Pending returns route matching menu URL
        Route::get('/pending-returns', [AssetAssignmentController::class, 'pendingReturnsView'])->name('returns');
      });
  });
});
