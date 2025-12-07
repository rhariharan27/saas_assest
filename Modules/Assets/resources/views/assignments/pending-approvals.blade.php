@php
    use Modules\Assets\app\Enums\ApprovalStatus;
@endphp
@extends('layouts.layoutMaster')

@section('title', 'Pending Assignment Approvals')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js',
    ])
@endsection

@section('page-style')
    <style>
        .overdue-row {
            background-color: #fff5f5 !important;
        }
        .status-badge {
            font-size: 0.75rem;
        }
        .days-pending {
            font-weight: 600;
        }
        .days-pending.overdue {
            color: #dc3545;
        }
        .employee-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
@endsection

@section('page-script')
    <script>
        const pendingApprovalsAjaxUrl = "{{ route('tenant.assignments.pendingApproval') }}";
        const resendNotificationUrl = "{{ route('tenant.assignments.sendReminder', ':id') }}";
        const assignmentDetailsUrl = "{{ route('tenant.assignments.details', ':id') }}";
        const cancelAssignmentUrl = "{{ route('tenant.assignments.cancel', ':id') }}";
        const updateAssignmentUrl = "{{ route('tenant.assignments.update', ':id') }}";
        const bulkRemindUrl = "{{ route('tenant.assignments.bulkRemind') }}";
        const exportUrl = "{{ route('tenant.assignments.exportPendingApprovals') }}";
        const csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/app/pending-approvals.js'])
@endsection

@section('content')
    <div class="container-fluid flex-grow-1 container-p-y">

        <div class="row mb-4">
            <div class="col-12">
                <h4 class="py-3 mb-0">
                    <span class="text-muted fw-light">Asset Management /</span> Pending Employee Approvals
                </h4>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="d-flex flex-column">
                                <div class="card-title mb-auto">
                                    <h5 class="mb-1 text-nowrap">Total Pending</h5>
                                    <p class="text-muted">Awaiting Response</p>
                                </div>
                                <div class="chart-statistics">
                                    <h3 class="card-title mb-1" id="totalPending">0</h3>
                                </div>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-warning rounded">
                                    <i class="bx bx-time fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="d-flex flex-column">
                                <div class="card-title mb-auto">
                                    <h5 class="mb-1 text-nowrap">Overdue</h5>
                                    <p class="text-muted">Beyond 7 Days</p>
                                </div>
                                <div class="chart-statistics">
                                    <h3 class="card-title mb-1 text-danger" id="totalOverdue">0</h3>
                                </div>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-danger rounded">
                                    <i class="bx bx-error fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="d-flex flex-column">
                                <div class="card-title mb-auto">
                                    <h5 class="mb-1 text-nowrap">This Week</h5>
                                    <p class="text-muted">New Assignments</p>
                                </div>
                                <div class="chart-statistics">
                                    <h3 class="card-title mb-1" id="thisWeek">0</h3>
                                </div>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-info rounded">
                                    <i class="bx bx-calendar fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="d-flex flex-column">
                                <div class="card-title mb-auto">
                                    <h5 class="mb-1 text-nowrap">Avg Response</h5>
                                    <p class="text-muted">Time in Days</p>
                                </div>
                                <div class="chart-statistics">
                                    <h3 class="card-title mb-1" id="avgResponse">0</h3>
                                </div>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-success rounded">
                                    <i class="bx bx-trending-up fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row gy-3">
                    <div class="col-md-3">
                        <label for="filter_employee" class="form-label">Employee</label>
                        <select id="filter_employee" class="form-select select2" data-allow-clear="true">
                            <option value="">All Employees</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->first_name }} {{ $user->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter_category" class="form-label">Asset Category</label>
                        <select id="filter_category" class="form-select select2" data-allow-clear="true">
                            <option value="">All Categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter_overdue" class="form-label">Overdue Status</label>
                        <select id="filter_overdue" class="form-select">
                            <option value="">All Assignments</option>
                            <option value="overdue">Overdue Only</option>
                            <option value="normal">Normal Only</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-primary me-2" id="refreshTable">
                            <i class="bx bx-refresh me-1"></i>Refresh
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="clearFilters">
                            <i class="bx bx-x me-1"></i>Clear
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Approvals Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Pending Assignment Approvals</h5>
                <div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="bulkRemindBtn" disabled>
                        <i class="bx bx-bell me-1"></i>Send Reminders
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm ms-2" id="exportBtn">
                        <i class="bx bx-download me-1"></i>Export
                    </button>
                </div>
            </div>
            <div class="card-datatable table-responsive">
                <table class="datatables-pending-approvals table table-bordered">
                    <thead>
                        <tr>
                            <th class="no-sort">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th>Employee</th>
                            <th>Asset</th>
                            <th>Category</th>
                            <th>Assigned By</th>
                            <th>Assigned Date</th>
                            <th>Expected Return</th>
                            <th>Days Pending</th>
                            <th>Status</th>
                            <th class="no-sort">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- DataTables will populate this -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions Modal -->
        <div class="modal fade" id="quickActionModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Quick Actions</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="actionAssignmentId">
                        <div class="text-center mb-4">
                            <div class="avatar avatar-xl mx-auto mb-3">
                                <div class="avatar-initial bg-primary rounded">
                                    <i class="bx bx-user fs-2"></i>
                                </div>
                            </div>
                            <h6 class="mb-1" id="actionEmployeeName">Employee Name</h6>
                            <p class="text-muted mb-0" id="actionAssetInfo">Asset Information</p>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-info w-100" id="sendReminderBtn">
                                    <i class="bx bx-bell me-2"></i>
                                    Send Reminder
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-primary w-100" id="viewDetailsBtn">
                                    <i class="bx bx-show me-2"></i>
                                    View Details
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-warning w-100" id="editAssignmentBtn">
                                    <i class="bx bx-edit me-2"></i>
                                    Edit Assignment
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-danger w-100" id="cancelAssignmentBtn">
                                    <i class="bx bx-x me-2"></i>
                                    Cancel Assignment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignment Details Modal -->
        <div class="modal fade" id="assignmentDetailsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Assignment Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="assignmentDetailsContent">
                            <!-- Content will be loaded via AJAX -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection