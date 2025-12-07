<?php

use Illuminate\Support\Facades\Route;
use Modules\Assets\app\Http\Controllers\Api\EmployeeAssetController;
use Modules\Assets\app\Http\Controllers\AssetAssignmentController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Http\Middleware\AddonCheckMiddleware;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

Route::group(['middleware' => function ($request, $next) {
  $request->headers->set('addon', ModuleConstants::ASSETS);
  return $next($request);
}], function () {
  Route::middleware([
    'api',
    'auth:api', // Use JWT for API authentication (matches your auth.php config)
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    AddonCheckMiddleware::class,
  ])->group(function () {
    
    // --- Employee Mobile API Routes ---
    Route::prefix('V1/employee/assets')
      ->name('api.v1.employee.assets.')
      ->group(function () {
        // Dashboard and Overview
        Route::get('/dashboard', [EmployeeAssetController::class, 'getDashboard'])->name('dashboard');
        
        // Asset Information
        Route::get('/pending-assignments', [EmployeeAssetController::class, 'getPendingAssignments'])->name('pendingAssignments');
        Route::get('/my-assets', [EmployeeAssetController::class, 'getMyAssets'])->name('myAssets');
        Route::get('/history', [EmployeeAssetController::class, 'getAssignmentHistory'])->name('history');
        
        // Employee Actions
        Route::post('/assignments/{assignment}/respond', [EmployeeAssetController::class, 'respondToAssignment'])->name('respond');
        Route::post('/assignments/{assignment}/request-return', [EmployeeAssetController::class, 'requestReturn'])->name('requestReturn');
        
        // Notifications
        Route::get('/notifications', [EmployeeAssetController::class, 'getNotifications'])->name('notifications');
        Route::post('/notifications/{notificationId}/read', [EmployeeAssetController::class, 'markNotificationAsRead'])->name('markNotificationRead');
      });

    // --- Admin API Routes (for AJAX calls from web interface) ---
    Route::prefix('V1/admin/assets')
      ->name('api.v1.admin.assets.')
      ->middleware('role:admin') // Adjust role middleware as needed
      ->group(function () {
        // Dashboard Data
        Route::get('/dashboard-stats', [AssetAssignmentController::class, 'getDashboardStats'])->name('dashboardStats');
        Route::get('/approval-trends', [AssetAssignmentController::class, 'getApprovalTrends'])->name('approvalTrends');
        Route::get('/recent-activity', [AssetAssignmentController::class, 'getRecentActivity'])->name('recentActivity');
        
        // Assignment Management
        Route::post('/assignments/{assignment}/cancel', [AssetAssignmentController::class, 'cancelAssignment'])->name('cancelAssignment');
        Route::post('/assignments/bulk-actions', [AssetAssignmentController::class, 'bulkActions'])->name('bulkActions');
      });
  });
});


