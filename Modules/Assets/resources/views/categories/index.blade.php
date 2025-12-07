@extends('layouts.layoutMaster')

@section('title', 'Asset Categories')

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
    'resources/assets/vendor/libs/animate-css/animate.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    'resources/assets/vendor/libs/@form-validation/form-validation.scss', // Optional for client-side validation
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    'resources/assets/vendor/libs/@form-validation/popular.js', // Optional
    'resources/assets/vendor/libs/@form-validation/bootstrap5.js', // Optional
    'resources/assets/vendor/libs/@form-validation/auto-focus.js', // Optional
  ])
@endsection

@section('page-style')
  <style>
    /* Add any specific styles if needed */
  </style>
@endsection

@section('page-script')
  <script>
    // Pass initial data and URLs to JavaScript
    const categoriesListAjaxUrl = "{{ route('assetCategories.listAjax') }}"; // Adjust route name if needed
    const categoriesStoreUrl = "{{ route('assetCategories.store') }}"; // Adjust route name
    const categoriesBaseUrl = "{{ url('asset-categories') }}"; // Base URL for update/delete/edit
    const csrfToken = "{{ csrf_token() }}";
  </script>
  @vite(['resources/assets/js/app/assets-categories-admin.js']) {{-- Link to specific JS --}}
@endsection

@section('content')

  <h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Assets /</span> Categories
  </h4>

  <div class="d-flex justify-content-end align-items-center mb-4">
    <button type="button" class="btn btn-primary" id="addCategoryBtn">
      <i class="bx bx-plus me-sm-1"></i> <span class="d-none d-sm-inline-block">Add New Category</span>
    </button>
  </div>

  <div class="card">
    <div class="card-header">
      <h5 class="card-title mb-0">Asset Category List</h5>
      {{-- Add Filters Here later if needed --}}
    </div>
    <div class="card-datatable table-responsive pt-0">
      <table class="datatables-asset-categories table table-bordered">
        <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Description</th>
          <th>Assets Count</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        {{-- DataTables will populate this --}}
        </tbody>
      </table>
    </div>
  </div>

  {{-- Offcanvas for Add/Edit Category --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="categoryOffcanvas" aria-labelledby="categoryOffcanvasLabel">
    <div class="offcanvas-header">
      <h5 id="categoryOffcanvasLabel" class="offcanvas-title">Add Category</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body mx-0 flex-grow-0">
      <form class="add-edit-category-form pt-0" id="categoryForm" onsubmit="return false;">
        @csrf
        <input type="hidden" name="_method" id="categoryMethod" value="POST">
        <input type="hidden" name="category_id" id="category_id" value="">

        {{-- Name --}}
        <div class="mb-3">
          <label class="form-label" for="categoryName">Category Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="categoryName" placeholder="e.g., IT Equipment, Furniture" name="name" required />
          <div class="invalid-feedback"></div>
        </div>

        {{-- Description --}}
        <div class="mb-3">
          <label class="form-label" for="categoryDescription">Description</label>
          <textarea class="form-control" id="categoryDescription" name="description" rows="4" placeholder="Brief description of the category..."></textarea>
          <div class="invalid-feedback"></div>
        </div>

        {{-- Is Active Switch --}}
        <div class="mb-4">
          <label class="switch switch-primary">
            <input type="checkbox" class="switch-input" id="is_active" name="is_active" value="1" checked> {{-- Default checked for new --}}
            <span class="switch-toggle-slider"><span class="switch-on"></span><span class="switch-off"></span></span>
            <span class="switch-label">Is Active?</span>
          </label>
          <input type="hidden" name="is_active" value="0"> {{-- Value when unchecked --}}
          <div class="invalid-feedback"></div>
        </div>

        {{-- General Error Message Area --}}
        <div class="mb-3">
          <small class="text-danger" id="general-error"></small>
        </div>


        <div class="mt-4">
          <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit" id="submitCategoryBtn">Submit</button>
          <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div> {{-- End Offcanvas --}}

@endsection
