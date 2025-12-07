<?php

namespace Modules\Assets\app\Http\Controllers; // Default nwidart namespace

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Assets\app\Models\AssetCategory;
use Yajra\DataTables\Facades\DataTables;
use Exception;

class AssetCategoryController extends Controller
{
  /**
   * Display the asset categories management view.
   * Route: GET /asset-categories
   * Name: tenant.assetCategories.index
   */
  public function index()
  {
    // You might add permission checks here later
    // abort_if_cannot('view_asset_categories');

    // Return the main view for the page (will contain the DataTable structure)
    return view('assets::categories.index'); // Point to view within the module
  }

  /**
   * Handle DataTables AJAX request for asset categories.
   * Route: GET /asset-categories/list
   * Name: tenant.assetCategories.listAjax
   */
  public function listAjax(Request $request): JsonResponse
  {
    // Add permission check if needed

    $query = AssetCategory::query()
      ->withCount('assets') // Count associated assets
      ->select('asset_categories.*'); // Select specific columns if needed

    // Apply search (if DataTables search is enabled)
    if ($request->filled('search.value')) {
      $searchValue = $request->input('search.value');
      $query->where(function($q) use ($searchValue) {
        $q->where('name', 'LIKE', "%{$searchValue}%")
          ->orWhere('description', 'LIKE', "%{$searchValue}%");
      });
    }

    return DataTables::eloquent($query)
      ->editColumn('description', function ($category) {
        return \Illuminate\Support\Str::limit($category->description, 50); // Show snippet
      })
      ->addColumn('status', function ($category) { // Use addColumn or editColumn for is_active rendering
        $isChecked = $category->is_active ? 'checked' : '';
        // Use correct route name defined in next step
        $statusUrl = route('assetCategories.toggleStatus', $category->id);
        // Use the user's preferred switch style
        return '<div class="d-flex justify-content-center"> ' .
          '<label class="switch mb-0">' .
          '<input '.
          'type="checkbox" '.
          'class="switch-input category-status-toggle" ' . // Specific class for this toggle
          'id="statusToggle' . $category->id . '" ' .
          'data-id="' . $category->id . '" ' .
          'data-url="' . $statusUrl . '" ' .
          $isChecked .
          '/>' .
          '<span class="switch-toggle-slider">' .
          '<span class="switch-on"><i class="bx bx-check"></i></span>' .
          '<span class="switch-off"><i class="bx bx-x"></i></span>' .
          '</span>' .
          '</label>' .
          '</div>';
      })
      ->addColumn('actions', function ($category) {
        $editUrl = route('assetCategories.edit', $category->id); // Adjust route name if needed
        $deleteUrl = route('assetCategories.destroy', $category->id); // Adjust route name

        // Use btn-sm for consistency if desired
        $editButton = '<button class="btn btn-sm btn-icon me-1 edit-category" data-id="' . $category->id . '" data-url="' . $editUrl . '" title="Edit"><i class="bx bx-pencil"></i></button>';
        $deleteButton = '<button class="btn btn-sm btn-icon text-danger delete-category" data-id="' . $category->id . '" data-url="' . $deleteUrl . '" title="Delete" data-count="' . $category->assets_count . '"><i class="bx bx-trash"></i></button>';

        return $editButton . $deleteButton;
      })
      ->addColumn('assets_count', function ($category) {
        return $category->assets->count(); // Display the count of assets
      })
      ->rawColumns(['actions','status']) // Only actions column contains raw HTML
      ->make(true); // Use make(true) for Eloquent query
  }

  /**
   * Store a newly created asset category in storage.
   * Route: POST /asset-categories
   * Name: tenant.assetCategories.store
   */
  public function store(Request $request): JsonResponse
  {
    // Add permission check if needed: abort_if_cannot('create_asset_categories');

    $validator = Validator::make($request->all(), [
      'name' => [
        'required',
        'string',
        'max:255',
        // Ensure name is unique within the current tenant
        Rule::unique('asset_categories', 'name')->where(function ($query) {
          // Assuming TenantTrait scopes automatically, otherwise add:
          // $query->where('tenant_id', Auth::user()->tenant_id);
        })
      ],
      'description' => 'nullable|string|max:1000',
      'is_active' => 'sometimes|boolean',
    ]);

    if ($validator->fails()) {
      return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
    }

    try {
      $data = $validator->validated();
      // Handle checkbox value (comes as '1' or not present)
      $data['is_active'] = filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN); // Default true

      $category = AssetCategory::create($data);

      return response()->json(['success' => true, 'message' => 'Asset Category created successfully.', 'category_id' => $category->id], 201);

    } catch (Exception $e) {
      Log::error('Error creating asset category: ' . $e->getMessage());
      return response()->json(['success' => false, 'message' => 'Failed to create category.'], 500);
    }
  }

  /**
   * Fetch data for editing the specified category.
   * Route: GET /asset-categories/{category}/edit
   * Name: tenant.assetCategories.edit
   */
  public function edit(AssetCategory $category): JsonResponse // Use Route Model Binding
  {
    // Add permission/ownership check if needed
    // abort_if_cannot('edit_asset_categories');

    // Check if category belongs to current tenant if not using global scope
    // if ($category->tenant_id !== Auth::user()->tenant_id) { abort(403); }

    // Return data structured for the edit form
    return response()->json([
      'success' => true,
      'category' => [
        'id' => $category->id,
        'name' => $category->name,
        'description' => $category->description,
        'is_active' => $category->is_active,
      ]
    ]);
  }

  /**
   * Update the specified asset category in storage.
   * Route: PUT /asset-categories/{category}
   * Name: tenant.assetCategories.update
   */
  public function update(Request $request, AssetCategory $category): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'name' => [
        'required',
        'string',
        'max:255',
        // Ensure name is unique within the current tenant, ignoring the current category ID
        Rule::unique('asset_categories', 'name')->where(function ($query) {
          // Assuming TenantTrait scopes automatically
        })->ignore($category->id)
      ],
      'description' => 'nullable|string|max:1000',
      'is_active' => 'sometimes|boolean',
    ]);

    if ($validator->fails()) {
      return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
    }

    try {
      $data = $validator->validated();
      // Handle checkbox value (comes as '1' or not present)
      $data['is_active'] = filter_var($request->input('is_active', false), FILTER_VALIDATE_BOOLEAN); // Default false if not sent on update

      $category->update($data);

      return response()->json(['success' => true, 'message' => 'Asset Category updated successfully.']);

    } catch (Exception $e) {
      Log::error("Error updating asset category {$category->id}: " . $e->getMessage());
      return response()->json(['success' => false, 'message' => 'Failed to update category.'], 500);
    }
  }

  /**
   * Toggle the active status of the specified asset category.
   * Route: POST /asset-categories/{category}/toggle-status
   * Name: tenant.assetCategories.toggleStatus
   */
  public function toggleStatus(AssetCategory $category): JsonResponse // Use Route Model Binding
  {
    try {
      $category->is_active = !$category->is_active;
      $category->save();

      $message = 'Category status updated to ' . ($category->is_active ? 'Active' : 'Inactive') . '.';

      return response()->json([
        'success' => true,
        'message' => $message,
        'newStatus' => $category->is_active // Send back new status for potential JS use
      ]);
    } catch (Exception $e) {
      Log::error("Error toggling status for asset category {$category->id}: " . $e->getMessage());
      return response()->json(['success' => false, 'message' => 'An error occurred.'], 500);
    }
  }

  /**
   * Remove the specified asset category from storage.
   * Route: DELETE /asset-categories/{category}
   * Name: tenant.assetCategories.destroy
   */
  public function destroy(AssetCategory $category): JsonResponse
  {

    try {
      // --- IMPORTANT: Check if category is in use ---
      if ($category->assets()->exists()) { // Use the relationship to check
        return response()->json([
          'success' => false,
          'message' => 'Cannot delete category: It is currently assigned to one or more assets. Please reassign assets first.'
        ], 409); // 409 Conflict
      }
      // --- End Check ---

      $category->delete(); // Standard delete (uses SoftDelete if trait is on model)

      return response()->json(['success' => true, 'message' => 'Asset Category deleted successfully.']);

    } catch (Exception $e) {
      Log::error("Error deleting asset category {$category->id}: " . $e->getMessage());
      return response()->json(['success' => false, 'message' => 'Failed to delete category.'], 500);
    }
  }
}
