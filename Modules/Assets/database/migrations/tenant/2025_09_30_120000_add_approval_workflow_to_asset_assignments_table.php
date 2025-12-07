<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('asset_assignments', function (Blueprint $table) {
            // Employee approval workflow fields
            $table->enum('employee_approval_status', ['pending', 'approved', 'rejected'])
                  ->default('pending')
                  ->after('notes')
                  ->comment('Employee response to assignment');
            
            $table->text('employee_approval_notes')
                  ->nullable()
                  ->after('employee_approval_status')
                  ->comment('Employee notes when responding');
            
            $table->timestamp('employee_responded_at')
                  ->nullable()
                  ->after('employee_approval_notes')
                  ->comment('When employee responded');
            
            // Return request workflow fields
            $table->boolean('return_requested')
                  ->default(false)
                  ->after('employee_responded_at')
                  ->comment('Employee requested return');
            
            $table->timestamp('return_requested_at')
                  ->nullable()
                  ->after('return_requested')
                  ->comment('When return was requested');
            
            $table->text('return_request_notes')
                  ->nullable()
                  ->after('return_requested_at')
                  ->comment('Employee notes for return request');
            
            $table->enum('return_approval_status', ['pending', 'approved', 'rejected'])
                  ->nullable()
                  ->after('return_request_notes')
                  ->comment('Admin response to return request');
            
            $table->text('return_approval_notes')
                  ->nullable()
                  ->after('return_approval_status')
                  ->comment('Admin notes for return response');
            
            $table->foreignId('return_approved_by_id')
                  ->nullable()
                  ->after('return_approval_notes')
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Admin who approved/rejected return');
            
            $table->timestamp('return_approved_at')
                  ->nullable()
                  ->after('return_approved_by_id')
                  ->comment('When admin responded to return request');
            
            // Indexes for performance
            $table->index(['employee_approval_status'], 'idx_employee_approval');
            $table->index(['return_requested'], 'idx_return_requested');
            $table->index(['return_approval_status'], 'idx_return_approval');
            $table->index(['tenant_id', 'employee_approval_status'], 'idx_tenant_emp_approval');
            $table->index(['tenant_id', 'return_requested'], 'idx_tenant_return_req');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_assignments', function (Blueprint $table) {
            $table->dropForeign(['return_approved_by_id']);
            $table->dropIndex('idx_employee_approval');
            $table->dropIndex('idx_return_requested');
            $table->dropIndex('idx_return_approval');
            $table->dropIndex('idx_tenant_emp_approval');
            $table->dropIndex('idx_tenant_return_req');
            
            $table->dropColumn([
                'employee_approval_status',
                'employee_approval_notes',
                'employee_responded_at',
                'return_requested',
                'return_requested_at',
                'return_request_notes',
                'return_approval_status',
                'return_approval_notes',
                'return_approved_by_id',
                'return_approved_at'
            ]);
        });
    }
};