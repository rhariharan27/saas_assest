@extends('layouts/layoutMaster')

@section('title', __('Visits'))

<!-- Vendor Styles -->
@section('vendor-style')
  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
 'resources/assets/vendor/libs/@form-validation/form-validation.scss',
 'resources/assets/vendor/libs/animate-css/animate.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
   'resources/assets/vendor/libs/@form-validation/popular.js',
    'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
    'resources/assets/vendor/libs/@form-validation/auto-focus.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
  <script>
    // Pass translations to JavaScript
    window.translations = {
      searchVisits: '{{ __("Search Visits") }}',
      noDataAvailable: '{{ __("No data available in table") }}',
      displayingEntries: '{{ __("Displaying") }}',
      showEntries: '{{ __("Show") }}',
      entries: '{{ __("entries") }}',
      info: '{{ __("Displaying _START_ to _END_ of _TOTAL_ entries") }}',
      next: '{{ __("Next") }}',
      previous: '{{ __("Previous") }}',
      firstPage: '{{ __("First Page") }}',
      lastPage: '{{ __("Last Page") }}'
    };
  </script>
  @vite(['resources/assets/js/app/visits-index.js'])
@endsection


@section('content')
  <div class="row">
    <div class="col">
      <h4>@lang('Visits')</h4>
    </div>
  </div>
  <!--Date Filter -->
  <div class="mb-4">
    <label for="dateFilter" class="form-label">{{ __('Filter by date') }}</label>
    <input type="date" id="dateFilter" name="dateFilter" class="form-control w-auto">
  </div>
  <!-- Visits table card -->
  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="datatables-visits table border-top">
        <thead>
        <tr>
          <th>@lang('')</th>
          <th>{{ __('Sl.No') }}</th>
          <th>{{ __('User') }}</th>
          <th>{{ __('Client') }}</th>
          <th>{{ __('Created At') }}</th>
          <th>{{ __('Image') }}</th>
          <th>{{ __('Actions') }}</th>
        </tr>
        </thead>
      </table>
    </div>
  </div>
  @include('_partials._modals.visit.show_visit_details')
@endsection
