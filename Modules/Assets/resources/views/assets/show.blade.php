@php
  use Modules\Assets\app\Enums\AssetStatus;
  use App\Enums\UserAccountStatus;
  use Modules\Assets\app\Enums\AssetCondition;
  use Illuminate\Support\Str;
@endphp
@extends('layouts.layoutMaster')

@section('title', 'Asset Details: ' . $asset->asset_tag)

@section('vendor-style')
  @vite([
      'resources/assets/vendor/libs/select2/select2.scss',
      'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
      'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
       // Optional: For Assignment/Maintenance History tables if using DataTables
      // 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
      // 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  ])
@endsection

@section('vendor-script')
  @vite([
      'resources/assets/vendor/libs/select2/select2.js',
      'resources/assets/vendor/libs/flatpickr/flatpickr.js',
      'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
       // Optional: For Assignment/Maintenance History tables if using DataTables
      // 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  ])
@endsection

{{-- Page Styles (Copy timeline styles from Recruitment) --}}
@section('page-style')
  <style>
    .detail-label { font-weight: 600; color: #566a7f; margin-right: 8px;}
    .detail-value { color: #6f8193; }
    dd { margin-bottom: 0.75rem; word-break: break-word; }
    dt { margin-bottom: 0.3rem; }

    /* Basic Timeline Styles (copy from recruitment/job_applications/show.blade.php) */
    .timeline { list-style: none; padding: 0; position: relative; }
    .timeline:before { content: ''; position: absolute; top: 0; bottom: 0; left: 30px; width: 2px; background-color: #e9ecef; margin-left: -1.5px; }
    .timeline-item { margin-bottom: 1.5rem; position: relative; padding-left: 60px; }
    .timeline-item:before, .timeline-item:after { content: " "; display: table;}
    .timeline-item:after { clear: both; }
    .timeline-icon { position: absolute; left: 15px; top: 0; width: 30px; height: 30px; border-radius: 50%; background-color: #696cff; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; z-index: 1; }
    /* Add icons for asset actions */
    .timeline-icon.created { background-color: #28a745; } /* Green */
    .timeline-icon.updated, .timeline-icon.status_changed { background-color: #ffc107; } /* Yellow */
    .timeline-icon.assigned { background-color: #007bff; } /* Blue */
    .timeline-icon.returned { background-color: #fd7e14; } /* Orange */
    .timeline-icon.maintenance_logged { background-color: #6f42c1; } /* Purple */
    .timeline-icon.deleted { background-color: #dc3545; } /* Red */
    .timeline-header { margin-bottom: 0.25rem; }
    .timeline-time { font-size: 0.8rem; color: #a1acb8; }
    .timeline-body { background: #f7f7f9; padding: 0.75rem 1rem; border-radius: 0.375rem; }
    .timeline-body p:last-child { margin-bottom: 0; }
    /* Ensure select2 dropdowns appear above modal */
    .select2-container--open { z-index: 1060; } /* Adjust if needed based on modal z-index */
  </style>
@endsection


@section('page-script')
  <script>
    // Adjust base URL and route names if necessary
    const assetsBaseUrl = "{{ url('assetsManagement') }}"; // Base URL for actions (update, assign, return)
    const csrfToken = "{{ csrf_token() }}";
    const currentAssetId = {{ $asset->id }};
    const assetEditUrl = "{{ route('assets.edit', $asset->id) }}"; // URL to fetch edit data
    const assetAssignUrl = "{{ route('assets.assignStore', $asset->id) }}"; // URL to POST assignment
    // Return URL removed - returns handled through approval workflow
    // Assuming asset update uses PUT /assets/{asset} route, handled via _method
    const assetUpdateUrl = `<span class="math-inline">\{assetsBaseUrl\}/</span>{currentAssetId}`;
    const assetMaintenanceStoreUrl = "{{ route('assets.maintenance.store', $asset->id) }}"; // New URL
  </script>
  @vite(['resources/assets/js/app/assets-admin-show.js']) {{-- Link to specific JS --}}
@endsection


@section('content')
  <div class="container-fluid flex-grow-1 container-p-y">

    {{-- Breadcrumbs & Action Buttons --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-style1 mb-0">
          <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Asset List</a></li>
          <li class="breadcrumb-item active">{{ $asset->asset_tag }} - {{ $asset->name }}</li>
        </ol>
      </nav>
      <div class="d-flex gap-2">
        {{-- Edit Asset Button (Triggers Offcanvas) --}}
        <button class="btn btn-primary" type="button" id="editAssetBtnDetailsPage" data-asset-id="{{ $asset->id }}">
          <i class="bx bx-pencil me-1"></i>Edit Asset
        </button>
        {{-- Assign Button (Return handled through approval workflow) --}}
        @if ($asset->status == AssetStatus::AVAILABLE)
          <button class="btn btn-success" type="button" id="assignAssetBtnDetailsPage" data-asset-id="{{ $asset->id }}" data-asset-name="{{e($asset->name)}}" data-asset-tag="{{e($asset->asset_tag)}}">
            <i class="bx bx-user-plus me-1"></i>Assign
          </button>
        @endif
        <button class="btn btn-info" type="button" data-bs-toggle="modal" data-bs-target="#maintenanceLogModal">
          <i class="bx bx-wrench me-1"></i>Add Maintenance Log
        </button>
      </div>
    </div>

    <div class="row">
      {{-- Left Column: Asset Details & Status --}}
      <div class="col-lg-5 col-xl-4">
        {{-- Asset Details Card --}}
        <div class="card mb-4">
          <div class="card-header"><h5 class="card-title mb-0">Asset Details</h5></div>
          <div class="card-body">
            <dl class="row">
              <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $asset->name }}</dd>
              <dt class="col-sm-4">Asset Tag</dt><dd class="col-sm-8">{{ $asset->asset_tag }}</dd>
              <dt class="col-sm-4">Category</dt><dd class="col-sm-8">{{ $asset->category?->name ?? 'N/A' }}</dd>
              <dt class="col-sm-4">Serial No.</dt><dd class="col-sm-8">{{ $asset->serial_number ?? 'N/A' }}</dd>
              <dt class="col-sm-4">Manufacturer</dt><dd class="col-sm-8">{{ $asset->manufacturer ?? 'N/A' }}</dd>
              <dt class="col-sm-4">Model</dt><dd class="col-sm-8">{{ $asset->model ?? 'N/A' }}</dd>
              <dt class="col-sm-4">Location</dt><dd class="col-sm-8">{{ $asset->location ?? 'N/A' }}</dd>
              <dt class="col-sm-4">Condition</dt>
              <dd class="col-sm-8">
                @if($asset->condition)
                  @php $condBadge = match($asset->condition) { AssetCondition::NEW => 'bg-label-info', AssetCondition::GOOD => 'bg-label-success', \Modules\Assets\Enums\AssetCondition::FAIR => 'bg-label-warning', \Modules\Assets\Enums\AssetCondition::POOR => 'bg-label-danger', \Modules\Assets\Enums\AssetCondition::BROKEN => 'bg-label-dark', default => 'bg-label-light' }; @endphp
                  <span class="badge {{ $condBadge }}">{{ $asset->condition->label() }}</span>
                @else N/A @endif
              </dd>
              <dt class="col-sm-4">Notes</dt><dd class="col-sm-8" style="white-space: pre-wrap;">{{ $asset->notes ?? 'N/A' }}</dd>
            </dl>
          </div>
        </div>
        {{-- Purchase & Warranty Card --}}
        <div class="card mb-4">
          <div class="card-header"><h5 class="card-title mb-0">Purchase & Warranty</h5></div>
          <div class="card-body">
            <dl class="row">
              <dt class="col-sm-5">Purchase Date</dt><dd class="col-sm-7">{{ $asset->purchase_date?->format('M d, Y') ?? 'N/A' }}</dd>
              <dt class="col-sm-5">Purchase Cost</dt><dd class="col-sm-7">{{ $asset->purchase_cost ? ($settings->currency_symbol ?? '$') . number_format($asset->purchase_cost, 2) : 'N/A' }}</dd> {{-- Assuming $settings available --}}
              <dt class="col-sm-5">Supplier</dt><dd class="col-sm-7">{{ $asset->supplier ?? 'N/A' }}</dd>
              <dt class="col-sm-5">Warranty Expiry</dt><dd class="col-sm-7">{{ $asset->warranty_expiry_date?->format('M d, Y') ?? 'N/A' }}</dd>
            </dl>
          </div>
        </div>
        {{-- Current Status Card --}}
        <div class="card mb-4">
          <div class="card-header"><h5 class="card-title mb-0">Current Status</h5></div>
          <div class="card-body">
            @php
              // Assuming $asset->status is an instance of Modules\Assets\Enums\AssetStatus Enum
              $status = $asset->status;
              $statusBadgeClass = match($status) {
                  AssetStatus::AVAILABLE => 'bg-label-success',
                  AssetStatus::ASSIGNED => 'bg-label-primary',
                  AssetStatus::IN_REPAIR => 'bg-label-warning',
                  AssetStatus::DAMAGED => 'bg-label-danger',
                  AssetStatus::LOST => 'bg-label-dark',
                  AssetStatus::DISPOSED, AssetStatus::ARCHIVED => 'bg-label-secondary',
                  default => 'bg-label-light', // Fallback for null or unexpected status
              };
            @endphp
            <p><strong>Status:</strong> <span class="badge {{ $statusBadgeClass }} ms-2">{{ $status->label() }}</span></p>
            @if($asset->status == AssetStatus::ASSIGNED && $asset->currentAssignment && $asset->currentAssignment->user)
              <p><strong>Assigned To:</strong>
                <a href="{{ route('employees.show', $asset->currentAssignment->user->id) }}">
                  {{ $asset->currentAssignment->user->getFullName() }} ({{$asset->currentAssignment->user->code}})
                </a>
              </p>
              <p><strong>Assigned On:</strong> {{ $asset->currentAssignment->assigned_at->format('M d, Y H:i A') }}</p>
              @if($asset->currentAssignment->expected_return_date)
                <p><strong>Expected Return:</strong> {{ $asset->currentAssignment->expected_return_date->format('M d, Y') }}</p>
              @endif
            @elseif($asset->status == AssetStatus::IN_REPAIR)
              <p>Asset is currently under maintenance/repair.</p> {{-- Link to maintenance log later --}}
            @elseif($asset->status == AssetStatus::AVAILABLE)
              <p>Asset is available for assignment.</p>
            @else
              <p>Asset is currently {{ strtolower($status->label()) }}.</p>
            @endif
          </div>
        </div>


      </div> {{-- End Left Column --}}

      {{-- Right Column: Timeline & History --}}
      <div class="col-lg-7 col-xl-8">
        {{-- Activity Timeline Card --}}
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="bx bx-list-ul me-2 text-muted"></i>Activity Timeline</h5>
          </div>
          <div class="card-body">
            <ul class="timeline ms-2">
              @forelse($asset->activities as $activity)
                <li class="timeline-item timeline-item-transparent">
                  @php // Icon logic based on activity->action
                                    $icon = 'bx-radio-circle'; $color = 'secondary';
                                    switch ($activity->action) {
                                        case 'created': $icon = 'bx-plus-circle'; $color = 'success'; break;
                                        case 'updated': $icon = 'bx-pencil'; $color = 'info'; break;
                                        case 'status_changed': $icon = 'bx-toggle-right'; $color = 'info'; break;
                                        case 'assigned': $icon = 'bx-user-plus'; $color = 'primary'; break;
                                        case 'returned': $icon = 'bx-user-minus'; $color = 'warning'; break;
                                        case 'maintenance_logged': $icon = 'bx-wrench'; $color = 'purple'; break;
                                        case 'deleted': $icon = 'bx-trash'; $color = 'danger'; break;
                                    }
                  @endphp
                  <span class="timeline-point timeline-point-{{ $color }}"></span>
                  <div class="timeline-event">
                    <div class="timeline-header border-bottom mb-3">
                      <h6 class="mb-0">{{ Str::title(str_replace('_', ' ', $activity->action)) }}</h6>
                      <small class="text-muted">{{ $activity->created_at->format('M d, Y - H:i A') }}</small>
                    </div>
                    <div class="d-flex justify-content-between flex-wrap">
                      <div>
                        <span>{{ $activity->details ?? 'Details not available.' }}</span>
                        @if ($activity->relatedUser)
                          <span class="text-muted">(User: {{ $activity->relatedUser->getFullName() }})</span>
                        @endif
                      </div>
                      <div class="text-end text-muted mt-1 mt-sm-0">
                        <small>by {{ $activity->user->first_name ?? 'System' }}</small>
                      </div>
                    </div>
                  </div>
                </li>
              @empty
                <li class="text-center text-muted py-3">No activities recorded yet.</li>
              @endforelse
            </ul>
          </div>
        </div>

        {{-- TODO: Add Assignment History Table Card --}}
        {{-- NEW: Maintenance History Card --}}
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="bx bx-history me-2 text-muted"></i>Maintenance History</h5>
            {{-- Maybe add filter/export later --}}
          </div>
          <div class="card-body">
            @if($asset->maintenances->isNotEmpty())
              <div class="table-responsive text-nowrap">
                <table class="table table-sm">
                  <thead>
                  <tr>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Provider</th>
                    <th>Cost</th>
                    <th>Logged By</th>
                    <th>Details</th>
                    <th>Next Due</th>
                  </tr>
                  </thead>
                  <tbody>
                  @foreach($asset->maintenances as $log)
                    <tr>
                      <td><span class="badge bg-label-secondary">{{ $log->maintenance_type->label() }}</span></td>
                      <td>{{ $log->performed_at->format('M d, Y') }}</td>
                      <td>{{ $log->provider ?? 'N/A' }}</td>
                      <td>{{ $log->cost ? ($settings->currency_symbol ?? '$') . number_format($log->cost, 2) : 'N/A' }}</td>
                      <td>{{ $log->completedBy?->first_name ?? 'N/A' }}</td>
                      <td>
                        @if($log->details)
                          <i class="bx bx-info-circle text-info" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ e($log->details) }}"></i>
                        @else
                          -
                        @endif
                      </td>
                      <td>{{ $log->next_due_date?->format('M d, Y') ?? '-' }}</td>
                    </tr>
                  @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <p class="text-muted text-center">No maintenance records found for this asset.</p>
            @endif
          </div>
        </div>

      </div> {{-- End Right Column --}}
    </div> {{-- End Row --}}

    {{-- Include Modals needed on this page --}}
    @include('assets::assets._add_edit_offcanvas') {{-- Asset Edit Form --}}
    @include('assets::assets._assign_modal') {{-- Assign Form --}}
    {{-- Return modal removed - returns handled through approval workflow --}}
    @include('assets::assets._maintenance_model')
    {{-- Include Maintenance Modal later --}}

  </div>
@endsection
