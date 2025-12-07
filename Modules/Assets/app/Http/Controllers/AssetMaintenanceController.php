<?php

namespace Modules\Assets\app\Http\Controllers; // Default nwidart namespace

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum as EnumRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;
use Modules\Assets\app\Enums\AssetStatus;
use Modules\Assets\app\Enums\MaintenanceType;
use Modules\Assets\app\Models\Asset;
use Modules\Assets\app\Models\AssetActivity;
use Modules\Assets\app\Models\AssetMaintenance;

// For date parsing/comparison

class AssetMaintenanceController extends Controller
{
  /**
   * Store a new maintenance log entry for an asset.
   * Route: POST /assets/{asset}/maintenance
   * Name: tenant.assets.maintenance.store (assuming nested naming)
   *
   * @param Request $request
   * @param Asset $asset The asset being maintained
   * @return JsonResponse
   */
  public function store(Request $request, Asset $asset): JsonResponse
  {
    // --- Validation ---
    $validator = Validator::make($request->all(), [
      'maintenance_type' => ['required', new EnumRule(MaintenanceType::class)],
      'performed_at' => 'required|date_format:Y-m-d', // Or datetime format if needed
      'cost' => 'nullable|numeric|min:0',
      'provider' => 'nullable|string|max:255',
      'details' => 'required|string|max:5000',
      'next_due_date' => 'nullable|date_format:Y-m-d|after_or_equal:performed_at',
      // Optionally accept a flag to update asset status
      'update_asset_status' => 'nullable|boolean',
      'new_asset_status' => ['required_if:update_asset_status,true', new EnumRule(AssetStatus::class)]
    ]);

    if ($validator->fails()) {
      return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
    }
    $validated = $validator->validated();

    // --- Database Transaction ---
    DB::beginTransaction();
    try {
      $userId = Auth::id();

      // 1. Create the Maintenance Record
      $maintenance = AssetMaintenance::create([
        'asset_id' => $asset->id,
        'maintenance_type' => $validated['maintenance_type'], // Enum instance via validation
        'performed_at' => $validated['performed_at'],
        'cost' => $validated['cost'] ?? null,
        'provider' => $validated['provider'] ?? null,
        'details' => $validated['details'],
        'next_due_date' => $validated['next_due_date'] ?? null,
        'completed_by_id' => $userId,
        // 'tenant_id' handled by trait?
      ]);

      // 2. Optionally Update Asset Status
      $assetStatusChanged = false;
      $newAssetStatus = null;
      if (filter_var($request->input('update_asset_status', false), FILTER_VALIDATE_BOOLEAN)) {
        $newAssetStatus = AssetStatus::from($validated['new_asset_status']);
        if ($asset->status !== $newAssetStatus) {
          $asset->status = $newAssetStatus;
          // Maybe update condition as well?
          // $asset->condition = AssetCondition::GOOD; // Example
          $asset->save();
          $assetStatusChanged = true;
        }
      }

      // --- Log Asset Activity ---
      // Assuming logAssetActivity exists in AssetController or a Trait/Service
      // Need to adapt this or create a similar helper here/in trait
      $logDetails = "Maintenance logged ({$maintenance->maintenance_type->label()}): " . Str::limit($validated['details'], 100);
      if ($assetStatusChanged && $newAssetStatus) {
        $logDetails .= ". Asset status changed to '{$newAssetStatus->value}'.";
      }

       $this->logAssetActivity($asset, 'maintenance_logged', $logDetails, null, $maintenance);




      DB::commit();

      // Return success and potentially the new maintenance record data
      return response()->json([
        'success' => true,
        'message' => 'Maintenance log added successfully.',
        'maintenance_id' => $maintenance->id
        // Optionally return formatted data for dynamic table update
      ], 201);

    } catch (Exception $e) {
      DB::rollBack();
      Log::error("Error logging maintenance for asset {$asset->id}: " . $e->getMessage());
      return response()->json(['success' => false, 'message' => 'Failed to log maintenance.'], 500);
    }
  }

  // --- Add index/listAjax, edit, update, destroy methods for maintenance logs later if needed ---


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
