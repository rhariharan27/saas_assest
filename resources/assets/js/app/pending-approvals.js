/**
 * Pending Approvals JavaScript
 * Handles the pending approvals page functionality
 */

'use strict';

$(function() {
    let pendingApprovalsTable;
    let selectedAssignments = [];

    // Initialize page
    initializePage();
    setupEventListeners();
    initializeDataTable();
    loadStats();

    /**
     * Initialize page components
     */
    function initializePage() {
        // Initialize Select2
        $('.select2').select2({
            allowClear: true,
            placeholder: function() {
                return $(this).data('placeholder') || 'Select...';
            }
        });

        // Auto-refresh every 3 minutes
        setInterval(function() {
            if (pendingApprovalsTable) {
                pendingApprovalsTable.ajax.reload(null, false);
            }
            loadStats();
        }, 180000);
    }

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Filter controls
        $('#filter_employee, #filter_category, #filter_overdue').on('change', function() {
            if (pendingApprovalsTable) {
                pendingApprovalsTable.ajax.reload();
            }
        });

        // Action buttons
        $('#refreshTable').on('click', function() {
            if (pendingApprovalsTable) {
                pendingApprovalsTable.ajax.reload();
            }
            loadStats();
        });

        $('#clearFilters').on('click', function() {
            $('#filter_employee, #filter_category, #filter_overdue').val('').trigger('change');
        });

        $('#bulkRemindBtn').on('click', handleBulkReminder);
        $('#exportBtn').on('click', handleExport);

        // Table row selection
        $(document).on('change', '#selectAll', function() {
            const isChecked = $(this).is(':checked');
            $('.row-select').prop('checked', isChecked);
            updateSelectedAssignments();
        });

        $(document).on('change', '.row-select', function() {
            updateSelectedAssignments();
        });

        // Action buttons in table
        $(document).on('click', '.quick-action-btn', handleQuickAction);
        $(document).on('click', '.send-reminder-btn', handleSendReminder);
        $(document).on('click', '.view-details-btn', handleViewDetails);

        // Modal actions
        $('#sendReminderBtn').on('click', handleModalReminder);
        $('#viewDetailsBtn').on('click', handleModalViewDetails);
        $('#editAssignmentBtn').on('click', handleModalEditAssignment);
        $('#cancelAssignmentBtn').on('click', handleModalCancelAssignment);
    }

    /**
     * Initialize DataTable
     */
    function initializeDataTable() {
        pendingApprovalsTable = $('.datatables-pending-approvals').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: pendingApprovalsAjaxUrl,
                data: function(d) {
                    d.employee_id = $('#filter_employee').val();
                    d.category_id = $('#filter_category').val();
                    d.overdue_status = $('#filter_overdue').val();
                }
            },
            columns: [
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return `<input type="checkbox" class="form-check-input row-select" value="${data}">`;
                    }
                },
                {
                    data: 'employee',
                    render: function(data, type, row) {
                        return `
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-2">
                                    <div class="avatar-initial bg-primary rounded">
                                        ${data.name.charAt(0)}
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-0">${data.name}</h6>
                                </div>
                            </div>
                        `;
                    }
                },
                {
                    data: 'asset',
                    render: function(data, type, row) {
                        return `
                            <div>
                                <h6 class="mb-0">${data.name}</h6>
                                <small class="text-muted">${data.asset_tag}</small>
                            </div>
                        `;
                    }
                },
                { 
                    data: 'asset.category',
                    defaultContent: 'Uncategorized',
                    render: function(data, type, row) {
                        return data || 'Uncategorized';
                    }
                },
                { 
                    data: 'assigned_by.name',
                    render: function(data, type, row) {
                        return data || 'System';
                    }
                },
                { 
                    data: 'formatted_assigned_at',
                    render: function(data, type, row) {
                        return data;
                    }
                },
                { 
                    data: 'formatted_expected_return_date',
                    render: function(data, type, row) {
                        return data || '<span class="text-muted">Not specified</span>';
                    }
                },
                {
                    data: 'days_pending',
                    render: function(data, type, row) {
                        const badgeClass = row.is_overdue ? 'bg-danger' : 'bg-warning';
                        return `<span class="badge ${badgeClass}">${data} days</span>`;
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        const statusClass = row.is_overdue ? 'bg-danger' : 'bg-warning';
                        const statusText = row.is_overdue ? 'Overdue' : 'Pending';
                        return `<span class="badge ${statusClass}">${statusText}</span>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return `
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                    Actions
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item quick-action-btn" href="#" data-assignment-id="${row.id}" data-employee="${row.employee.name}" data-asset="${row.asset.name}">
                                        <i class="bx bx-cog me-1"></i> Quick Actions
                                    </a>
                                    <a class="dropdown-item send-reminder-btn" href="#" data-assignment-id="${row.id}">
                                        <i class="bx bx-bell me-1"></i> Send Reminder
                                    </a>
                                    <a class="dropdown-item view-details-btn" href="#" data-assignment-id="${row.id}">
                                        <i class="bx bx-show me-1"></i> View Details
                                    </a>
                                </div>
                            </div>
                        `;
                    }
                }
            ],
            order: [[5, 'desc']], // Order by assigned date desc
            pageLength: 25,
            responsive: true,
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            language: {
                emptyTable: "No pending approvals found",
                zeroRecords: "No matching records found"
            },
            createdRow: function(row, data, dataIndex) {
                if (data.is_overdue) {
                    $(row).addClass('overdue-row');
                }
            }
        });
    }

    /**
     * Load page statistics
     */
    function loadStats() {
        $.ajax({
            url: pendingApprovalsAjaxUrl,
            method: 'GET',
            data: { stats_only: true },
            success: function(response) {
                if (response.success && response.stats) {
                    updateStatsDisplay(response.stats);
                }
            },
            error: function(xhr) {
                console.error('Error loading stats:', xhr);
            }
        });
    }

    /**
     * Update statistics display
     */
    function updateStatsDisplay(stats) {
        $('#totalPending').text(stats.total || 0);
        $('#totalOverdue').text(stats.overdue || 0);
        $('#thisWeek').text(stats.this_week || 0);
        $('#avgResponse').text((stats.avg_response || 0) + ' days');
    }

    /**
     * Update selected assignments tracking
     */
    function updateSelectedAssignments() {
        selectedAssignments = [];
        $('.row-select:checked').each(function() {
            selectedAssignments.push($(this).val());
        });

        // Update bulk action buttons
        const hasSelected = selectedAssignments.length > 0;
        $('#bulkRemindBtn').prop('disabled', !hasSelected);

        // Update select all checkbox state
        const totalCheckboxes = $('.row-select').length;
        const checkedCheckboxes = $('.row-select:checked').length;
        
        $('#selectAll').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
        $('#selectAll').prop('checked', checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0);
    }

    /**
     * Handle quick action button
     */
    function handleQuickAction(e) {
        e.preventDefault();
        const $btn = $(this);
        const assignmentId = $btn.data('assignment-id');
        const employee = $btn.data('employee');
        const asset = $btn.data('asset');

        $('#actionAssignmentId').val(assignmentId);
        $('#actionEmployeeName').text(employee);
        $('#actionAssetInfo').text(asset);
        $('#quickActionModal').modal('show');
    }

    /**
     * Handle send reminder
     */
    function handleSendReminder(e) {
        e.preventDefault();
        const assignmentId = $(this).data('assignment-id');
        sendReminder(assignmentId);
    }

    /**
     * Handle view details
     */
    function handleViewDetails(e) {
        e.preventDefault();
        const assignmentId = $(this).data('assignment-id');
        loadAssignmentDetails(assignmentId);
    }

    /**
     * Handle bulk reminder
     */
    function handleBulkReminder() {
        if (selectedAssignments.length === 0) {
            showNotification('Please select assignments to remind', 'warning');
            return;
        }

        Swal.fire({
            title: 'Send Bulk Reminders?',
            text: `Send reminders to ${selectedAssignments.length} selected employees?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Send Reminders',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                sendBulkReminders();
            }
        });
    }

    /**
     * Handle export
     */
    function handleExport() {
        window.location.href = exportUrl + '?' + $.param({
            employee_id: $('#filter_employee').val(),
            category_id: $('#filter_category').val(),
            overdue_status: $('#filter_overdue').val()
        });
    }

    /**
     * Send reminder to single assignment
     */
    function sendReminder(assignmentId) {
        const url = resendNotificationUrl.replace(':id', assignmentId);
        
        $.ajax({
            url: url,
            method: 'POST',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                } else {
                    showNotification(response.message || 'Error sending reminder', 'error');
                }
            },
            error: function(xhr) {
                console.error('Error sending reminder:', xhr);
                showNotification('Error sending reminder', 'error');
            }
        });
    }

    /**
     * Send bulk reminders
     */
    function sendBulkReminders() {
        $.ajax({
            url: bulkRemindUrl,
            method: 'POST',
            data: {
                assignment_ids: selectedAssignments,
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    if (pendingApprovalsTable) {
                        pendingApprovalsTable.ajax.reload();
                    }
                    // Clear selection
                    $('.row-select, #selectAll').prop('checked', false);
                    updateSelectedAssignments();
                } else {
                    showNotification(response.message || 'Error sending reminders', 'error');
                }
            },
            error: function(xhr) {
                console.error('Error sending bulk reminders:', xhr);
                showNotification('Error sending reminders', 'error');
            }
        });
    }

    /**
     * Load assignment details
     */
    function loadAssignmentDetails(assignmentId) {
        const modal = $('#assignmentDetailsModal');
        const content = $('#assignmentDetailsContent');
        
        content.html('<div class="text-center"><div class="spinner-border" role="status"></div></div>');
        modal.modal('show');

        const url = assignmentDetailsUrl.replace(':id', assignmentId);

        $.ajax({
            url: url,
            method: 'GET',
            success: function(response) {
                content.html(response);
            },
            error: function(xhr) {
                console.error('Error loading assignment details:', xhr);
                content.html('<div class="alert alert-danger">Error loading assignment details</div>');
            }
        });
    }

    // Modal action handlers
    function handleModalReminder() {
        const assignmentId = $('#actionAssignmentId').val();
        if (assignmentId) {
            sendReminder(assignmentId);
            $('#quickActionModal').modal('hide');
        }
    }

    function handleModalViewDetails() {
        const assignmentId = $('#actionAssignmentId').val();
        if (assignmentId) {
            loadAssignmentDetails(assignmentId);
            $('#quickActionModal').modal('hide');
        }
    }

    function handleModalEditAssignment() {
        // TODO: Implement edit assignment functionality
        showNotification('Edit assignment feature coming soon', 'info');
        $('#quickActionModal').modal('hide');
    }

    function handleModalCancelAssignment() {
        const assignmentId = $('#actionAssignmentId').val();
        if (!assignmentId) return;

        Swal.fire({
            title: 'Cancel Assignment?',
            text: 'This will cancel the pending assignment. This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Cancel Assignment',
            confirmButtonColor: '#d33',
            cancelButtonText: 'Keep Assignment'
        }).then((result) => {
            if (result.isConfirmed) {
                // TODO: Implement cancel assignment functionality
                showNotification('Cancel assignment feature coming soon', 'info');
                $('#quickActionModal').modal('hide');
            }
        });
    }

    /**
     * Show notification
     */
    function showNotification(message, type = 'info') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                text: message,
                icon: type,
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        } else {
            alert(message);
        }
    }
});