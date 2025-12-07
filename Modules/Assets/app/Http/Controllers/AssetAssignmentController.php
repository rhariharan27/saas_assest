<?php

namespace Modules\Assets\app\Http\Controllers; // Default nwidart namespace

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum as EnumRule;
use Modules\Assets\app\Enums\ApprovalStatus;
use Modules\Assets\app\Enums\AssetCondition;
use Modules\Assets\app\Enums\AssetStatus;
use Modules\Assets\app\Models\Asset;
use Modules\Assets\app\Models\AssetActivity;
use Modules\Assets\app\Models\AssetAssignment;

class AssetAssignmentController extends Controller
{
    /**
     * Store a new asset assignment record.
     * Assigns an asset to a user and updates the asset status.
     * Route: POST /assets/{asset}/assign
     * Name: tenant.assets.assignStore
     *
     * @param  Asset  $asset  The asset to be assigned (Route Model Binding)
     */
    public function store(Request $request, Asset $asset): JsonResponse
    {
        // --- Authorization Check ---
        // if (!Auth::user()->can('assign_assets')) {
        //     return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
        // }

        // --- First check - Asset status validation ---
        if ($asset->status !== AssetStatus::AVAILABLE) {
            return response()->json([
                'success' => false,
                'message' => 'Asset is not available for assignment (Current status: '.$asset->status->label().').',
                'error_code' => 'ASSET_NOT_AVAILABLE',
            ], 409);
        }

        // --- Second check - Active assignment validation (excluding rejected ones) ---
        if ($asset->hasActiveAssignment()) {
            $currentAssignment = $asset->getCurrentAssignment();
            $assignedTo = $currentAssignment ? $currentAssignment->user->getFullName() : 'Unknown User';

            return response()->json([
                'success' => false,
                'message' => "Asset is currently assigned to {$assignedTo}. Status: {$currentAssignment->employee_approval_status->label()}",
                'error_code' => 'ASSET_ALREADY_ASSIGNED',
                'current_assignment' => [
                    'assigned_to' => $assignedTo,
                    'assigned_date' => $currentAssignment?->assigned_at?->format('M d, Y'),
                    'status' => $currentAssignment?->employee_approval_status?->label(),
                    'assignment_id' => $currentAssignment?->id,
                ],
            ], 409);
        }

        // --- Third check - User already has pending assignment for this asset ---
        $existingPendingAssignment = AssetAssignment::where('asset_id', $asset->id)
            ->where('user_id', $request->user_id)
            ->where('employee_approval_status', ApprovalStatus::PENDING)
            ->whereNull('returned_at')
            ->first();

        if ($existingPendingAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'This user already has a pending assignment for this asset.',
                'error_code' => 'DUPLICATE_PENDING_ASSIGNMENT',
                'existing_assignment_id' => $existingPendingAssignment->id,
            ], 409);
        }

        // --- Fourth check - Prevent immediate reassignment to user who just rejected ---
        $recentRejection = AssetAssignment::where('asset_id', $asset->id)
            ->where('user_id', $request->user_id)
            ->where('employee_approval_status', ApprovalStatus::REJECTED)
            ->whereNull('returned_at')
            ->where('employee_responded_at', '>', now()->subHours(24)) // Within last 24 hours
            ->first();

        if ($recentRejection) {
            return response()->json([
                'success' => false,
                'message' => 'This user recently rejected this asset assignment. Please wait 24 hours before reassigning or choose a different employee.',
                'error_code' => 'RECENT_REJECTION',
                'rejected_at' => $recentRejection->employee_responded_at->format('M d, Y H:i'),
                'rejection_reason' => $recentRejection->employee_approval_notes,
            ], 409);
        }

        // --- Validation ---
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where(function ($query) {
                $query->where('tenant_id', Auth::user()->tenant_id);
            })],
            'assigned_at' => 'required|date_format:Y-m-d',
            'expected_return_date' => 'nullable|date_format:Y-m-d|after_or_equal:assigned_at',
            'condition_out' => ['nullable', new EnumRule(AssetCondition::class)],
            'notes' => 'nullable|string|max:2000',
        ], [
            'user_id.exists' => 'The selected employee is invalid or not active.',
    ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // --- Database Transaction ---
        DB::beginTransaction();
        try {
            $assignerId = Auth::id();
            $assignee = User::find($validated['user_id']);

            // Create assignment with pending approval status
            $assignment = AssetAssignment::create([
                'asset_id' => $asset->id,
                'user_id' => $validated['user_id'],
                'assigned_at' => $validated['assigned_at'],
                'expected_return_date' => $validated['expected_return_date'] ?? null,
                'condition_out' => $validated['condition_out'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'assigned_by_id' => $assignerId,
                'employee_approval_status' => ApprovalStatus::PENDING,
                'tenant_id' => Auth::user()->tenant_id,
            ]);

            // Update asset status - Keep as ASSIGNED with pending note
            $asset->update([
                'status' => AssetStatus::ASSIGNED,
                'location' => 'Assigned to '.$assignee->getFullName().' (Pending Approval)',
            ]);

            // Check if this asset was previously rejected and add note
            $rejectionNote = '';
            if ($asset->hasRejectedAssignments()) {
                $lastRejection = $asset->getLastRejectedAssignment();
                $rejectionNote = " (Previously rejected by {$lastRejection->user->getFullName()} on {$lastRejection->employee_responded_at->format('M d, Y')})";
            }

            // Log activity
            $this->logAssetActivity(
                $asset,
                'assigned_pending_approval',
                "Assigned to {$assignee->getFullName()} (User ID: {$assignee->id}) - Awaiting employee approval{$rejectionNote}",
                $assignee->id,
                $assignment
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Asset assigned successfully. Employee will be notified for approval.',
                'data' => [
                    'assignment_id' => $assignment->id,
                    'status' => 'pending_approval',
                    'previously_rejected' => $asset->hasRejectedAssignments(),
                ],
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error assigning asset {$asset->id}: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign asset.',
            ], 500);
        }
    }

    /**
     * Mark an assigned asset as returned.
     * Finds the current open assignment and updates it. Updates asset status.
     * Route: POST /assets/{asset}/return
     * Name: tenant.assets.return
     *
     * @param  Asset  $asset  The asset being returned
     */
    public function returnAsset(Request $request, Asset $asset): JsonResponse
    {
        // --- Authorization Check ---
        // if (!Auth::user()->can('return_assets')) {
        //     return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
        // }

        // --- Pre-condition Check ---
        if ($asset->status !== AssetStatus::ASSIGNED) {
            return response()->json(['success' => false, 'message' => 'Asset is not currently assigned.'], 409); // Conflict
        }

        // Find the current open assignment for this asset
        $currentAssignment = $asset->currentAssignment()->first(); // Use the relationship

        if (! $currentAssignment) {
            // This indicates a data inconsistency (status is ASSIGNED but no open assignment found)
            Log::error("Data inconsistency: Asset ID {$asset->id} has status ASSIGNED but no open assignment record found.");

            // Force status back maybe? Or return error.
            // $asset->status = AssetStatus::AVAILABLE;
            // $asset->save();
            return response()->json(['success' => false, 'message' => 'Could not find the current assignment record for this asset. Please check asset status.'], 404);
        }

        // --- Validation ---
        $validator = Validator::make($request->all(), [
            'returned_at' => 'required|date_format:Y-m-d', // Or Y-m-d H:i:s if time matters
            'condition_in' => ['required', new EnumRule(AssetCondition::class)], // Condition upon return is required
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        // --- Database Transaction ---
        DB::beginTransaction();
        try {

            // New
            $validated = $validator->validated();
            $receiverId = Auth::id();
            $returnCondition = AssetCondition::from($validated['condition_in']);
            $returneeId = $currentAssignment->user_id; // Get ID before potentially detaching user
            $returnee = $currentAssignment->user; // Get user model for logging

            // 1. Update Assignment Record
            $currentAssignment->update([
                'returned_at' => $validated['returned_at'],
                'condition_in' => $returnCondition,
                'notes' => ($currentAssignment->notes ? $currentAssignment->notes."\n---\nRETURN:\n" : "RETURN:\n").($validated['notes'] ?? 'Returned.'),
                'received_by_id' => $receiverId,
            ]);

            // 2. Update Asset Status
            $newAssetStatus = match ($returnCondition) {
                AssetCondition::GOOD, AssetCondition::FAIR, AssetCondition::NEW => AssetStatus::AVAILABLE, // Becomes available
                AssetCondition::POOR, AssetCondition::BROKEN => AssetStatus::IN_REPAIR, // Needs check/repair
                default => AssetStatus::AVAILABLE, // Default fallback
            };
            $asset->status = $newAssetStatus;
            $asset->location = 'Returned - '.$newAssetStatus->label();
            $asset->save();

            // --- Log Activity ---
            $this->logAssetActivity(
                $asset,
                'returned',
                "Returned by {$returnee?->getFullName()} (User ID: {$returneeId}). Condition: {$returnCondition->value}. New Status: {$newAssetStatus->value}.",
                $returneeId, // relatedUserId is the person who returned it
                $currentAssignment // relatedModel is the assignment record
            );
            // --- End Log Activity ---
            // TODO: Send Notification to relevant parties? (e.g., IT if status is IN_REPAIR)

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Asset returned successfully.']);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error returning asset {$asset->id} (Assignment ID {$currentAssignment->id}): ".$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to process asset return.'], 500);
        }
    }

    /**
     * Helper method to log asset activity.
     *
     * @param  Asset  $asset  The asset the activity relates to.
     * @param  string  $action  The action performed (e.g., 'created', 'assigned').
     * @param  string|null  $details  Optional descriptive details.
     * @param  int|null  $relatedUserId  Optional ID of the user involved (e.g., assignee).
     * @param  Model|null  $relatedModel  Optional related model instance (e.g., AssetAssignment).
     */
    private function logAssetActivity(
        Asset $asset,
        string $action,
        ?string $details = null,
        ?int $relatedUserId = null,
        ?Model $relatedModel = null
    ): void {
        try {
            // Ensure we have an authenticated user performing the action
            $actorUserId = Auth::id();
            if (! $actorUserId) {
                Log::warning("Attempted to log asset activity for Asset ID {$asset->id} without an authenticated user.");

                // Decide if you want to log with null user_id or skip logging
                // $actorUserId = null; // Option: Log as system action
                return; // Option: Skip logging if no actor
            }

            AssetActivity::create([
                'asset_id' => $asset->id,
                'user_id' => $actorUserId, // The admin/user doing the action
                'related_user_id' => $relatedUserId, // e.g., The employee assigned/returning
                'related_model_type' => $relatedModel ? get_class($relatedModel) : null,
                'related_model_id' => $relatedModel?->id,
                'action' => $action,
                'details' => $details,
                'tenant_id' => $asset->tenant_id, // Get tenant from the asset itself
            ]);
        } catch (Exception $e) {
            Log::error("Failed to log activity for Asset ID {$asset->id}: ".$e->getMessage());
            // Do not re-throw, logging failure shouldn't stop the main action
        }
    }

    /**
     * Employee responds to asset assignment (approve/reject).
     * Route: POST /assignments/{assignment}/respond
     * Name: tenant.assignments.respond
     */
    public function respondToAssignment(Request $request, AssetAssignment $assignment): JsonResponse
    {
        // Ensure the assignment belongs to the authenticated user
        if ($assignment->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to respond to this assignment.'], 403);
        }

        // Check if employee can still respond
        if (! $assignment->canEmployeeRespond()) {
            return response()->json(['success' => false, 'message' => 'This assignment cannot be responded to.'], 409);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'response' => ['required', 'string', Rule::in(['approve', 'reject'])],
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
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
                    'location' => 'Assigned to '.$user->getFullName().' (Approved)',
                ]);

                $this->logAssetActivity(
                    $asset,
                    'assignment_approved',
                    "Assignment approved by {$user->getFullName()}. Asset now assigned.",
                    $user->id,
                    $assignment
                );

                $message = 'Assignment approved successfully. The asset is now assigned to you.';
            } else {
                // Update asset status to AVAILABLE when rejected
                $asset->update([
                    'status' => AssetStatus::AVAILABLE,
                    'location' => 'Available - Assignment rejected by '.$user->getFullName(),
                ]);

                $this->logAssetActivity(
                    $asset,
                    'assignment_rejected',
                    "Assignment rejected by {$user->getFullName()}. Reason: {$notes}. Asset is now available for reassignment.",
                    $user->id,
                    $assignment
                );

                $message = 'Assignment rejected successfully. The asset is now available for reassignment to other employees.';
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'assignment_status' => $approvalStatus->value,
                    'asset_status' => $asset->fresh()->status->value,
                ],
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error responding to assignment {$assignment->id}: ".$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to process response.'], 500);
        }
    }

    /**
     * Employee requests to return an asset.
     * Route: POST /assignments/{assignment}/request-return
     * Name: tenant.assignments.requestReturn
     */
    public function requestReturn(Request $request, AssetAssignment $assignment): JsonResponse
    {
        // Ensure the assignment belongs to the authenticated user
        if ($assignment->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to request return for this assignment.'], 403);
        }

        // Check if employee can request return
        if (! $assignment->canRequestReturn()) {
            return response()->json(['success' => false, 'message' => 'Cannot request return for this assignment.'], 409);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        DB::beginTransaction();
        try {
            // Update assignment with return request
            $assignment->update([
                'return_requested' => true,
                'return_requested_at' => now(),
                'return_request_notes' => $validated['reason'],
                'return_approval_status' => ApprovalStatus::PENDING,
            ]);

            $user = $assignment->user;
            $this->logAssetActivity(
                $assignment->asset,
                'return_requested',
                "Return requested by {$user->getFullName()}. Reason: {$validated['reason']}",
                $user->id,
                $assignment
            );

            DB::commit();

            // TODO: Send notification to admin for return approval

            return response()->json(['success' => true, 'message' => 'Return request submitted successfully.']);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error requesting return for assignment {$assignment->id}: ".$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to submit return request.'], 500);
        }
    }

    /**
     * Admin responds to employee return request (approve/reject).
     * Route: POST /assignments/{assignment}/respond-return
     * Name: tenant.assignments.respondReturn
     */
    public function respondToReturn(Request $request, AssetAssignment $assignment): JsonResponse
    {
        // Check if admin can respond to return request
        if (! $assignment->canAdminRespondToReturn()) {
            return response()->json(['success' => false, 'message' => 'No pending return request for this assignment.'], 409);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'response' => ['required', 'string', Rule::in(['approve', 'reject'])],
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $response = $validated['response'];
        $notes = $validated['notes'];

        DB::beginTransaction();
        try {
            $approvalStatus = $response === 'approve' ? ApprovalStatus::APPROVED : ApprovalStatus::REJECTED;

            // Update assignment
            $assignment->update([
                'return_approval_status' => $approvalStatus,
                'return_approval_notes' => $notes,
                'return_approved_by_id' => Auth::id(),
                'return_approved_at' => now(),
            ]);

            $user = $assignment->user;
            $admin = Auth::user();

            if ($response === 'approve') {
                $this->logAssetActivity(
                    $assignment->asset,
                    'return_approved',
                    "Return request approved by {$admin->getFullName()}. Employee {$user->getFullName()} can now return the asset.",
                    $user->id,
                    $assignment
                );
            } else {
                $this->logAssetActivity(
                    $assignment->asset,
                    'return_rejected',
                    "Return request rejected by {$admin->getFullName()}. Reason: {$notes}",
                    $user->id,
                    $assignment
                );
            }

            DB::commit();

            // TODO: Send notification to employee about return request response

            $message = $response === 'approve' ? 'Return request approved successfully.' : 'Return request rejected successfully.';

            return response()->json(['success' => true, 'message' => $message]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error responding to return request for assignment {$assignment->id}: ".$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to process return response.'], 500);
        }
    }

    /**
     * Enhanced return asset method that handles approved return requests.
     * Route: POST /assignments/{assignment}/return
     * Name: tenant.assignments.return
     */
    public function returnAssetEnhanced(Request $request, AssetAssignment $assignment): JsonResponse
    {
        // Check if return is approved or if admin is forcing return
        $canReturn = $assignment->isReturnApproved() || Auth::user()->hasRole('admin'); // Adjust role check as needed

        if (! $canReturn) {
            return response()->json(['success' => false, 'message' => 'Return request must be approved before asset can be returned.'], 409);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'returned_at' => 'required|date_format:Y-m-d',
            'condition_in' => ['required', new EnumRule(AssetCondition::class)],
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        DB::beginTransaction();
        try {
            $receiverId = Auth::id();
            $returnCondition = AssetCondition::from($validated['condition_in']);
            $returnee = $assignment->user;

            // Update Assignment Record
            $assignment->update([
                'returned_at' => $validated['returned_at'],
                'condition_in' => $returnCondition,
                'notes' => ($assignment->notes ? $assignment->notes."\n---\nRETURN:\n" : "RETURN:\n").($validated['notes'] ?? 'Returned.'),
                'received_by_id' => $receiverId,
            ]);

            // Update Asset Status
            $asset = $assignment->asset;
            $newAssetStatus = match ($returnCondition) {
                AssetCondition::GOOD, AssetCondition::FAIR, AssetCondition::NEW => AssetStatus::AVAILABLE,
                AssetCondition::POOR, AssetCondition::BROKEN => AssetStatus::IN_REPAIR,
                default => AssetStatus::AVAILABLE,
            };

            $asset->update([
                'status' => $newAssetStatus,
                'location' => 'Returned - '.$newAssetStatus->label(),
            ]);

            $this->logAssetActivity(
                $asset,
                'returned',
                "Returned by {$returnee->getFullName()}. Condition: {$returnCondition->value}. New Status: {$newAssetStatus->value}.",
                $returnee->id,
                $assignment
            );

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Asset returned successfully.']);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error returning asset for assignment {$assignment->id}: ".$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to process asset return.'], 500);
        }
    }

    /**
     * Get assignments pending employee approval.
     * Route: GET /assignments/pending-approval
     * Name: tenant.assignments.pendingApproval
     */
    public function getPendingApprovals(Request $request): JsonResponse
    {
        try {
            // Handle stats-only request
            if ($request->has('stats_only')) {
                return $this->getPendingApprovalsStats();
            }

            // Build base query with relationships
            $query = AssetAssignment::with(['asset.category', 'user', 'assignedBy'])
                ->pendingEmployeeApproval();

            // Apply filters
            if ($request->filled('employee_id')) {
                $query->where('user_id', $request->employee_id);
            }

            if ($request->filled('category_id')) {
                $query->whereHas('asset', function ($q) use ($request) {
                    $q->where('category_id', $request->category_id);
                });
            }

            if ($request->filled('overdue_status')) {
                if ($request->overdue_status === 'overdue') {
                    $query->where('assigned_at', '<=', now()->subDays(7));
                } elseif ($request->overdue_status === 'normal') {
                    $query->where('assigned_at', '>', now()->subDays(7));
                }
            }

            // Handle DataTables server-side processing
            if ($request->has('draw')) {
                return $this->processDataTablesRequest($query, $request);
            }

            // Default behavior for non-DataTables requests
            $assignments = $query->orderBy('assigned_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $assignments->map(function ($assignment) {
                    return [
                        'id' => $assignment->id,
                        'asset' => [
                            'id' => $assignment->asset->id,
                            'name' => $assignment->asset->name,
                            'asset_tag' => $assignment->asset->asset_tag,
                            'category' => $assignment->asset->category->name ?? 'Uncategorized',
                        ],
                        'employee' => [
                            'id' => $assignment->user->id,
                            'name' => $assignment->user->getFullName(),
                        ],
                        'assigned_by' => [
                            'id' => $assignment->assignedBy->id,
                            'name' => $assignment->assignedBy->getFullName(),
                        ],
                        'assigned_at' => $assignment->assigned_at->format('Y-m-d'),
                        'formatted_assigned_at' => $assignment->assigned_at->format('M d, Y'),
                        'expected_return_date' => $assignment->expected_return_date?->format('Y-m-d'),
                        'formatted_expected_return_date' => $assignment->expected_return_date?->format('M d, Y'),
                        'notes' => $assignment->notes,
                        'days_pending' => $assignment->getDaysPendingApproval(),
                        'is_overdue' => $assignment->isOverdue(),
                    ];
                }),
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching pending approvals: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to fetch pending approvals.'], 500);
        }
    }

    /**
     * Get assignments with pending return requests.
     * Route: GET /assignments/pending-returns
     * Name: tenant.assignments.pendingReturns
     */
    public function getPendingReturns(): JsonResponse
    {
        try {
            $assignments = AssetAssignment::with(['asset', 'user'])
                ->pendingReturnApproval()
                ->orderBy('return_requested_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $assignments->map(function ($assignment) {
                    return [
                        'id' => $assignment->id,
                        'asset' => [
                            'id' => $assignment->asset->id,
                            'name' => $assignment->asset->name,
                            'asset_tag' => $assignment->asset->asset_tag,
                        ],
                        'employee' => [
                            'id' => $assignment->user->id,
                            'name' => $assignment->user->getFullName(),
                        ],
                        'return_requested_at' => $assignment->return_requested_at->format('Y-m-d H:i'),
                        'return_request_notes' => $assignment->return_request_notes,
                        'days_pending_return' => $assignment->getDaysPendingReturn(),
                    ];
                }),
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching pending returns: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to fetch pending returns.'], 500);
        }
    }

    /**
     * Display the approval workflow dashboard.
     * Route: GET /assignments/dashboard
     * Name: tenant.assignments.dashboard
     *
     * @return \Illuminate\View\View
     */
    public function approvalDashboard()
    {
        $users = User::where('status', \App\Enums\UserAccountStatus::ACTIVE)->get();
        $categories = \Modules\Assets\app\Models\AssetCategory::where('is_active', true)->get();

        return view('assets::assignments.dashboard', compact('users', 'categories'));
    }

    /**
     * Display the pending approvals view.
     * Route: GET /assignments/pending-approvals
     * Name: tenant.assignments.pendingApprovalsView
     *
     * @return \Illuminate\View\View
     */
    public function pendingApprovalsView()
    {
        $users = User::where('status', \App\Enums\UserAccountStatus::ACTIVE)->get();
        $categories = \Modules\Assets\app\Models\AssetCategory::where('is_active', true)->get();

        return view('assets::assignments.pending-approvals', compact('users', 'categories'));
    }

    /**
     * Display the pending returns view.
     * Route: GET /assignments/pending-returns
     * Name: tenant.assignments.pendingReturnsView
     *
     * @return \Illuminate\View\View
     */
    public function pendingReturnsView()
    {
        $users = User::where('status', \App\Enums\UserAccountStatus::ACTIVE)->get();
        $categories = \Modules\Assets\app\Models\AssetCategory::where('is_active', true)->get();

        return view('assets::assignments.pending-returns', compact('users', 'categories'));
    }

    /**
     * Get assignment details for modal display.
     * Route: GET /assignments/{assignment}/details
     * Name: tenant.assignments.details
     *
     * @return \Illuminate\View\View
     */
    public function assignmentDetails(AssetAssignment $assignment)
    {
        $assignment->load(['asset.category', 'user', 'assignedBy', 'receivedBy', 'returnApprovedBy']);

        return view('assets::assignments._assignment_details', compact('assignment'));
    }

    /**
     * Send reminder notification to employee.
     * Route: POST /assignments/{assignment}/send-reminder
     * Name: tenant.assignments.sendReminder
     */
    public function sendReminder(AssetAssignment $assignment): JsonResponse
    {
        if (! $assignment->canEmployeeRespond()) {
            return response()->json(['success' => false, 'message' => 'Cannot send reminder for this assignment.'], 409);
        }

        try {
            // TODO: Send notification to employee
            // You can implement this using Laravel's notification system

            $this->logAssetActivity(
                $assignment->asset,
                'reminder_sent',
                "Reminder sent to {$assignment->user->getFullName()} for pending assignment approval",
                $assignment->user_id,
                $assignment
            );

            return response()->json(['success' => true, 'message' => 'Reminder sent successfully.']);

        } catch (Exception $e) {
            Log::error("Error sending reminder for assignment {$assignment->id}: ".$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to send reminder.'], 500);
        }
    }

    /**
     * Send bulk reminders to multiple employees.
     * Route: POST /assignments/bulk-remind
     * Name: tenant.assignments.bulkRemind
     */
    public function bulkSendReminders(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'assignment_ids' => 'required|array|min:1',
            'assignment_ids.*' => 'exists:asset_assignments,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid assignment IDs.'], 422);
        }

        try {
            $assignments = AssetAssignment::whereIn('id', $request->assignment_ids)
                ->pendingEmployeeApproval()
                ->with(['user', 'asset'])
                ->get();

            $sentCount = 0;
            foreach ($assignments as $assignment) {
                // TODO: Send notification to employee

                $this->logAssetActivity(
                    $assignment->asset,
                    'bulk_reminder_sent',
                    "Bulk reminder sent to {$assignment->user->getFullName()}",
                    $assignment->user_id,
                    $assignment
                );

                $sentCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "Reminders sent to {$sentCount} employees successfully.",
            ]);

        } catch (Exception $e) {
            Log::error('Error sending bulk reminders: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to send reminders.'], 500);
        }
    }

    /**
     * Bulk approve return requests.
     * Route: POST /assignments/bulk-approve-returns
     * Name: tenant.assignments.bulkApproveReturns
     */
    public function bulkApproveReturns(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'assignment_ids' => 'required|array|min:1',
            'assignment_ids.*' => 'exists:asset_assignments,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid input.'], 422);
        }

        DB::beginTransaction();
        try {
            $assignments = AssetAssignment::whereIn('id', $request->assignment_ids)
                ->pendingReturnApproval()
                ->with(['user', 'asset'])
                ->get();

            $approvedCount = 0;
            $notes = $request->notes ?? 'Bulk approved by admin';

            foreach ($assignments as $assignment) {
                $assignment->update([
                    'return_approval_status' => ApprovalStatus::APPROVED,
                    'return_approval_notes' => $notes,
                    'return_approved_by_id' => Auth::id(),
                    'return_approved_at' => now(),
                ]);

                $this->logAssetActivity(
                    $assignment->asset,
                    'bulk_return_approved',
                    "Return request bulk approved for {$assignment->user->getFullName()}",
                    $assignment->user_id,
                    $assignment
                );

                $approvedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$approvedCount} return requests approved successfully.",
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error bulk approving returns: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to approve returns.'], 500);
        }
    }

    /**
     * Get dashboard data for AJAX requests.
     * Route: GET /assignments/dashboard/data
     * Name: tenant.assignments.dashboardData
     */
    public function dashboardData(): JsonResponse
    {
        try {
            $pendingApprovals = AssetAssignment::pendingEmployeeApproval()->count();
            $overdueApprovals = AssetAssignment::overdue()->count();
            $pendingReturns = AssetAssignment::pendingReturnApproval()->count();
            $urgentReturns = AssetAssignment::pendingReturnApproval()
                ->where('return_requested_at', '<', now()->subDays(3))
                ->count();
            $activeAssets = AssetAssignment::approvedByEmployee()->active()->count();

            // Calculate response rate (last 30 days)
            $totalAssignments = AssetAssignment::where('assigned_at', '>=', now()->subDays(30))->count();
            $respondedAssignments = AssetAssignment::where('assigned_at', '>=', now()->subDays(30))
                ->whereNotNull('employee_responded_at')
                ->count();
            $responseRate = $totalAssignments > 0 ? round(($respondedAssignments / $totalAssignments) * 100) : 0;

            // Calculate average response time in hours
            $avgResponseTime = AssetAssignment::whereNotNull('employee_responded_at')
                ->where('assigned_at', '>=', now()->subDays(30))
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, assigned_at, employee_responded_at)) as avg_hours')
                ->value('avg_hours');
            $avgResponseTime = round($avgResponseTime ?? 0, 1);

            return response()->json([
                'success' => true,
                'data' => [
                    'pending_approvals' => $pendingApprovals,
                    'overdue_approvals' => $overdueApprovals,
                    'pending_returns' => $pendingReturns,
                    'urgent_returns' => $urgentReturns,
                    'active_assets' => $activeAssets,
                    'response_rate' => $responseRate,
                    'avg_response_time' => $avgResponseTime,
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching dashboard data: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to fetch dashboard data.'], 500);
        }
    }

    /**
     * Export pending approvals to Excel/CSV.
     * Route: GET /assignments/export/pending-approvals
     * Name: tenant.assignments.exportPendingApprovals
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportPendingApprovals(Request $request)
    {
        try {
            $assignments = AssetAssignment::with(['asset.category', 'user', 'assignedBy'])
                ->pendingEmployeeApproval()
                ->orderBy('assigned_at', 'desc')
                ->get();

            // TODO: Implement Excel export using Laravel Excel or similar
            // For now, return JSON data that can be processed by frontend

            $data = $assignments->map(function ($assignment) {
                return [
                    'Employee' => $assignment->user->getFullName(),
                    'Asset' => $assignment->asset->name,
                    'Asset Tag' => $assignment->asset->asset_tag,
                    'Category' => $assignment->asset->category->name ?? 'Uncategorized',
                    'Assigned By' => $assignment->assignedBy->getFullName(),
                    'Assigned Date' => $assignment->assigned_at->format('Y-m-d'),
                    'Expected Return' => $assignment->expected_return_date?->format('Y-m-d') ?? 'Not specified',
                    'Days Pending' => $assignment->getDaysPendingApproval(),
                    'Is Overdue' => $assignment->isOverdue() ? 'Yes' : 'No',
                    'Notes' => $assignment->notes ?? '',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'filename' => 'pending_approvals_'.now()->format('Y-m-d_H-i-s').'.csv',
            ]);

        } catch (Exception $e) {
            Log::error('Error exporting pending approvals: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to export data.'], 500);
        }
    }

    /**
     * Export pending returns to Excel/CSV.
     * Route: GET /assignments/export/pending-returns
     * Name: tenant.assignments.exportPendingReturns
     */
    public function exportPendingReturns(Request $request): JsonResponse
    {
        try {
            $assignments = AssetAssignment::with(['asset.category', 'user'])
                ->pendingReturnApproval()
                ->orderBy('return_requested_at', 'desc')
                ->get();

            $data = $assignments->map(function ($assignment) {
                return [
                    'Employee' => $assignment->user->getFullName(),
                    'Asset' => $assignment->asset->name,
                    'Asset Tag' => $assignment->asset->asset_tag,
                    'Category' => $assignment->asset->category->name ?? 'Uncategorized',
                    'Return Requested' => $assignment->return_requested_at->format('Y-m-d H:i'),
                    'Days Pending' => $assignment->getDaysPendingReturn(),
                    'Return Reason' => $assignment->return_request_notes ?? '',
                    'Priority' => $assignment->getDaysPendingReturn() > 3 ? 'Urgent' : 'Normal',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'filename' => 'pending_returns_'.now()->format('Y-m-d_H-i-s').'.csv',
            ]);

        } catch (Exception $e) {
            Log::error('Error exporting pending returns: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to export data.'], 500);
        }
    }

    /**
     * Process DataTables server-side request for pending approvals.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private function processDataTablesRequest($query, Request $request): JsonResponse
    {
        try {
            // Get total count before filtering
            $totalRecords = $query->count();

            // Apply search if provided
            if ($request->filled('search.value')) {
                $searchValue = $request->input('search.value');
                $query->where(function ($q) use ($searchValue) {
                    $q->whereHas('user', function ($userQuery) use ($searchValue) {
                        $userQuery->where('first_name', 'like', "%{$searchValue}%")
                            ->orWhere('last_name', 'like', "%{$searchValue}%");
                    })
                        ->orWhereHas('asset', function ($assetQuery) use ($searchValue) {
                            $assetQuery->where('name', 'like', "%{$searchValue}%")
                                ->orWhere('asset_tag', 'like', "%{$searchValue}%");
                        })
                        ->orWhereHas('assignedBy', function ($assignedByQuery) use ($searchValue) {
                            $assignedByQuery->where('first_name', 'like', "%{$searchValue}%")
                                ->orWhere('last_name', 'like', "%{$searchValue}%");
                        });
                });
            }

            // Get filtered count
            $filteredRecords = $query->count();

            // Apply ordering
            if ($request->filled('order.0.column')) {
                $orderColumn = $request->input('order.0.column');
                $orderDir = $request->input('order.0.dir', 'asc');

                switch ($orderColumn) {
                    case '1': // Employee
                        $query->join('users', 'asset_assignments.user_id', '=', 'users.id')
                            ->orderBy('users.first_name', $orderDir);
                        break;
                    case '2': // Asset
                        $query->join('assets', 'asset_assignments.asset_id', '=', 'assets.id')
                            ->orderBy('assets.name', $orderDir);
                        break;
                    case '5': // Assigned Date
                        $query->orderBy('assigned_at', $orderDir);
                        break;
                    case '6': // Expected Return
                        $query->orderBy('expected_return_date', $orderDir);
                        break;
                    case '7': // Days Pending
                        $query->orderBy('assigned_at', $orderDir === 'asc' ? 'desc' : 'asc');
                        break;
                    default:
                        $query->orderBy('assigned_at', 'desc');
                }
            } else {
                $query->orderBy('assigned_at', 'desc');
            }

            // Apply pagination
            $start = $request->input('start', 0);
            $length = $request->input('length', 25);
            $assignments = $query->skip($start)->take($length)->get();

            // Format data for DataTables
            $data = $assignments->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'asset' => [
                        'id' => $assignment->asset->id,
                        'name' => $assignment->asset->name,
                        'asset_tag' => $assignment->asset->asset_tag,
                        'category' => $assignment->asset->category->name ?? 'Uncategorized',
                    ],
                    'employee' => [
                        'id' => $assignment->user->id,
                        'name' => $assignment->user->getFullName(),
                    ],
                    'assigned_by' => [
                        'id' => $assignment->assignedBy->id,
                        'name' => $assignment->assignedBy->getFullName(),
                    ],
                    'assigned_at' => $assignment->assigned_at->format('Y-m-d'),
                    'formatted_assigned_at' => $assignment->assigned_at->format('M d, Y'),
                    'expected_return_date' => $assignment->expected_return_date?->format('Y-m-d'),
                    'formatted_expected_return_date' => $assignment->expected_return_date?->format('M d, Y'),
                    'notes' => $assignment->notes,
                    'days_pending' => $assignment->getDaysPendingApproval(),
                    'is_overdue' => $assignment->isOverdue(),
                ];
            });

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);

        } catch (Exception $e) {
            Log::error('Error processing DataTables request: '.$e->getMessage());

            return response()->json([
                'draw' => intval($request->input('draw', 0)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Failed to load data',
            ]);
        }
    }

    /**
     * Get statistics for pending approvals.
     */
    private function getPendingApprovalsStats(): JsonResponse
    {
        try {
            $total = AssetAssignment::pendingEmployeeApproval()->count();
            $overdue = AssetAssignment::pendingEmployeeApproval()
                ->where('assigned_at', '<=', now()->subDays(7))
                ->count();
            $thisWeek = AssetAssignment::pendingEmployeeApproval()
                ->where('assigned_at', '>=', now()->startOfWeek())
                ->count();

            // Calculate average response time for assignments that have been responded to
            $avgResponseTime = AssetAssignment::whereNotNull('employee_responded_at')
                ->where('assigned_at', '>=', now()->subDays(30))
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, assigned_at, employee_responded_at) / 24) as avg_days')
                ->value('avg_days');

            return response()->json([
                'success' => true,
                'stats' => [
                    'total' => $total,
                    'overdue' => $overdue,
                    'this_week' => $thisWeek,
                    'avg_response' => round($avgResponseTime ?? 0, 1),
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching pending approvals stats: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'stats' => [
                    'total' => 0,
                    'overdue' => 0,
                    'this_week' => 0,
                    'avg_response' => 0,
                ],
            ]);
        }
    }
} // End Controller Class
