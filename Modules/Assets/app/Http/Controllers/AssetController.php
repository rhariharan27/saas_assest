<?php

namespace Modules\Assets\app\Http\Controllers; // Default nwidart namespace

use App\Enums\UserAccountStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum as EnumRule;
use Modules\Assets\app\Enums\AssetCondition;
use Modules\Assets\app\Enums\AssetStatus;
use Modules\Assets\app\Enums\MaintenanceType;
use Modules\Assets\app\Models\Asset;
use Modules\Assets\app\Models\AssetActivity;
use Modules\Assets\app\Models\AssetCategory;
use Yajra\DataTables\Facades\DataTables;
use Exception;

class AssetController extends Controller
{
  /**
   * Display the assets management view.
   * Route: GET /assets
   * Name: tenant.assets.index
   */
  public function index()
  {
    // abort_if_cannot('view_assets');

    // Data needed for filters and Add/Edit modal
    $categories = AssetCategory::where('is_active', true)->orderBy('name')->select('id', 'name')->get();
    // Provide list of statuses and conditions from Enums
    $statuses = AssetStatus::cases();
    $conditions = AssetCondition::cases();
    // Users for assignment dropdown (will be needed later, maybe pass now)
    $users = User::where('status', \App\Enums\UserAccountStatus::ACTIVE)->orderBy('first_name')->select('id', 'first_name', 'last_name')->get();

    return view('assets::assets.index', compact('categories', 'statuses', 'conditions', 'users'));
  }

  /**
   * Handle DataTables AJAX request for assets.
   * Route: GET /assets/list
   * Name: tenant.assets.listAjax
   */
  public function listAjax(Request $request): JsonResponse
  {
    // abort_if_cannot('view_assets');

    $query = Asset::with([
      'category:id,name',
      // Load current assignment and the assigned user details
      'currentAssignment.user:id,first_name,last_name'
    ])
      ->select('assets.*'); // Select specific columns if needed

    // --- Filtering ---
    if ($request->filled('filter_category_id')) {
      $query->where('asset_category_id', $request->input('filter_category_id'));
    }
    if ($request->filled('filter_status')) {
      $query->where('status', $request->input('filter_status'));
    }
    // Filter by assigned user
    if ($request->filled('filter_user_id')) {
      $userId = $request->input('filter_user_id');
      $query->whereHas('currentAssignment', function ($q) use ($userId) {
        $q->where('user_id', $userId);
      });
    }
    // Basic Search
    if ($request->filled('search.value')) {
      $searchValue = $request->input('search.value');
      $query->where(function ($q) use ($searchValue) {
        $q->where('name', 'LIKE', "%{$searchValue}%")
          ->orWhere('asset_tag', 'LIKE', "%{$searchValue}%")
          ->orWhere('serial_number', 'LIKE', "%{$searchValue}%")
          ->orWhere('model', 'LIKE', "%{$searchValue}%")
          ->orWhere('manufacturer', 'LIKE', "%{$searchValue}%");
      });
    }
    // --- End Filtering ---

    return DataTables::eloquent($query)
      ->addColumn('category_name', function ($asset) {
        return $asset->category?->name ?? '<span class="text-muted">None</span>';
      })
      ->addColumn('current_assignee', function ($asset) {
        // Access user through the eager-loaded relationship
        $user = $asset->currentAssignment?->user;
        if ($user) {
          return view('_partials._profile-avatar', compact('user'))->render();
        }
        return '<span class="text-muted">Unassigned</span>';
      })
      ->editColumn('status', function ($asset) {
        $status = $asset->status; // Already cast to Enum
        $badgeClass = match ($status) {
          AssetStatus::AVAILABLE => 'bg-label-success',
          AssetStatus::ASSIGNED => 'bg-label-primary',
          AssetStatus::IN_REPAIR => 'bg-label-warning',
          AssetStatus::DAMAGED => 'bg-label-danger',
          AssetStatus::LOST => 'bg-label-dark',
          AssetStatus::DISPOSED => 'bg-label-secondary',
          AssetStatus::ARCHIVED => 'bg-label-secondary',
          default => 'bg-label-light'
        };
        return '<span class="badge ' . $badgeClass . '">' . $status->label() . '</span>'; // Use label()
      })
      ->editColumn('condition', function ($asset) {
        if (!$asset->condition) return 'N/A';
        $condition = $asset->condition; // Already cast to Enum
        $badgeClass = match ($condition) {
          AssetCondition::NEW => 'bg-label-info',
          AssetCondition::GOOD => 'bg-label-success',
          AssetCondition::FAIR => 'bg-label-warning',
          AssetCondition::POOR => 'bg-label-danger',
          AssetCondition::BROKEN => 'bg-label-dark',
          default => 'bg-label-light'
        };
        return '<span class="badge ' . $badgeClass . '">' . $condition->label() . '</span>'; // Use label()
      })
      ->addColumn('actions', function ($asset) {
        $viewUrl = route('assets.show', $asset->id);
        $editUrl = route('assets.edit', $asset->id);
        $deleteUrl = route('assets.destroy', $asset->id);
        $assignUrl = route('assets.assignStore', $asset->id); // For assign action (POST target)
        $returnUrl = route('assets.return', $asset->id);

        $viewButton = '<a href="' . $viewUrl . '" class="btn btn-sm btn-icon me-1" title="View Details"><i class="bx bx-show"></i></a>';
        $editButton = '<button class="btn btn-sm btn-icon me-1 edit-asset" data-id="' . $asset->id . '" data-url="' . $editUrl . '" title="Edit"><i class="bx bx-pencil"></i></button>';
        $deleteButton = '<button class="btn btn-sm btn-icon text-danger delete-asset" data-id="' . $asset->id . '" data-url="' . $deleteUrl . '" title="Delete" data-assigned="' . ($asset->status == AssetStatus::ASSIGNED ? 'true' : 'false') . '"><i class="bx bx-trash"></i></button>';

        // Add Assign button only (Return button hidden)
        $assignReturnButton = '';
        if ($asset->status == AssetStatus::AVAILABLE) {
          $assignReturnButton = '<button class="btn btn-sm btn-icon me-1 assign-asset" data-id="' . $asset->id . '" data-url="' . $assignUrl . '" title="Assign Asset"><i class="bx bx-user-plus text-primary"></i></button>';
        }
        // Return button is hidden - assets can only be returned through the approval workflow
        return '<div class="d-flex justify-content-center">' . $viewButton . $editButton . $assignReturnButton . $deleteButton . '</div>';
      })
      ->rawColumns(['category_name', 'current_assignee', 'status', 'condition', 'actions'])
      ->make(true);
  }

  /**
   * Display the specified asset's details, history, and timeline.
   * Route: GET /assets/{asset}
   * Name: tenant.assets.show
   */
  public function show(Asset $asset) // Route model binding
  {
    // --- Authorization Check ---
    // abort_if_cannot('view_assets');
    // Optional: Check if asset belongs to user's tenant if not using global scopes
    // if ($asset->tenant_id !== Auth::user()->tenant_id) { abort(403); }

    // --- Eager Load Necessary Relationships ---
    $asset->load([
      'category:id,name',
      // Current assignment with user details
      'currentAssignment.user:id,first_name,last_name,code', // 'code' for display maybe
      // Assignment History with related users
      'assignments' => function ($query) {
        $query->with([
          'user:id,first_name,last_name,code',      // The assignee
          'assignedBy:id,first_name,last_name', // User who assigned
          'receivedBy:id,first_name,last_name' // User who received return
        ])
          ->orderBy('assigned_at', 'desc'); // Order history newest first
      },
      // Activity Timeline with related users
      'activities' => function ($query) {
        $query->with([
          'user:id,first_name,last_name',        // User performing action
          'relatedUser:id,first_name,last_name' // User involved (assignee etc.)
        ])
          ->orderBy('created_at', 'desc'); // Order timeline newest first
      },
      // Maintenance History (load for future use)
      'maintenances' => function ($query) {
        $query->with(['completedBy:id,first_name,last_name']) // User who logged/completed
          ->orderBy('performed_at', 'desc');
      }
    ]);

    // --- Data needed for Modals included on this page ---
    $categories = AssetCategory::where('is_active', true)->orderBy('name')->select('id', 'name')->get();
    $statuses = AssetStatus::cases();
    $conditions = AssetCondition::cases();
    $users = User::where('status', UserAccountStatus::ACTIVE)->orderBy('first_name')->select('id', 'first_name', 'last_name')->get();
    $maintenanceTypes = MaintenanceType::cases();

    // Return the view, passing the loaded asset and modal data
    return view('assets::assets.show', compact(
      'asset',
      'categories',
      'statuses',
      'conditions',
      'users',
      'maintenanceTypes'
    ));
  }

  /**
   * Store a newly created asset in storage.
   * Route: POST /assets
   * Name: tenant.assets.store
   */
  public function store(Request $request): JsonResponse
  {
    // Preprocess purchase_cost: remove commas if present
    $request->merge([
      'purchase_cost' => $request->input('purchase_cost') !== null ? str_replace(',', '', $request->input('purchase_cost')) : null,
    ]);

    // abort_if_cannot('create_assets');

    $tenantId = Auth::user()->tenant_id; // Assuming tenant context

    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255',
      'asset_tag' => [
        'required',
        'string',
        'max:100',
        Rule::unique('assets', 'asset_tag')->where(fn($query) => $query->where('tenant_id', $tenantId))
      ],
      'asset_category_id' => 'nullable|exists:asset_categories,id',
      'manufacturer' => 'nullable|string|max:100',
      'model' => 'nullable|string|max:100',
      'serial_number' => 'nullable|string|max:255', // Consider adding unique constraint if needed
      'purchase_date' => 'nullable|date_format:Y-m-d',
      'purchase_cost' => 'nullable|numeric|min:0',
      'supplier' => 'nullable|string|max:255',
      'warranty_expiry_date' => 'nullable|date_format:Y-m-d|after_or_equal:purchase_date',
      'status' => ['required', new EnumRule(AssetStatus::class)],
      'condition' => ['nullable', new EnumRule(AssetCondition::class)],
      'location' => 'nullable|string|max:255',
      'notes' => 'nullable|string|max:5000',
    ]);

    if ($validator->fails()) {
      return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
    }

    try {
      $validatedData = $validator->validated();
      // Explicitly add user/tenant IDs if not handled by traits/events
      // $validatedData['created_by_id'] = Auth::id();
      // $validatedData['tenant_id'] = $tenantId;

      $asset = Asset::create($validatedData);

      // --- Log Activity ---
      $this->logAssetActivity(
        $asset,
        'created',
        "Asset '{$asset->asset_tag}' ({$asset->name}) created."
        // No related user or model for simple creation
      );

      // Log activity
      Log::info("Asset created: ID {$asset->id}, Tag {$asset->asset_tag} by User " . Auth::id());

      return response()->json(['success' => true, 'message' => 'Asset created successfully.', 'asset_id' => $asset->id], 201);
    } catch (Exception $e) {
      Log::error('Error creating asset: ' . $e->getMessage());
      return response()->json(['success' => false, 'message' => 'Failed to create asset.'], 500);
    }
  }

  /**
   * Fetch data for editing the specified asset.
   * Route: GET /assets/{asset}/edit
   * Name: tenant.assets.edit
   */
  public function edit(Asset $asset): JsonResponse // Route model binding
  {
    // abort_if_cannot('edit_assets');
    // Check tenant ownership if not using global scope
    // if ($asset->tenant_id !== Auth::user()->tenant_id) { abort(403); }

    // Prepare data for the form (camelCase keys if JS prefers)
    $assetData = [
      'id' => $asset->id,
      'name' => $asset->name,
      'assetTag' => $asset->asset_tag, // camelCase example
      'assetCategoryId' => $asset->asset_category_id,
      'manufacturer' => $asset->manufacturer,
      'model' => $asset->model,
      'serialNumber' => $asset->serial_number,
      'purchaseDate' => $asset->purchase_date?->format('Y-m-d'),
      'purchaseCost' => $asset->purchase_cost,
      'supplier' => $asset->supplier,
      'warrantyExpiryDate' => $asset->warranty_expiry_date?->format('Y-m-d'),
      'status' => $asset->status->value, // Send Enum value
      'condition' => $asset->condition?->value, // Send Enum value or null
      'location' => $asset->location,
      'notes' => $asset->notes,
    ];

    return response()->json(['success' => true, 'asset' => $assetData]);
  }

  /**
   * Update the specified asset in storage.
   * Route: PUT /assets/{asset}
   * Name: tenant.assets.update
   */
  public function update(Request $request, Asset $asset): JsonResponse
  {
    // Preprocess purchase_cost: remove commas if present
    $request->merge([
      'purchase_cost' => $request->input('purchase_cost') !== null ? str_replace(',', '', $request->input('purchase_cost')) : null,
    ]);

    // abort_if_cannot('edit_assets');
    // if ($asset->tenant_id !== Auth::user()->tenant_id) { abort(403); }

    $tenantId = Auth::user()->tenant_id;

    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255',
      'asset_tag' => [
        'required',
        'string',
        'max:100',
        Rule::unique('assets', 'asset_tag')->where(fn($query) => $query->where('tenant_id', $tenantId))->ignore($asset->id) // Ignore self on update
      ],
      'asset_category_id' => 'nullable|exists:asset_categories,id',
      // ... other fields similar to store validation ...
      'serial_number' => 'nullable|string|max:255', // Add unique check if needed, ignoring self
      'status' => ['required', new EnumRule(AssetStatus::class)],
      'condition' => ['nullable', new EnumRule(AssetCondition::class)],
      'purchase_date' => 'nullable|date_format:Y-m-d',
      'purchase_cost' => 'nullable|numeric|min:0',
      'warranty_expiry_date' => 'nullable|date_format:Y-m-d|after_or_equal:purchase_date',
      'supplier' => 'nullable|string|max:255',
      'manufacturer' => 'nullable|string|max:100',
      'location' => 'nullable|string|max:255',
      'notes' => 'nullable|string|max:1000',
      // ... location, notes, etc. ...
    ]);

    if ($validator->fails()) {
      return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
    }

    try {
      $validatedData = $validator->validated();
      $originalStatus = $asset->status; // Get status before update
      $asset->update($validatedData);
      $asset->refresh(); // Refresh to get updated data including potential casts

      // --- Log Activity ---
      $details = "Asset '{$asset->asset_tag}' details updated.";
      // Check if status changed and log specifically
      if ($originalStatus !== $asset->status) {
        $details .= " Status changed from '{$originalStatus->value}' to '{$asset->status->value}'.";
        $this->logAssetActivity($asset, 'status_changed', $details);
      } else {
        // Log generic update if status didn't change (optional)
        $this->logAssetActivity($asset, 'updated', $details);
      }
      // --- End Log Activity ---

      return response()->json(['success' => true, 'message' => 'Asset updated successfully.']);
    } catch (Exception $e) {
      Log::error("Error updating asset {$asset->id}: " . $e->getMessage());
      return response()->json(['success' => false, 'message' => 'Failed to update asset.'], 500);
    }
  }

  /**
   * Remove the specified asset from storage (Soft Delete).
   * Route: DELETE /assets/{asset}
   * Name: tenant.assets.destroy
   */
  public function destroy(Asset $asset): JsonResponse
  {
    // abort_if_cannot('delete_assets');
    // if ($asset->tenant_id !== Auth::user()->tenant_id) { abort(403); }

    try {
      // Prevent deletion if currently assigned
      if ($asset->isAssigned()) { // Use helper method from model if created
        return response()->json([
          'success' => false,
          'message' => 'Cannot delete: Asset is currently assigned. Please return it first.'
        ], 409); // 409 Conflict
      }

      try {

        $assetTag = $asset->asset_tag; // Get details before deleting
        $assetId = $asset->id;
        $actorId = Auth::id();

        $asset->delete();

        AssetActivity::create([
          'asset_id' => $assetId,
          'user_id' => $actorId,
          'action' => 'deleted',
          'details' => "Asset '{$assetTag}' deleted.",
          'tenant_id' => $asset->tenant_id, // Get tenant ID before delete if needed
        ]);
      } catch (Exception $logE) {
        Log::error("Failed to log asset deletion for Asset ID {$assetId}: " . $logE->getMessage());
      }

      return response()->json(['success' => true, 'message' => 'Asset deleted successfully.']);
    } catch (Exception $e) {
      Log::error("Error deleting asset {$asset->id}: " . $e->getMessage());
      return response()->json(['success' => false, 'message' => 'Failed to delete asset.'], 500);
    }
  }

  /**
   * Check asset availability for assignment
   * Route: GET /assets/{asset}/availability-check
   * Name: assets.availability-check
   */
  public function checkAvailability(Asset $asset): JsonResponse
  {
    $available = $asset->isAvailableForAssignment();
    $currentAssignment = null;
    $rejectionHistory = null;
    
    if (!$available && $asset->hasActiveAssignment()) {
      $assignment = $asset->getCurrentAssignment();
      $currentAssignment = [
        'assignment_id' => $assignment->id,
        'assigned_to' => $assignment->user->getFullName(),
        'assigned_date' => $assignment->assigned_at->format('M d, Y'),
        'status' => $assignment->employee_approval_status->label(),
        'expected_return' => $assignment->expected_return_date?->format('M d, Y')
      ];
    }
    
    // Include rejection history if available
    if ($available && $asset->hasRejectedAssignments()) {
      $lastRejection = $asset->getLastRejectedAssignment();
      $rejectionHistory = [
        'last_rejected_by' => $lastRejection->user->getFullName(),
        'rejected_at' => $lastRejection->employee_responded_at->format('M d, Y H:i'),
        'rejection_reason' => $lastRejection->employee_approval_notes
      ];
    }
    
    return response()->json([
      'available' => $available,
      'status' => $asset->status->value,
      'message' => $available 
        ? ($rejectionHistory ? 'Asset is available (previously rejected)' : 'Asset is available for assignment')
        : 'Asset is currently assigned to another user',
      'current_assignment' => $currentAssignment,
      'rejection_history' => $rejectionHistory
    ]);
  }

  /**
   * Helper method to log asset activity.
   *
   * @param Asset $asset The asset the activity relates to.
   * @param string $action The action performed (e.g., 'created', 'assigned').
   * @param string|null $details Optional descriptive details.
   * @param int|null $relatedUserId Optional ID of the user involved (e.g., assignee).
   * @param Model|null $relatedModel Optional related model instance (e.g., AssetAssignment).
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
      if (!$actorUserId) {
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
      Log::error("Failed to log activity for Asset ID {$asset->id}: " . $e->getMessage());
      // Do not re-throw, logging failure shouldn't stop the main action
    }
  }
}
