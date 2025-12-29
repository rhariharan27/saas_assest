@php
    use Modules\Assets\app\Enums\ApprovalStatus;
    use Modules\Assets\app\Enums\AssetStatus;
    use Modules\Assets\app\Enums\AssetCondition;
@endphp

<div class="row">
    <!-- Assignment Overview -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Assignment Overview</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td class="fw-medium text-muted">Assignment ID:</td>
                                <td>#{{ $assignment->id }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-muted">Employee:</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm me-2">
                                            <div class="avatar-initial bg-primary rounded">
                                                {{ substr($assignment->user->first_name, 0, 1) }}{{ substr($assignment->user->last_name, 0, 1) }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="fw-medium">{{ $assignment->user->getFullName() }}</div>
                                            <small class="text-muted">{{ $assignment->user->email }}</small>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-muted">Assigned By:</td>
                                <td>{{ $assignment->assignedBy->getFullName() }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-muted">Assignment Date:</td>
                                <td>{{ $assignment->assigned_at->format('M d, Y') }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-muted">Expected Return:</td>
                                <td>
                                    @if($assignment->expected_return_date)
                                        {{ $assignment->expected_return_date->format('M d, Y') }}
                                        @if($assignment->expected_return_date->isPast())
                                            <span class="badge bg-danger ms-1">Overdue</span>
                                        @endif
                                    @else
                                        <span class="text-muted">Not specified</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td class="fw-medium text-muted">Current Status:</td>
                                <td>{!! $assignment->getStatusBadgeAttribute() !!}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-muted">Days with Asset:</td>
                                <td>{{ $assignment->getDaysWithAsset() }} days</td>
                            </tr>
                            @if($assignment->isPendingEmployeeApproval())
                            <tr>
                                <td class="fw-medium text-muted">Days Pending:</td>
                                <td>
                                    <span class="fw-medium {{ $assignment->isOverdue() ? 'text-danger' : 'text-warning' }}">
                                        {{ $assignment->getDaysPendingApproval() }} days
                                    </span>
                                    @if($assignment->isOverdue())
                                        <span class="badge bg-danger ms-1">Overdue</span>
                                    @endif
                                </td>
                            </tr>
                            @endif
                            @if($assignment->condition_out)
                            <tr>
                                <td class="fw-medium text-muted">Condition Out:</td>
                                <td>
                                    <span class="badge bg-{{ $assignment->condition_out->color() }}">
                                        {{ $assignment->condition_out->label() }}
                                    </span>
                                </td>
                            </tr>
                            @endif
                            @if($assignment->condition_in)
                            <tr>
                                <td class="fw-medium text-muted">Condition In:</td>
                                <td>
                                    <span class="badge bg-{{ $assignment->condition_in->color() }}">
                                        {{ $assignment->condition_in->label() }}
                                    </span>
                                </td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Asset Information -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Asset Information</h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    @if($assignment->asset->image_url)
                        <img src="{{ $assignment->asset->image_url }}" alt="{{ $assignment->asset->name }}" class="img-fluid rounded" style="max-height: 120px;">
                    @else
                        <div class="avatar avatar-xl mx-auto">
                            <div class="avatar-initial bg-secondary rounded">
                                <i class="bx bx-package fs-2"></i>
                            </div>
                        </div>
                    @endif
                </div>
                <table class="table table-borderless table-sm">
                    <tr>
                        <td class="fw-medium text-muted">Asset Name:</td>
                        <td class="fw-medium">{{ $assignment->asset->name }}</td>
                    </tr>
                    <tr>
                        <td class="fw-medium text-muted">Asset Tag:</td>
                        <td><code>{{ $assignment->asset->asset_tag }}</code></td>
                    </tr>
                    <tr>
                        <td class="fw-medium text-muted">Category:</td>
                        <td>{{ $assignment->asset->category->name ?? 'Uncategorized' }}</td>
                    </tr>
                    @if($assignment->asset->model)
                    <tr>
                        <td class="fw-medium text-muted">Model:</td>
                        <td>{{ $assignment->asset->model }}</td>
                    </tr>
                    @endif
                    @if($assignment->asset->serial_number)
                    <tr>
                        <td class="fw-medium text-muted">Serial Number:</td>
                        <td><code>{{ $assignment->asset->serial_number }}</code></td>
                    </tr>
                    @endif
                    <tr>
                        <td class="fw-medium text-muted">Current Status:</td>
                        <td>
                            <span class="badge bg-{{ $assignment->asset->status->color() }}">
                                {{ $assignment->asset->status->label() }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-medium text-muted">Location:</td>
                        <td>{{ $assignment->asset->location ?? 'Not specified' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Approval Timeline -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">{{ __('Approval Timeline') }}</h6>
            </div>
            <div class="card-body">
                <div class="timeline timeline-advance">
                    <!-- Assignment Created -->
                    <div class="timeline-item">
                        <span class="timeline-indicator-advanced timeline-indicator-success">
                            <i class="bx bx-plus"></i>
                        </span>
                        <div class="timeline-event">
                            <div class="timeline-header">
                                <h6 class="mb-1">{{ __('Assignment Created') }}</h6>
                                <small class="text-muted">{{ $assignment->assigned_at->format('M d, Y H:i') }}</small>
                            </div>
                            <p class="mb-0">
                                Assignment created by {{ $assignment->assignedBy->getFullName() }}
                            </p>
                        </div>
                    </div>

                    <!-- Employee Response -->
                    @if($assignment->employee_responded_at)
                    <div class="timeline-item">
                        <span class="timeline-indicator-advanced timeline-indicator-{{ $assignment->employee_approval_status === ApprovalStatus::APPROVED ? 'success' : 'danger' }}">
                            <i class="bx bx-{{ $assignment->employee_approval_status === ApprovalStatus::APPROVED ? 'check' : 'x' }}"></i>
                        </span>
                        <div class="timeline-event">
                            <div class="timeline-header">
                                <h6 class="mb-1">Employee {{ $assignment->employee_approval_status->label() }}</h6>
                                <small class="text-muted">{{ $assignment->employee_responded_at->format('M d, Y H:i') }}</small>
                            </div>
                            <p class="mb-0">
                                {{ $assignment->user->getFullName() }} {{ strtolower($assignment->employee_approval_status->label()) }} the assignment
                                @if($assignment->employee_approval_notes)
                                    <br><em>"{{ $assignment->employee_approval_notes }}"</em>
                                @endif
                            </p>
                        </div>
                    </div>
                    @else
                    <div class="timeline-item">
                        <span class="timeline-indicator-advanced timeline-indicator-warning">
                            <i class="bx bx-time"></i>
                        </span>
                        <div class="timeline-event">
                            <div class="timeline-header">
                                <h6 class="mb-1">Awaiting Employee Response</h6>
                                <small class="text-muted">Pending since {{ $assignment->assigned_at->diffForHumans() }}</small>
                            </div>
                            <p class="mb-0">
                                Waiting for {{ $assignment->user->getFullName() }} to accept or reject the assignment
                                @if($assignment->isOverdue())
                                    <br><span class="text-danger"><strong>This assignment is overdue!</strong></span>
                                @endif
                            </p>
                        </div>
                    </div>
                    @endif

                    <!-- Return Request -->
                    @if($assignment->return_requested)
                    <div class="timeline-item">
                        <span class="timeline-indicator-advanced timeline-indicator-info">
                            <i class="bx bx-undo"></i>
                        </span>
                        <div class="timeline-event">
                            <div class="timeline-header">
                                <h6 class="mb-1">Return Requested</h6>
                                <small class="text-muted">{{ $assignment->return_requested_at->format('M d, Y H:i') }}</small>
                            </div>
                            <p class="mb-0">
                                {{ $assignment->user->getFullName() }} requested to return the asset
                                @if($assignment->return_request_notes)
                                    <br><em>"{{ $assignment->return_request_notes }}"</em>
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Return Response -->
                    @if($assignment->return_approved_at)
                    <div class="timeline-item">
                        <span class="timeline-indicator-advanced timeline-indicator-{{ $assignment->return_approval_status === ApprovalStatus::APPROVED ? 'success' : 'danger' }}">
                            <i class="bx bx-{{ $assignment->return_approval_status === ApprovalStatus::APPROVED ? 'check' : 'x' }}"></i>
                        </span>
                        <div class="timeline-event">
                            <div class="timeline-header">
                                <h6 class="mb-1">Return {{ $assignment->return_approval_status->label() }}</h6>
                                <small class="text-muted">{{ $assignment->return_approved_at->format('M d, Y H:i') }}</small>
                            </div>
                            <p class="mb-0">
                                Return request {{ strtolower($assignment->return_approval_status->label()) }} by {{ $assignment->returnApprovedBy->getFullName() }}
                                @if($assignment->return_approval_notes)
                                    <br><em>"{{ $assignment->return_approval_notes }}"</em>
                                @endif
                            </p>
                        </div>
                    </div>
                    @else
                    <div class="timeline-item">
                        <span class="timeline-indicator-advanced timeline-indicator-warning">
                            <i class="bx bx-time"></i>
                        </span>
                        <div class="timeline-event">
                            <div class="timeline-header">
                                <h6 class="mb-1">Awaiting Admin Response</h6>
                                <small class="text-muted">Pending since {{ $assignment->return_requested_at->diffForHumans() }}</small>
                            </div>
                            <p class="mb-0">
                                Return request awaiting admin approval
                                @if($assignment->getDaysPendingReturn() > 3)
                                    <br><span class="text-warning"><strong>High priority - {{ $assignment->getDaysPendingReturn() }} days pending</strong></span>
                                @endif
                            </p>
                        </div>
                    </div>
                    @endif
                    @endif

                    <!-- Asset Returned -->
                    @if($assignment->returned_at)
                    <div class="timeline-item">
                        <span class="timeline-indicator-advanced timeline-indicator-success">
                            <i class="bx bx-check-circle"></i>
                        </span>
                        <div class="timeline-event">
                            <div class="timeline-header">
                                <h6 class="mb-1">Asset Returned</h6>
                                <small class="text-muted">{{ $assignment->returned_at->format('M d, Y H:i') }}</small>
                            </div>
                            <p class="mb-0">
                                Asset returned and received by {{ $assignment->receivedBy->getFullName() }}
                                @if($assignment->condition_in)
                                    <br>Return condition: <span class="badge bg-{{ $assignment->condition_in->color() }}">{{ $assignment->condition_in->label() }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Notes and Comments -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Notes and Comments</h6>
            </div>
            <div class="card-body">
                @if($assignment->notes)
                    <div class="alert alert-info">
                        <h6 class="alert-heading">Assignment Notes:</h6>
                        <p class="mb-0">{{ $assignment->notes }}</p>
                    </div>
                @endif

                @if($assignment->employee_approval_notes)
                    <div class="alert alert-{{ $assignment->employee_approval_status === ApprovalStatus::APPROVED ? 'success' : 'warning' }}">
                        <h6 class="alert-heading">Employee Response Notes:</h6>
                        <p class="mb-0">{{ $assignment->employee_approval_notes }}</p>
                    </div>
                @endif

                @if($assignment->return_request_notes)
                    <div class="alert alert-secondary">
                        <h6 class="alert-heading">Return Request Reason:</h6>
                        <p class="mb-0">{{ $assignment->return_request_notes }}</p>
                    </div>
                @endif

                @if($assignment->return_approval_notes)
                    <div class="alert alert-{{ $assignment->return_approval_status === ApprovalStatus::APPROVED ? 'success' : 'warning' }}">
                        <h6 class="alert-heading">Admin Return Response:</h6>
                        <p class="mb-0">{{ $assignment->return_approval_notes }}</p>
                    </div>
                @endif

                @if(!$assignment->notes && !$assignment->employee_approval_notes && !$assignment->return_request_notes && !$assignment->return_approval_notes)
                    <p class="text-muted text-center">No additional notes or comments for this assignment.</p>
                @endif
            </div>
        </div>
    </div>
</div>