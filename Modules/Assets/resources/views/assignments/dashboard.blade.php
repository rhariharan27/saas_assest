@php
    use Modules\Assets\app\Enums\ApprovalStatus;
@endphp
@extends('layouts.layoutMaster')

@section('title', 'Approval Workflow Dashboard')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
        'resources/assets/vendor/libs/apex-charts/apexcharts.js',
    ])
@endsection

@section('page-style')
    <style>
        .dashboard-card {
            transition: transform 0.2s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 25px 0 rgba(0, 0, 0, 0.1);
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .status-dot.pending { background-color: #ff9f43; }
        .status-dot.overdue { background-color: #ea5455; }
        .status-dot.urgent { background-color: #dc3545; }
        .status-dot.normal { background-color: #28c76f; }
        .quick-action-btn {
            border: 1px dashed #ddd;
            transition: all 0.2s ease;
        }
        .quick-action-btn:hover {
            border-color: #7367f0;
            background-color: #f8f7ff;
        }
        .recent-activity {
            max-height: 400px;
            overflow-y: auto;
        }
        .activity-item {
            border-left: 3px solid #e3e6ea;
            padding-left: 15px;
            margin-bottom: 15px;
            position: relative;
        }
        .activity-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 8px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #7367f0;
        }
        .activity-item.overdue {
            border-left-color: #ea5455;
        }
        .activity-item.overdue::before {
            background-color: #ea5455;
        }
    </style>
@endsection

@section('page-script')
    <script>
        const dashboardDataUrl = "{{ route('tenant.assignments.dashboardData') }}";
        const pendingApprovalsUrl = "{{ route('tenant.assignments.pendingApproval') }}";
        const pendingReturnsUrl = "{{ route('tenant.assignments.pendingReturns') }}";
        const quickStatsUrl = "{{ route('tenant.assignments.quickStats') }}";
        const csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/app/approval-dashboard.js'])
@endsection

@section('content')
    <div class="container-fluid flex-grow-1 container-p-y">

        <div class="row mb-4">
            <div class="col-12">
                <h4 class="py-3 mb-0">
                    <span class="text-muted fw-light">Asset Management /</span> Approval Workflow Dashboard
                </h4>
                <p class="text-muted">Monitor and manage asset assignment approvals and return requests</p>
            </div>
        </div>

        <!-- Overview Stats -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="d-flex flex-column">
                                <div class="card-title mb-auto">
                                    <h5 class="mb-1 text-nowrap">Pending Approvals</h5>
                                    <p class="text-muted">Employee Responses</p>
                                </div>
                                <div class="chart-statistics">
                                    <h3 class="card-title mb-1" id="totalPendingApprovals">0</h3>
                                    <small class="text-muted">
                                        <span class="status-dot overdue"></span>
                                        <span id="overdueApprovals">0</span> overdue
                                    </small>
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
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="d-flex flex-column">
                                <div class="card-title mb-auto">
                                    <h5 class="mb-1 text-nowrap">Return Requests</h5>
                                    <p class="text-muted">Admin Actions Needed</p>
                                </div>
                                <div class="chart-statistics">
                                    <h3 class="card-title mb-1" id="totalPendingReturns">0</h3>
                                    <small class="text-muted">
                                        <span class="status-dot urgent"></span>
                                        <span id="urgentReturns">0</span> urgent
                                    </small>
                                </div>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-info rounded">
                                    <i class="bx bx-undo fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="d-flex flex-column">
                                <div class="card-title mb-auto">
                                    <h5 class="mb-1 text-nowrap">Active Assets</h5>
                                    <p class="text-muted">Currently Assigned</p>
                                </div>
                                <div class="chart-statistics">
                                    <h3 class="card-title mb-1" id="totalActiveAssets">0</h3>
                                    <small class="text-muted">
                                        <span class="status-dot normal"></span>
                                        Approved assignments
                                    </small>
                                </div>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-success rounded">
                                    <i class="bx bx-check-shield fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="d-flex flex-column">
                                <div class="card-title mb-auto">
                                    <h5 class="mb-1 text-nowrap">Response Rate</h5>
                                    <p class="text-muted">Last 30 Days</p>
                                </div>
                                <div class="chart-statistics">
                                    <h3 class="card-title mb-1" id="responseRate">0%</h3>
                                    <small class="text-muted">
                                        Avg response time: <span id="avgResponseTime">0</span>h
                                    </small>
                                </div>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-primary rounded">
                                    <i class="bx bx-trending-up fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                <a href="{{ route('tenant.assignments.pendingApprovalsView') }}" class="btn quick-action-btn w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                    <i class="bx bx-time fs-1 text-warning mb-2"></i>
                                    <span class="fw-medium">Pending Approvals</span>
                                    <small class="text-muted">Review employee responses</small>
                                </a>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                <a href="{{ route('tenant.assignments.pendingReturnsView') }}" class="btn quick-action-btn w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                    <i class="bx bx-undo fs-1 text-info mb-2"></i>
                                    <span class="fw-medium">Return Requests</span>
                                    <small class="text-muted">Approve/reject returns</small>
                                </a>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                <a href="{{ route('assets.index') }}" class="btn quick-action-btn w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                    <i class="bx bx-plus fs-1 text-primary mb-2"></i>
                                    <span class="fw-medium">New Assignment</span>
                                    <small class="text-muted">Assign asset to employee</small>
                                </a>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                <button type="button" class="btn quick-action-btn w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3" id="sendBulkReminders">
                                    <i class="bx bx-bell fs-1 text-secondary mb-2"></i>
                                    <span class="fw-medium">Send Reminders</span>
                                    <small class="text-muted">Notify overdue employees</small>
                                </button>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                <button type="button" class="btn quick-action-btn w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3" id="generateReport">
                                    <i class="bx bx-chart fs-1 text-success mb-2"></i>
                                    <span class="fw-medium">Generate Report</span>
                                    <small class="text-muted">Export analytics</small>
                                </button>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                <a href="{{ route('assetCategories.index') }}" class="btn quick-action-btn w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                    <i class="bx bx-cog fs-1 text-muted mb-2"></i>
                                    <span class="fw-medium">Settings</span>
                                    <small class="text-muted">Manage categories</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Recent Activity -->
        <div class="row mb-4">
            <!-- Approval Trends Chart -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h5 class="card-title mb-0">Approval Trends</h5>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Last 30 Days
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" data-period="7">Last 7 Days</a></li>
                                <li><a class="dropdown-item" href="#" data-period="30">Last 30 Days</a></li>
                                <li><a class="dropdown-item" href="#" data-period="90">Last 90 Days</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="approvalTrendsChart"></div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="recent-activity" id="recentActivityList">
                            <!-- Activity items will be loaded here -->
                        </div>
                        <div class="text-center">
                            <a href="#" class="btn btn-outline-primary btn-sm" id="viewAllActivity">View All Activity</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Tables -->
        <div class="row">
            <!-- Recent Pending Approvals -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Recent Pending Approvals</h5>
                        <a href="{{ route('tenant.assignments.pendingApprovalsView') }}" class="btn btn-outline-primary btn-sm">
                            View All
                        </a>
                    </div>
                    <div class="card-datatable table-responsive">
                        <table class="table table-sm" id="recentPendingTable">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Asset</th>
                                    <th>Days</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Will be populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Return Requests -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Recent Return Requests</h5>
                        <a href="{{ route('tenant.assignments.pendingReturnsView') }}" class="btn btn-outline-primary btn-sm">
                            View All
                        </a>
                    </div>
                    <div class="card-datatable table-responsive">
                        <table class="table table-sm" id="recentReturnsTable">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Asset</th>
                                    <th>Reason</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Will be populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Response Modal -->
        <div class="modal fade" id="quickResponseModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Quick Response</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <div class="avatar avatar-xl mx-auto mb-3">
                                <div class="avatar-initial bg-primary rounded">
                                    <i class="bx bx-user fs-2"></i>
                                </div>
                            </div>
                            <h6 class="mb-1" id="quickResponseEmployee">Employee Name</h6>
                            <p class="text-muted mb-0" id="quickResponseAsset">Asset Information</p>
                        </div>
                        
                        <div class="row g-2">
                            <div class="col-6">
                                <button type="button" class="btn btn-success w-100" id="quickApproveBtn">
                                    <i class="bx bx-check me-1"></i>Approve
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-danger w-100" id="quickRejectBtn">
                                    <i class="bx bx-x me-1"></i>Reject
                                </button>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <textarea class="form-control" id="quickResponseNotes" rows="2" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection