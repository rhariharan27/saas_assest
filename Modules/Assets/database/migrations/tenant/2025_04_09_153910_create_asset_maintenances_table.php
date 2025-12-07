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
      Schema::create('asset_maintenances', function (Blueprint $table) {
        $table->id();
        $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete(); // Link to asset

        // Use string for type until Enum is created
        $table->string('maintenance_type')->index()->comment('e.g., repair, upgrade, cleaning');

        $table->timestamp('performed_at')->useCurrent()->index(); // When maintenance done/logged
        $table->decimal('cost', 8, 2)->nullable(); // Cost of maintenance
        $table->string('provider')->nullable()->comment('Internal dept or external vendor');
        $table->text('details'); // Description of work done
        $table->date('next_due_date')->nullable()->comment('For scheduled maintenance'); // Optional next due date

        $table->foreignId('completed_by_id')->nullable()->constrained('users')->nullOnDelete(); // User who logged this entry

        $table->string('tenant_id', 191)->index();
        $table->timestamps(); // Log entry creation time
      });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_maintenances');
    }
};
