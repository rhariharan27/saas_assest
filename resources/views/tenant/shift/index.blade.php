@php
  // $configData = Helper::appClasses(); // Only needed if layoutMaster requires it
@endphp

@extends('layouts.layoutMaster')

@section('title', __('Shifts Management'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', // Keep if using export buttons
    'resources/assets/vendor/libs/animate-css/animate.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss', // For time pickers in modal
    'resources/assets/vendor/libs/select2/select2.scss' // Needed if adding filters later
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    'resources/assets/vendor/libs/flatpickr/flatpickr.js', // For time pickers in modal
    'resources/assets/vendor/libs/select2/select2.js' // Needed if adding filters later
  ])
@endsection

@section('page-style')
  <style>
    /* Ensure toggle switches align nicely in table cells */
    .datatables-shifts .form-check.form-switch {
      display: flex;
      justify-content: center;
    }

    /* Adjust action button size if needed */
    .datatables-shifts .btn-icon.btn-sm {
      /* padding: 0.2rem 0.5rem; */
      /* font-size: 0.8rem; */
    }

    /* Ensure Select2 dropdown appears over offcanvas */
    .select2-container--open {
      z-index: 1090;
    }
  </style>
@endsection

@section('page-script')
  <script>
    // Pass URLs and CSRF token using standardized route names
    const shiftListAjaxUrl = "{{ route('shifts.listAjax') }}"
    const shiftStoreUrl = "{{ route('shifts.store') }}"
    const shiftBaseUrl = "{{ url('shifts') }}" // Base URL for /shifts/{id}/edit, /shifts/{id} (PUT/DELETE), /shifts/{id}/toggle-status
    const csrfToken = "{{ csrf_token() }}"
    // Pass translations to JavaScript
    window.translations = {
      searchShifts: '{{ __("Search Shifts") }}',
      addNewShift: '{{ __("Add New Shift") }}',
      editShift: '{{ __("Edit Shift") }}',
      loadingShiftData: '{{ __("Loading Shift Data...") }}',
      operationFailed: '{{ __("Operation failed.") }}',
      updateFailed: '{{ __("Update failed.") }}',
      cannotDeleteShift: '{{ __("Cannot delete shift: It is currently assigned to users.") }}',
      areYouSure: '{{ __("Are you sure?") }}',
      deleteConfirmation: '{{ __("Delete this shift?") }}',
      yesDeleteIt: '{{ __("Yes, delete it!") }}',
      submit: '{{ __("Submit") }}',
      update: '{{ __("Update") }}',
      processing: '{{ __("Processing") }}'
    };
  </script>
  @vite(['resources/assets/js/app/shift-index.js']) {{-- Link to the refactored JS file --}}
@endsection


@section('content')
  <div class="container-fluid flex-grow-1 container-p-y"> {{-- Added container --}}

    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
      <h4 class="mb-0">{{ __('Shifts Management') }}</h4>
      {{-- Add Button --}}
      <button type="button" class="btn btn-primary add-new" data-bs-toggle="offcanvas"
              data-bs-target="#offcanvasAddOrUpdateShift">
        <i class="bx bx-plus bx-sm me-0 me-sm-1"></i><span
          class="d-none d-sm-inline-block">{{ __('Add New Shift') }}</span>
      </button>
    </div>

    {{-- Optional Filters Card - Add later if needed --}}
    {{-- <div class="card mb-4"> ... Filters ... </div> --}}

    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">{{ __('Shift List') }}</h5>
      </div>
      <div class="card-datatable table-responsive pt-0">
        <table class="datatables-shifts table table-bordered"> {{-- Use specific class --}}
          <thead>
          <tr>
            {{-- Remove empty first column unless using DataTables responsive control --}}
            {{-- <th></th> --}}
            <th>{{ __('Id') }}</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Code') }}</th>
            <th>{{ __('Shift Days') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
          </thead>
          <tbody>
          {{-- DataTables Server-Side will populate this --}}
          </tbody>
        </table>
      </div>
    </div>

  </div>

  {{-- Include the Offcanvas partial --}}
  @include('_partials._modals.shift.add_or_update_shift') {{-- Ensure path is correct --}}

@endsection
