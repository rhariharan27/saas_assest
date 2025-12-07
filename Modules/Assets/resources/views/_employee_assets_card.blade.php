@php
  // --- Data Fetching Directly in View (Not Recommended Practice) ---
  use App\Models\User; // Assuming User model path
  use Illuminate\Support\Facades\Log;use Modules\Assets\app\Models\AssetAssignment;
  use Carbon\Carbon;

  $employee = null;
  $assignments = collect(); // Default to empty collection

  if (isset($userId)) { // Check if userId was passed
      // Find the user (optional, if you need user details here)
      // $employee = User::find($userId);

      // Fetch current assignments for the user, loading necessary relations
      $assignments = AssetAssignment::where('user_id', $userId)
          ->whereNull('returned_at') // Only currently assigned
          ->with([
              'asset' => function($assetQuery) {
                  // Select fields needed from asset
                  $assetQuery->select('id', 'name', 'asset_tag', 'asset_category_id', 'serial_number')
                             ->with('category:id,name'); // Also get category name
              }
          ])
          ->select('id', 'asset_id', 'user_id', 'assigned_at', 'condition_out') // Select fields from assignment
          ->orderBy('assigned_at', 'desc') // Show most recent first
          ->get();
  } else {
       // Handle case where $userId wasn't passed (optional)
       Log::warning('Attempted to load _assigned_assets_cards partial without a $userId.');
  }
  // --- End Data Fetching ---
@endphp

{{-- Row to hold the asset cards --}}
<div class="row g-3">
  @forelse($assignments as $assignment)
    @php $asset = $assignment->asset; @endphp {{-- Get the loaded asset model --}}
    @if($asset)
      {{-- Check if asset relation loaded correctly --}}
      <div class="col-md-6 col-lg-6 col-xl-4"> {{-- Adjust columns for desired grid layout --}}
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            {{-- Asset Name & Tag --}}
            <h6 class="card-title mb-1">
              {{-- Link requires tenant prefix if applicable --}}
              <a href="{{ route('assets.show', $asset->id) }}" class="text-primary">
                {{ $asset->name ?: 'Unnamed Asset' }}
              </a>
            </h6>
            <p class="card-subtitle text-muted small mb-2">{{ $asset->asset_tag }}</p>

            {{-- Details List --}}
            <ul class="list-unstyled mb-2 small">
              <li class="mb-1">
                <i class="bx bx-category-alt bx-xs me-1 text-secondary"></i>
                <strong>Category:</strong> {{ $asset->category?->name ?? 'N/A' }}
              </li>
              <li class="mb-1">
                <i class="bx bx-barcode bx-xs me-1 text-secondary"></i>
                <strong>Serial:</strong> {{ $asset->serial_number ?: 'N/A' }}
              </li>
              <li class="mb-1">
                <i class="bx bx-calendar-check bx-xs me-1 text-secondary"></i>
                <strong>Assigned:</strong> {{ $assignment->assigned_at ? $assignment->assigned_at->format('M d, Y') : 'N/A' }}
              </li>
              <li class="mb-1">
                <i class="bx bx-shield-quarter bx-xs me-1 text-secondary"></i>
                <strong>Condition Out:</strong>
                @if($assignment->condition_out)
                  {{-- Check if it's an Enum instance before calling label() --}}
                  {{ $assignment->condition_out instanceof AssetCondition ? $assignment->condition_out->label() : e($assignment->condition_out) }}
                @else
                  N/A
                @endif
              </li>
            </ul>

            {{-- Link to Asset Details --}}
            <a href="{{ route('assets.show', $asset->id) }}" class="btn btn-xs btn-outline-secondary mt-2">View
              Details</a>
          </div>
        </div>
      </div>
    @else
      {{-- This indicates an issue with loading the asset relationship --}}
      <div class="col-12"><p class="text-warning small">Info not available for an assigned asset.</p></div>
    @endif
  @empty
    {{-- Message displayed if $assignments collection is empty --}}
    <div class="col-12">
      <p class="text-muted text-center mb-0">No assets currently assigned to this employee.</p>
    </div>
  @endforelse
</div>
