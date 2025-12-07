/**
 * Pending Returns JavaScript
 * Handles the pending returns page functionality
 */

'use strict';

$(function() {
    let pendingReturnsTable;
    let selectedReturns = [];

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
            if (pendingReturnsTable) {
                pendingReturnsTable.ajax.reload(null, false);
            }
            loadStats();
        }, 180000);
    }

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Filter controls
        $('#filter_employee, #filter_category, #filter_priority').on('change', function() {
            if (pendingReturnsTable) {
                pendingReturnsTable.ajax.reload();
            }
        });

        // Action buttons
        $('#refreshTable').on('click', function() {
            if (pendingReturnsTable) {
                pendingReturnsTable.ajax.reload();
            }
            loadStats();
        });

        $('#clearFilters').on('click', function() {
            $('#filter_employee, #filter_category, #filter_priority').val('').trigger('change');
        });

        $('#bulkApproveBtn').on('click', handleBulkApprove);
        $('#exportBtn').on('click', handleExport);

        // Table row selection
        $(document).on('change', '#selectAll', function() {
            const isChecked = $(this).is(':checked');
            $('.row-select').prop('checked', isChecked);
            updateSelectedReturns();
        });

        $(document).on('change', '.row-select', function() {
            updateSelectedReturns();
        });

        // Action buttons in table
        $(document).on('click', '.respond-btn', handleRespondToReturn);
        $(document).on('click', '.view-details-btn', handleViewDetails);
        $(document).on('click', '.quick-approve-btn', handleQuickApprove);

        // Modal form submission
        $('#returnResponseForm').on('submit', handleReturnResponse);
        $('#confirmBulkApprove').on('click', handleConfirmBulkApprove);
    }

    /**
     * Initialize DataTable
     */
    function initializeDataTable() {
        pendingReturnsTable = $('.datatables-pending-returns').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: pendingReturnsAjaxUrl,
                data: function(d) {
                    d.employee_id = $('#filter_employee').val();
                    d.category_id = $('#filter_category').val();
                    d.priority = $('#filter_priority').val();
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
                            <div class="employee-info">
                                <div class="avatar avatar-sm me-2">
                                    <div class="avatar-initial bg-info rounded">
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
                { data: 'asset.category', defaultContent: 'Uncategorized' },
                { 
                    data: 'return_requested_at',
                    render: function(data, type, row) {
                        const date = new Date(data);
                        return date.toLocaleDateString('en-US', { 
                            month: 'short', 
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }
                },
                {
                    data: 'days_pending_return',
                    render: function(data, type, row) {
                        const isUrgent = data > 3;
                        const badgeClass = isUrgent ? 'bg-danger' : 'bg-warning';
                        return `<span class="badge ${badgeClass}">${data} days</span>`;
                    }
                },
                {
                    data: 'return_request_notes',
                    render: function(data, type, row) {
                        if (!data) return '<span class="text-muted">No reason provided</span>';
                        
                        const shortText = data.length > 50 ? data.substring(0, 50) + '...' : data;
                        return `<span class="return-reason" title="${data}">${shortText}</span>`;
                    }
                },
                {
                    data: 'days_pending_return',
                    render: function(data, type, row) {
                        const isUrgent = data > 3;
                        const priorityClass = isUrgent ? 'priority-high' : 'priority-normal';
                        const priorityText = isUrgent ? 'Urgent' : 'Normal';
                        return `<span class="${priorityClass}">${priorityText}</span>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return `
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-success respond-btn" 
                                        data-assignment-id="${row.id}"
                                        data-employee="${row.employee.name}"
                                        data-asset="${row.asset.name}"
                                        data-reason="${row.return_request_notes}">
                                    <i class="bx bx-check"></i> Respond
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary view-details-btn" 
                                        data-assignment-id="${row.id}">
                                    <i class="bx bx-show"></i>
                                </button>
                            </div>
                        `;
                    }
                }
            ],
            order: [[4, 'desc']], // Order by request date desc
            pageLength: 25,
            responsive: true,
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            language: {
                emptyTable: "No pending return requests found",
                zeroRecords: "No matching records found"
            },
            createdRow: function(row, data, dataIndex) {
                if (data.days_pending_return > 3) {
                    $(row).addClass('urgent-return');
                }
            }
        });
    }

    /**
     * Load page statistics
     */
    function loadStats() {
        $.ajax({
            url: pendingReturnsAjaxUrl,
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
        $('#totalPendingReturns').text(stats.total || 0);
        $('#urgentReturns').text(stats.urgent || 0);
        $('#todayRequests').text(stats.today || 0);
        $('#avgResponseTime').text((stats.avg_response_hours || 0) + 'h');
    }

    /**
     * Update selected returns tracking
     */
    function updateSelectedReturns() {
        selectedReturns = [];
        $('.row-select:checked').each(function() {
            selectedReturns.push($(this).val());
        });

        // Update bulk action buttons
        const hasSelected = selectedReturns.length > 0;
        $('#bulkApproveBtn').prop('disabled', !hasSelected);

        // Update select all checkbox state
        const totalCheckboxes = $('.row-select').length;
        const checkedCheckboxes = $('.row-select:checked').length;
        
        $('#selectAll').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
        $('#selectAll').prop('checked', checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0);
    }

    /**
     * Handle respond to return button
     */
    function handleRespondToReturn(e) {
        e.preventDefault();
        const $btn = $(this);
        const assignmentId = $btn.data('assignment-id');
        const employee = $btn.data('employee');
        const asset = $btn.data('asset');
        const reason = $btn.data('reason');

        $('#responseAssignmentId').val(assignmentId);
        $('#responseEmployeeName').text(employee);
        $('#responseAssetInfo').text(asset);
        $('#returnReasonText').text(reason || 'No reason provided');
        
        // Reset form
        $('#returnResponseForm')[0].reset();
        $('#response-general-error').text('');
        
        $('#returnResponseModal').modal('show');
    }

    /**
     * Handle view details
     */
    function handleViewDetails(e) {
        e.preventDefault();
        const assignmentId = $(this).data('assignment-id');
        loadReturnDetails(assignmentId);
    }

    /**
     * Handle quick approve
     */
    function handleQuickApprove(e) {
        e.preventDefault();
        const assignmentId = $(this).data('assignment-id');
        
        Swal.fire({
            title: 'Approve Return Request?',
            text: 'This will approve the return request and allow the employee to return the asset.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Approve',
            confirmButtonColor: '#28c76f',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                submitReturnResponse(assignmentId, 'approve', 'Quick approved by admin');
            }
        });
    }

    /**
     * Handle return response form submission
     */
    function handleReturnResponse(e) {
        e.preventDefault();
        
        const assignmentId = $('#responseAssignmentId').val();
        const response = $('input[name="response"]:checked').val();
        const notes = $('#responseNotes').val();

        if (!response) {
            $('#response-general-error').text('Please select approve or reject');
            return;
        }

        submitReturnResponse(assignmentId, response, notes);
    }

    /**
     * Submit return response
     */
    function submitReturnResponse(assignmentId, response, notes) {
        const submitBtn = $('#submitResponseBtn');
        const originalText = submitBtn.text();
        
        submitBtn.prop('disabled', true).text('Processing...');

        $.ajax({
            url: respondToReturnUrl.replace(':id', assignmentId),
            method: 'POST',
            data: {
                response: response,
                notes: notes,
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    $('#returnResponseModal').modal('hide');
                    if (pendingReturnsTable) {
                        pendingReturnsTable.ajax.reload();
                    }
                    loadStats();
                } else {
                    $('#response-general-error').text(response.message || 'Error processing response');
                }
            },
            error: function(xhr) {
                console.error('Error submitting return response:', xhr);
                let errorMessage = 'Error processing response';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                $('#response-general-error').text(errorMessage);
            },
            complete: function() {
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Handle bulk approve
     */
    function handleBulkApprove() {
        if (selectedReturns.length === 0) {
            showNotification('Please select return requests to approve', 'warning');
            return;
        }

        $('#bulkCount').text(selectedReturns.length);
        $('#bulkNotes').val('');
        $('#bulkApproveModal').modal('show');
    }

    /**
     * Handle confirm bulk approve
     */
    function handleConfirmBulkApprove() {
        const notes = $('#bulkNotes').val() || 'Bulk approved by admin';
        
        $.ajax({
            url: '/assignments/bulk-approve-returns',
            method: 'POST',
            data: {
                assignment_ids: selectedReturns,
                notes: notes,
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    $('#bulkApproveModal').modal('hide');
                    if (pendingReturnsTable) {
                        pendingReturnsTable.ajax.reload();
                    }
                    // Clear selection
                    $('.row-select, #selectAll').prop('checked', false);
                    updateSelectedReturns();
                    loadStats();
                } else {
                    showNotification(response.message || 'Error processing bulk approval', 'error');
                }
            },
            error: function(xhr) {
                console.error('Error processing bulk approval:', xhr);
                showNotification('Error processing bulk approval', 'error');
            }
        });
    }

    /**
     * Handle export
     */
    function handleExport() {
        const params = {
            employee_id: $('#filter_employee').val(),
            category_id: $('#filter_category').val(),
            priority: $('#filter_priority').val()
        };
        
        // TODO: Implement actual export functionality
        showNotification('Export functionality coming soon', 'info');
    }

    /**
     * Load return details
     */
    function loadReturnDetails(assignmentId) {
        const modal = $('#returnDetailsModal');
        const content = $('#returnDetailsContent');
        
        content.html('<div class="text-center"><div class="spinner-border" role="status"></div></div>');
        modal.modal('show');

        $.ajax({
            url: assignmentDetailsUrl.replace(':id', assignmentId),
            method: 'GET',
            success: function(response) {
                content.html(response);
            },
            error: function(xhr) {
                console.error('Error loading return details:', xhr);
                content.html('<div class="alert alert-danger">Error loading return details</div>');
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