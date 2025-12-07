<?php

namespace Modules\Assets\app\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Exception;
use Modules\Assets\app\Enums\ApprovalStatus;
use Modules\Assets\app\Enums\AssetStatus;
use Modules\Assets\app\Models\Asset;
use Modules\Assets\app\Models\AssetActivity;
use Modules\Assets\app\Models\AssetAssignment;

/**
 * Mobile API Controller for Employee Asset Management
 * Provides endpoints specifically designed for mobile app consumption
 */
class EmployeeAssetController extends Controller
{
  /**
   * Get employee's pending asset assignments (awaiting approval).
   * Route: GET /api/employee/assets/pending-assignments
   * 
   * @return JsonResponse
   */
  public function getPendingAssignments(): JsonResponse
  {
    try {
      $userId = Auth::id();
      
      $assignments = AssetAssignment::with(['asset.category', 'assignedBy'])
        ->where('user_id', $userId)
        ->pendingEmployeeApproval()
        ->orderBy('assigned_at', 'desc')
        ->get();

      return response()->json([
        'success' => true,
        'message' => 'Pending assignments retrieved successfully',
        'data' => [
          'count' => $assignments->count(),
          'assignments' => $assignments->map(function ($assignment) {
            return [
              'id' => $assignment->id,
              'asset' => [
                'id' => $assignment->asset->id,
                'name' => $assignment->asset->name,
                'asset_tag' => $assignment->asset->asset_tag,
                'description' => $assignment->asset->description,
                'category' => $assignment->asset->category->name ?? 'Uncategorized',
                'image_url' => $assignment->asset->image_url,
                'model' => $assignment->asset->model,
                'serial_number' => $assignment->asset->serial_number,
              ],
              'assigned_by' => [
                'id' => $assignment->assignedBy->id,
                'name' => $assignment->assignedBy->getFullName(),
              ],
              'assigned_at' => $assignment->assigned_at->format('Y-m-d H:i:s'),
              'formatted_assigned_at' => $assignment->assigned_at->format('M d, Y'),
              'expected_return_date' => $assignment->expected_return_date?->format('Y-m-d'),
              'formatted_expected_return_date' => $assignment->expected_return_date?->format('M d, Y'),
              'notes' => $assignment->notes,
              'condition_out' => $assignment->condition_out?->value,
              'condition_out_label' => $assignment->condition_out?->label(),
              'days_pending' => $assignment->getDaysPendingApproval(),
              'is_overdue' => $assignment->isOverdue(),
            ];
          }),
        ],
      ]);

    } catch (Exception $e) {
      Log::error("Error fetching pending assignments for user " . Auth::id() . ": " . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Failed to retrieve pending assignments',
        'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
      ], 500);
    }
  }

  /**
   * Get employee's active assets (approved assignments).
   * Route: GET /api/employee/assets/my-assets
   * 
   * @return JsonResponse
   */
  public function getMyAssets(): JsonResponse
  {
    try {
      $userId = Auth::id();
      
      $assignments = AssetAssignment::with(['asset.category'])
        ->where('user_id', $userId)
        ->approvedByEmployee()
        ->active()
        ->orderBy('assigned_at', 'desc')
        ->get();

      return response()->json([
        'success' => true,
        'message' => 'Active assets retrieved successfully',
        'data' => [
          'count' => $assignments->count(),
          'assets' => $assignments->map(function ($assignment) {
            return [
              'assignment_id' => $assignment->id,
              'asset' => [
                'id' => $assignment->asset->id,
                'name' => $assignment->asset->name,
                'asset_tag' => $assignment->asset->asset_tag,
                'description' => $assignment->asset->description,
                'category' => $assignment->asset->category->name ?? 'Uncategorized',
                'image_url' => $assignment->asset->image_url,
                'model' => $assignment->asset->model,
                'serial_number' => $assignment->asset->serial_number,
                'location' => $assignment->asset->location,
              ],
              'assigned_at' => $assignment->assigned_at->format('Y-m-d H:i:s'),
              'formatted_assigned_at' => $assignment->assigned_at->format('M d, Y'),
              'expected_return_date' => $assignment->expected_return_date?->format('Y-m-d'),
              'formatted_expected_return_date' => $assignment->expected_return_date?->format('M d, Y'),
              'days_with_asset' => $assignment->getDaysWithAsset(),
              'condition_out' => $assignment->condition_out?->value,
              'condition_out_label' => $assignment->condition_out?->label(),
              'notes' => $assignment->notes,
              'can_request_return' => $assignment->canRequestReturn(),
              'return_requested' => $assignment->hasReturnRequest(),
              'return_status' => $assignment->return_approval_status?->value,
              'return_status_label' => $assignment->return_approval_status?->label(),
              'return_requested_at' => $assignment->return_requested_at?->format('Y-m-d H:i:s'),
              'return_request_notes' => $assignment->return_request_notes,
              'return_rejected_at' => $assignment->return_rejected_at?->format('Y-m-d H:i:s'),
              'return_rejection_notes' => $assignment->return_rejection_notes,
            ];
          }),
        ],
      ]);

    } catch (Exception $e) {
      Log::error("Error fetching active assets for user " . Auth::id() . ": " . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Failed to retrieve active assets',
        'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
      ], 500);
    }
  }

  /**
   * Get employee's asset assignment history.
   * Route: GET /api/employee/assets/history
   * 
   * @param Request $request
   * @return JsonResponse
   */
  public function getAssignmentHistory(Request $request): JsonResponse
  {
    try {
      $userId = Auth::id();
      $page = $request->get('page', 1);
      $perPage = min($request->get('per_page', 10), 50); // Max 50 per page
      
      $query = AssetAssignment::with(['asset.category'])
        ->where('user_id', $userId)
        ->orderBy('assigned_at', 'desc');

      // Filter by status if provided
      if ($request->has('status') && in_array($request->status, ['approved', 'rejected', 'returned'])) {
        switch ($request->status) {
          case 'approved':
            $query->approvedByEmployee();
            break;
          case 'rejected':
            $query->rejectedByEmployee();
            break;
          case 'returned':
            $query->whereNotNull('returned_at');
            break;
        }
      }

      $assignments = $query->paginate($perPage, ['*'], 'page', $page);

      return response()->json([
        'success' => true,
        'message' => 'Assignment history retrieved successfully',
        'data' => [
          'current_page' => $assignments->currentPage(),
          'per_page' => $assignments->perPage(),
          'total' => $assignments->total(),
          'last_page' => $assignments->lastPage(),
          'assignments' => $assignments->items()->map(function ($assignment) {
            return [
              'id' => $assignment->id,
              'asset' => [
                'id' => $assignment->asset->id,
                'name' => $assignment->asset->name,
                'asset_tag' => $assignment->asset->asset_tag,
                'category' => $assignment->asset->category->name ?? 'Uncategorized',
                'image_url' => $assignment->asset->image_url,
              ],
              'assigned_at' => $assignment->assigned_at->format('Y-m-d H:i:s'),
              'formatted_assigned_at' => $assignment->assigned_at->format('M d, Y'),
              'returned_at' => $assignment->returned_at?->format('Y-m-d H:i:s'),
              'formatted_returned_at' => $assignment->returned_at?->format('M d, Y'),
              'employee_approval_status' => $assignment->employee_approval_status->value,
              'employee_approval_status_label' => $assignment->employee_approval_status->label(),
              'employee_responded_at' => $assignment->employee_responded_at?->format('Y-m-d H:i:s'),
              'days_with_asset' => $assignment->getDaysWithAsset(),
              'return_requested' => $assignment->hasReturnRequest(),
              'return_approval_status' => $assignment->return_approval_status?->value,
              'return_approval_status_label' => $assignment->return_approval_status?->label(),
            ];
          }),
        ],
      ]);

    } catch (Exception $e) {
      Log::error("Error fetching assignment history for user " . Auth::id() . ": " . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Failed to retrieve assignment history',
        'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
      ], 500);
    }
  }

  /**
   * Employee responds to asset assignment (approve/reject).
   * Route: POST /api/employee/assets/assignments/{assignment}/respond
   * 
   * @param Request $request
   * @param AssetAssignment $assignment
   * @return JsonResponse
   */
  public function respondToAssignment(Request $request, AssetAssignment $assignment): JsonResponse
  {
    // Ensure the assignment belongs to the authenticated user
    if ($assignment->user_id !== Auth::id()) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthorized to respond to this assignment'
      ], 403);
    }

    // Check if employee can still respond
    if (!$assignment->canEmployeeRespond()) {
      return response()->json([
        'success' => false,
        'message' => 'This assignment cannot be responded to. It may have already been responded to or the asset has been returned.'
      ], 409);
    }

    // Validation
    $validator = Validator::make($request->all(), [
      'response' => ['required', 'string', Rule::in(['approve', 'reject'])],
      'notes' => 'nullable|string|max:1000',
    ], [
      'response.required' => 'Please specify whether you approve or reject this assignment',
      'response.in' => 'Response must be either approve or reject',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Invalid input provided',
        'errors' => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();
    $response = $validated['response'];
    $notes = $validated['notes'];

    DB::beginTransaction();
    try {
      $approvalStatus = $response === 'approve' ? ApprovalStatus::APPROVED : ApprovalStatus::REJECTED;
      
      // Update assignment
      $assignment->update([
        'employee_approval_status' => $approvalStatus,
        'employee_approval_notes' => $notes,
        'employee_responded_at' => now(),
      ]);

      $asset = $assignment->asset;
      $user = $assignment->user;

      if ($response === 'approve') {
        // Update asset status to ASSIGNED when approved
        $asset->update([
          'status' => AssetStatus::ASSIGNED,
          'location' => 'Assigned to ' . $user->getFullName(),
        ]);

        $this->logAssetActivity(
          $asset,
          'assignment_approved',
          "Assignment approved by {$user->getFullName()} via mobile app. Asset now assigned.",
          $user->id,
          $assignment
        );

        $message = 'Assignment approved successfully! The asset is now assigned to you.';
      } else {
        // Keep asset as AVAILABLE when rejected
        $this->logAssetActivity(
          $asset,
          'assignment_rejected',
          "Assignment rejected by {$user->getFullName()} via mobile app. Reason: {$notes}",
          $user->id,
          $assignment
        );

        $message = 'Assignment rejected successfully. The asset will remain available for reassignment.';
      }

      DB::commit();

      return response()->json([
        'success' => true,
        'message' => $message,
        'data' => [
          'assignment_id' => $assignment->id,
          'status' => $approvalStatus->value,
          'status_label' => $approvalStatus->label(),
          'responded_at' => $assignment->employee_responded_at->format('Y-m-d H:i:s'),
        ]
      ]);

    } catch (Exception $e) {
      DB::rollBack();
      Log::error("Error responding to assignment {$assignment->id} by user " . Auth::id() . ": " . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Failed to process your response. Please try again.',
        'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
      ], 500);
    }
  }

  /**
   * Employee requests to return an asset.
   * Route: POST /api/employee/assets/assignments/{assignment}/request-return
   * 
   * @param Request $request
   * @param AssetAssignment $assignment
   * @return JsonResponse
   */
  public function requestReturn(Request $request, AssetAssignment $assignment): JsonResponse
  {
    // Ensure the assignment belongs to the authenticated user
    if ($assignment->user_id !== Auth::id()) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthorized to request return for this assignment'
      ], 403);
    }

    // Check if employee can request return
    if (!$assignment->canRequestReturn()) {
      $message = 'Cannot request return for this assignment.';
      
      if ($assignment->employee_approval_status !== ApprovalStatus::APPROVED) {
        $message = 'The assignment must be approved before you can request a return.';
      } elseif ($assignment->return_requested && $assignment->return_approval_status === ApprovalStatus::PENDING) {
        $message = 'You already have a pending return request for this assignment.';
      } elseif (!is_null($assignment->returned_at)) {
        $message = 'This asset has already been returned.';
      }
      
      return response()->json([
        'success' => false,
        'message' => $message
      ], 409);
    }

    // Validation
    $validator = Validator::make($request->all(), [
      'reason' => 'required|string|min:10|max:1000',
    ], [
      'reason.required' => 'Please provide a reason for returning this asset',
      'reason.min' => 'Reason must be at least 10 characters',
      'reason.max' => 'Reason cannot exceed 1000 characters',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Invalid input provided',
        'errors' => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();

    DB::beginTransaction();
    try {
      $user = $assignment->user;
      $isReRequest = $assignment->return_requested && $assignment->return_approval_status === ApprovalStatus::REJECTED;
      
      // Update assignment with return request
      $assignment->update([
        'return_requested' => true,
        'return_requested_at' => now(),
        'return_request_notes' => $validated['reason'],
        'return_approval_status' => ApprovalStatus::PENDING,
        // Clear previous rejection details if this is a re-request
        'return_rejected_at' => null,
        'return_rejection_notes' => null,
      ]);

      $actionType = $isReRequest ? 'return_re_requested' : 'return_requested';
      $actionMessage = $isReRequest 
        ? "Return re-requested by {$user->getFullName()} via mobile app after previous rejection. New reason: {$validated['reason']}"
        : "Return requested by {$user->getFullName()} via mobile app. Reason: {$validated['reason']}";
      
      $this->logAssetActivity(
        $assignment->asset,
        $actionType,
        $actionMessage,
        $user->id,
        $assignment
      );

      DB::commit();

      $responseMessage = $isReRequest 
        ? 'Return request resubmitted successfully! You will be notified once an admin reviews your new request.'
        : 'Return request submitted successfully! You will be notified once an admin reviews your request.';

      return response()->json([
        'success' => true,
        'message' => $responseMessage,
        'data' => [
          'assignment_id' => $assignment->id,
          'return_requested_at' => $assignment->return_requested_at->format('Y-m-d H:i:s'),
          'return_approval_status' => ApprovalStatus::PENDING->value,
          'return_approval_status_label' => ApprovalStatus::PENDING->label(),
          'is_re_request' => $isReRequest,
        ]
      ]);

    } catch (Exception $e) {
      DB::rollBack();
      Log::error("Error requesting return for assignment {$assignment->id} by user " . Auth::id() . ": " . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Failed to submit return request. Please try again.',
        'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
      ], 500);
    }
  }

  /**
   * Get employee's notifications related to asset assignments.
   * Route: GET /api/employee/assets/notifications
   * 
   * @param Request $request
   * @return JsonResponse
   */
  public function getNotifications(Request $request): JsonResponse
  {
    try {
      $userId = Auth::id();
      $page = $request->get('page', 1);
      $perPage = min($request->get('per_page', 20), 50);
      
      // Get notifications from the notifications table
      // Assuming you have a notifications table with user_id, type, data, read_at columns
      $query = DB::table('notifications')
        ->where('notifiable_id', $userId)
        ->where('notifiable_type', User::class)
        ->whereJsonContains('data->module', 'assets')
        ->orderBy('created_at', 'desc');

      $notifications = $query->paginate($perPage, ['*'], 'page', $page);

      return response()->json([
        'success' => true,
        'message' => 'Notifications retrieved successfully',
        'data' => [
          'current_page' => $notifications->currentPage(),
          'per_page' => $notifications->perPage(),
          'total' => $notifications->total(),
          'unread_count' => DB::table('notifications')
            ->where('notifiable_id', $userId)
            ->where('notifiable_type', User::class)
            ->whereJsonContains('data->module', 'assets')
            ->whereNull('read_at')
            ->count(),
          'notifications' => collect($notifications->items())->map(function ($notification) {
            $data = json_decode($notification->data, true);
            return [
              'id' => $notification->id,
              'type' => $notification->type,
              'title' => $data['title'] ?? 'Asset Notification',
              'message' => $data['message'] ?? '',
              'asset_id' => $data['asset_id'] ?? null,
              'assignment_id' => $data['assignment_id'] ?? null,
              'is_read' => !is_null($notification->read_at),
              'created_at' => $notification->created_at,
              'read_at' => $notification->read_at,
            ];
          }),
        ],
      ]);

    } catch (Exception $e) {
      Log::error("Error fetching notifications for user " . Auth::id() . ": " . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Failed to retrieve notifications',
        'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
      ], 500);
    }
  }

  /**
   * Mark notification as read.
   * Route: POST /api/employee/assets/notifications/{notificationId}/read
   * 
   * @param string $notificationId
   * @return JsonResponse
   */
  public function markNotificationAsRead(string $notificationId): JsonResponse
  {
    try {
      $userId = Auth::id();
      
      $updated = DB::table('notifications')
        ->where('id', $notificationId)
        ->where('notifiable_id', $userId)
        ->where('notifiable_type', User::class)
        ->whereNull('read_at')
        ->update(['read_at' => now()]);

      if (!$updated) {
        return response()->json([
          'success' => false,
          'message' => 'Notification not found or already read'
        ], 404);
      }

      return response()->json([
        'success' => true,
        'message' => 'Notification marked as read',
        'data' => [
          'notification_id' => $notificationId,
          'read_at' => now()->format('Y-m-d H:i:s'),
        ]
      ]);

    } catch (Exception $e) {
      Log::error("Error marking notification {$notificationId} as read for user " . Auth::id() . ": " . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Failed to mark notification as read',
        'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
      ], 500);
    }
  }

  /**
   * Get dashboard summary for employee.
   * Route: GET /api/employee/assets/dashboard
   * 
   * @return JsonResponse
   */
  public function getDashboard(): JsonResponse
  {
    try {
      $userId = Auth::id();
      
      // Get counts
      $pendingAssignments = AssetAssignment::where('user_id', $userId)
        ->pendingEmployeeApproval()
        ->count();

      $activeAssets = AssetAssignment::where('user_id', $userId)
        ->approvedByEmployee()
        ->active()
        ->count();

      $pendingReturns = AssetAssignment::where('user_id', $userId)
        ->pendingReturnApproval()
        ->count();

      $totalAssignments = AssetAssignment::where('user_id', $userId)->count();

      // Get recent activity
      $recentAssignments = AssetAssignment::with(['asset'])
        ->where('user_id', $userId)
        ->orderBy('assigned_at', 'desc')
        ->limit(5)
        ->get();

      // Get overdue assignments
      $overdueAssignments = AssetAssignment::where('user_id', $userId)
        ->overdue()
        ->count();

      return response()->json([
        'success' => true,
        'message' => 'Dashboard data retrieved successfully',
        'data' => [
          'summary' => [
            'pending_assignments' => $pendingAssignments,
            'active_assets' => $activeAssets,
            'pending_returns' => $pendingReturns,
            'total_assignments' => $totalAssignments,
            'overdue_assignments' => $overdueAssignments,
          ],
          'recent_assignments' => $recentAssignments->map(function ($assignment) {
            return [
              'id' => $assignment->id,
              'asset_name' => $assignment->asset->name,
              'asset_tag' => $assignment->asset->asset_tag,
              'status' => $assignment->employee_approval_status->value,
              'status_label' => $assignment->employee_approval_status->label(),
              'assigned_at' => $assignment->assigned_at->format('M d, Y'),
              'days_pending' => $assignment->getDaysPendingApproval(),
            ];
          }),
          'quick_actions' => [
            'has_pending_assignments' => $pendingAssignments > 0,
            'has_pending_returns' => $pendingReturns > 0,
            'has_overdue_assignments' => $overdueAssignments > 0,
          ],
        ],
      ]);

    } catch (Exception $e) {
      Log::error("Error fetching dashboard for user " . Auth::id() . ": " . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Failed to retrieve dashboard data',
        'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
      ], 500);
    }
  }

  /**
   * Helper method to log asset activity.
   *
   * @param Asset $asset
   * @param string $action
   * @param string|null $details
   * @param int|null $relatedUserId
   * @param Model|null $relatedModel
   */
  private function logAssetActivity(
    Asset $asset,
    string $action,
    ?string $details = null,
    ?int $relatedUserId = null,
    ?object $relatedModel = null
  ): void {
    try {
      $actorUserId = Auth::id();
      if (!$actorUserId) {
        Log::warning("Attempted to log asset activity for Asset ID {$asset->id} without an authenticated user.");
        return;
      }

      AssetActivity::create([
        'asset_id' => $asset->id,
        'user_id' => $actorUserId,
        'related_user_id' => $relatedUserId,
        'related_model_type' => $relatedModel ? get_class($relatedModel) : null,
        'related_model_id' => $relatedModel?->id,
        'action' => $action,
        'details' => $details,
        'tenant_id' => $asset->tenant_id,
      ]);
    } catch (Exception $e) {
      Log::error("Failed to log activity for Asset ID {$asset->id}: " . $e->getMessage());
    }
  }
}