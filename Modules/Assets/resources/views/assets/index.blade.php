@php
    // Import Enums if needed directly, though controller passes cases
    use Modules\Assets\app\Enums\AssetStatus;
    use Modules\Assets\app\Enums\AssetCondition;
@endphp
@extends('layouts.layoutMaster')

@section('title', 'Asset Management')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
        'resources/assets/vendor/libs/@form-validation/form-validation.scss', // If using client-side validation JS
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js',
        'resources/assets/vendor/libs/cleavejs/cleave.js', // For numeric inputs like cost
        'resources/assets/vendor/libs/cleavejs/cleave-phone.js', // Cleave addons if needed
        'resources/assets/vendor/libs/@form-validation/popular.js',
        'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
        'resources/assets/vendor/libs/@form-validation/auto-focus.js',
    ])
@endsection

@section('page-style')
    <style>
        /* Add any specific styles if needed */
        .select2-container--open {
            z-index: 1090;
        }

        /* Ensure Select2 is above offcanvas */
    </style>
@endsection

@section('page-script')
    <script>
        // Pass URLs and CSRF token to JavaScript
        const assetsListAjaxUrl = "{{ route('assets.listAjax') }}"; // Adjust route name if needed
        const assetsStoreUrl = "{{ route('assets.store') }}"; // Adjust route name
        const assetsAssignUrl = "assetsManagement/"; // Adjust route name
        const assetsBaseUrl = "{{ url('assets') }}"; // Base URL for update/delete/edit etc. Adjust prefix if needed
        const csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/app/assets-admin.js']) {{-- Link to specific JS --}}
@endsection

@section('content')
    <div class="container-fluid flex-grow-1 container-p-y">

        <h4 class="py-3 mb-4">
            <span class="text-muted fw-light">Manage /</span> Assets
        </h4>

        <div class="card mb-4">
            <div class="card-widget-separator-wrapper">
                <div class="card-body card-widget-separator">
                    <div class="row gy-4 gy-sm-1">
                        {{-- Filter by Category --}}
                        <div class="col-md-3 col-sm-6 col-12">
                            <label for="filter_category_id" class="form-label">Category</label>
                            <select id="filter_category_id" class="form-select select2" data-allow-clear="true">
                                <option value="">All Categories</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Filter by Status --}}
                        <div class="col-md-3 col-sm-6 col-12">
                            <label for="filter_status" class="form-label">Status</label>
                            <select id="filter_status" class="form-select select2" data-allow-clear="true">
                                <option value="">All Statuses</option>
                                @foreach ($statuses as $status)
                                    {{-- Passed from controller --}}
                                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Filter by Assigned User --}}
                        <div class="col-md-3 col-sm-6 col-12">
                            <label for="filter_user_id" class="form-label">Assigned To</label>
                            <select id="filter_user_id" class="form-select select2" data-allow-clear="true">
                                <option value="">Any User</option>
                                <option value="unassigned">Unassigned</option>
                                @foreach ($users as $user)
                                    {{-- Passed from controller --}}
                                    <option value="{{ $user->id }}">{{ $user->first_name }} {{ $user->last_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Add Button --}}
                        <div class="col-md-3 col-sm-6 col-12 d-flex align-items-end justify-content-end">
                            <button type="button" class="btn btn-primary w-100 w-md-auto" id="addAssetBtn">
                                <i class="bx bx-plus me-sm-1"></i> <span class="d-none d-sm-inline-block">Add New
                                    Asset</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Asset List</h5>
            </div>
            <div class="card-datatable table-responsive pt-0">
                <table class="datatables-assets table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Asset Tag</th>
                            <th>Category</th>
                            <th>Serial No.</th>
                            <th>Status</th>
                            <th>Condition</th>
                            <th>Assigned To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- DataTables will populate this --}}
                    </tbody>
                </table>
            </div>
        </div>

        @include('assets::assets._add_edit_offcanvas') {{-- Asset Edit Form --}}
        @include('assets::assets._assign_modal') {{-- Assign Form --}}
        {{-- Return modal removed - returns handled through approval workflow --}}

    </div>
@endsection
