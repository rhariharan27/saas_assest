@php
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', __('Addons'))
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/animate-css/animate.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('content')
  <div class="container-fluid"> {{-- Use container-fluid for better width usage --}}
    <div class="row mb-4 align-items-center">
      <div class="col-md-6">
        <h4 class="fw-bold mb-md-0 mb-2">{{ __('Addons') }}</h4> {{-- Adjusted margin for smaller screens --}}
      </div>
      <div class="col-md-6 text-md-end text-start"> {{-- Adjusted text alignment --}}
        {{-- Upload Button --}}
        <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#uploadSection">
          <i class="bx bx-plus me-1"></i> {{ __('Add New Addon') }}
        </button>
      </div>
    </div>

    {{-- Upload Form (Initially Collapsed) --}}
    <div class="collapse mb-4" id="uploadSection">
      <div class="card card-body shadow-sm border-0 rounded-4">
        <h5 class="mb-3 fw-semibold">{{ __('Upload New Addon') }}</h5>
        <form action="{{ route('module.upload') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="mb-3">
            <label for="moduleFile" class="form-label">{{ __('Select addon zip file') }}</label>
            <input type="file" name="module" id="moduleFile" class="form-control" accept=".zip" required>
          </div>
          <button type="submit" class="btn btn-primary">{{ __('Upload') }}</button>
        </form>
      </div>
    </div>

    {{-- Demo Mode Alert --}}
    @if(env('APP_DEMO'))
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Premium Addons: </strong> These are the premium addons that are not included in the standard version. You can purchase them from the respective links.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    {{-- Installed Addons List View (Table) --}}
    <h5 class="fw-bold mb-3">{{ __('Installed Addons') }}</h5>
    <div class="card shadow-sm border-0 rounded-4 mb-5">
      <div class="table-responsive text-nowrap rounded-4">
        <table class="table table-hover align-middle">
          <thead>
          <tr>
            <th>{{ __('Addon') }}</th>
            <th>{{ __('Version') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
          </thead>
          <tbody class="table-border-bottom-0">
          @forelse ($modules as $module)
            <tr>
              {{-- Addon Name & Description --}}
              <td>
                <div class="d-flex align-items-center">
                  <div class="me-3">
                    <i class="bx bx-category-alt bx-md text-primary"></i> {{-- Kept icon --}}
                  </div>
                  <div>
                    <h6 class="mb-0 fw-semibold">{{ __(ucwords(str_replace('-', ' ', $module->get('displayName') ?? $module->getName()))) }}</h6>
                    <small class="text-muted">{{ __($module->get('description', 'No description available.')) }}</small>
                  </div>
                </div>
              </td>
              {{-- Version --}}
              <td>{{ $module->get('version') ?? 'N/A' }}</td>
              {{-- Status --}}
              <td>
                <span class="badge bg-label-{{ $module->isEnabled() ? 'success' : 'danger' }}">{{ $module->isEnabled() ? __('Enabled') : __('Disabled') }}</span>
              </td>
              {{-- Actions --}}
              <td>
                <div class="d-flex gap-2 align-items-center">
                  {{-- Activate/Deactivate Button --}}
                  @if ($module->isEnabled())
                    <form action="{{ route('module.deactivate') }}" method="POST" class="mb-0">
                      @csrf
                      <input type="hidden" name="module" value="{{ $module->getName() }}">
                      <button type="submit" class="btn btn-xs btn-warning">
                        {{ __('Deactivate') }}
                      </button>
                    </form>
                  @else
                    <form action="{{ route('module.activate') }}" method="POST" class="mb-0">
                      @csrf
                      <input type="hidden" name="module" value="{{ $module->getName() }}">
                      <button type="submit" class="btn btn-xs btn-success">
                        {{ __('Activate') }}
                      </button>
                    </form>
                  @endif

                  {{-- Uninstall / Buy Now Button --}}
                  @if(!env('APP_DEMO'))
                    {{-- Uninstall Button --}}
                    <button type="button" class="btn btn-xs btn-icon uninstall-module" title="Uninstall" data-module="{{ $module->getName() }}">
                      <i class="bx bx-trash text-danger"></i>
                    </button>
                    <form id="uninstall-form-{{ $module->getName() }}" action="{{ route('module.uninstall') }}"
                          method="POST" class="d-none">
                      @csrf
                      @method('DELETE')
                      <input type="hidden" name="module" value="{{ $module->getName() }}">
                    </form>
                  @else
                    {{-- Buy now link --}}
                    <a href="{{ Constants::All_ADDONS_ARRAY[$module->getName()]['purchase_link'] ?? '#' }}" target="_blank" class="btn btn-xs btn-primary" title="Buy Now">
                      <i class="bx bx-cart"></i>
                    </a>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center">{{ __('No addons installed yet.') }}</td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>


    {{-- Explore More Addons Section --}}
    <h5 class="fw-bold mt-5">
      <i class="bx bx-compass me-2"></i>
      {{ __('Explore More Addons') }}
    </h5>
    <p class="text-muted mb-4">{{ __('Discover more addons to enhance your application.') }}</p>
    {{-- Addons List Group View --}}
    <div class="list-group rounded-4 shadow-sm border-0 mb-4">
      @php $hasAvailableAddons = false; @endphp
      @foreach (Constants::All_ADDONS_ARRAY as $addonKey => $addon)
        @if(Module::has($addonKey))
          @continue
        @endif
        @php $hasAvailableAddons = true; @endphp
        <div class="list-group-item list-group-item-action d-flex flex-column flex-sm-row justify-content-between align-items-sm-center p-3 gap-2">
          {{-- Name & Description --}}
          <div class="mb-2 mb-sm-0">
            <h6 class="mb-1 fw-semibold">{{ __($addon['name']) }}</h6>
            <small class="text-muted">{{ __($addon['description']) }}</small>
          </div>
          {{-- Buy Now Button --}}
          <div class="text-start text-sm-end">
            <a href="{{ $addon['purchase_link'] ?? '#' }}" target="_blank" class="btn btn-sm btn-primary">
              <i class="bx bx-cart me-1"></i> {{ __('Buy Now') }}
            </a>
          </div>
        </div>
      @endforeach
      @if(!$hasAvailableAddons)
        <div class="list-group-item text-center text-muted p-3">
          {{ __('All available addons from the list are already installed.') }}
        </div>
      @endif
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    // SweetAlert Confirmation for Uninstall (remains the same)
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.uninstall-module').forEach(button => {
        button.addEventListener('click', function () {
          const moduleName = this.getAttribute('data-module');
          const uninstallForm = document.getElementById(`uninstall-form-${moduleName}`);

          Swal.fire({
            title: window.__('Are you sure?'),
            text: window.__('You are about to uninstall the') + ` "${moduleName}" ` + window.__('module. This action cannot be undone!'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: window.__('Yes, uninstall it!'),
            cancelButtonText: window.__('Cancel'),
            customClass: {
              confirmButton: 'btn btn-danger me-3', // Changed to danger for uninstall
              cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
          }).then((result) => {
            if (result.isConfirmed) {
              uninstallForm.submit();
            }
          });
        });
      });
    });
  </script>
@endsection
