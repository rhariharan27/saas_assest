@extends('layouts/layoutMaster')

@section('title', 'Users List')

<!-- Vendor Styles -->
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/@form-validation/form-validation.scss',
    'resources/assets/vendor/libs/animate-css/animate.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/moment/moment.js',
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/@form-validation/popular.js',
    'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
    'resources/assets/vendor/libs/@form-validation/auto-focus.js',
    'resources/assets/vendor/libs/cleavejs/cleave.js',
    'resources/assets/vendor/libs/cleavejs/cleave-phone.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

<!-- Page Scripts -->
@section('page-script')
  @vite(['resources/js/main-helper.js'])
  @vite(['resources/assets/js/app/user-index.js'])
@endsection

@section('content')

  <div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">{{ __('Users') }}</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{$totalUser}}</h4>
                <p class="text-success mb-0">(100%)</p>
              </div>
              <small class="mb-0">{{ __('Total Users') }}</small>
            </div>
            <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="bx bx-user bx-lg"></i>
            </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">{{ __('Verified Users') }}</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{$verified}}</h4>
                <p class="text-success mb-0">(+95%)</p>
              </div>
              <small class="mb-0">{{ __('Recent analytics') }}</small>
            </div>
            <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
              <i class="bx bx-user-check bx-lg"></i>
            </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">{{ __('Duplicate Users') }}</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{$userDuplicates}}</h4>
                <p class="text-success mb-0">(0%)</p>
              </div>
              <small class="mb-0">{{ __('Recent analytics') }}</small>
            </div>
            <div class="avatar">
            <span class="avatar-initial rounded bg-label-danger">
              <i class="bx bx-group bx-lg"></i>
            </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">{{ __('Verification Pending') }}</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{$notVerified}}</h4>
                <p class="text-danger mb-0">(+6%)</p>
              </div>
              <small class="mb-0">{{ __('Recent analytics') }}</small>
            </div>
            <div class="avatar">
            <span class="avatar-initial rounded bg-label-warning">
              <i class="bx bx-user-voice bx-lg"></i>
            </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Users List Table -->
  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="datatables-users table border-top">
        <thead>
        <tr>
          <th></th>
          <th>{{ __('Id') }}</th>
          <th>{{ __('User') }}</th>
          <th>{{ __('Email') }}</th>
          <th>{{ __('Verified') }}</th>
          <th>{{ __('Actions') }}</th>
        </tr>
        </thead>
      </table>
    </div>
  </div>
  @include('_partials._modals.account.add_new_user')
@endsection
