@extends('layouts.layoutMaster')

@section('title', __('Attendances'))

<!-- Vendor Styles -->
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss', // Added for date picker
  ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/moment/moment.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/flatpickr/flatpickr.js', // Added for date picker
  ])
@endsection

@section('page-script')
  <script>
    // Pass translations to JavaScript
    window.translations = {
      attendanceRecords: '{{ __("Attendance Records") }}',
      search: '{{ __("Search") }}',
      show: '{{ __("Show") }}',
      entries: '{{ __("entries") }}',
      selectEmployee: '{{ __("Select an Employee") }}',
      noDataAvailable: '{{ __("No data available in table") }}',
      info: '{{ __("Showing _START_ to _END_ of _TOTAL_ entries") }}',
      infoEmpty: '{{ __("Showing 0 to 0 of 0 entries") }}',
      firstPage: '{{ __("First Page") }}',
      previous: '{{ __("Previous") }}',
      next: '{{ __("Next") }}',
      lastPage: '{{ __("Last Page") }}'
    };
  </script>
  @vite([
    'resources/js/main-select2.js',
    'resources/assets/js/app/attendance-index.js',
  ])
@endsection

@section('content')
  <div class="container-fluid flex-grow-1 container-p-y"> {{-- Use container-fluid and standard padding --}}
    <h4 class="py-3 mb-4">
      <span class="text-muted fw-light"></span> {{ __('Attendances') }}
    </h4>

    <!-- Filter Section -->
    <div class="card mb-4">
      <div class="card-widget-separator-wrapper">
        <div class="card-body card-widget-separator">
          <div class="row gy-4 gy-sm-1">
            <div class="col-md-4 col-lg-4 col-sm-6 col-12">
              <label for="date" class="form-label">{{ __('Select Date') }}</label>
              <input type="text" id="date" name="date" class="form-control flatpickr-date" {{-- Use flatpickr class --}}
                     placeholder="YYYY-MM-DD" value="{{ request()->get('date', now()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-4 col-lg-4 col-sm-6 col-12">
              <label for="userId" class="form-label">{{ __('Select Employee') }}</label>
              <select id="userId" name="userId" class="form-select select2" data-allow-clear="true"> {{-- Added allow-clear --}}
                <option value="">{{ __('All Employees') }}</option>
                @foreach($users as $user)
                  <option
                    value="{{ $user->id }}" {{ request()->get('user') == $user->id ? 'selected' : '' }}>
                    {{ $user->code }} - {{ $user->getFullName() }}
                  </option>
                @endforeach
              </select>
            </div>
            {{-- Optional: Add more filters like Department if needed --}}
          </div>
        </div>
      </div>
    </div>


    <!-- Attendance Summary Table -->
    <div class="card">
      <div class="card-datatable table-responsive pt-0"> {{-- Added card-datatable and pt-0 --}}
        <table id="attendanceTable" class="table table-bordered"> {{-- Added table-bordered --}}
          <thead>
          <tr>
            <th>{{ __('ID') }}</th>
            <th>{{ __('Employee') }}</th>
            <th>{{ __('Shift') }}</th>
            <th>{{ __('First Check-in') }}</th>
            <th>{{ __('Last Check-out') }}</th>
            <th>{{ __('Duration') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Logs') }}</th> {{-- Renamed from Log Count --}}
            {{-- Add Actions column if needed --}}
            {{-- <th>Actions</th> --}}
          </tr>
          </thead>
          <tbody>
          {{-- DataTables will populate this --}}
          </tbody>
        </table>
      </div>
    </div>

  </div>

  {{-- Removed inline script, logic moved to attendance-index.js --}}
@endsection
