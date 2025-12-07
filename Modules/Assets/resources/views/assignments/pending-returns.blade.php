@php
    use Modules\Assets\app\Enums\ApprovalStatus;
@endphp
@extends('layouts.layoutMaster')

@section('title', 'Pending Return Requests')

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
        .urgent-return {
            background-color: #fff8e1 !important;
        }
        .priority-high {
            color: #d32f2f;
            font-weight: 600;
        }
        .priority-normal {
            color: #1976d2;
        }
        .return-reason {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .employee-info {
            display: flex;
            align-items: center;
            gap: 8px;
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
        const pendingReturnsAjaxUrl = "{{ route('tenant.assignments.pendingReturns') }}";
        const respondToReturnUrl = "{{ route('tenant.assignments.respondReturn', ':id') }}";
        const assignmentDetailsUrl = "{{ route('tenant.assignments.details', ':id') }}";
        const csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/app/pending-returns.js'])
@endsection

@section('content')
    <div class="container-fluid flex-grow-1 container-p-y">

        <div class="row mb-4">
            <div class="col-12">
                <h4 class="py-3 mb-0">
                    <span class="text-muted fw-light">Asset Management /</span> Pending Return Requests
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
                                    <p class="text-muted">Return Requests</p>
                                </div>
                                <div class="chart-statistics">
                                    <h3 class="card-title mb-1" id="totalPendingReturns">0</h3>
                                </div>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-warning rounded">
                                    <i class="bx bx-undo fs-4"></i>
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
                                    <h5 class="mb-1 text-nowrap">Urgent</h5>
                                    <p class="text-muted">Older than 3 Days</p>
                                </div>
                                <div class="chart-statistics">
                                    <h3 class="card-title mb-1 text-danger" id="urgentReturns">0</h3>
                                </div>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-danger rounded">
                                    <i class="bx bx-error-circle fs-4"></i>
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
                                    <h5 class="mb-1 text-nowrap">Today</h5>
                                    <p class="text-muted">New Requests</p>
                                </div>
                                <div class="chart-statistics">
                                    <h3 class="card-title mb-1" id="todayRequests">0</h3>
                                </div>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-info rounded">
                                    <i class="bx bx-calendar-check fs-4"></i>
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
                                    <p class="text-muted">Time in Hours</p>
                                </div>
                                <div class="chart-statistics">
                                    <h3 class="card-title mb-1" id="avgResponseTime">0</h3>
                                </div>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-success rounded">
                                    <i class="bx bx-time-five fs-4"></i>
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
                        <label for="filter_priority" class="form-label">Priority</label>
                        <select id="filter_priority" class="form-select">
                            <option value="">All Priorities</option>
                            <option value="urgent">Urgent (3+ days)</option>
                            <option value="normal">Normal</option>
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

        <!-- Pending Returns Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Pending Return Requests</h5>
                <div>
                    <button type="button" class="btn btn-success btn-sm" id="bulkApproveBtn" disabled>
                        <i class="bx bx-check me-1"></i>Bulk Approve
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm ms-2" id="exportBtn">
                        <i class="bx bx-download me-1"></i>Export
                    </button>
                </div>
            </div>
            <div class="card-datatable table-responsive">
                <table class="datatables-pending-returns table table-bordered">
                    <thead>
                        <tr>
                            <th class="no-sort">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th>Employee</th>
                            <th>Asset</th>
                            <th>Category</th>
                            <th>Request Date</th>
                            <th>Days Pending</th>
                            <th>Return Reason</th>
                            <th>Priority</th>
                            <th class="no-sort">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- DataTables will populate this -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Return Request Details Modal -->
        <div class="modal fade" id="returnDetailsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Return Request Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="returnDetailsContent">
                            <!-- Content will be loaded via AJAX -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Return Response Modal -->
        <div class="modal fade" id="returnResponseModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Respond to Return Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="returnResponseForm" onsubmit="return false;">
                        @csrf
                        <input type="hidden" id="responseAssignmentId" name="assignment_id">
                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <div class="avatar avatar-xl mx-auto mb-3">
                                    <div class="avatar-initial bg-warning rounded">
                                        <i class="bx bx-undo fs-2"></i>
                                    </div>
                                </div>
                                <h6 class="mb-1" id="responseEmployeeName">Employee Name</h6>
                                <p class="text-muted mb-0" id="responseAssetInfo">Asset Information</p>
                            </div>

                            <div class="alert alert-info">
                                <h6 class="alert-heading mb-2">Return Reason:</h6>
                                <p class="mb-0" id="returnReasonText">Reason will be shown here</p>
                            </div>

                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label">Response <span class="text-danger">*</span></label>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="response" id="approveReturn" value="approve" required>
                                                <label class="form-check-label text-success" for="approveReturn">
                                                    <i class="bx bx-check-circle me-1"></i>Approve Return
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="response" id="rejectReturn" value="reject" required>
                                                <label class="form-check-label text-danger" for="rejectReturn">
                                                    <i class="bx bx-x-circle me-1"></i>Reject Return
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="responseNotes" class="form-label">Admin Notes</label>
                                    <textarea class="form-control" id="responseNotes" name="notes" rows="3" placeholder="Optional notes about your decision..."></textarea>
                                </div>
                            </div>

                            <div class="mb-3">
                                <small class="text-danger" id="response-general-error"></small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="submitResponseBtn">Submit Response</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Bulk Approve Confirmation Modal -->
        <div class="modal fade" id="bulkApproveModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Bulk Approve Returns</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <div class="avatar avatar-xl mx-auto mb-3">
                                <div class="avatar-initial bg-success rounded">
                                    <i class="bx bx-check-double fs-2"></i>
                                </div>
                            </div>
                            <h6 class="mb-1">Approve Multiple Returns</h6>
                            <p class="text-muted mb-0">You are about to approve <span id="bulkCount">0</span> return requests</p>
                        </div>

                        <div class="alert alert-warning">
                            <i class="bx bx-info-circle me-2"></i>
                            This action will approve all selected return requests. Employees will be notified and can proceed with returning their assets.
                        </div>

                        <div class="form-group">
                            <label for="bulkNotes" class="form-label">Bulk Approval Notes (Optional)</label>
                            <textarea class="form-control" id="bulkNotes" rows="2" placeholder="Notes to be added to all approved requests..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" id="confirmBulkApprove">
                            <i class="bx bx-check me-1"></i>Approve All Selected
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection