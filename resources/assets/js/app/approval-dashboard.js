/**
 * Approval Dashboard JavaScript
 * Handles the main approval workflow dashboard functionality
 */

'use strict';

$(function() {
    let dashboardData = {};
    let approvalTrendsChart = null;

    // Initialize dashboard
    initializeDashboard();
    setupEventListeners();
    loadDashboardData();
    initializeCharts();
    loadRecentActivity();
    loadRecentTables();

    /**
     * Initialize dashboard components
     */
    function initializeDashboard() {
        // Auto-refresh every 5 minutes
        setInterval(function() {
            loadDashboardData();
            loadRecentActivity();
            loadRecentTables();
        }, 300000);
    }

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Quick action buttons
        $('#sendBulkReminders').on('click', handleBulkReminders);
        $('#generateReport').on('click', handleGenerateReport);
        $('#viewAllActivity').on('click', handleViewAllActivity);

        // Chart period selector
        $('.dropdown-item[data-period]').on('click', function(e) {
            e.preventDefault();
            const period = $(this).data('period');
            updateChartPeriod(period);
        });

        // Quick response modal
        $(document).on('click', '.quick-respond-btn', handleQuickResponse);
        $('#quickApproveBtn').on('click', function() { handleQuickDecision('approve'); });
        $('#quickRejectBtn').on('click', function() { handleQuickDecision('reject'); });

        // Refresh functionality
        $('.refresh-dashboard').on('click', function() {
            loadDashboardData();
            loadRecentActivity();
            loadRecentTables();
        });
    }

    /**
     * Load dashboard statistics
     */
    function loadDashboardData() {
        $.ajax({
            url: dashboardDataUrl,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    updateDashboardStats(response.data);
                    dashboardData = response.data;
                }
            },
            error: function(xhr) {
                console.error('Error loading dashboard data:', xhr);
                showNotification('Error loading dashboard data', 'error');
            }
        });
    }

    /**
     * Update dashboard statistics display
     */
    function updateDashboardStats(data) {
        $('#totalPendingApprovals').text(data.pending_approvals || 0);
        $('#overdueApprovals').text(data.overdue_approvals || 0);
        $('#totalPendingReturns').text(data.pending_returns || 0);
        $('#urgentReturns').text(data.urgent_returns || 0);
        $('#totalActiveAssets').text(data.active_assets || 0);
        $('#responseRate').text((data.response_rate || 0) + '%');
        $('#avgResponseTime').text((data.avg_response_time || 0) + 'h');

        // Update quick action indicators
        updateQuickActionIndicators(data);
    }

    /**
     * Update quick action indicators
     */
    function updateQuickActionIndicators(data) {
        const hasOverdue = data.overdue_approvals > 0;
        const hasUrgentReturns = data.urgent_returns > 0;
        
        if (hasOverdue) {
            $('#sendBulkReminders').removeClass('btn-outline-secondary').addClass('btn-warning');
        }

        // Update notification badges if needed
        $('.pending-count').text(data.pending_approvals);
        $('.urgent-count').text(data.urgent_returns);
    }

    /**
     * Initialize approval trends chart
     */
    function initializeCharts() {
        const chartElement = document.querySelector('#approvalTrendsChart');
        if (!chartElement) return;

        const chartOptions = {
            series: [{
                name: 'Approved',
                data: []
            }, {
                name: 'Rejected',
                data: []
            }, {
                name: 'Pending',
                data: []
            }],
            chart: {
                height: 300,
                type: 'line',
                toolbar: {
                    show: false
                }
            },
            stroke: {
                width: 3,
                curve: 'smooth'
            },
            colors: ['#28c76f', '#ea5455', '#ff9f43'],
            xaxis: {
                categories: []
            },
            legend: {
                position: 'top'
            },
            responsive: [{
                breakpoint: 768,
                options: {
                    chart: {
                        height: 250
                    }
                }
            }]
        };

        approvalTrendsChart = new ApexCharts(chartElement, chartOptions);
        approvalTrendsChart.render();

        // Load initial chart data
        loadChartData(30);
    }

    /**
     * Load chart data for specified period
     */
    function loadChartData(days) {
        // TODO: Implement actual chart data loading
        // For now, using mock data
        const mockData = generateMockChartData(days);
        
        if (approvalTrendsChart) {
            approvalTrendsChart.updateSeries([
                { name: 'Approved', data: mockData.approved },
                { name: 'Rejected', data: mockData.rejected },
                { name: 'Pending', data: mockData.pending }
            ]);
            approvalTrendsChart.updateOptions({
                xaxis: { categories: mockData.categories }
            });
        }
    }

    /**
     * Generate mock chart data
     */
    function generateMockChartData(days) {
        const data = { approved: [], rejected: [], pending: [], categories: [] };
        const today = new Date();
        
        for (let i = days - 1; i >= 0; i--) {
            const date = new Date(today);
            date.setDate(date.getDate() - i);
            
            data.categories.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
            data.approved.push(Math.floor(Math.random() * 10) + 5);
            data.rejected.push(Math.floor(Math.random() * 3) + 1);
            data.pending.push(Math.floor(Math.random() * 8) + 2);
        }
        
        return data;
    }

    /**
     * Load recent activity
     */
    function loadRecentActivity() {
        const activityList = $('#recentActivityList');
        
        // TODO: Load actual activity from API
        // For now, using mock data
        const mockActivity = [
            {
                action: 'Assignment approved',
                user: 'John Doe',
                asset: 'Laptop Dell XPS',
                time: '2 hours ago',
                type: 'approval'
            },
            {
                action: 'Return requested',
                user: 'Jane Smith',
                asset: 'iPhone 13',
                time: '4 hours ago',
                type: 'return'
            },
            {
                action: 'Assignment overdue',
                user: 'Mike Johnson',
                asset: 'MacBook Pro',
                time: '1 day ago',
                type: 'overdue'
            }
        ];

        let html = '';
        mockActivity.forEach(activity => {
            const itemClass = activity.type === 'overdue' ? 'activity-item overdue' : 'activity-item';
            html += `
                <div class="${itemClass}">
                    <h6 class="mb-1">${activity.action}</h6>
                    <p class="mb-0 text-muted">${activity.user} - ${activity.asset}</p>
                    <small class="text-muted">${activity.time}</small>
                </div>
            `;
        });

        activityList.html(html);
    }

    /**
     * Load recent tables data
     */
    function loadRecentTables() {
        loadRecentPendingTable();
        loadRecentReturnsTable();
    }

    /**
     * Load recent pending approvals table
     */
    function loadRecentPendingTable() {
        const table = $('#recentPendingTable tbody');
        
        $.ajax({
            url: pendingApprovalsUrl,
            method: 'GET',
            data: { limit: 5 },
            success: function(response) {
                if (response.success && response.data) {
                    let html = '';
                    response.data.assignments.slice(0, 5).forEach(assignment => {
                        const daysBadge = assignment.is_overdue ? 
                            `<span class="badge bg-danger">${assignment.days_pending}d</span>` :
                            `<span class="badge bg-warning">${assignment.days_pending}d</span>`;
                        
                        html += `
                            <tr>
                                <td>${assignment.employee.name}</td>
                                <td>${assignment.asset.name}</td>
                                <td>${daysBadge}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary quick-respond-btn" 
                                            data-assignment-id="${assignment.id}"
                                            data-employee="${assignment.employee.name}"
                                            data-asset="${assignment.asset.name}">
                                        <i class="bx bx-send"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    table.html(html || '<tr><td colspan="4" class="text-center text-muted">No pending approvals</td></tr>');
                }
            },
            error: function(xhr) {
                console.error('Error loading recent pending table:', xhr);
                table.html('<tr><td colspan="4" class="text-center text-danger">Error loading data</td></tr>');
            }
        });
    }

    /**
     * Load recent returns table
     */
    function loadRecentReturnsTable() {
        const table = $('#recentReturnsTable tbody');
        
        $.ajax({
            url: pendingReturnsUrl,
            method: 'GET',
            data: { limit: 5 },
            success: function(response) {
                if (response.success && response.data) {
                    let html = '';
                    response.data.assignments.slice(0, 5).forEach(assignment => {
                        const reasonShort = assignment.return_request_notes.length > 30 ? 
                            assignment.return_request_notes.substring(0, 30) + '...' :
                            assignment.return_request_notes;
                        
                        html += `
                            <tr>
                                <td>${assignment.employee.name}</td>
                                <td>${assignment.asset.name}</td>
                                <td title="${assignment.return_request_notes}">${reasonShort}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-success quick-approve-return-btn" 
                                            data-assignment-id="${assignment.id}">
                                        <i class="bx bx-check"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    table.html(html || '<tr><td colspan="4" class="text-center text-muted">No pending returns</td></tr>');
                }
            },
            error: function(xhr) {
                console.error('Error loading recent returns table:', xhr);
                table.html('<tr><td colspan="4" class="text-center text-danger">Error loading data</td></tr>');
            }
        });
    }

    /**
     * Handle bulk reminders
     */
    function handleBulkReminders() {
        if (dashboardData.overdue_approvals === 0) {
            showNotification('No overdue assignments to remind', 'info');
            return;
        }

        Swal.fire({
            title: 'Send Bulk Reminders?',
            text: `Send reminders to ${dashboardData.overdue_approvals} employees with overdue assignments?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Send Reminders',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // TODO: Implement bulk reminder functionality
                showNotification('Reminders sent successfully', 'success');
                loadDashboardData();
            }
        });
    }

    /**
     * Handle generate report
     */
    function handleGenerateReport() {
        // TODO: Implement report generation
        showNotification('Report generation feature coming soon', 'info');
    }

    /**
     * Handle view all activity
     */
    function handleViewAllActivity() {
        // TODO: Navigate to full activity log
        showNotification('Full activity log feature coming soon', 'info');
    }

    /**
     * Handle quick response button click
     */
    function handleQuickResponse(e) {
        const $btn = $(this);
        const assignmentId = $btn.data('assignment-id');
        const employee = $btn.data('employee');
        const asset = $btn.data('asset');

        $('#quickResponseModal').modal('show');
        $('#quickResponseEmployee').text(employee);
        $('#quickResponseAsset').text(asset);
        $('#quickResponseModal').data('assignment-id', assignmentId);
    }

    /**
     * Handle quick decision (approve/reject)
     */
    function handleQuickDecision(action) {
        const modal = $('#quickResponseModal');
        const assignmentId = modal.data('assignment-id');
        const notes = $('#quickResponseNotes').val();

        if (!assignmentId) return;

        $.ajax({
            url: `/assignments/${assignmentId}/respond`,
            method: 'POST',
            data: {
                response: action,
                notes: notes,
                _token: csrfToken
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    modal.modal('hide');
                    loadDashboardData();
                    loadRecentTables();
                } else {
                    showNotification(response.message || 'Error processing request', 'error');
                }
            },
            error: function(xhr) {
                console.error('Error processing quick decision:', xhr);
                showNotification('Error processing request', 'error');
            }
        });
    }

    /**
     * Update chart period
     */
    function updateChartPeriod(days) {
        loadChartData(days);
        $('.dropdown-toggle').text(`Last ${days} Days`);
    }

    /**
     * Show notification using SweetAlert or toast
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