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
      Schema::create('asset_activities', function (Blueprint $table) {
        $table->id();

        // Link to the asset this activity pertains to
        $table->foreignId('asset_id')
          ->constrained('assets') // Assumes assets table name
          ->cascadeOnDelete(); // If asset is deleted, logs are deleted

        // User who PERFORMED the action (e.g., admin assigning the asset)
        $table->foreignId('user_id')
          ->nullable() // May be null for system events?
          ->comment('User performing the action')
          ->constrained('users') // Assumes users table name
          ->nullOnDelete();

        // User who is the SUBJECT of the action (e.g., employee receiving asset)
        $table->foreignId('related_user_id')
          ->nullable()
          ->comment('User involved in the action (e.g., assignee)')
          ->constrained('users')
          ->nullOnDelete();

        // Optional: Link to related records like Assignment or Maintenance entry
        $table->string('related_model_type')->nullable();
        $table->unsignedBigInteger('related_model_id')->nullable();
        $table->index(['related_model_type', 'related_model_id']); // Index for polymorphic relation

        // Type of action performed
        $table->string('action')->index()->comment('e.g., created, updated, assigned, returned, maintenance_logged, status_changed, deleted');

        // Details of the action
        $table->text('details')->nullable()->comment('e.g., Assigned to John Doe, Status changed to In Repair, Maintenance: Screen replaced');

        $table->string('tenant_id', 191)->index(); // Consistent tenant ID

        // Use created_at as the timestamp for when the activity occurred
        $table->timestamp('created_at')->nullable();
        // No updated_at usually needed for log entries
      });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_activities');
    }
};
