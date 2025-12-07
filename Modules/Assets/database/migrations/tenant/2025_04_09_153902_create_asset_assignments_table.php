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
      Schema::create('asset_assignments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete(); // Link to asset
        $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Employee assigned to, set null if user deleted

        $table->timestamp('assigned_at')->index(); // When assignment started
        $table->timestamp('returned_at')->nullable()->index(); // When asset was returned (NULL if currently assigned)
        $table->date('expected_return_date')->nullable(); // Optional expected return

        // Use string for condition until Enums are created and cast in model
        $table->string('condition_out')->nullable()->comment('Condition when assigned');
        $table->string('condition_in')->nullable()->comment('Condition when returned');

        $table->text('notes')->nullable(); // Notes for this specific assignment/return

        $table->foreignId('assigned_by_id')->nullable()->constrained('users')->nullOnDelete(); // Admin who assigned
        $table->foreignId('received_by_id')->nullable()->constrained('users')->nullOnDelete(); // Admin who received return

        $table->string('tenant_id', 191)->index();
        $table->timestamps(); // created_at (assignment created), updated_at (return logged)
      });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};
